'use strict';
// Real local REST acceptance, then controlled presentation/transport boundaries.
const {chromium}=require('../.tools/frontend-tests/node_modules/playwright');
const assert=require('node:assert/strict');
const reference=require('../wp-content/plugins/love-fortune-core/config/references/timezones-v1.json');
(async()=>{
 const browser=await chromium.launch({headless:true});
 try{
 const context=await browser.newContext(),page=await context.newPage(),requests=[],errors=[];let leak=false;
 page.on('pageerror',e=>errors.push(e.message));page.on('console',m=>{if(/1990-01-01|1992-02-02|signedContext/.test(m.text()))leak=true;});
 page.on('request',r=>{if(r.url().includes('/love-fortune/v1/'))requests.push({url:r.url(),body:r.postDataJSON()});});
 await page.goto('http://localhost:8080/love-fortune-local-test/');
 const app=page.locator('.love-fortune-app'),field=n=>app.locator(`[data-field=${n}]`),view=n=>app.locator(`[data-view=${n}]`),action=n=>app.locator(`[data-action=${n}]`),submit=app.locator('[type=submit]'),sub=n=>app.locator(`[data-period=${n}]`);
 const storage=()=>page.evaluate(()=>({local:{...localStorage},session:Object.fromEntries(Object.entries(sessionStorage).filter(([k])=>k!=='wpEmojiSettingsSupports'))}));
 const url=page.url(),stored=await storage(),cookies=await context.cookies();
 assert.equal(await app.locator('[data-mode=compatibility]').getAttribute('aria-pressed'),'true');
 for(const [person,date] of [['personA','1990-01-01'],['personB','1992-02-02']]){await app.locator(`[data-person=${person}] [data-field=birthDate]`).fill(date);await app.locator(`[data-person=${person}] select`).selectOption('LOC000002');}
 await field('relationshipType').selectOption('FRIEND');
 await app.locator('[data-mode=daily]').click();await field('date').fill('2026-10-14');await field('dailyTimezone').selectOption('America/New_York');
 await app.locator('[data-mode=period]').focus();await page.keyboard.press('Enter');
 assert.equal(requests.length,0);assert.equal(await sub('weekly').getAttribute('aria-pressed'),'true');assert.equal(await field('weekStartDate').inputValue(),'');
 assert.deepEqual(await field('periodTimezone').locator('option').evaluateAll(xs=>xs.map(x=>x.value)),reference.zones.map(x=>x.timezoneId));assert.equal(await field('periodTimezone').inputValue(),'Asia/Tokyo');
 for(const value of ['2026-10-13','2026-10-14','2026-10-15','2026-10-16','2026-10-17','2026-10-18','2099-12-28','1899-12-25']){await field('weekStartDate').fill(value);assert.equal(await field('weekStartDate').evaluate(e=>e.checkValidity()),false);assert.equal(await field('weekStartDate').inputValue(),value);}
 for(const value of ['1900-01-01','2099-12-21','2026-12-28']){await field('weekStartDate').fill(value);assert.equal(await field('weekStartDate').evaluate(e=>e.checkValidity()),true);}
 await field('weekStartDate').fill('2026-10-12');
 await app.locator('[data-person=personA] [data-field=unknown]').uncheck();await app.locator('[data-person=personA] [data-field=birthTime]').fill('10:30');
 const real={};
 for(const type of ['weekly','monthly','yearly']){
  await sub(type).click();
  if(type!=='weekly'){const year=field(type==='monthly'?'monthlyYear':'yearlyYear');assert.equal(await year.inputValue(),'');for(const value of ['','1899','2100','2026.5']){await year.fill(value);assert.equal(await year.evaluate(e=>e.checkValidity()),false);}await year.fill('2026');if(type==='monthly'){assert.equal(await field('month').inputValue(),'');assert.equal(await field('month').locator('option').count(),13);await field('month').selectOption('10');}}
  const before=requests.length,wait=page.waitForResponse(r=>r.url().endsWith('/fortune/'+type));await submit.click();const response=await wait;assert.equal(response.status(),200);real[type]=await response.json();await view('result').waitFor({state:'visible'});
  assert.equal(requests.length,before+1);const body=requests.at(-1).body;
  assert.deepEqual(Object.keys(body).sort(),['locale','personA','personB','relationshipType','targetTimezone',...(type==='weekly'?['weekStartDate']:type==='monthly'?['year','month']:['year'])].sort());
  assert.equal(body.personA.birthTime,'10:30');assert.equal(body.personB.birthTime,null);assert.equal(body.relationshipType,'FRIEND');assert.equal(body.targetTimezone,'Asia/Tokyo');if(type!=='weekly')assert.equal(body.year,2026);if(type==='monthly')assert.equal(body.month,10);if(type==='weekly')assert.equal(body.weekStartDate,'2026-10-12');
  assert.equal(await view('score').textContent(),String(real[type][type+'Score']));assert.equal(await view('categories').isVisible(),false);assert.equal(await view('categories').locator('article').count(),0);
  assert.equal(await view('result').evaluate(e=>e===document.activeElement),true);assert.equal(await app.locator('[data-view=calculation-info]').evaluate(e=>e.open),false);
  const iw=page.waitForResponse(r=>r.url().endsWith('/interpretation/generate'));await action('interpret').click();const ir=await iw;assert.equal(ir.status(),200);assert.equal((await ir.json()).meta.interpretationMode,'FALLBACK');await view('interpretation').waitFor({state:'visible'});assert.deepEqual(Object.keys(requests.at(-1).body).sort(),['locale','signedContext']);assert.equal((await page.content()).includes(real[type].signedInterpretationContext),false);
 }
 assert.equal(requests.length,6);
 for(const width of [320,375,768,1280]){await page.setViewportSize({width,height:900});assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));assert.ok(await app.evaluate(e=>e.scrollWidth<=e.clientWidth));await page.screenshot({path:'.tools/frontend-v1-3-'+width+'.png',fullPage:true});}
 const fake=async(type,result)=>{await sub(type).click();const route='**/fortune/'+type;await page.route(route,r=>r.fulfill({status:200,contentType:'application/json',body:JSON.stringify(result)}));await submit.click();await view('result').waitFor({state:'visible'});await page.unroute(route);};
 for(const type of ['weekly','monthly','yearly']){
  const r=real[type],yearly=type==='yearly',best=yearly?'bestMonths':'bestDays',caution=yearly?'cautionMonths':'cautionDays',vol=yearly?'yearlyVolatility':'volatility';
  const nullResult={...r,[type+'Score']:null,periodDelta:null,status:'INSUFFICIENT_DATA',trendStatus:'INSUFFICIENT_DATA',trendDirection:'UNKNOWN',coverage:0.25,resultConfidence:0,[vol]:null,[best]:[],[caution]:[],slope:null};delete nullResult.signedInterpretationContext;
  await fake(type,nullResult);assert.match(await view('score').textContent(),/정보가 부족/);assert.equal(await view('error').isVisible(),false);assert.equal(await action('interpret').isVisible(),false);assert.equal(await view('score-unit').isVisible(),false);assert.equal(await view('period-result').locator('li').count(),0);
  const items=[{date:yearly?'2026-10-01':'2026-10-16',score:80,resultConfidence:0.9},{date:yearly?'2026-02-01':'2026-10-12',score:70,resultConfidence:0.8}];
  for(const [delta,trend,direction] of [[3,'STABLE','STABLE'],[8,'NOTICEABLE','UP'],[-8,'SIGNIFICANT','DOWN'],[0,'STABLE','STABLE']]){
   await fake(type,{...r,[type+'Score']:80,periodDelta:delta,trendStatus:trend,trendDirection:direction,coverage:0.25,resultConfidence:0.8,[vol]:0,slope:null,[best]:items,[caution]:[]});
   assert.equal(await view('daily-delta').textContent(),'기본 궁합 대비 차이: '+(delta>0?'+':'')+delta);assert.match(await view('details').textContent(),/0.25.*0.8.*지표: 0/);assert.equal(await view('period-result').locator('li').first().locator('span').textContent(),'80점');
   if(yearly)assert.equal(await view('period-result').locator('li').first().locator('strong').textContent(),'2026년 10월');
   assert.match(await view('trend').textContent(),trend==='STABLE'?/큰 차이가 없는/:trend==='NOTICEABLE'?/차이가 있는/:/차이가 큰/);
  }
 }
 for(const [status,label] of [['CAUTION','배려가 필요한 관계'],['BALANCED','균형을 알아가는 관계'],['GOOD','좋은 조화'],['VERY_GOOD','매우 좋은 조화'],['EXCELLENT','뛰어난 조화']]){await fake('weekly',{...real.weekly,status,slope:0.1234});assert.equal(await view('status').textContent(),label);assert.match(await view('details').textContent(),/기울기: 0.1234/);}
 const absent={...real.weekly};delete absent.slope;await fake('weekly',absent);assert.doesNotMatch(await view('details').textContent(),/기울기/);
 await page.route('**/interpretation/generate',r=>r.fulfill({status:503,contentType:'application/json',body:'{"error":{"code":"INTERPRETATION_UNAVAILABLE"}}'}));await action('interpret').click();await view('interpretation-error').waitFor({state:'visible'});assert.equal(await view('result').isVisible(),true);await page.unroute('**/interpretation/generate');
 let release;const held=new Promise(r=>release=r);await page.route('**/fortune/weekly',async r=>{await held;await r.fulfill({status:200,contentType:'application/json',body:JSON.stringify(real.weekly)}).catch(()=>{});});await submit.click();assert.equal(await submit.isDisabled(),true);await sub('monthly').click();release();await page.waitForTimeout(100);assert.equal(await view('result').isVisible(),false);await page.unroute('**/fortune/weekly');
 await sub('weekly').click();
 for(const [code,copy] of [['INVALID_REQUEST','입력 내용'],['INVALID_REQUEST_SEMANTICS','선택한 기간'],['UNSUPPORTED_DATE','기간 범위'],['REFERENCE_NOT_FOUND','출생지역'],['CALCULATION_FAILED','계산을 완료'],['SERVICE_UNAVAILABLE','준비']]){await page.route('**/fortune/weekly',r=>r.fulfill({status:422,contentType:'application/json',body:JSON.stringify({error:{code}})}));await submit.click();await view('error').waitFor({state:'visible'});assert.ok((await view('error').textContent()).includes(copy));await page.unroute('**/fortune/weekly');}
 await page.route('**/fortune/weekly',r=>r.abort());await submit.click();await view('error').waitFor({state:'visible'});assert.match(await view('error').textContent(),/연결/);await page.unroute('**/fortune/weekly');
 const malformed={...real.weekly};delete malformed.bestDays;await page.route('**/fortune/weekly',r=>r.fulfill({status:200,contentType:'application/json',body:JSON.stringify(malformed)}));await submit.click();await view('error').waitFor({state:'visible'});assert.equal(await view('result').isVisible(),false);await page.unroute('**/fortune/weekly');
 await fake('weekly',real.weekly);const editCount=requests.length;await action('reset').click();assert.equal(await field('weekStartDate').inputValue(),'2026-10-12');assert.equal(await view('result').isVisible(),false);await action('interpret').dispatchEvent('click');assert.equal(requests.length,editCount);
 await fake('weekly',real.weekly);await page.route('**/interpretation/generate',r=>r.fulfill({status:200,contentType:'application/json',body:JSON.stringify({summary:'<img src=x onerror=alert(1)>',strengths:[],challenges:[],advice:'<script>alert(1)</script>'})}));await action('interpret').click();await view('interpretation').waitFor({state:'visible'});assert.equal(await view('interpretation').locator('img,script').count(),0);assert.match(await view('interpretation').textContent(),/<img/);await page.unroute('**/interpretation/generate');
 await page.route('**/fortune/weekly',r=>r.fulfill({status:429,headers:{'Retry-After':'60'},contentType:'application/json',body:'{"error":{"code":"RATE_LIMITED"}}'}));await submit.click();await view('error').waitFor({state:'visible'});await page.unroute('**/fortune/weekly');const count=requests.length;
 await fake('monthly',real.monthly);await fake('yearly',real.yearly);assert.equal(requests.length,count+2);await sub('weekly').click();await submit.click();await view('error').waitFor({state:'visible'});assert.equal(requests.length,count+2);
 await field('periodTimezone').selectOption('Europe/London');await sub('monthly').click();assert.equal(await field('periodTimezone').inputValue(),'Europe/London');
 await app.locator('[data-mode=daily]').click();assert.equal(await field('dailyTimezone').inputValue(),'America/New_York');assert.equal(await field('date').inputValue(),'2026-10-14');await app.locator('[data-mode=compatibility]').click();assert.equal(requests.length,count+2);
 assert.equal(page.url(),url);assert.deepEqual(await storage(),stored);assert.deepEqual(await context.cookies(),cookies);assert.equal(leak,false);assert.deepEqual(errors,[]);
 assert.equal(await app.locator('input,select').evaluateAll(xs=>xs.every(x=>x.labels.length)),true);
 await page.reload();assert.equal(await app.locator('[data-mode=compatibility]').getAttribute('aria-pressed'),'true');assert.equal(await field('birthDate').first().inputValue(),'');
 // Isolated pages give each route its own fresh cooldown for the complete matrix.
 for(const blocked of ['weekly','monthly','yearly']){
  await page.reload();
  for(const [person,date] of [['personA','1990-01-01'],['personB','1992-02-02']]){await app.locator(`[data-person=${person}] [data-field=birthDate]`).fill(date);await app.locator(`[data-person=${person}] select`).selectOption('LOC000002');}
  await app.locator('[data-mode=period]').click();await field('weekStartDate').fill('2026-10-12');
  await sub('monthly').click();await field('monthlyYear').fill('2026');await field('month').selectOption('10');await sub('yearly').click();await field('yearlyYear').fill('2026');await sub(blocked).click();
  const route='**/fortune/'+blocked;let releasePending,seenPending;const pending=new Promise(r=>releasePending=r),seen=new Promise(r=>seenPending=r);
  await page.route(route,async r=>{seenPending();await pending;await r.fulfill({status:200,contentType:'application/json',body:JSON.stringify(real[blocked])}).catch(()=>{});});
  await submit.click();await seen;assert.equal(await submit.isDisabled(),true);assert.equal(await view('loading').textContent(),({weekly:'주간',monthly:'월간',yearly:'연간'})[blocked]+' 흐름을 계산하고 있어요.');
  if(blocked==='weekly')await sub('monthly').click();else await app.locator('[data-mode='+ (blocked==='monthly'?'daily':'compatibility')+']').click();
  releasePending();await page.waitForTimeout(100);assert.equal(await view('result').isVisible(),false);await page.unroute(route);await app.locator('[data-mode=period]').click();await sub(blocked).click();
  // Input edit cancels the old generation; the replacement result wins.
  let releaseOld,seenOld;const old=new Promise(r=>releaseOld=r),arrived=new Promise(r=>seenOld=r);let n=0;
  await page.route(route,async r=>{const sequence=++n;if(sequence===1){seenOld();await old;}await r.fulfill({status:200,contentType:'application/json',body:JSON.stringify({...real[blocked],[blocked+'Score']:sequence===1?10:77})}).catch(()=>{});});
  await submit.click();await arrived;await field('relationshipType').selectOption('FRIEND');await submit.click();await view('result').waitFor({state:'visible'});releaseOld();await page.waitForTimeout(100);assert.equal(await view('score').textContent(),'77');await page.unroute(route);
  // Delay interpretation to observe its loading copy before successful safe rendering.
  await page.route('**/interpretation/generate',async r=>{await new Promise(resolve=>setTimeout(resolve,200));await r.fulfill({status:200,contentType:'application/json',body:JSON.stringify({summary:'참고 해석',strengths:[],challenges:[],advice:'서로 대화해보세요.'})});});
  await action('interpret').click();assert.equal(await view('loading').textContent(),'흐름 해석을 준비하고 있어요.');await view('interpretation').waitFor({state:'visible'});await page.unroute('**/interpretation/generate');
  await page.route(route,r=>r.fulfill({status:429,headers:{'Retry-After':'60'},contentType:'application/json',body:'{"error":{"code":"RATE_LIMITED"}}'}));await submit.click();await view('error').waitFor({state:'visible'});await page.unroute(route);
  const before=requests.length;for(const other of ['weekly','monthly','yearly'].filter(x=>x!==blocked))await fake(other,real[other]);assert.equal(requests.length,before+2);
  await sub(blocked).click();await submit.click();await view('error').waitFor({state:'visible'});assert.equal(requests.length,before+2);
 }
 assert.deepEqual(errors,[]);assert.equal(leak,false);assert.equal(page.url(),url);assert.deepEqual(await storage(),stored);assert.deepEqual(await context.cookies(),cookies);
 console.log(JSON.stringify({result:'PASS',realPeriodCalls:3,realFallbacks:3,widths:[320,375,768,1280],privacy:'PASS',boundaries:'PASS',cancellation:'PASS / all routes and replacement',routeRateLimits:'PASS / 3 blocked routes, 6 unblocked peers'}));
 }finally{await browser.close();}
})().catch(e=>{console.error('PERIOD_BROWSER_FAIL (payloads/tokens suppressed)');console.error((e.stack||'').split('\n').filter(x=>x.trim().startsWith('at ')).join('\n'));process.exitCode=1;});
