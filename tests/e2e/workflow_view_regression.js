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
    await page.goto(baseUrl + '/workflow/requests', { waitUntil: 'networkidle' });

    const link = page.locator('a[href*="/workflow/view/"]').first();
    if ((await link.count()) === 0) {
      throw new Error('No workflow request link found');
    }

    await Promise.all([
      page.waitForLoadState('networkidle'),
      link.click()
    ]);

    const body = (await page.locator('body').textContent()) || '';
    if (/Fatal error|Cannot access offset of type string/.test(body)) {
      throw new Error('Workflow request view still renders fatal error');
    }

    if (!body.includes('申請詳細')) {
      throw new Error('Workflow request view did not render expected title');
    }

    console.log('workflow_view_regression: OK');
  } finally {
    await browser.close();
  }
}

run().catch((error) => {
  console.error('workflow_view_regression: FAIL');
  console.error(error && error.stack ? error.stack : String(error));
  process.exit(1);
});
