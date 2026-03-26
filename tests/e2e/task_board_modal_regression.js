const { chromium } = require('../../node_modules/playwright');

async function login(page, baseUrl, username, password) {
  await page.goto(baseUrl + '/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.click('button[type="submit"]')
  ]);
}

async function openFirstBoard(page, baseUrl) {
  await page.goto(baseUrl + '/task', { waitUntil: 'networkidle' });
  const boardLink = page.locator('a[href*="/task/board/"]').first();
  if ((await boardLink.count()) === 0) {
    throw new Error('No task board link found');
  }

  await Promise.all([
    page.waitForLoadState('networkidle'),
    boardLink.click()
  ]);
}

async function run() {
  const baseUrl = process.env.GW_BASE_URL || 'http://192.168.1.5/groupware';
  const username = process.env.GW_USERNAME || 'admin';
  const password = process.env.GW_PASSWORD || 'admin123';

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    ignoreHTTPSErrors: true,
    viewport: { width: 1440, height: 1200 }
  });
  const page = await context.newPage();
  page.setDefaultTimeout(30000);

  try {
    await login(page, baseUrl, username, password);
    await openFirstBoard(page, baseUrl);
    await page.waitForFunction(() => {
      return typeof TaskBoard !== 'undefined' && TaskBoard && TaskBoard._initialized === true;
    });

    const card = page.locator('.kanban-card').first();
    if ((await card.count()) === 0) {
      throw new Error('No kanban card found on board page');
    }

    const beforeUrl = page.url();
    await card.click();
    await page.waitForSelector('#cardDetailModal.show');
    const afterUrl = page.url();

    if (beforeUrl !== afterUrl) {
      throw new Error(`Card click changed URL unexpectedly: ${beforeUrl} -> ${afterUrl}`);
    }

    const modalText = ((await page.locator('#cardDetailBody').textContent()) || '').trim();
    if (!modalText) {
      throw new Error('Card detail modal opened without content');
    }

    const editLink = page.locator('#cardDetailBody a[href*="/task/edit-card/"]').first();
    if ((await editLink.count()) === 0) {
      throw new Error('Expected explicit edit page link not found in card detail modal');
    }

    console.log('task_board_modal_regression: OK');
  } finally {
    await browser.close();
  }
}

run().catch((error) => {
  console.error('task_board_modal_regression: FAIL');
  console.error(error && error.stack ? error.stack : String(error));
  process.exit(1);
});
