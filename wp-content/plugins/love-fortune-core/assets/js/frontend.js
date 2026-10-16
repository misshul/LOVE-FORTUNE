(() => {
'use strict';
const categories = [['ATTRACTION','끌림'],['EMOTION','감정'],['COMMUNICATION','소통'],['PASSION','열정'],['STABILITY','안정감'],['HARMONY','조화'],['SUPPORT','서로의 지지'],['LONG_TERM','장기적 관계']];
const statuses = {CAUTION:'배려가 필요한 관계',BALANCED:'균형을 알아가는 관계',GOOD:'좋은 조화',VERY_GOOD:'매우 좋은 조화',EXCELLENT:'뛰어난 조화',INSUFFICIENT_DATA:'아직 결과를 내기 어려워요'};
const dailyStatuses={VERY_LOW:'신중히 살펴볼 흐름',LOW:'여유를 두면 좋은 흐름',STABLE:'차분한 흐름',GOOD:'긍정적인 흐름',VERY_GOOD:'한층 긍정적인 흐름',INSUFFICIENT_DATA:'계산 정보 부족',INSUFFICIENT_PERIOD_DATA:'날짜별 정보 부족'};
const periodLabels={weekly:'주간',monthly:'월간',yearly:'연간'};
const trends={STABLE:'기본 궁합과 큰 차이가 없는 흐름',NOTICEABLE:'기본 궁합과 차이가 있는 흐름',SIGNIFICANT:'기본 궁합과 차이가 큰 흐름',INSUFFICIENT_DATA:'비교할 정보가 부족해요.'};
const directions={UP:'기본 궁합보다 높은 점수',DOWN:'기본 궁합보다 낮은 점수',STABLE:'기본 궁합과 비슷한 점수',UNKNOWN:'비교 방향 정보 없음'};
const errorText = (status,code,retry) => {
 if (code==='INTERPRETATION_CONTEXT_EXPIRED') return '해석 요청 시간이 지났습니다. 궁합을 다시 계산해주세요.';
 if (['INVALID_INTERPRETATION_CONTEXT','INVALID_INTERPRETATION_PURPOSE','INTERPRETATION_LOCALE_MISMATCH'].includes(code)) return '해석 정보를 확인할 수 없습니다. 궁합을 다시 계산해주세요.';
 const map={400:'입력 내용을 다시 확인해주세요.',404:'출생 지역 정보를 확인해주세요.',413:'요청을 처리하지 못했어요. 페이지를 새로 열어 다시 시도해주세요.',415:'요청을 처리하지 못했어요. 페이지를 새로 열어 다시 시도해주세요.',422:'입력한 생년월일 또는 지역 정보를 확인해주세요.',429:'요청이 너무 많습니다. 잠시 후 다시 시도해주세요.',503:'현재 결과를 생성할 수 없습니다. 잠시 후 다시 시도해주세요.'};
 return (map[status]||'결과를 가져오지 못했습니다. 잠시 후 다시 시도해주세요.')+(status===429&&/^\d{1,6}$/.test(retry||'')?' 약 '+retry+'초 후에 시도해주세요.':'');
};
for(const app of document.querySelectorAll('.love-fortune-app')) {
 const q=s=>app.querySelector(s), view=n=>q('[data-view="'+n+'"]'),form=q('form'),submit=q('[type="submit"]'),interpret=q('[data-action="interpret"]');
 const edit=q('[data-action="reset"]'),privacyDescription=form.getAttribute('aria-describedby');
 let token=null,controller=null,generation=0,busy=false,mode='compatibility',period='weekly';
 const retries=new Map();
 const periodBox=document.createElement('div');periodBox.dataset.view='period-result';q('[data-action="interpret"]').before(periodBox);
 const api=new URL(app.dataset.api,location.href);
 if(api.origin!==location.origin) {view('error').textContent='같은 사이트의 API에 연결할 수 없습니다.';view('error').hidden=false;continue;}
 function endpoint(path){const u=new URL(api);if(u.searchParams.has('rest_route'))u.searchParams.set('rest_route',u.searchParams.get('rest_route').replace(/\/$/,'')+'/'+path);else u.pathname=u.pathname.replace(/\/$/,'')+'/'+path;return u.href;}
 function state(working,message=''){busy=working;submit.disabled=working;interpret.disabled=working;form.setAttribute('aria-busy',String(working));view('loading').textContent=message;}
 function clear(){periodBox.replaceChildren();view('interpretation-error').hidden=true;generation++;controller?.abort();controller=null;token=null;state(false);for(const n of ['result','interpretation','error'])view(n).hidden=true;for(const n of ['score','status','categories','details','warnings','daily-reference','daily-delta','interpretation','interpretation-error'])view(n).replaceChildren();interpret.hidden=true;edit.hidden=true;q('details').open=false;form.setAttribute('aria-describedby',privacyDescription);}
 function fail(message){view('error').textContent=message;view('error').hidden=false;form.setAttribute('aria-describedby',view('error').id);view('error').focus();}
 function text(parent,tag,value){const el=document.createElement(tag);el.textContent=value;parent.append(el);return el;}
 async function post(path,payload){if(mode!=='compatibility'&&Date.now()<(retries.get(path)||0))throw new Error('잠시 후 다시 시도해주세요. 요청 대기 시간이 남아 있어요.');controller=new AbortController();const currentController=controller;const timer=setTimeout(()=>currentController.abort(),60000);try{const response=await fetch(endpoint(path),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload),credentials:'omit',cache:'no-store',redirect:'error',signal:currentController.signal});let data;try{data=await response.json();}catch{throw new Error('결과를 불러오지 못했어요. 잠시 후 다시 시도해주세요.');}if(!response.ok){
 const retry=response.headers.get('Retry-After');
 if(response.status===429){const seconds=/^\d+$/.test(retry||'')?Number(retry):Math.max(0,(Date.parse(retry)-Date.now())/1000);if(Number.isFinite(seconds))retries.set(path,Date.now()+seconds*1000);}
 const code=data?.error?.code||data?.code;
 const dailyErrors={INVALID_REQUEST:'입력 내용을 확인해주세요.',INVALID_REQUEST_SEMANTICS:'날짜와 시간대 등 입력값을 확인해주세요.',UNSUPPORTED_DATE:'지원하는 날짜 범위를 확인해주세요.',REFERENCE_NOT_FOUND:'목록에서 출생지역을 다시 선택해주세요.',CALCULATION_FAILED:'계산을 완료하지 못했어요. 잠시 후 다시 시도해주세요.',SERVICE_UNAVAILABLE:'결과를 제공할 준비가 되지 않았어요. 잠시 후 다시 시도해주세요.'};
 let message=mode==='daily'&&dailyErrors[code]?dailyErrors[code]:errorText(response.status,code,retry);
 if(mode==='period')message=({...dailyErrors,INVALID_REQUEST_SEMANTICS:'선택한 기간과 시간대 등 입력값을 확인해주세요.',UNSUPPORTED_DATE:'지원하는 기간 범위를 확인해주세요.',SERVICE_UNAVAILABLE:'결과를 제공할 준비가 되지 않았어요.'})[code]||message.replace('궁합을 다시 계산','기간별 흐름을 다시 계산');
 if(mode==='daily')message=message.replace('궁합을 다시 계산','날짜별 흐름을 다시 계산');
 throw new Error(message);
 }return data;}finally{clearTimeout(timer);}}

 function interpretationFail(message){const box=view('interpretation-error');box.textContent=message;box.hidden=false;box.focus();}
 function renderDaily(r){
  const numeric=v=>typeof v==='number'&&Number.isFinite(v);
  if(!Object.hasOwn(dailyStatuses,r.dailyStatus)||!(r.dailyScore===null||numeric(r.dailyScore))||!numeric(r.dailyDelta)||!numeric(r.coverage)||!numeric(r.resultConfidence)||typeof r.date!=='string'||typeof r.targetTimezone!=='string')throw new Error('결과를 표시하지 못했어요. 잠시 후 다시 시도해주세요.');
  view('daily-reference').textContent=r.date+' · '+r.targetTimezone;view('daily-reference').hidden=false;
  view('score').textContent=r.dailyScore===null?'점수를 계산할 정보가 부족해요.':String(r.dailyScore);
  view('score').classList.toggle('lf-unavailable',r.dailyScore===null);view('score-unit').hidden=r.dailyScore===null;
  view('status').textContent=dailyStatuses[r.dailyStatus];
  const insufficient=['INSUFFICIENT_DATA','INSUFFICIENT_PERIOD_DATA'].includes(r.dailyStatus);
  view('daily-delta').hidden=insufficient;view('daily-delta').textContent='기본 궁합 대비 조정값 '+(r.dailyDelta>0?'+':'')+String(r.dailyDelta);
  view('warnings').textContent=r.dailyStatus==='INSUFFICIENT_PERIOD_DATA'?'날짜별 정보가 부족해 기본 궁합 점수를 표시해요.':Array.isArray(r.warnings)&&r.warnings.length?'일부 정보가 제한되어 계산에 반영되지 않았을 수 있어요.':'';
  const box=view('categories');box.replaceChildren();box.hidden=!r.categoryScores;
  if(r.categoryScores)for(const [key,label] of categories){
   const card=text(box,'article','');text(card,'h4',label);const body=text(card,'div','');const score=r.categoryScores[key];
   const value=text(body,'p',score==null?(key==='COMMUNICATION'?'점수 미제공':'계산 정보 부족'):String(score)+'점');value.className=score==null?'lf-category-unavailable':'lf-category-value';
   if(key==='LONG_TERM')text(body,'p','기본 궁합 점수이며, 날짜별 변동은 적용하지 않아요.').className='lf-hint';
  }
  view('details').textContent='계산 범위: '+String(r.coverage)+' · 계산 신뢰 지표: '+String(r.resultConfidence)+' (0–1). 예측 정확도나 사건 발생 확률을 의미하지 않아요.'+(numeric(r.lifetimeScore)?' 기본 궁합 점수: '+String(r.lifetimeScore):'')+' 조정값은 점수의 0~100 범위 제한을 적용하기 전 값이에요.';
  token=!insufficient&&typeof r.signedInterpretationContext==='string'&&r.signedInterpretationContext.split('.').length===3?r.signedInterpretationContext:null;
  interpret.hidden=!token;edit.hidden=false;view('result').hidden=false;view('result').focus();
 }
 function selectMode(next){
  clear();mode=next;const daily=mode==='daily',isPeriod=mode==='period';
  for(const button of app.querySelectorAll('[data-mode]'))button.setAttribute('aria-pressed',String(button.dataset.mode===mode));
  view('daily-intro').hidden=!daily;view('daily-inputs').hidden=!daily;view('compatibility-timezone').hidden=daily||isPeriod;
  view('period-intro').hidden=!isPeriod;view('period-inputs').hidden=!isPeriod;
  for(const group of app.querySelectorAll('[data-period-input]')){group.hidden=group.dataset.periodInput!==period;for(const input of group.querySelectorAll('input,select'))input.disabled=!isPeriod||group.hidden;}
  q('[data-field="periodTimezone"]').disabled=!isPeriod;
  for(const button of app.querySelectorAll('[data-period]'))button.setAttribute('aria-pressed',String(button.dataset.period===period));
  q('[data-field="date"]').disabled=!daily;q('[data-field="dailyTimezone"]').disabled=!daily;q('[data-field="targetTimezone"]').disabled=daily;
  submit.textContent=daily?'선택한 날짜의 흐름 보기':'궁합 보기';interpret.textContent=daily?'흐름 해석 보기':'해석 보기';
  q('.lf-result-heading h3').textContent=daily?'선택한 날짜의 관계 흐름':'궁합 결과';view('result').setAttribute('aria-label',daily?'날짜별 흐름 결과':'궁합 결과');
  q('summary').textContent=daily?'계산 정보':'상세 정보';view('daily-reference').hidden=true;view('daily-delta').hidden=true;view('categories').hidden=false;
  if(isPeriod){q('[data-field="targetTimezone"]').disabled=true;submit.textContent=periodLabels[period]+' 흐름 보기';interpret.textContent='흐름 해석 보기';q('.lf-result-heading h3').textContent=periodLabels[period]+' 관계 흐름';view('result').setAttribute('aria-label',periodLabels[period]+' 흐름 결과');q('summary').textContent='계산 정보';view('categories').hidden=true;}
 }
 for(const button of app.querySelectorAll('[data-period]'))button.addEventListener('click',()=>{if(period!==button.dataset.period){period=button.dataset.period;selectMode('period');}});
 const week=q('[data-field="weekStartDate"]');
 week.addEventListener('input',()=>{const d=week.valueAsDate;week.setCustomValidity(d&&d.getUTCDay()!==1?'주 시작 날짜는 월요일로 선택해주세요.':'');});
 function dateLabel(value,monthOnly=false){const parts=/^(\d{4})-(\d{2})-(\d{2})$/.exec(value);if(!parts)throw new Error('기간 날짜 정보를 확인할 수 없어요.');return parts[1]+'년 '+Number(parts[2])+'월'+(monthOnly?'':' '+Number(parts[3])+'일');}
 function renderPeriod(r){
  const numeric=v=>typeof v==='number'&&Number.isFinite(v),nullable=v=>v===null||numeric(v),yearly=period==='yearly';
  const best=r[yearly?'bestMonths':'bestDays'],caution=r[yearly?'cautionMonths':'cautionDays'],vol=r[yearly?'yearlyVolatility':'volatility'];
  if(!r.period||r.period.type!==({weekly:'WEEK',monthly:'MONTH',yearly:'YEAR'})[period]||!nullable(r[period+'Score'])||!nullable(r.periodDelta)||!nullable(vol)||!numeric(r.coverage)||!numeric(r.resultConfidence)||!Object.hasOwn(statuses,r.status)||!Object.hasOwn(trends,r.trendStatus)||!Object.hasOwn(directions,r.trendDirection)||!Array.isArray(best)||!Array.isArray(caution))throw new Error('결과를 표시하지 못했어요. 잠시 후 다시 시도해주세요.');
  const identity=period==='weekly'?dateLabel(r.period.startDate)+' ~ '+dateLabel(r.period.endDate):String(r.period.year)+'년'+(yearly?'':' '+String(r.period.month)+'월');
  view('daily-reference').textContent=identity+' · '+r.targetTimezone;view('daily-reference').hidden=false;
  view('score').textContent=r[period+'Score']===null?'계산할 정보가 부족해요':String(r[period+'Score']);view('score').classList.toggle('lf-unavailable',r[period+'Score']===null);view('score-unit').hidden=r[period+'Score']===null;
  view('status').textContent=r.status==='INSUFFICIENT_DATA'?'계산 정보 부족':statuses[r.status];
  view('daily-delta').textContent='기본 궁합 대비 차이: '+(r.periodDelta===null?'비교할 정보가 부족해요':(r.periodDelta>0?'+':'')+String(r.periodDelta));view('daily-delta').hidden=false;
  periodBox.replaceChildren();text(periodBox,'p',trends[r.trendStatus]);text(periodBox,'p',directions[r.trendDirection]);
  const ranks=text(periodBox,'div','');ranks.className='lf-period-rankings';
  for(const [items,label] of [[best,'상대적으로 높은'],[caution,'상대적으로 낮은']]){const group=text(ranks,'section','');text(group,'h4',label+' 점수의 '+(yearly?'월':'날짜'));if(!items.length){text(group,'p','해당 기간에 표시할 정보가 없어요.');continue;}const list=text(group,'ol','');for(const item of items){if(!numeric(item.score)||!numeric(item.resultConfidence))throw new Error('순위 정보를 확인할 수 없어요.');const li=text(list,'li','');text(li,'strong',dateLabel(item.date,yearly));text(li,'span',String(item.score)+'점');text(li,'small','계산 신뢰 지표: '+String(item.resultConfidence));}}
  view('details').textContent='계산 범위: '+r.coverage+' · 계산 신뢰 지표: '+r.resultConfidence+' (0–1). 예측 정확도나 사건 발생 확률을 의미하지 않아요. 기간 내 점수 차이 지표: '+(vol===null?'계산 정보 부족':String(vol))+(period==='weekly'&&numeric(r.slope)?' · 기간 내 점수 변화 기울기: '+String(r.slope):'');
  view('categories').replaceChildren();view('categories').hidden=true;
  token=r[period+'Score']!==null&&typeof r.signedInterpretationContext==='string'&&r.signedInterpretationContext.split('.').length===3?r.signedInterpretationContext:null;
  interpret.hidden=!token;edit.hidden=false;view('result').hidden=false;view('result').focus();
 }
 for(const button of app.querySelectorAll('[data-mode]'))button.addEventListener('click',()=>{if(button.dataset.mode!==mode)selectMode(button.dataset.mode);});
 function person(fieldset){const field=n=>fieldset.querySelector('[data-field="'+n+'"]');return {birthDate:field('birthDate').value,birthTime:field('unknown').checked?null:field('birthTime').value,birthLocationId:field('birthLocationId').value};}
 for(const fs of form.querySelectorAll('[data-person]'))fs.querySelector('[data-field="unknown"]').addEventListener('change',()=>{const unknown=fs.querySelector('[data-field="unknown"]').checked,time=fs.querySelector('[data-field="birthTime"]');time.disabled=unknown;time.required=!unknown;if(unknown)time.value='';});
 form.addEventListener('input',()=>clear());form.addEventListener('change',()=>clear());
 form.addEventListener('submit',async event=>{event.preventDefault();if(busy||!form.reportValidity())return;clear();const run=generation;state(true,mode==='period'?periodLabels[period]+' \uD750\uB984\uC744 \uACC4\uC0B0\uD558\uACE0 \uC788\uC5B4\uC694.':mode==='daily'?'선택한 날짜의 흐름을 계산하고 있어요.':'궁합을 계산하고 있어요.');try{
 const payload={personA:person(q('[data-person="personA"]')),personB:person(q('[data-person="personB"]')),relationshipType:q('[data-field="relationshipType"]').value,targetTimezone:q(mode==='period'?'[data-field="periodTimezone"]':mode==='daily'?'[data-field="dailyTimezone"]':'[data-field="targetTimezone"]').value,locale:'ko-KR'};
 if(mode==='daily')payload.date=q('[data-field="date"]').value;
 if(mode==='period'){if(period==='weekly')payload.weekStartDate=week.value;else {payload.year=Number(q('[data-field="'+(period==='monthly'?'monthlyYear':'yearlyYear')+'"]').value);if(period==='monthly')payload.month=Number(q('[data-field="month"]').value);}}
 const r=await post(mode==='period'?'fortune/'+period:mode==='daily'?'fortune/daily':'compatibility/calculate',payload);if(run!==generation)return;
 if(mode==='period'){renderPeriod(r);return;}
 if(mode==='daily'){renderDaily(r);return;}
 if(!r.categories||!Object.hasOwn(statuses,r.status)||!(r.overallScore===null||typeof r.overallScore==='number'))throw new Error('결과를 표시하지 못했어요. 잠시 후 다시 시도해주세요.');
 view('score').textContent=r.overallScore===null?'계산 정보 없음':String(r.overallScore);view('score').classList.toggle('lf-unavailable',r.overallScore===null);view('score-unit').hidden=r.overallScore===null;view('status').textContent=statuses[r.status];view('categories').replaceChildren();
 for(const [key,label] of categories){const card=text(view('categories'),'article','');text(card,'h4',label);const score=r.categories[key]?.score;const value=text(card,'p',score==null?(key==='COMMUNICATION'&&score===null?'점수 제공 안 함':'계산 정보 없음'):String(score)+'점');value.className=score==null?'lf-category-unavailable':'lf-category-value';}
 view('details').textContent='데이터 반영 범위: '+String(r.coverage)+' · 결과 신뢰 지표: '+String(r.resultConfidence)+' (0–1)';
 view('warnings').textContent=Array.isArray(r.warnings)&&r.warnings.length?'일부 정보가 제한되어 결과에 반영되지 않았을 수 있습니다.':'';
 token=typeof r.signedInterpretationContext==='string'&&r.signedInterpretationContext.split('.').length===3?r.signedInterpretationContext:null;interpret.hidden=!token;edit.hidden=false;view('result').hidden=false;view('result').focus();
 }catch(e){if(run===generation)fail(e.name==='AbortError'?'요청 시간이 초과되었습니다. 다시 시도해주세요.':e instanceof TypeError?mode==='period'?'\uC5F0\uACB0\uC744 \uD655\uC778\uD558\uACE0 \uB2E4\uC2DC \uC2DC\uB3C4\uD574\uC8FC\uC138\uC694.':'연결하지 못했어요. 인터넷 연결을 확인하고 다시 시도해주세요.':e.message);}finally{if(run===generation)state(false);}});
 interpret.addEventListener('click',async()=>{if(busy||!token)return;const run=generation;view('error').hidden=true;view('interpretation-error').hidden=true;state(true,mode!=='compatibility'?'흐름 해석을 준비하고 있어요.':'해석을 준비하고 있어요.');try{const r=await post('interpretation/generate',{signedContext:token,locale:'ko-KR'});if(run!==generation)return;const box=view('interpretation');box.replaceChildren();text(box,'h3','전체 해석');text(box,'p',r.summary);for(const [key,label] of [['strengths','강점'],['challenges','살펴볼 점']])if(Array.isArray(r[key])&&r[key].length){text(box,'h4',label);const ul=text(box,'ul','');for(const item of r[key])text(ul,'li',item.text);}text(box,'h4','조언');text(box,'p',r.advice);box.hidden=false;box.focus();}catch(e){if(run===generation)(mode!=='compatibility'?interpretationFail:fail)(e.name==='AbortError'?'요청 시간이 초과되었습니다.':e instanceof TypeError?mode==='period'?'\uC5F0\uACB0\uC744 \uD655\uC778\uD558\uACE0 \uB2E4\uC2DC \uC2DC\uB3C4\uD574\uC8FC\uC138\uC694.':'연결하지 못했어요. 인터넷 연결을 확인하고 다시 시도해주세요.':e.message);}finally{if(run===generation)state(false);}});
 q('[data-action="reset"]').addEventListener('click',()=>{clear();form.querySelector('input').focus();});
 window.addEventListener('pagehide',()=>{clear();form.reset();week.setCustomValidity('');period='weekly';selectMode('compatibility');});
 window.addEventListener('pageshow',()=>{for(const fs of form.querySelectorAll('[data-person]')){const t=fs.querySelector('[data-field="birthTime"]');t.disabled=fs.querySelector('[data-field="unknown"]').checked;t.required=!t.disabled;}});
 submit.disabled=false;
}
})();
