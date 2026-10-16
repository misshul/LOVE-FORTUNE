'use strict';
const {chromium}=require('../.tools/frontend-tests/node_modules/playwright');
const assert=require('node:assert/strict');
(async()=>{
 const browser=await chromium.launch({headless:true});
 try{
  const journeyWidth=Number(process.env.FRONTEND_TEST_WIDTH||1280);
  assert.ok([320,375,768,1280].includes(journeyWidth));
  const context=await browser.newContext({viewport:{width:journeyWidth,height:900}}),page=await context.newPage();let calls=0;const errors=[];
  page.on('pageerror',e=>errors.push(e.message));page.on('request',r=>{if(r.url().includes('/love-fortune/v1/'))calls++;});
  await page.goto('http://localhost:8080/love-fortune-local-test/');
  const app=page.locator('.love-fortune-app'),view=n=>app.locator(`[data-view=${n}]`),field=n=>app.locator(`[data-field=${n}]`),submit=app.locator('[type=submit]');
  assert.match(await app.locator('header').textContent(),/기본 궁합과 선택한 날짜·기간/);
  assert.equal(await view('shared-inputs').locator('[data-person]').count(),2);
  assert.equal(await view('shared-inputs').locator('[data-field=relationshipType]').count(),1);
  assert.equal(await view('target-inputs').isVisible(),false);
  assert.equal(await app.locator('.lf-common-disclaimer').count(),1);
  assert.equal(await app.locator('.lf-switch-hint').count(),1);
  assert.equal(await app.locator('form .lf-note').textContent(),'입력 정보와 결과는 브라우저에 저장하지 않아요.');
  for(const [person,date] of [['personA','1990-01-01'],['personB','1992-02-02']]){await app.locator(`[data-person=${person}] [data-field=birthDate]`).fill(date);await app.locator(`[data-person=${person}] select`).selectOption('LOC000002');}
  const layout=[];
  if(process.argv.includes('--cancellation-only')){
   const fixture={overallScore:50,status:'BALANCED',coverage:1,resultConfidence:1,categories:Object.fromEntries(['ATTRACTION','EMOTION','COMMUNICATION','PASSION','STABILITY','HARMONY','SUPPORT','LONG_TERM'].map(k=>[k,{score:k==='COMMUNICATION'?null:50}])),signedInterpretationContext:'synthetic.context.only'};
   for(const routeName of ['compatibility/calculate','interpretation/generate']){
    if(routeName==='interpretation/generate'){
     await page.route('**/compatibility/calculate',r=>r.fulfill({status:200,contentType:'application/json',body:JSON.stringify(fixture)}));
     await submit.click();await view('result').waitFor({state:'visible'});
    }
    let release,arrived;const held=new Promise(r=>release=r),seen=new Promise(r=>arrived=r);
    await page.route('**/'+routeName,async r=>{arrived();await held;await r.fulfill({status:200,contentType:'application/json',body:JSON.stringify(routeName==='compatibility/calculate'?fixture:{summary:'synthetic late response',strengths:[],challenges:[],advice:'synthetic'})}).catch(()=>{});});
    await (routeName==='compatibility/calculate'?submit:app.locator('[data-action=interpret]')).click();await seen;
    await app.locator('[data-mode=daily]').click();release();await page.waitForTimeout(100);
    assert.equal(await view('result').isVisible(),false);assert.equal(await view('interpretation').isVisible(),false);assert.equal(await app.locator('[data-action=interpret]').isVisible(),false);
    await page.unroute('**/'+routeName);await app.locator('[data-mode=compatibility]').click();
   }
   assert.deepEqual(errors,[]);console.log(JSON.stringify({result:'COMPATIBILITY_INTERPRETATION_CANCELLATION_PASS',transport:'intercepted synthetic responses',realApiCalls:0}));return;
  }
  for(const mode of ['compatibility','daily','weekly','monthly','yearly']){
   await page.setViewportSize({width:journeyWidth,height:900});
   const before=calls;await app.locator(`[data-mode=${['weekly','monthly','yearly'].includes(mode)?'period':mode}]`).click();
   if(['weekly','monthly','yearly'].includes(mode))await app.locator(`[data-period=${mode}]`).click();
   assert.equal(calls,before);assert.equal(await field('birthDate').first().inputValue(),'1990-01-01');
   assert.equal(await view('target-inputs').isVisible(),mode!=='compatibility');
   if(mode==='daily')await field('date').fill('2026-10-14');
   if(mode==='weekly')await field('weekStartDate').fill('2026-10-12');
   if(mode==='monthly'){await field('monthlyYear').fill('2026');await field('month').selectOption('10');}
   if(mode==='yearly')await field('yearlyYear').fill('2026');
   const route=mode==='compatibility'?'compatibility/calculate':'fortune/'+mode;
   const wait=page.waitForResponse(r=>r.url().endsWith('/'+route));await submit.click();const response=await wait;assert.equal(response.status(),200);const result=await response.json();await view('result').waitFor({state:'visible'});
   assert.equal(calls,before+1);const score=mode==='compatibility'?result.overallScore:result[mode==='daily'?'dailyScore':mode+'Score'];assert.equal(await view('score').textContent(),String(score));
   assert.equal(await view('calculation-info').locator('summary').textContent(),'계산 정보');assert.equal(await view('details').locator('dt').first().textContent(),'계산 범위: ');assert.equal(await view('details').locator('dt').nth(1).textContent(),'계산 신뢰 지표: ');
   if(mode==='daily'){assert.match(await view('daily-delta').textContent(),/기본 궁합에 반영된 날짜 조정값/);assert.equal(await view('delta-explanation').textContent(),'점수를 0~100 범위로 제한하기 전 조정값이에요.');}
   if(['weekly','monthly','yearly'].includes(mode)){
    assert.equal(await view('comparison-heading').textContent(),'기본 궁합과 비교');assert.equal(await view('comparison').locator('[data-view=daily-delta]').count(),1);assert.equal(await view('comparison').locator('[data-view=trend]').count(),1);
    assert.equal(await view('delta-explanation').textContent(),'선택한 기간의 점수와 기본 궁합 점수를 비교한 값이에요.');assert.match(await view('period-result').textContent(),/실제 사건의 좋고 나쁨을 뜻하지 않아요/);
    assert.equal(await app.locator('.lf-period-rankings li small').count(),0);
    const arrays=mode==='yearly'?[result.bestMonths,result.cautionMonths]:[result.bestDays,result.cautionDays];
    const lists=app.locator('.lf-period-rankings section');for(let i=0;i<arrays.length;i++){assert.equal(await lists.nth(i).locator('li').count(),arrays[i].length);const info=lists.nth(i).locator('details');if(arrays[i].length){assert.equal(await info.evaluate(e=>e.open),false);await info.locator('summary').click();assert.deepEqual(await info.locator('dd').allTextContents(),arrays[i].map(x=>String(x.resultConfidence)));await info.locator('summary').click();}}
   }else assert.match(await view('categories').locator('article').nth(2).textContent(),/점수로 제공하지 않아요/);
   const cta=app.locator('[data-action=interpret]');assert.equal(await cta.textContent(),mode==='compatibility'?'궁합 해석 보기':'흐름 해석 보기');
   const iw=page.waitForResponse(r=>r.url().endsWith('/interpretation/generate'));await cta.click();const ir=await iw,body=await ir.json();assert.equal(ir.status(),200);await view('interpretation').waitFor({state:'visible'});
   const title={compatibility:'궁합 해석',daily:'선택한 날짜의 흐름 해석',weekly:'주간 흐름 해석',monthly:'월간 흐름 해석',yearly:'연간 흐름 해석'}[mode];assert.equal(await view('interpretation').locator('h3').textContent(),title);assert.equal(await view('interpretation').locator('p').first().textContent(),body.summary);
   await view('calculation-info').locator('summary').click();
   for(const width of [320,375,768,1280]){
    await page.setViewportSize({width,height:900});assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
    const metrics=await app.evaluate(el=>{const rect=s=>el.querySelector(s).getBoundingClientRect(),edit=rect('[data-action=reset]'),heading=rect('.lf-result-heading h3');return {pageHeight:document.documentElement.scrollHeight,ctaY:rect('[type=submit]').top+scrollY,editBelow:edit.top>=heading.bottom,editHeight:edit.height,checkHeight:rect('.lf-check').height};});
    if(width<=375){assert.equal(metrics.editBelow,true);assert.ok(metrics.editHeight>=44);assert.ok(metrics.checkHeight>=44);}layout.push({mode,width,...metrics});
    await page.screenshot({path:'.tools/frontend-polish-'+mode+'-'+width+'.png',fullPage:true});
   }
   await view('calculation-info').locator('summary').click();
  }
  assert.deepEqual(errors,[]);console.log(JSON.stringify({result:'FRONTEND_FINAL_POLISH_BROWSER_PASS',journeyWidth,calculationCalls:5,interpretationCalls:5,layout}));
 }finally{await browser.close();}
})().catch(e=>{console.error('POLISH_BROWSER_FAIL (payloads/tokens suppressed)');console.error((e.stack||'').split('\n').filter(x=>x.trim().startsWith('at ')).join('\n'));process.exitCode=1;});
