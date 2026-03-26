const { chromium } = require('/Volumes/www/html/groupware/node_modules/playwright');

async function run(base, user, password, label) {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();
  const logs = [];
  page.on('response', async res => {
    const url = res.url();
    if (url.includes('/api/workflow/requests')) {
      let body = '';
      try { body = await res.text(); } catch {}
      logs.push({status: res.status(), url, body});
    }
  });
  await page.goto(base + '/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="username"]', user);
  await page.fill('input[name="password"]', password);
  await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type="submit"]')]);
  await page.goto(base + '/workflow/create/1', { waitUntil: 'networkidle' });
  const required = await page.locator('input[required],select[required],textarea[required]').evaluateAll(nodes => nodes.map(n => ({id:n.id,name:n.name,type:n.type||n.tagName.toLowerCase(),tag:n.tagName.toLowerCase()})));
  console.log('REQUIRED', label, JSON.stringify(required));
  await page.fill('#title', 'Probe-' + Date.now());
  for (const item of required) {
    if (item.id === 'title') continue;
    const sel = item.id ? ('#' + item.id) : `[name="${item.name}"]`;
    if (item.tag === 'select') {
      const vals = await page.locator(sel + ' option').evaluateAll(opts => opts.map(o=>o.value).filter(Boolean));
      if (vals[0]) await page.selectOption(sel, vals[0]);
    } else if (item.type === 'date') {
      await page.fill(sel, '2026-03-26');
    } else if (item.type === 'number') {
      await page.fill(sel, '1');
    } else if (item.type === 'radio') {
      await page.locator(`[name="${item.name}"]`).first().check().catch(()=>{});
    } else if (item.type === 'checkbox') {
      await page.locator(sel).check().catch(()=>{});
    } else {
      await page.fill(sel, 'テスト').catch(()=>{});
    }
  }
  await page.click('#btn-submit-request');
  await page.waitForTimeout(3000);
  console.log('URL', label, page.url());
  console.log('API', label, JSON.stringify(logs));
  const bodyText = await page.locator('body').innerText();
  console.log('BODY', label, bodyText.slice(0, 1200));
  await browser.close();
}
(async()=>{
  await run('https://groupware.yuus-program.com','admin','demo1234','xserver');
  await run('http://192.168.1.5/groupware','admin','admin123','local');
})();
