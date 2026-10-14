'use strict';
// Actual local WordPress/API acceptance followed by controlled UI boundary responses.
const {chromium}=require('../.tools/frontend-tests/node_modules/playwright');
const assert=require('node:assert/strict');
const reference=require('../wp-content/plugins/love-fortune-core/config/references/timezones-v1.json');
(async()=>{
 const browser=await chromium.launch({headless:true});
 try {
 const context=await browser.newContext(),page=await context.newPage();const requests=[],errors=[];let leak=false;
 page.on('pageerror',e=>errors.push(e.message));page.on('console',m=>{if(/1990-01-01|1992-02-02|signedContext/.test(m.text()))leak=true;});
 page.on('request',r=>{if(r.url().includes('/love-fortune/v1/'))requests.push({url:r.url(),body:r.postDataJSON()});});
 await page.goto('http://localhost:8080/love-fortune-local-test/');
 const app=page.locator('.love-fortune-app'),field=n=>app.locator(`[data-field=${n}]`),view=n=>app.locator(`[data-view=${n}]`),action=n=>app.locator(`[data-action=${n}]`),submit=app.locator('[type=submit]');
 const storage=()=>page.evaluate(()=>({local:{...localStorage},session:Object.fromEntries(Object.entries(sessionStorage).filter(([k])=>k!=='wpEmojiSettingsSupports'))}));
 const original=page.url(),stored=await storage(),cookies=await context.cookies();
 assert.equal(await app.locator('[data-mode=compatibility]').getAttribute('aria-pressed'),'true');assert.equal(requests.length,0);
 for(const [person,date] of [['personA','1990-01-01'],['personB','1992-02-02']]){await app.locator(`[data-person=${person}] [data-field=birthDate]`).fill(date);await app.locator(`[data-person=${person}] select`).selectOption('LOC000002');}
 await field('relationshipType').selectOption('FRIEND');
 await app.locator('[data-mode=daily]').focus();
 assert.equal(await app.locator('[data-mode=daily]').evaluate(el=>getComputedStyle(el).outlineStyle),'solid');
 await page.keyboard.press('Enter');assert.equal(requests.length,0);
 assert.equal(await app.locator('[data-mode=daily]').getAttribute('aria-pressed'),'true');
 assert.equal(await app.locator('[role=tab]').count(),0);
 assert.equal(await field('relationshipType').inputValue(),'FRIEND');assert.equal(await field('date').inputValue(),'');assert.equal(await field('dailyTimezone').inputValue(),'Asia/Tokyo');
 assert.deepEqual(await field('dailyTimezone').locator('option').evaluateAll(xs=>xs.map(x=>x.value)),reference.zones.map(x=>x.timezoneId));
 assert.equal(await field('birthLocationId').first().locator('option').count(),130);
 for(const value of ['','1899-12-31','2100-01-01','1900-02-29'])assert.equal(await field('date').evaluate((el,v)=>{el.value=v;return el.checkValidity();},value),false);
 for(const value of ['1900-01-01','2000-02-29','2099-12-31'])assert.equal(await field('date').evaluate((el,v)=>{el.value=v;return el.checkValidity();},value),true);
 await field('date').fill('2026-10-14');await field('dailyTimezone').selectOption('America/New_York');
 assert.equal(await field('date').inputValue(),'2026-10-14');
 await app.locator('[data-person=personA] [data-field=unknown]').uncheck();await app.locator('[data-person=personA] [data-field=birthTime]').fill('10:30');
 const response=page.waitForResponse(r=>r.url().includes('/fortune/daily')&&r.request().method()==='POST');await submit.click();
 const rr=await response;assert.equal(rr.status(),200);const real=await rr.json();await view('result').waitFor({state:'visible'});
 assert.equal(requests.length,1);assert.ok(requests[0].url.endsWith('/fortune/daily'));
 assert.deepEqual(Object.keys(requests[0].body).sort(),['date','locale','personA','personB','relationshipType','targetTimezone']);
 for(const person of ['personA','personB'])assert.deepEqual(Object.keys(requests[0].body[person]).sort(),['birthDate','birthLocationId','birthTime']);
 assert.equal(await view('result').evaluate(el=>el===document.activeElement),true);
 assert.equal(requests[0].body.personA.birthTime,'10:30');assert.equal(requests[0].body.personB.birthTime,null);assert.equal(requests[0].body.targetTimezone,'America/New_York');
 assert.equal(await view('score').textContent(),String(real.dailyScore));assert.ok((await view('daily-reference').textContent()).includes(real.targetTimezone));
 assert.match(await view('categories').locator('article').nth(2).textContent(),/점수 미제공/);assert.match(await view('categories').locator('article').nth(7).textContent(),/날짜별 변동은 적용하지 않아요/);
 const interpretation=page.waitForResponse(r=>r.url().includes('/interpretation/generate'));await action('interpret').click();const ir=await interpretation;assert.equal(ir.status(),200);assert.equal((await ir.json()).meta.interpretationMode,'FALLBACK');await view('interpretation').waitFor({state:'visible'});
 assert.deepEqual(Object.keys(requests[1].body).sort(),['locale','signedContext']);assert.ok(!(await page.content()).includes(real.signedInterpretationContext));
 assert.equal(await app.locator('details').evaluate(el=>el.open),false);
 assert.equal(await app.evaluate(el=>!!(el.querySelector('[data-view=interpretation]').compareDocumentPosition(el.querySelector('details'))&Node.DOCUMENT_POSITION_FOLLOWING)),true);
 for(const width of [320,375,768,1280]){await page.setViewportSize({width,height:900});assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));assert.ok(await app.evaluate(el=>el.scrollWidth<=el.clientWidth));await page.screenshot({path:'.tools/frontend-v1-2-'+width+'.png',fullPage:true});}
 assert.equal(await app.locator('input,select').evaluateAll(xs=>xs.every(x=>x.labels.length>0)),true);
 assert.equal(await view('loading').getAttribute('aria-live'),'polite');assert.equal(await view('interpretation-error').getAttribute('role'),'alert');
 const count=requests.length;await action('reset').click();assert.equal(await field('date').inputValue(),'2026-10-14');assert.equal(await field('dailyTimezone').inputValue(),'America/New_York');assert.equal(await view('result').isVisible(),false);assert.equal(await view('score').textContent(),'');
 await app.locator('[data-mode=compatibility]').click();await app.locator('[data-mode=daily]').click();assert.equal(requests.length,count);await action('interpret').dispatchEvent('click');assert.equal(requests.length,count);
 const dailyRoute='**/fortune/daily';
 const fake=async result=>{await page.route(dailyRoute,route=>route.fulfill({status:200,contentType:'application/json',body:JSON.stringify(result)}));await submit.click();await view('result').waitFor({state:'visible'});await page.unroute(dailyRoute);};
 for(const [status,copy,delta] of [['VERY_LOW','신중히 살펴볼 흐름',-6],['LOW','여유를 두면 좋은 흐름',-3],['STABLE','차분한 흐름',0],['GOOD','긍정적인 흐름',3],['VERY_GOOD','한층 긍정적인 흐름',6]]){
  await fake({...real,dailyStatus:status,dailyDelta:delta,dailyScore:100,lifetimeScore:99,coverage:0.25,resultConfidence:0.8});
  assert.equal(await view('score').textContent(),'100');assert.equal(await view('status').textContent(),copy);assert.equal(await view('daily-delta').textContent(),'기본 궁합 대비 조정값 '+(delta>0?'+':'')+delta);assert.match(await view('details').textContent(),/0.25.*0.8/);
 }
 const nullResult={...real,dailyScore:null,lifetimeScore:null,dailyDelta:0,dailyStatus:'INSUFFICIENT_DATA',coverage:0.5,resultConfidence:0};delete nullResult.signedInterpretationContext;
 await fake(nullResult);assert.match(await view('score').textContent(),/정보가 부족/);assert.equal(await view('score-unit').isVisible(),false);assert.equal(await action('interpret').isVisible(),false);assert.match(await view('details').textContent(),/0.5/);
 const fallback={...real,dailyScore:50,lifetimeScore:50,dailyDelta:0,dailyStatus:'INSUFFICIENT_PERIOD_DATA',resultConfidence:0};delete fallback.signedInterpretationContext;
 await fake(fallback);assert.equal(await view('score').textContent(),'50');assert.equal(await view('daily-delta').isVisible(),false);assert.match(await view('warnings').textContent(),/기본 궁합 점수/);assert.equal(await action('interpret').isVisible(),false);
 await fake({...real,categoryScores:{...real.categoryScores,ATTRACTION:null}});assert.match(await view('categories').locator('article').first().textContent(),/계산 정보 부족/);
 const absent={...real};delete absent.categoryScores;await fake(absent);assert.equal(await view('categories').isVisible(),false);assert.equal(await view('categories').locator('article').count(),0);
 await fake(real);await page.route('**/interpretation/generate',route=>route.fulfill({status:503,contentType:'application/json',body:'{"error":{"code":"INTERPRETATION_UNAVAILABLE"}}'}));await action('interpret').click();await view('interpretation-error').waitFor({state:'visible'});assert.equal(await view('result').isVisible(),true);await page.unroute('**/interpretation/generate');
 let release;const held=new Promise(resolve=>release=resolve);await page.route(dailyRoute,async route=>{await held;await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify(real)}).catch(()=>{});});
 await submit.click();assert.equal(await submit.isDisabled(),true);assert.match(await view('loading').textContent(),/선택한 날짜/);await app.locator('form').dispatchEvent('submit');await app.locator('[data-mode=compatibility]').click();release();await page.waitForTimeout(100);assert.equal(await view('result').isVisible(),false);await page.unroute(dailyRoute);await app.locator('[data-mode=daily]').click();
 for(const [status,code,copy] of [[400,'INVALID_REQUEST','입력 내용'],[422,'INVALID_REQUEST_SEMANTICS','시간대'],[422,'UNSUPPORTED_DATE','날짜 범위'],[404,'REFERENCE_NOT_FOUND','출생지역'],[500,'CALCULATION_FAILED','계산을 완료'],[503,'SERVICE_UNAVAILABLE','준비']]){
  await page.route(dailyRoute,route=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({error:{code}})}));await submit.click();await view('error').waitFor({state:'visible'});assert.ok((await view('error').textContent()).includes(copy));await page.unroute(dailyRoute);
 }
 await page.route(dailyRoute,route=>route.abort());await submit.click();await view('error').waitFor({state:'visible'});assert.match(await view('error').textContent(),/인터넷 연결을 확인/);await page.unroute(dailyRoute);
 await page.route(dailyRoute,route=>route.fulfill({status:429,contentType:'application/json',headers:{'Retry-After':'30'},body:'{"error":{"code":"RATE_LIMITED"}}'}));await submit.click();await view('error').waitFor({state:'visible'});assert.match(await view('error').textContent(),/30초/);const beforeRetry=requests.length;await submit.click();await page.waitForTimeout(100);assert.equal(requests.length,beforeRetry);await page.unroute(dailyRoute);
 assert.equal(page.url(),original);assert.deepEqual(await storage(),stored);assert.deepEqual(await context.cookies(),cookies);assert.equal(leak,false);assert.deepEqual(errors,[]);
 await page.reload();assert.equal(await app.locator('[data-mode=compatibility]').getAttribute('aria-pressed'),'true');assert.equal(await field('birthDate').first().inputValue(),'');await app.locator('[data-mode=daily]').click();assert.equal(await field('date').inputValue(),'');
 console.log(JSON.stringify({result:'FRONTEND_DAILY_BROWSER_PASS',realDaily:true,realInterpretationFallback:true,widths:[320,375,768,1280],controlledBoundaryCases:true,privacy:'PASS'}));
 }finally{await browser.close();}
})().catch(e=>{console.error('FRONTEND_DAILY_BROWSER_FAIL (payloads and tokens suppressed)');console.error((e.stack||'').split('\n').filter(line=>/^\s+at /.test(line)).join('\n'));process.exitCode=1;});
