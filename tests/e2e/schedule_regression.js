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

async function fetchSchedule(page, scheduleId) {
  return page.evaluate(async (sid) => {
    const url = window.location.origin + (window.BASE_PATH || '') + '/api/schedule/' + sid;
    const response = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    return await response.json();
  }, scheduleId);
}

async function createSchedule(page, baseUrl, title) {
  await page.goto(baseUrl + '/schedule/create?date=2026-03-26&time=10:00', { waitUntil: 'networkidle' });
  await page.fill('#title', title);
  await page.click('button[type="submit"]');
  await page.waitForURL(/\/schedule\/view\/\d+/);
  return page.url().split('/').pop();
}

async function verifyParticipantUpdate(page, baseUrl, participantId) {
  const title = 'E2E-PARTICIPANT-' + Date.now();
  const scheduleId = await createSchedule(page, baseUrl, title);

  await page.goto(baseUrl + '/schedule/edit/' + scheduleId, { waitUntil: 'networkidle' });
  await page.selectOption('#visibility', 'specific');
  await page.selectOption('#participants', String(participantId));
  await page.click('button[type="submit"]');
  await page.waitForURL(new RegExp('/schedule/view/' + scheduleId + '$'));

  const response = await fetchSchedule(page, scheduleId);
  const participantIds = (response.data?.participants || []).map((participant) => Number(participant.id));
  if (!participantIds.includes(Number(participantId))) {
    throw new Error('Participant update regression: selected participant was not saved');
  }
}

async function verifyDeleteRefresh(page, baseUrl) {
  const title = 'E2E-DELETE-' + Date.now();
  await createSchedule(page, baseUrl, title);

  await page.goto(baseUrl + '/schedule/day?date=2026-03-26', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1500);

  const item = page.locator(`.schedule-item:has-text("${title}")`).first();
  if ((await item.count()) !== 1) {
    throw new Error('Delete refresh regression: created schedule was not rendered in day view');
  }

  await item.click();
  await page.waitForSelector('#schedule-modal.show');
  page.on('dialog', (dialog) => dialog.accept().catch(() => {}));
  await page.locator('#schedule-modal .delete-btn').nth(1).click();
  await page.waitForTimeout(4000);

  if ((await page.locator(`.schedule-item:has-text("${title}")`).count()) !== 0) {
    throw new Error('Delete refresh regression: deleted schedule remained visible in day view');
  }
}

async function run() {
  const baseUrl = process.env.GW_BASE_URL || 'http://192.168.1.5/groupware';
  const username = process.env.GW_USERNAME || 'admin';
  const password = process.env.GW_PASSWORD || 'admin123';
  const participantId = Number(process.env.GW_PARTICIPANT_ID || '3');

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    ignoreHTTPSErrors: true,
    viewport: { width: 1440, height: 1200 }
  });
  const page = await context.newPage();
  page.setDefaultTimeout(30000);

  try {
    await login(page, baseUrl, username, password);
    await verifyParticipantUpdate(page, baseUrl, participantId);
    await verifyDeleteRefresh(page, baseUrl);
    console.log('schedule_regression: OK');
  } finally {
    await browser.close();
  }
}

run().catch((error) => {
  console.error('schedule_regression: FAIL');
  console.error(error && error.stack ? error.stack : String(error));
  process.exit(1);
});
