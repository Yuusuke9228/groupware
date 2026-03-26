const { chromium } = require('/Volumes/www/html/groupware/node_modules/playwright');
(async()=>{
  const browser = await chromium.launch({headless:true});
  const page = await (await browser.newContext({ignoreHTTPSErrors:true})).newPage();
  try {
    console.log('start');
    await page.goto('https://groupware.yuus-program.com/login', {waitUntil:'domcontentloaded'});
    console.log('login page');
    await page.fill('input[name="username"]','admin');
    await page.fill('input[name="password"]','demo1234');
    await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type="submit"]')]);
    console.log('logged in', page.url());
    await page.goto('https://groupware.yuus-program.com/workflow/create/1', {waitUntil:'networkidle'});
    console.log('workflow page');
    await page.fill('#title','UIProbe'); console.log('title');
    await page.selectOption('#leave_type', {index:1}); console.log('leave_type');
    await page.fill('#start_date','2026-03-26'); console.log('start_date');
    await page.fill('#end_date','2026-03-26'); console.log('end_date');
    await page.fill('#days_count','1'); console.log('days_count');
    await page.fill('#reason','テスト'); console.log('reason');
    const validity = await page.$eval('form', f => ({valid: f.checkValidity(), status: document.getElementById('status').value, action: f.action}));
    console.log('validity', JSON.stringify(validity));
    await page.click('#btn-submit-request'); console.log('clicked');
    await page.waitForTimeout(5000);
    console.log('after', page.url());
    const body = await page.locator('body').innerText();
    console.log(body.slice(0,1000));
  } catch (e) {
    console.error('ERR', e);
  }
  await browser.close();
})();
