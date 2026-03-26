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

async function assertNoRequestOnLoad(page, pathPart, action) {
  const hits = [];
  const handler = (req) => {
    if (req.url().includes(pathPart)) {
      hits.push(req.url());
    }
  };

  page.on('request', handler);
  try {
    await action();
  } finally {
    page.off('request', handler);
  }

  if (hits.length > 0) {
    throw new Error(`Unexpected request detected for ${pathPart}: ${hits[0]}`);
  }
}

async function getApiCacheControl(page, path) {
  return page.evaluate(async (path) => {
    const response = await fetch(path, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      cache: 'no-store'
    });

    return {
      status: response.status,
      cacheControl: response.headers.get('cache-control') || '',
      pragma: response.headers.get('pragma') || '',
      expires: response.headers.get('expires') || ''
    };
  }, path);
}

async function verifyUserView(page, baseUrl) {
  await assertNoRequestOnLoad(page, '/api/users/1/organizations', async () => {
    await page.goto(baseUrl + '/users/view/1', { waitUntil: 'networkidle' });
  });

  const orgList = page.locator('#organization-list');
  await orgList.waitFor();

  const text = (await orgList.textContent()) || '';
  if (!text.includes('主組織') && !text.includes('所属組織はありません') && text.trim().length === 0) {
    throw new Error('User view regression: organization list was not rendered on initial load');
  }

  await assertNoRequestOnLoad(page, '/api/users/1/organizations', async () => {
    await page.reload({ waitUntil: 'networkidle' });
  });

  const orgLink = page.locator('#organization-list a').first();
  if (await orgLink.count()) {
    return await orgLink.getAttribute('href');
  }

  return baseUrl + '/organizations/view/1';
}

async function verifyOrganizationView(page, orgUrl) {
  const resolvedOrgUrl = new URL(orgUrl, page.url()).toString();
  const orgPath = new URL(resolvedOrgUrl).pathname;
  const apiPathPart = '/api/organizations/' + orgPath.split('/').pop() + '/users';

  await assertNoRequestOnLoad(page, apiPathPart, async () => {
    await page.goto(resolvedOrgUrl, { waitUntil: 'networkidle' });
  });

  const bodyText = (await page.locator('body').textContent()) || '';
  if (!bodyText.includes('所属ユーザー')) {
    throw new Error('Organization view regression: organization detail page did not render');
  }

  const usersTable = page.locator('#users-table');
  if (await usersTable.count()) {
    const rowCount = await usersTable.locator('tbody tr').count();
    if (rowCount === 0) {
      throw new Error('Organization view regression: users table rendered with no rows');
    }
  }

  await assertNoRequestOnLoad(page, apiPathPart, async () => {
    await page.reload({ waitUntil: 'networkidle' });
  });
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

    const basePath = new URL(baseUrl).pathname.replace(/\/$/, '');

    const cache1 = await getApiCacheControl(page, basePath + '/api/organizations');
    if (!cache1.cacheControl.includes('no-store')) {
      throw new Error('API cache header regression on /api/organizations: ' + JSON.stringify(cache1));
    }

    const cache2 = await getApiCacheControl(page, basePath + '/api/users/1/organizations');
    if (!cache2.cacheControl.includes('no-store')) {
      throw new Error('API cache header regression on /api/users/1/organizations: ' + JSON.stringify(cache2));
    }

    const orgUrl = await verifyUserView(page, baseUrl);
    await verifyOrganizationView(page, orgUrl);
    console.log('cache_profile_org_regression: OK');
  } finally {
    await browser.close();
  }
}

run().catch((error) => {
  console.error('cache_profile_org_regression: FAIL');
  console.error(error && error.stack ? error.stack : String(error));
  process.exit(1);
});
