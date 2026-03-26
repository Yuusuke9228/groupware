const { chromium } = require('/Volumes/www/html/groupware/node_modules/playwright');
const fs = require('fs');

function stamp() { return Math.floor(Date.now() / 1000); }
function abs(base, path) { return base.replace(/\/$/, '') + path; }

async function attachMonitors(page, bucket) {
  page.on('pageerror', err => bucket.push({ type: 'pageerror', text: String(err) }));
  page.on('console', msg => {
    if (['error', 'warning'].includes(msg.type())) {
      bucket.push({ type: 'console:' + msg.type(), text: msg.text() });
    }
  });
  page.on('requestfailed', req => {
    bucket.push({ type: 'requestfailed', text: `${req.method()} ${req.url()} :: ${req.failure()?.errorText || 'unknown'}` });
  });
  page.on('response', res => {
    const url = res.url();
    if (res.status() >= 400 && !url.includes('favicon')) {
      bucket.push({ type: 'http' + res.status(), text: `${res.status()} ${url}` });
    }
  });
}

async function ensure(cond, msg) {
  if (!cond) throw new Error(msg);
}

async function login(page, base, username, password) {
  await page.goto(abs(base, '/login'), { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.click('button[type="submit"]')
  ]);
  await ensure(!page.url().includes('/login'), `login failed for ${username} at ${base}`);
}

async function logout(page, base) {
  await page.goto(abs(base, '/logout'), { waitUntil: 'networkidle' }).catch(() => {});
}

async function testSchedule(page, base, label) {
  await page.goto(abs(base, '/schedule'), { waitUntil: 'networkidle' });
  await page.waitForTimeout(1200);
  const appDefined = await page.evaluate(() => typeof App !== 'undefined');
  await ensure(appDefined, `${label}: App is undefined on schedule page`);
}

async function fillWorkflow(page) {
  await page.fill('#title', `Smoke-${stamp()}`);
  const requiredFields = await page.locator('input[required], select[required], textarea[required]').elementHandles();
  for (const handle of requiredFields) {
    const meta = await handle.evaluate(el => ({
      tag: el.tagName.toLowerCase(),
      type: (el.getAttribute('type') || '').toLowerCase(),
      id: el.id || '',
      name: el.getAttribute('name') || ''
    }));
    if (meta.id === 'title') continue;
    if (meta.tag === 'select') {
      const value = await handle.evaluate(el => Array.from(el.options).map(o => o.value).find(Boolean) || '');
      if (value) await handle.selectOption(value);
    } else if (meta.type === 'date') {
      await handle.fill('2026-03-26');
    } else if (meta.type === 'number') {
      await handle.fill('1');
    } else if (meta.type === 'radio') {
      await page.locator(`input[type="radio"][name="${meta.name}"]`).first().check().catch(() => {});
    } else if (meta.type === 'checkbox') {
      await handle.check().catch(() => {});
    } else {
      await handle.fill('テスト').catch(() => {});
    }
  }
}

async function testWorkflow(page, base, label) {
  await page.goto(abs(base, '/workflow/create/1'), { waitUntil: 'networkidle' });
  await page.waitForSelector('#btn-submit-request');
  await fillWorkflow(page);
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.click('#btn-submit-request')
  ]);
  const ok = /\/workflow\/(requests|view|request)/.test(page.url()) || await page.locator('text=承認待ち').count() > 0;
  await ensure(ok, `${label}: workflow submit did not complete`);
}

async function testTask(page, base, label) {
  const uniq = `SmokeBoard-${stamp()}`;
  await page.goto(abs(base, '/task/create-board'), { waitUntil: 'networkidle' });
  await page.fill('#boardName', uniq);
  await page.fill('#boardDescription', 'smoke');
  await Promise.all([
    page.waitForURL(/\/task\/board\/\d+/),
    page.click('#submitBtn')
  ]);
  const boardId = page.url().match(/\/task\/board\/(\d+)/)?.[1];
  await ensure(!!boardId, `${label}: board id not found`);

  await page.click('#add-list-btn');
  await page.fill('#listName', 'ToDo');
  await page.click('#saveListBtn');
  await page.waitForTimeout(1500);
  await ensure(await page.locator('.kanban-list-title:has-text("ToDo")').count() > 0, `${label}: list create failed`);

  await page.locator('.add-card').first().click();
  await page.fill('#cardTitle', 'SmokeCard');
  await page.fill('#cardDescription', 'card body');
  await page.click('#saveCardBtn');
  await page.waitForTimeout(1500);
  await ensure(await page.locator('.kanban-card-title:has-text("SmokeCard")').count() > 0, `${label}: card create failed`);

  await page.goto(abs(base, `/task/edit-board/${boardId}`), { waitUntil: 'networkidle' });
  await page.fill('#boardName', uniq + '-Edited');
  await Promise.all([
    page.waitForURL(new RegExp(`/task/board/${boardId}$`)),
    page.locator('#boardForm button[type="submit"]').click()
  ]);
  await ensure(await page.locator(`h4:has-text("${uniq}-Edited")`).count() > 0, `${label}: board update failed`);

  await page.goto(abs(base, `/task/edit-board/${boardId}`), { waitUntil: 'networkidle' });
  page.on('dialog', d => d.accept().catch(() => {}));
  await page.click('#deleteBoard');
  await page.waitForTimeout(500);
  const confirmBox = page.locator('#confirmBoardDelete');
  if (await confirmBox.count()) await confirmBox.check();
  const confirmBtn = page.locator('#confirmDelete');
  if (await confirmBtn.count()) {
    await Promise.all([
      page.waitForLoadState('networkidle'),
      confirmBtn.click()
    ]);
  } else {
    await page.locator('text=削除').last().click().catch(() => {});
    await page.waitForLoadState('networkidle');
  }
  await ensure(/\/task(\/|$)/.test(page.url()), `${label}: board delete did not redirect`);
}

async function testFile(page, base, label, approverLabel) {
  const uniq = `SmokeFile-${stamp()}`;
  const p1 = '/tmp/smoke-file-v1.txt';
  const p2 = '/tmp/smoke-file-v2.txt';
  fs.writeFileSync(p1, 'version1 ' + uniq + '\n');
  fs.writeFileSync(p2, 'version2 ' + uniq + '\n');

  await page.goto(abs(base, '/files/upload'), { waitUntil: 'networkidle' });
  await page.setInputFiles('#fileInput', p1);
  await page.fill('#title', uniq);
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.click('#uploadBtn')
  ]);
  await page.click(`text=${uniq}`);
  await page.waitForLoadState('networkidle');
  const fileId = page.url().match(/\/files\/file\/(\d+)/)?.[1];
  await ensure(!!fileId, `${label}: file detail open failed`);

  if (await page.locator('form[action$="/checkout"]').count()) {
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.locator('form[action$="/checkout"] button').click()
    ]);
  }

  await page.setInputFiles('#updateFile', p2);
  await page.fill('#comment', 'v2');
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.locator('form[action$="/update"] button[type="submit"]').click()
  ]);
  await ensure(await page.locator('text=Ver.2').count() > 0 || await page.locator('text=バージョン 2').count() > 0, `${label}: file update failed`);

  if (approverLabel && await page.locator('form[action*="request-approval"]').count()) {
    await page.selectOption('form[action*="request-approval"] select[name="approval_user_ids[]"]', { label: approverLabel }).catch(() => {});
    await page.fill('form[action*="request-approval"] textarea[name="approval_comment"]', 'approve smoke');
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.locator('form[action*="request-approval"] button[type="submit"]').click()
    ]);
  }

  return { fileId, uniq };
}

async function approveFileIfPossible(page, base, fileId) {
  await page.goto(abs(base, `/files/file/${fileId}`), { waitUntil: 'networkidle' });
  const approveBtn = page.locator('form[action*="/approve"] button[type="submit"]');
  if (await approveBtn.count()) {
    await Promise.all([
      page.waitForLoadState('networkidle'),
      approveBtn.click()
    ]);
  }
}

async function deleteFile(page, base, fileId, label) {
  await page.goto(abs(base, `/files/file/${fileId}`), { waitUntil: 'networkidle' });
  page.on('dialog', d => d.accept().catch(() => {}));
  const deleteBtn = page.locator(`form[action$="/files/file/${fileId}/delete"] button, form[action$="/file/${fileId}/delete"] button`);
  await ensure(await deleteBtn.count() > 0, `${label}: file delete button missing`);
  await Promise.all([
    page.waitForLoadState('networkidle'),
    deleteBtn.click()
  ]);
}

async function testMessageSend(page, base, label, recipientLabel) {
  const uniq = `SmokeMessage-${stamp()}`;
  await page.goto(abs(base, '/messages/compose'), { waitUntil: 'networkidle' });
  await page.selectOption('#recipients', { label: recipientLabel });
  await page.fill('#subject', uniq);
  await page.fill('#body', 'message body');
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.locator('#message-form button[type="submit"]').click()
  ]);
  await ensure(/\/messages\/sent/.test(page.url()), `${label}: message send redirect failed`);
  await ensure(await page.locator(`text=${uniq}`).count() > 0, `${label}: message not found in sent list`);
  return uniq;
}

async function deleteMessageAsRecipient(page, base, label, subject) {
  await page.goto(abs(base, '/messages/inbox'), { waitUntil: 'networkidle' });
  await page.click(`text=${subject}`);
  await page.waitForLoadState('networkidle');
  const starBtn = page.locator('.btn-toggle-star').first();
  if (await starBtn.count()) await starBtn.click().catch(() => {});
  const unreadBtn = page.locator('.btn-mark-as-unread').first();
  if (await unreadBtn.count()) await unreadBtn.click().catch(() => {});
  page.on('dialog', d => d.accept().catch(() => {}));
  const deleteBtn = page.locator('.btn-delete-message').first();
  await ensure(await deleteBtn.count() > 0, `${label}: delete message button missing`);
  await deleteBtn.click();
  await page.waitForLoadState('networkidle');
  await ensure(/\/messages\/inbox/.test(page.url()), `${label}: message delete did not return inbox`);
}

async function runSuite(base, adminCreds, recipientCreds, recipientLabel, label) {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1440, height: 1200 } });
  const page = await context.newPage();
  const errors = [];
  await attachMonitors(page, errors);

  try {
    await login(page, base, adminCreds.user, adminCreds.pass);
    await testSchedule(page, base, label);
    await testWorkflow(page, base, label);
    await testTask(page, base, label);
    const fileInfo = await testFile(page, base, label, recipientLabel);
    const subject = await testMessageSend(page, base, label, recipientLabel);

    if (recipientCreds) {
      await logout(page, base);
      await login(page, base, recipientCreds.user, recipientCreds.pass);
      await approveFileIfPossible(page, base, fileInfo.fileId);
      await deleteMessageAsRecipient(page, base, label, subject);
      await logout(page, base);
      await login(page, base, adminCreds.user, adminCreds.pass);
    }

    await deleteFile(page, base, fileInfo.fileId, label);
    return { label, ok: true, errors };
  } catch (error) {
    return { label, ok: false, error: String(error), url: page.url(), errors };
  } finally {
    await browser.close();
  }
}

(async () => {
  const results = [];
  results.push(await runSuite('https://groupware.yuus-program.com', { user: 'admin', pass: 'demo1234' }, { user: 'yamada', pass: 'demo1234' }, '山田太郎 (yamada)', 'xserver'));
  results.push(await runSuite('http://192.168.1.5/groupware', { user: 'admin', pass: 'admin123' }, { user: 'qa_viewer', pass: 'admin123' }, 'QA Viewer (qa_viewer)', 'local'));
  console.log(JSON.stringify(results, null, 2));
})();
