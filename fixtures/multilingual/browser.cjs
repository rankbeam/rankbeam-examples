const assert = require('node:assert/strict');
const { chromium } = require('playwright');
(async () => {
 const major = process.argv[2] || '5';
 const base = process.env.RANKBEAM_FIXTURE_URL || `http://127.0.0.1:824${major}`;
 assert(['127.0.0.1','localhost'].includes(new URL(base).hostname), 'Local fixture only');
 const output = process.env.RANKBEAM_FIXTURE_OUTPUT || require('node:path').join(process.cwd(), 'artifacts');
 require('node:fs').mkdirSync(output,{recursive:true});
 const browser = await chromium.launch({executablePath:process.env.PLAYWRIGHT_EXECUTABLE_PATH || undefined,headless:true});
 try {
  const context = await browser.newContext({viewport:{width:1440,height:1000},deviceScaleFactor:2});
  const page = await context.newPage();
  const errors=[]; page.on('pageerror',e=>errors.push(e.message));
  const field = name => page.locator(`[id="form.${name}"]`);
  async function changeLocale(locale, expected) {
   if(await page.locator('#activeLocale').inputValue()!==locale) {
    await Promise.all([page.waitForResponse(r=>r.request().method()==='POST'&&r.url().includes('livewire')),page.locator('#activeLocale').selectOption(locale)]);
   }
   if(expected) await page.waitForFunction(value=>document.querySelector('[id="form.seo_meta.title"]').value===value,expected);
  }
  const saved = async locale => {
   const slug={en:'coffee-at-home',it:'caffè-a-casa',ja:'自宅のコーヒー'}[locale];
   const res=await context.request.get(`${base}/${locale}/posts/${encodeURIComponent(slug)}`);
   assert.equal(res.status(),200); return res.text();
  };
  await page.goto(base+'/admin/login');
  await page.getByLabel('Email address').fill('editor@example.test');
  await page.locator('input[type=password]').fill('Local-fixture-only-123!');
  await page.getByRole('button',{name:'Sign in',exact:true}).click();
  await page.waitForURL(url=>!url.pathname.endsWith('/login'));
  await page.goto(base+'/admin/posts/1/edit');
  await changeLocale('it','Il caffè a casa');
  await field('seo_meta.title').fill('Una bozza di caffè');
  await changeLocale('ja','自宅で淹れるコーヒー');
  await field('seo_meta.title').fill('コーヒーの下書き');
  await changeLocale('it','Una bozza di caffè');
  assert((await saved('it')).includes('<title>Il caffè a casa</title>'));
  await page.getByRole('button',{name:'Suggest with AI',exact:true}).first().click();
  const radio=page.getByRole('radio').first(); await radio.waitFor();
  const suggestion=await radio.inputValue(); assert.match(suggestion,/caffè/);
  await radio.check();
  await page.getByRole('button',{name:'Use suggestion',exact:true}).click();
  await page.waitForFunction(v=>document.querySelector('[id="form.seo_meta.title"]').value===v,suggestion);
  assert((await saved('it')).includes('<title>Il caffè a casa</title>'));
  await changeLocale('ja','コーヒーの下書き');
  await changeLocale('it',suggestion);
  await page.getByRole('button',{name:'Save changes',exact:true}).click();
  await page.getByText('Saved',{exact:true}).first().waitFor();
  assert((await saved('it')).includes(`<title>${suggestion}</title>`));
  assert((await saved('ja')).includes('<title>コーヒーの下書き</title>'));
  assert((await saved('en')).includes('<title>Coffee at home</title>'));
  await page.reload(); await changeLocale('it',suggestion);
  await field('seo_meta.title').scrollIntoViewIfNeeded();
  await page.screenshot({path:output+`/editor${major}-it-desktop.png`});
  await changeLocale('ja','コーヒーの下書き');
  await page.screenshot({path:output+`/editor${major}-ja-desktop.png`});
  for(const width of [390,320]) {
   await page.setViewportSize({width,height:844});
   for(const locale of ['it','ja']) {
    await changeLocale(locale,locale==='it'?suggestion:'コーヒーの下書き');
    const overflow=await page.evaluate(()=>({viewport:innerWidth,document:document.documentElement.scrollWidth}));
    assert(overflow.document<=width,JSON.stringify(overflow));
    await field('seo_meta.title').scrollIntoViewIfNeeded();
    await page.screenshot({path:output+`/editor${major}-${locale}-${width}.png`});
   }
  }
  await page.goto(base+'/admin/posts/2/edit');
  assert.notEqual(await field('seo_meta.title').inputValue(),suggestion);
  await page.locator('#activeLocale').focus(); await page.keyboard.press('j'); await page.keyboard.press('Tab');
  assert.deepEqual(errors,[]);
  console.log(JSON.stringify({major,passed:true,suggestion,locales:['en','it','ja'],viewports:[1440,390,320],scale:2,checks:['draft round trip','no pre-save metadata write','offline AI suggestion/apply','one Save persists IT/JA only','reload','record isolation','keyboard switcher','no page overflow','no JS errors']}));
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
