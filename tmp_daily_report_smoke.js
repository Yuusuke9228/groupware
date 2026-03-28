const { chromium } = require('/Volumes/www/html/groupware/node_modules/playwright');

function abs(base, path) { return base.replace(/\/$/, '') + path; }
function uniq(prefix) { return `${prefix}-${Date.now()}-${Math.floor(Math.random() * 1000)}`; }

async function login(page, base, user, pass) {
  await page.goto(abs(base, '/login'), { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="username"]', user);
  await page.fill('input[name="password"]', pass);
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('button[type="submit"]')]);
  if (page.url().includes('/login')) {
    throw new Error(`login failed: ${user} @ ${base}`);
  }
}

async function createTemplate(page, base, title, description) {
  await page.goto(abs(base, '/daily-report/template/edit'), { waitUntil: 'domcontentloaded' });
  await page.fill('#title', title);
  await page.fill('#description', description);
  await page.fill('#content', '# 日報テンプレ\n- 自由記述');

  // 1行目を上書き
  await page.fill('#templateSectionTable tbody .section-row:nth-child(1) .section-key', 'daily_summary');
  await page.fill('#templateSectionTable tbody .section-row:nth-child(1) .section-title', '本日のサマリー');
  await page.selectOption('#templateSectionTable tbody .section-row:nth-child(1) .section-type', 'textarea');

  await page.click('#addSectionBtn');
  const rows = page.locator('#templateSectionTable tbody .section-row');
  const last = rows.last();
  await last.locator('.section-key').fill('daily_activity');
  await last.locator('.section-title').fill('主要活動');
  await last.locator('.section-type').selectOption('text');

  await Promise.all([
    page.waitForURL(/\/daily-report\/templates/),
    page.click('#templateForm button[type="submit"]')
  ]);

  const editLink = page.locator(`a[href*="/daily-report/template/edit/"]:has-text("編集")`).first();
  const row = page.locator('table tbody tr').filter({ hasText: title }).first();
  if (await row.count()) {
    const href = await row.locator('a[href*="/daily-report/template/edit/"]').first().getAttribute('href');
    const m = href && href.match(/\/daily-report\/template\/edit\/(\d+)/);
    if (m) return Number(m[1]);
  }

  if (await editLink.count()) {
    const href = await editLink.getAttribute('href');
    const m = href && href.match(/\/daily-report\/template\/edit\/(\d+)/);
    if (m) return Number(m[1]);
  }

  throw new Error('template id not found after create');
}

async function createReport(page, base, templateId, reportTitle) {
  await page.goto(abs(base, `/daily-report/create?template_id=${templateId}`), { waitUntil: 'domcontentloaded' });
  await page.fill('#title', reportTitle);
  await page.fill('#summary_text', '成果: テスト登録');
  await page.fill('#issues_text', '課題: なし');
  await page.fill('#tomorrow_plan_text', '明日: 継続対応');
  await page.fill('#reflection_text', '所感: 問題なし');
  await page.fill('#work_minutes', '420');

  const firstDetailValue = page.locator('#detailItemsContainer .detail-item-row .detail-item-value').first();
  if (await firstDetailValue.count()) {
    await firstDetailValue.fill('テンプレート項目入力テスト');
  }

  const firstActivity = page.locator('#activityTable tbody .activity-row').first();
  await firstActivity.locator('.activity-start').fill('09:00');
  await firstActivity.locator('.activity-end').fill('10:00');
  await firstActivity.locator('.activity-type').fill('開発');
  await firstActivity.locator('.activity-subject').fill('日報機能改修');
  await firstActivity.locator('.activity-result').fill('実装完了');
  await firstActivity.locator('.activity-memo').fill('動作確認予定');

  await Promise.all([
    page.waitForURL(/\/daily-report\/view\/(\d+)/),
    page.click('#reportForm button[type="submit"]')
  ]);

  const m = page.url().match(/\/daily-report\/view\/(\d+)/);
  if (!m) throw new Error('report id not found after create');
  return Number(m[1]);
}

async function updateReport(page, base, reportId, suffix) {
  await page.goto(abs(base, `/daily-report/edit/${reportId}`), { waitUntil: 'domcontentloaded' });
  await page.fill('#summary_text', `成果: 更新確認 ${suffix}`);
  await page.fill('#reflection_text', `所感: 更新 ${suffix}`);
  await page.fill('#work_minutes', '450');

  await page.click('#addActivityRowBtn');
  const last = page.locator('#activityTable tbody .activity-row').last();
  await last.locator('.activity-start').fill('16:00');
  await last.locator('.activity-end').fill('17:00');
  await last.locator('.activity-type').fill('レビュー');
  await last.locator('.activity-subject').fill('最終確認');
  await last.locator('.activity-result').fill('更新反映');

  await Promise.all([
    page.waitForURL(new RegExp(`/daily-report/view/${reportId}$`)),
    page.click('#reportForm button[type="submit"]')
  ]);

  const ok = await page.locator(`text=成果: 更新確認 ${suffix}`).count();
  if (!ok) throw new Error('report update verification failed');
}

async function verifyReadList(page, base, reportTitle) {
  await page.goto(abs(base, `/daily-report/list?search=${encodeURIComponent(reportTitle)}`), { waitUntil: 'domcontentloaded' });
  const count = await page.locator('table tbody tr', { hasText: reportTitle }).count();
  if (!count) throw new Error('report not found in list search');
}

async function deleteReport(page, base, reportId) {
  await page.goto(abs(base, `/daily-report/edit/${reportId}`), { waitUntil: 'domcontentloaded' });
  page.on('dialog', d => d.accept().catch(() => {}));
  await page.click('#deleteButton');
  await page.waitForURL(/\/daily-report(\/|$)/);
}

async function deleteTemplate(page, base, templateId) {
  await page.goto(abs(base, '/daily-report/templates'), { waitUntil: 'domcontentloaded' });
  page.on('dialog', d => d.accept().catch(() => {}));
  const btn = page.locator(`button.delete-template[data-id="${templateId}"]`).first();
  if (await btn.count()) {
    await btn.click();
    await page.waitForTimeout(1000);
  }
}

async function runSuite(target) {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1440, height: 1200 } });
  const page = await context.newPage();
  page.setDefaultTimeout(15000);
  page.setDefaultNavigationTimeout(30000);
  try {
    console.log(`[${target.label}] start`);
    await login(page, target.base, target.user, target.pass);
    console.log(`[${target.label}] login ok`);

    const keepTemplateTitle = uniq(target.keepPrefix + '-tpl');
    const keepReportTitle = uniq(target.keepPrefix + '-rep');

    const keepTemplateId = await createTemplate(page, target.base, keepTemplateTitle, '確認用テンプレート');
    console.log(`[${target.label}] template create ok: ${keepTemplateId}`);
    const keepReportId = await createReport(page, target.base, keepTemplateId, keepReportTitle);
    console.log(`[${target.label}] report create ok: ${keepReportId}`);
    await updateReport(page, target.base, keepReportId, 'KEEP');
    console.log(`[${target.label}] report update ok`);
    await verifyReadList(page, target.base, keepReportTitle);
    console.log(`[${target.label}] list read ok`);

    let deleted = { reportId: null, templateId: null };

    if (!target.preserveData) {
      const delTemplateTitle = uniq(target.keepPrefix + '-del-tpl');
      const delReportTitle = uniq(target.keepPrefix + '-del-rep');
      const delTemplateId = await createTemplate(page, target.base, delTemplateTitle, '削除確認テンプレート');
      const delReportId = await createReport(page, target.base, delTemplateId, delReportTitle);
      await deleteReport(page, target.base, delReportId);
      await deleteTemplate(page, target.base, delTemplateId);
      deleted = { reportId: delReportId, templateId: delTemplateId };
      console.log(`[${target.label}] delete ok`);
    }

    console.log(JSON.stringify({
      label: target.label,
      status: 'ok',
      kept: { templateId: keepTemplateId, reportId: keepReportId, templateTitle: keepTemplateTitle, reportTitle: keepReportTitle },
      deleted,
      preserveData: target.preserveData
    }));
  } catch (e) {
    console.log(JSON.stringify({ label: target.label, status: 'fail', error: String(e) }));
  }
  await browser.close();
}

(async () => {
  const targets = [
    {
      label: 'local',
      base: 'http://127.0.0.1:8090',
      user: 'admin',
      pass: 'admin123',
      preserveData: false,
      keepPrefix: 'LOCAL-NIPPO'
    },
    {
      label: 'demo',
      base: 'https://groupware.yuus-program.com',
      user: 'admin',
      pass: 'demo1234',
      preserveData: true,
      keepPrefix: 'DEMO-NIPPO'
    }
  ];

  for (const target of targets) {
    await runSuite(target);
  }
})();
