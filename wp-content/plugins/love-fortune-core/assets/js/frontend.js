(() => {
'use strict';
const categories = [['ATTRACTION','끌림'],['EMOTION','감정'],['COMMUNICATION','소통'],['PASSION','열정'],['STABILITY','안정감'],['HARMONY','조화'],['SUPPORT','서포트'],['LONG_TERM','장기적 관계']];
const statuses = {CAUTION:'서로를 배려하며 천천히',BALANCED:'균형을 알아가는 관계',GOOD:'좋은 조화',VERY_GOOD:'매우 좋은 조화',EXCELLENT:'풍부한 조화',INSUFFICIENT_DATA:'계산 정보 없음'};
const errorText = (status,code,retry) => {
 if (code==='INTERPRETATION_CONTEXT_EXPIRED') return '해석 요청 시간이 지났습니다. 궁합을 다시 계산해주세요.';
 if (['INVALID_INTERPRETATION_CONTEXT','INVALID_INTERPRETATION_PURPOSE','INTERPRETATION_LOCALE_MISMATCH'].includes(code)) return '해석 정보를 확인할 수 없습니다. 궁합을 다시 계산해주세요.';
 const map={400:'입력 내용을 다시 확인해주세요.',404:'출생 지역 정보를 확인해주세요.',413:'요청이 너무 큽니다. 입력 내용을 확인해주세요.',415:'요청 형식을 확인할 수 없습니다. 페이지를 새로 열어주세요.',422:'입력한 생년월일 또는 지역 정보를 확인해주세요.',429:'요청이 너무 많습니다. 잠시 후 다시 시도해주세요.',503:'현재 결과를 생성할 수 없습니다. 잠시 후 다시 시도해주세요.'};
 return (map[status]||'결과를 가져오지 못했습니다. 잠시 후 다시 시도해주세요.')+(status===429&&/^\d{1,6}$/.test(retry||'')?' 약 '+retry+'초 후에 시도해주세요.':'');
};
for(const app of document.querySelectorAll('.love-fortune-app')) {
 const q=s=>app.querySelector(s), view=n=>q('[data-view="'+n+'"]'),form=q('form'),submit=q('[type="submit"]'),interpret=q('[data-action="interpret"]');
 let token=null,controller=null,generation=0,busy=false;
 const api=new URL(app.dataset.api,location.href);
 if(api.origin!==location.origin) {view('error').textContent='같은 사이트의 API에 연결할 수 없습니다.';view('error').hidden=false;continue;}
 function endpoint(path){const u=new URL(api);if(u.searchParams.has('rest_route'))u.searchParams.set('rest_route',u.searchParams.get('rest_route').replace(/\/$/,'')+'/'+path);else u.pathname=u.pathname.replace(/\/$/,'')+'/'+path;return u.href;}
 function state(working,message=''){busy=working;submit.disabled=working;interpret.disabled=working;form.setAttribute('aria-busy',String(working));view('loading').textContent=message;}
 function clear(){generation++;controller?.abort();controller=null;token=null;state(false);for(const n of ['result','interpretation','error'])view(n).hidden=true;view('interpretation').replaceChildren();interpret.hidden=true;}
 function fail(message){view('error').textContent=message;view('error').hidden=false;form.setAttribute('aria-describedby',view('error').id);view('error').focus();}
 function text(parent,tag,value){const el=document.createElement(tag);el.textContent=value;parent.append(el);return el;}
 async function post(path,payload){controller=new AbortController();const currentController=controller;const timer=setTimeout(()=>currentController.abort(),60000);try{const response=await fetch(endpoint(path),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload),credentials:'omit',cache:'no-store',redirect:'error',signal:currentController.signal});let data;try{data=await response.json();}catch{throw new Error('서버 응답을 읽을 수 없습니다.');}if(!response.ok)throw new Error(errorText(response.status,data?.error?.code||data?.code,response.headers.get('Retry-After')));return data;}finally{clearTimeout(timer);}}
 function person(fieldset){const field=n=>fieldset.querySelector('[data-field="'+n+'"]');return {birthDate:field('birthDate').value,birthTime:field('unknown').checked?null:field('birthTime').value,birthLocationId:field('birthLocationId').value};}
 for(const fs of form.querySelectorAll('[data-person]'))fs.querySelector('[data-field="unknown"]').addEventListener('change',()=>{const unknown=fs.querySelector('[data-field="unknown"]').checked,time=fs.querySelector('[data-field="birthTime"]');time.disabled=unknown;time.required=!unknown;if(unknown)time.value='';});
 form.addEventListener('input',()=>clear());form.addEventListener('change',()=>clear());
 form.addEventListener('submit',async event=>{event.preventDefault();if(busy||!form.reportValidity())return;clear();const run=generation;state(true,'궁합을 계산하고 있습니다...');try{
 const payload={personA:person(q('[data-person="personA"]')),personB:person(q('[data-person="personB"]')),relationshipType:q('[data-field="relationshipType"]').value,targetTimezone:q('[data-field="targetTimezone"]').value,locale:'ko-KR'};
 const r=await post('compatibility/calculate',payload);if(run!==generation)return;
 if(!r.categories||!Object.hasOwn(statuses,r.status)||!(r.overallScore===null||typeof r.overallScore==='number'))throw new Error('결과 형식을 확인할 수 없습니다.');
 view('score').textContent=r.overallScore===null?'계산 정보 없음':String(r.overallScore)+' / 100';view('status').textContent=statuses[r.status];view('categories').replaceChildren();
 for(const [key,label] of categories){const card=text(view('categories'),'article','');text(card,'h4',label);text(card,'p',r.categories[key]?.score==null?'계산 정보 없음':String(r.categories[key].score)+'점');}
 view('details').textContent='데이터 반영 범위: '+String(r.coverage)+' · 결과 신뢰 지표: '+String(r.resultConfidence)+' (0–1)';
 view('warnings').textContent=Array.isArray(r.warnings)&&r.warnings.length?'일부 정보가 제한되어 결과에 반영되지 않았을 수 있습니다.':'';
 token=typeof r.signedInterpretationContext==='string'&&r.signedInterpretationContext.split('.').length===3?r.signedInterpretationContext:null;interpret.hidden=!token;view('result').hidden=false;view('result').focus();
 }catch(e){if(run===generation)fail(e.name==='AbortError'?'요청 시간이 초과되었습니다. 다시 시도해주세요.':e instanceof TypeError?'서버와 연결할 수 없습니다.':e.message);}finally{if(run===generation)state(false);}});
 interpret.addEventListener('click',async()=>{if(busy||!token)return;const run=generation;view('error').hidden=true;state(true,'해석을 준비하고 있습니다...');try{const r=await post('interpretation/generate',{signedContext:token,locale:'ko-KR'});if(run!==generation)return;const box=view('interpretation');box.replaceChildren();text(box,'h3','전체 해석');text(box,'p',r.summary);for(const [key,label] of [['strengths','강점'],['challenges','주의할 점']])if(Array.isArray(r[key])&&r[key].length){text(box,'h4',label);const ul=text(box,'ul','');for(const item of r[key])text(ul,'li',item.text);}text(box,'h4','조언');text(box,'p',r.advice);box.hidden=false;box.focus();}catch(e){if(run===generation)fail(e.name==='AbortError'?'요청 시간이 초과되었습니다.':e instanceof TypeError?'서버와 연결할 수 없습니다.':e.message);}finally{if(run===generation)state(false);}});
 q('[data-action="reset"]').addEventListener('click',()=>{clear();form.querySelector('input').focus();});
 window.addEventListener('pagehide',()=>{clear();form.reset();});
 window.addEventListener('pageshow',()=>{for(const fs of form.querySelectorAll('[data-person]')){const t=fs.querySelector('[data-field="birthTime"]');t.disabled=fs.querySelector('[data-field="unknown"]').checked;t.required=!t.disabled;}});
 submit.disabled=false;
}
})();
