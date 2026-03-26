const { chromium } = require('/Volumes/www/html/groupware/node_modules/playwright');

async function login(page, base, user, pass) {
  await page.goto(base + '/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="username"]', user);
  await page.fill('input[name="password"]', pass);
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.click('button[type="submit"]')
  ]);
}

async function run(base, user, pass, label) {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1440, height: 1200 } });
  const page = await context.newPage();
  page.setDefaultTimeout(15000);
  page.on('console', msg => console.log(label, 'console', msg.type(), msg.text()));
  page.on('response', async res => {
    if (!res.url().includes('/api/task/')) return;
    let body = '';
    try { body = await res.text(); } catch (e) {}
    console.log(label, 'api', res.status(), res.url(), body.slice(0, 250));
  });

  try {
    await login(page, base, user, pass);
    console.log(label, 'logged in', page.url());

    const boardName = 'Board-' + Date.now();
    await page.goto(base + '/task/create-board', { waitUntil: 'networkidle' });
    await page.fill('#boardName', boardName);
    await page.fill('#boardDescription', 'smoke');
    await Promise.all([
      page.waitForURL(/\/task\/board\/\d+/),
      page.click('#submitBtn')
    ]);
    const boardUrl = page.url();
    const boardId = (boardUrl.match(/\/(\d+)$/) || [])[1];
    console.log(label, 'board', boardUrl, boardId);

    await page.click('#add-list-btn');
    await page.waitForSelector('#addListModal.show');
    await page.locator('#addListModal #listName').fill('ToDo');
    await page.click('#saveListBtn');
    await page.waitForTimeout(2000);
    console.log(label, 'lists', await page.locator('.kanban-list-title').allTextContents());

    const addCard = page.locator('.kanban-add-card .add-card').first();
    await addCard.click();
    await page.waitForSelector('#addCardModal.show');
    await page.locator('#addCardModal #cardTitle').fill('Card1');
    await page.locator('#addCardModal #cardDescription').fill('body');
    await page.click('#saveCardBtn');
    await page.waitForTimeout(2000);
    console.log(label, 'cards', await page.locator('.kanban-card-title').allTextContents());

    await page.goto(base + '/task/edit-board/' + boardId, { waitUntil: 'networkidle' });
    await page.fill('#boardName', boardName + '-Edited');
    await Promise.all([
      page.waitForURL(new RegExp('/task/board/' + boardId + '$')),
      page.locator('#boardForm button[type="submit"]').click()
    ]);
    console.log(label, 'updated', await page.locator('h4').first().textContent());

    page.on('dialog', d => d.accept().catch(() => {}));
    await page.goto(base + '/task/edit-board/' + boardId, { waitUntil: 'networkidle' });
    await page.click('#deleteBoard');
    if (await page.locator('#confirmBoardDelete').count()) {
      await page.locator('#confirmBoardDelete').check();
    }
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.click('#confirmDelete')
    ]);
    console.log(label, 'after delete', page.url());
    console.log(label, 'OK');
  } catch (error) {
    console.error(label, 'FAIL', error && error.stack ? error.stack : String(error));
  } finally {
    await browser.close();
  }
}

(async () => {
  await run('https://groupware.yuus-program.com', 'admin', 'demo1234', 'xserver');
  await run('http://192.168.1.5/groupware', 'admin', 'admin123', 'local');
})();
