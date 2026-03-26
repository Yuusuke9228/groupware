const { chromium } = require('/Volumes/www/html/groupware/node_modules/playwright');
const fs = require('fs');

function abs(base, path) { return base.replace(/\/$/, '') + path; }
function uniq(prefix) { return `${prefix}-${Date.now()}`; }

async function setValue(page, selector, value) {
  await page.$eval(selector, (el, v) => {
    el.value = v;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }, value);
}

async function login(page, base, user, pass) {
  await page.goto(abs(base, '/login'), { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="username"]', user);
  await page.fill('input[name="password"]', pass);
  await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type="submit"]')]);
  if (page.url().includes('/login')) throw new Error(`login failed: ${user} @ ${base}`);
}

async function logout(page, base) {
  await page.goto(abs(base, '/logout'), { waitUntil: 'networkidle' }).catch(() => {});
}

async function checkSchedule(page, base, label) {
  await page.goto(abs(base, '/schedule'), { waitUntil: 'networkidle' });
  await page.waitForTimeout(1000);
  const ok = await page.evaluate(() => typeof App !== 'undefined');
  if (!ok) throw new Error(`${label}: App undefined on schedule`);
}

async function submitWorkflow(page, base, label, type) {
  await page.goto(abs(base, '/workflow/create/1'), { waitUntil: 'networkidle' });
  await page.fill('#title', uniq('WF'));
  if (type === 'xserver') {
    await page.selectOption('#leave_type', { index: 1 });
    await setValue(page, '#start_date', '2026-03-26');
    await setValue(page, '#end_date', '2026-03-26');
    await page.fill('#days_count', '1');
    await page.fill('#reason', 'テスト');
  } else {
    await page.fill('#field1', 'テスト');
    await setValue(page, '#field2', '2026-03-26');
    await page.fill('#field3', '1');
    await page.fill('#field4', 'テスト');
  }
  await page.click('#btn-submit-request');
  await page.waitForTimeout(2500);
  if (!/\/workflow\/requests/.test(page.url())) {
    throw new Error(`${label}: workflow submit failed, stayed at ${page.url()}`);
  }
}

async function taskCrud(page, base, label) {
  const name = uniq('Board');
  await page.goto(abs(base, '/task/create-board'), { waitUntil: 'networkidle' });
  await page.fill('#boardName', name);
  await page.fill('#boardDescription', 'smoke');
  await Promise.all([page.waitForURL(/\/task\/board\/\d+/), page.click('#submitBtn')]);
  const boardId = page.url().match(/\/task\/board\/(\d+)/)?.[1];
  if (!boardId) throw new Error(`${label}: board create failed`);

  await page.click('#add-list-btn');
  await page.fill('#listName', 'ToDo');
  await page.click('#saveListBtn');
  await page.waitForTimeout(1500);
  if (!await page.locator('.kanban-list-title:has-text("ToDo")').count()) throw new Error(`${label}: list create failed`);

  await page.locator('.add-card').first().click();
  await page.fill('#cardTitle', 'Card1');
  await page.fill('#cardDescription', 'body');
  await page.click('#saveCardBtn');
  await page.waitForTimeout(1500);
  if (!await page.locator('.kanban-card-title:has-text("Card1")').count()) throw new Error(`${label}: card create failed`);

  await page.goto(abs(base, `/task/edit-board/${boardId}`), { waitUntil: 'networkidle' });
  await page.fill('#boardName', name + '-Edited');
  await Promise.all([page.waitForURL(new RegExp(`/task/board/${boardId}$`)), page.locator('#boardForm button[type="submit"]').click()]);
  if (!await page.locator(`h4:has-text("${name}-Edited")`).count()) throw new Error(`${label}: board update failed`);

  await page.goto(abs(base, `/task/edit-board/${boardId}`), { waitUntil: 'networkidle' });
  page.on('dialog', d => d.accept().catch(() => {}));
  await page.click('#deleteBoard');
  await page.waitForTimeout(500);
  if (await page.locator('#confirmBoardDelete').count()) await page.locator('#confirmBoardDelete').check();
  if (await page.locator('#confirmDelete').count()) {
    await Promise.all([page.waitForLoadState('networkidle'), page.locator('#confirmDelete').click()]);
  }
  if (!/\/task(\/|$)/.test(page.url())) throw new Error(`${label}: board delete failed`);
}

async function fileCrud(page, base, label) {
  const title = uniq('File');
  const p1 = '/tmp/file-v1.txt';
  const p2 = '/tmp/file-v2.txt';
  fs.writeFileSync(p1, 'v1 ' + title + '\n');
  fs.writeFileSync(p2, 'v2 ' + title + '\n');
  await page.goto(abs(base, '/files/upload'), { waitUntil: 'networkidle' });
  await page.setInputFiles('#fileInput', p1);
  await page.fill('#title', title);
  await Promise.all([page.waitForLoadState('networkidle'), page.click('#uploadBtn')]);
  await page.click(`text=${title}`);
  await page.waitForLoadState('networkidle');
  const fileId = page.url().match(/\/files\/file\/(\d+)/)?.[1];
  if (!fileId) throw new Error(`${label}: file open failed`);

  if (await page.locator('form[action$="/checkout"] button').count()) {
    await Promise.all([page.waitForLoadState('networkidle'), page.locator('form[action$="/checkout"] button').click()]);
  }
  await page.setInputFiles('#updateFile', p2);
  await page.fill('#comment', 'v2');
  await Promise.all([page.waitForLoadState('networkidle'), page.locator('form[action$="/update"] button[type="submit"]').click()]);
  if (!await page.locator('text=Ver.2').count()) throw new Error(`${label}: file update failed`);

  page.on('dialog', d => d.accept().catch(() => {}));
  const del = page.locator(`form[action$="/file/${fileId}/delete"] button, form[action$="/files/file/${fileId}/delete"] button`).first();
  await Promise.all([page.waitForLoadState('networkidle'), del.click()]);
}

async function messageFlow(page, base, label, recipientLabel) {
  const subject = uniq('Msg');
  await page.goto(abs(base, '/messages/compose'), { waitUntil: 'networkidle' });
  await page.selectOption('#recipients', { label: recipientLabel });
  await page.fill('#subject', subject);
  await page.fill('#body', 'body');
  await Promise.all([page.waitForLoadState('networkidle'), page.locator('#message-form button[type="submit"]').click()]);
  if (!/\/messages\/sent/.test(page.url())) throw new Error(`${label}: message send failed`);
  return subject;
}

async function messageDelete(page, base, label, subject) {
  await page.goto(abs(base, '/messages/inbox'), { waitUntil: 'networkidle' });
  await page.click(`text=${subject}`);
  await page.waitForLoadState('networkidle');
  if (await page.locator('.btn-toggle-star').count()) await page.locator('.btn-toggle-star').first().click().catch(() => {});
  if (await page.locator('.btn-mark-as-unread').count()) await page.locator('.btn-mark-as-unread').first().click().catch(() => {});
  page.on('dialog', d => d.accept().catch(() => {}));
  await page.locator('.btn-delete-message').first().click();
  await page.waitForTimeout(1500);
  if (!/\/messages\/inbox/.test(page.url())) throw new Error(`${label}: message delete failed`);
}

async function suite(base, admin, recipient, label, wfType) {
  const browser = await chromium.launch({ headless: true });
  const page = await (await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1440, height: 1200 } })).newPage();
  try {
    await login(page, base, admin.user, admin.pass);
    await checkSchedule(page, base, label);
    await submitWorkflow(page, base, label, wfType);
    await taskCrud(page, base, label);
    await fileCrud(page, base, label);
    const subject = await messageFlow(page, base, label, recipient.label);
    await logout(page, base);
    await login(page, base, recipient.user, recipient.pass);
    await messageDelete(page, base, label, subject);
    console.log(label, 'OK');
  } catch (e) {
    console.error(label, 'FAIL', String(e));
  }
  await browser.close();
}

(async()=>{
  await suite('https://groupware.yuus-program.com', {user:'admin',pass:'demo1234'}, {user:'yamada',pass:'demo1234',label:'山田太郎 (yamada)'}, 'xserver', 'xserver');
  await suite('http://192.168.1.5/groupware', {user:'admin',pass:'admin123'}, {user:'qa_viewer',pass:'admin123',label:'QA Viewer (qa_viewer)'}, 'local', 'local');
})();
