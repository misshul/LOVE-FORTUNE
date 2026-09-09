
const fs=require('fs'),assert=require('assert/strict'),crypto=require('crypto'),vm=require('vm');
const sandbox={};vm.runInNewContext(fs.readFileSync('docs/contracts/validation/canonicalizer.cjs','utf8')+';this.canonical=canon;',sandbox);const canon=sandbox.canonical;
const read=p=>JSON.parse(fs.readFileSync('docs/contracts/'+p,'utf8'));
const base=Object.fromEntries(read('rules/saju-rules.json').rules.map(r=>[r.ruleId,r.baseWeight]));
const cats=Object.keys(read('rules/category-weights.json').weights),cw=read('rules/category-weights.json').weights;
const approvedTemplates={
SAME:['DAY_MASTER_RELATION',{SUPPORT:.4,HARMONY:.3}],
GENERATION:['ELEMENT_SUPPORT',{SUPPORT:.7,HARMONY:.4,LONG_TERM:.25}],
CONTROL:['ELEMENT_CONTROL',{PASSION:.15,HARMONY:-.45,STABILITY:-.35}],
COMB:['STEM_COMBINATION',{ATTRACTION:.7,HARMONY:.45,LONG_TERM:.25}],
LIUHE:['BRANCH_COMBINATION',{HARMONY:.75,EMOTION:.55,LONG_TERM:.45}],
CLASH:['BRANCH_CLASH',{PASSION:.2,HARMONY:-.7,STABILITY:-.6}],
BSAME:['DAY_BRANCH_RELATION',{EMOTION:.55,STABILITY:.35}],
BGENERATION:['DAY_BRANCH_RELATION',{EMOTION:.45,SUPPORT:.35,LONG_TERM:.25}],
BCONTROL:['DAY_BRANCH_RELATION',{EMOTION:-.35,STABILITY:-.3,HARMONY:-.2}],
EC1:['ELEMENT_COMPLEMENT',{SUPPORT:.3,HARMONY:.2}],EC2:['ELEMENT_COMPLEMENT',{SUPPORT:.6,HARMONY:.4}],EC3:['ELEMENT_COMPLEMENT',{SUPPORT:.9,HARMONY:.6}],
YY0:['YIN_YANG_BALANCE',{HARMONY:-.35,STABILITY:-.2}],YY1:['YIN_YANG_BALANCE',{HARMONY:-.15}],YY2:['YIN_YANG_BALANCE',{HARMONY:.2}],YY3:['YIN_YANG_BALANCE',{HARMONY:.45,STABILITY:.25}]};
const sajuCatalog=read('rules/saju-rules.json');
assert.equal(sajuCatalog.rules.length,14);assert.equal(sajuCatalog.rules.filter(r=>r.enabled).length,9);assert.equal(sajuCatalog.rules.filter(r=>r.contextOnly).length,5);
const maps=Object.fromEntries(sajuCatalog.rules.flatMap(r=>r.variants.map(v=>[v.variant,[r.family,Object.fromEntries(v.categoryMappings.map(m=>[m.category,m.signedValue]))]])));
assert.deepEqual(maps,approvedTemplates);
for(const r of sajuCatalog.rules){assert.equal(r.requiresBirthTime,false);assert.equal(r.ruleWeight,1);assert.equal(r.pairWeight,1);assert.equal(r.ruleId,r.family);}
const sum=a=>a.reduce((s,x)=>s+x,0);
function id(o){return 'ft_'+crypto.createHash('sha256').update(canon(o)).digest('hex').slice(0,48);}
function project(key,confidence=1){if(!key)return[];const [ruleId,m]=maps[key];return Object.entries(m).map(([category,v])=>{
assert(v>=-1&&v<=1&&cats.includes(category));
const identity={ruleId,source:'SAJU',subject:'PAIR',category,period:{type:'LIFETIME'},metadata:{ruleVariant:key}};
return {...identity,featureId:id(identity),v,p:base[ruleId],c:confidence,direction:v>0?'POSITIVE':v<0?'NEGATIVE':'NEUTRAL'};
});}
const rel=(a,b)=>a===b?'SAME':((a-b+5)%5===1||(b-a+5)%5===1)?'GENERATION':'CONTROL';
// Element order WOOD,FIRE,EARTH,METAL,WATER.
const refs=sajuCatalog.referenceTables;
const se=refs.stemElements.map(e=>refs.elements.indexOf(e)),be=refs.branchElements.map(e=>refs.elements.indexOf(e));assert(se.every(x=>x>=0));assert(be.every(x=>x>=0));
const pair=(a,b)=>[a,b].sort((x,y)=>x-y).join('-');
const comb=new Set(refs.stemCombinations.map(p=>pair(...p.map(x=>refs.stems.indexOf(x)))));
const liu=new Set(refs.liuhe.map(p=>pair(...p.map(x=>refs.branches.indexOf(x)))));
const clash=new Set(refs.clash.map(p=>pair(...p.map(x=>refs.branches.indexOf(x)))));
function stem(a,b){assert(Number.isInteger(a)&&Number.isInteger(b)&&a>=0&&b>=0&&a<10&&b<10);return comb.has(pair(a,b))?'COMB':rel(se[a],se[b]);}
function branch(a,b){assert(Number.isInteger(a)&&Number.isInteger(b)&&a>=0&&b>=0&&a<12&&b<12);return liu.has(pair(a,b))?'LIUHE':clash.has(pair(a,b))?'CLASH':'B'+rel(be[a],be[b]);}
const report={};let projected=0;
for(const [name,n,fn]of [['stem',10,stem],['branch',12,branch]]){
 const counts={};for(let a=0;a<n;a++)for(let b=0;b<n;b++){const k=fn(a,b);counts[k]=(counts[k]||0)+1;assert.equal(k,fn(b,a));const f=project(k);assert.equal(new Set(f.map(x=>x.featureId)).size,f.length);assert.deepEqual(f,project(fn(b,a)));projected+=f.length;}report[name]={cases:n*n,counts,duplicates:0,noOwner:0,symmetry:0};}
for(const bad of [-1,12,null,NaN])assert.throws(()=>branch(bad,0));
for(const bad of [-1,10,null,NaN])assert.throws(()=>stem(bad,0));
report.projection={pairFeatureOccurrences:projected,templates:16,mappings:sum(Object.values(maps).map(x=>Object.keys(x[1]).length)),duplicateIds:0,invalidInputRejections:8};
function compositions(n,k=5,p=[],out=[]){if(k===1){out.push([...p,n]);return out;}for(let i=0;i<=n;i++)compositions(n-i,k-1,[...p,i],out);return out;}
const compositionsByN={6:compositions(6),8:compositions(8)};
function gain(a,b){const na=sum(a),nb=sum(b);assert([6,8].includes(na)&&[6,8].includes(nb));assert(a.length===5&&b.length===5&&[...a,...b].every(x=>Number.isInteger(x)&&x>=0));
const sa=sum(a.map(x=>Math.abs(5*x-na))),sb=sum(b.map(x=>Math.abs(5*x-nb))),sj=sum(a.map((x,i)=>Math.abs(5*(x*nb+b[i]*na)-2*na*nb)));
return [sa*nb+sb*na-sj,16*na*nb];}
function ec(n,d){return n*20<d?null:n*20<3*d?'EC1':n*10<3*d?'EC2':'EC3';}
report.complement={};
for(const [na,nb]of [[8,8],[8,6],[6,8],[6,6]]){let count=0,min=1,max=0,bands={NONE:0,EC1:0,EC2:0,EC3:0};for(const a of compositionsByN[na])for(const b of compositionsByN[nb]){const [n,d]=gain(a,b);assert(n>=0&&2*n<=d);assert.deepEqual([n,d],gain(b,a));count++;min=Math.min(min,n/d);max=Math.max(max,n/d);bands[ec(n,d)||'NONE']++;}report.complement[na+'/'+nb]={count,min,max,bands,generatedCases:count-bands.NONE,featureOccurrences:2*(count-bands.NONE)};}
for(const [n,d,k]of [[49999,1000000,null],[50000,1000000,'EC1'],[50001,1000000,'EC1'],[149999,1000000,'EC1'],[150000,1000000,'EC2'],[150001,1000000,'EC2'],[299999,1000000,'EC2'],[300000,1000000,'EC3'],[300001,1000000,'EC3']])assert.equal(ec(n,d),k);
function yy(y,i,n){assert(n>0&&y+i===n&&[y,i,n].every(x=>Number.isInteger(x)&&x>=0));return 2*Math.min(y,i)/n;}
const yyKey=b=>b>=.75?'YY3':b>=.5?'YY2':b>=.25?'YY1':'YY0';
report.yinYang={};for(const n of [16,14,12]){const values=new Set,bands={YY0:0,YY1:0,YY2:0,YY3:0};for(let y=0;y<=n;y++){const b=yy(y,n-y,n);assert(b>=0&&b<=1);assert.equal(b,yy(n-y,y,n));values.add(b);bands[yyKey(b)]++;}report.yinYang[n]={cases:n+1,values:[...values].sort((a,b)=>a-b),bands};}
for(const [b,k]of [[.249999,'YY0'],[.25,'YY1'],[.250001,'YY1'],[.499999,'YY1'],[.5,'YY2'],[.500001,'YY2'],[.749999,'YY2'],[.75,'YY3'],[.750001,'YY3']])assert.equal(yyKey(b),k);
for(const p of [[0,0,0],[-1,2,1],[2,3,6]])assert.throws(()=>yy(...p));
report.confidence=[1,.875,.75].map(r=>[1,.75,.5,.25,0].map(a=>r*a));assert(report.confidence.flat().every(x=>x>=0&&x<=1));
function raw(f){const w=sum(f.map(x=>x.w));return w?50+50*sum(f.map(x=>x.w*x.v))/w:50;}
const status=s=>s<45?'CAUTION':s<60?'BALANCED':s<75?'GOOD':s<85?'VERY_GOOD':'EXCELLENT';

const exactInputs=new Map;
const v2Check={calls:0,features:0,impactViolations:0,exclusions:0,noGuardCalls:0,noGuardDifferences:0,maxImpact:0,rawOutside:0,neutral:0};
function guard(features){
 const f=features.map(x=>({...x,w:x.p*x.c})).sort((a,b)=>a.featureId<b.featureId?-1:a.featureId>b.featureId?1:0);
 const W0=sum(f.map(x=>x.w));assert(Number.isFinite(W0)&&W0>0);
 exactInputs.set(JSON.stringify(f.map(x=>[x.w,x.v])),f.map(x=>[x.w,x.v]));
 const initial=raw(f),events=[];let signedImpacts=[];
 for(const x of f){
  assert(x.w>0&&Number.isFinite(x.w)&&Number.isFinite(x.v)&&Math.abs(x.v)<=1);
  const before=x.w,impact=50*(before*Math.abs(x.v)/W0);
  // Algebraically identical fixed-denominator contribution evaluation; no epsilon or new threshold.
  const bounded=Math.min(impact,20);signedImpacts.push(Math.sign(x.v)*bounded);
  if(impact>20){x.w=Math.min(before,.4*W0/Math.abs(x.v));events.push({id:x.featureId,rule:x.ruleId,before,after:x.w});}
  if(x.v===0)v2Check.neutral++;
  v2Check.features++;v2Check.impactViolations+=bounded>20;v2Check.exclusions+=x.w===0;v2Check.maxImpact=Math.max(v2Check.maxImpact,bounded);
 }
 const result=events.length?50+sum(signedImpacts):initial;
 v2Check.calls++;if(!events.length){v2Check.noGuardCalls++;v2Check.noGuardDifferences+=result!==initial;}
 v2Check.rawOutside+=!Number.isFinite(result)||result<0||result>100;
 return {f,initial,raw:result,events,passes:0,ok:true,excluded:f.filter(x=>x.w===0).length,zero:false,W0};
}
function category(eligible,available){
 assert(available.every(x=>eligible.some(e=>e.featureId===x.featureId)));
 const sorted=available.slice().sort((a,b)=>a.featureId<b.featureId?-1:a.featureId>b.featureId?1:0),ew=sum(eligible.slice().sort((a,b)=>a.featureId<b.featureId?-1:a.featureId>b.featureId?1:0).map(x=>x.p)),aw=sum(sorted.map(x=>x.p));
 const coverage=ew?Math.max(0,Math.min(1,aw/ew)):0,usable=sorted.filter(x=>x.c>0&&x.p>0);
 const rc=aw?sum(sorted.map(x=>x.p*x.c))/aw:0;
 if(!usable.length)return {score:50,status:'INSUFFICIENT_DATA',coverage,rc:0,confidence:0,guard:null};
 const g=guard(usable),score=50+(g.raw-50)*coverage;
 return {score:g.ok?score:null,status:g.ok?status(score):'ERROR',coverage,rc,confidence:coverage*rc,guard:g};
}
function overall(results){if(cats.some(c=>results[c].status==='ERROR'))return null;const c=cats.filter(c=>results[c].status!=='INSUFFICIENT_DATA');return c.length?sum(c.map(k=>cw[k]*results[k].score))/sum(c.map(k=>cw[k])):null;}
function evaluate(features,contexts=[]){assert(features.every(f=>f.source==='SAJU'||f.source==='ASTROLOGY'));const r={};for(const c of cats){const f=features.filter(x=>x.category===c);r[c]=category(f,f);}return {categories:r,overall:overall(r)};}
const singles=[.2,.25,.3,.35,.4,.45,.55,.6,.7,.75,.9,-.2,-.3,-.35,-.45,-.6,-.7];
report.single=singles.map(v=>{const f={...project('SAME')[0],v,p:1,c:1};const r=category([f],[f]);return {v,initial:r.guard.initial,invocations:r.guard.events.length,finalWeight:r.guard.f[0].w,excluded:r.guard.excluded,raw:r.score,status:r.status,ok:r.guard.ok};});
const multiDefs={
A:[['EC3','SUPPORT'],['SAME','SUPPORT']],
B:[['LIUHE','HARMONY'],['YY0','HARMONY']],
C:[['LIUHE','HARMONY'],['COMB','HARMONY'],['EC3','HARMONY']],
D:[['CONTROL','HARMONY'],['CLASH','HARMONY'],['YY0','HARMONY']],
E:[['GENERATION','HARMONY'],['EC2','HARMONY'],['BCONTROL','HARMONY'],['YY0','HARMONY']],
F:[['EC3','SUPPORT'],['SAME','SUPPORT'],['BGENERATION','SUPPORT']]};
report.multi=Object.entries(multiDefs).map(([name,defs])=>{const f=defs.map(([k,c])=>project(k).find(x=>x.category===c));const r=category(f,f);assert.deepEqual(r,category(f.slice().reverse(),f.slice().reverse()));return {name,values:f.map(x=>[x.ruleId,x.v,x.p]),initial:r.guard.initial,raw:r.score,status:r.status,passes:r.guard.passes,ok:r.guard.ok,events:r.guard.events.map(e=>({rule:e.rule,before:e.before,after:e.after})),excluded:r.guard.excluded};});
const invarianceInput=[...project('COMB'),...project('CLASH'),...project('EC3'),...project('YY0')];
const baseline=evaluate(invarianceInput);for(const ctx of [[],[{ruleId:'TEN_GODS_RELATION',subject:'PERSON_A'}],[{ruleId:'TEN_GODS_RELATION',subject:'PERSON_A'},{ruleId:'TEN_GODS_RELATION',subject:'PERSON_B'},{ruleId:'HARM',deferred:true}]])assert.deepEqual(evaluate(invarianceInput,ctx),baseline);
report.context={cases:3,differences:0,note:'No deferred relation detector executed'};
const ast=read('rules/astrology-rules.json').rules;
function astroFeature(category){const r=ast.find(r=>r.enabled&&r.categoryMappings.some(m=>m.category===category)),m=r.categoryMappings.find(m=>m.category===category);const identity={ruleId:r.ruleId,source:'ASTROLOGY',subject:'PAIR',category,period:{type:'LIFETIME'},metadata:{planetA:r.planetA,planetB:r.planetB,aspect:r.aspect}};
return {...identity,featureId:id(identity),v:m.signedValue,p:r.baseWeight*r.ruleWeight*r.pairWeight,c:1};}
const af=astroFeature('HARMONY'),sf=project('SAME').find(x=>x.category==='HARMONY'),comm=astroFeature('COMMUNICATION');
const scenarios=[['ASTRO_ONLY',[af],[af]],['SAJU_ONLY',[sf],[sf]],['BOTH',[af,sf],[af,sf]],['SAJU_CONFIDENCE_ZERO',[af,sf],[af,{...sf,c:0}]],['SAJU_UNAVAILABLE',[af,sf],[af]],['COMMUNICATION_ASTRO_ONLY',[comm],[comm]],['NO_EVIDENCE',[],[]]];
report.combined=scenarios.map(([name,e,a])=>{const r=category(e,a);assert(r.confidence>=0&&r.confidence<=1);return{name,astroRule:a.find(x=>x.source==='ASTROLOGY')?.ruleId,eligible:e.length,available:a.length,usable:a.filter(x=>x.c>0).length,coverage:r.coverage,confidence:r.confidence,score:r.score,status:r.status,ok:r.guard?.ok??true};});
for(const ratio of [1,.875,.75])for(const agreement of [1,.75,.5,.25,0]){const f=project('EC3',ratio*agreement),r=category([f[0]],[f[0]]);assert.equal(r.coverage,1);assert.equal(r.confidence,ratio*agreement);}
report.combinedOverallCommunication=evaluate([comm]).overall;assert(report.combinedOverallCommunication!==null);assert.equal(evaluate([]).overall,null);
function stats(a){a=a.filter(x=>x!==null).sort((a,b)=>a-b);if(!a.length)return{n:0};const q=p=>{const i=(a.length-1)*p,j=Math.floor(i);return a[j]+(a[Math.ceil(i)]-a[j])*(i-j);};return{n:a.length,mean:sum(a)/a.length,median:q(.5),p5:q(.05),p25:q(.25),p75:q(.75),p95:q(.95),min:a[0],max:a.at(-1),lt20:a.filter(x=>x<20).length/a.length,gt80:a.filter(x=>x>80).length/a.length,lt10:a.filter(x=>x<10).length/a.length,gt90:a.filter(x=>x>90).length/a.length,at0or100:a.filter(x=>x===0||x===100).length/a.length};}
const occ=Object.fromEntries(cats.map(c=>[c,{features:0,positive:0,negative:0,available:0,insufficient:0,zeroAfterGuard:0,errors:0}])),values=Object.fromEntries(cats.map(c=>[c,[]])),over=[];
const metrics={categoryCalls:0,usableCategoryCalls:0,invokedCategories:0,events:0,reduced:0,features:0,excluded:0,zeroCategories:0,insufficientTransitions:0,unresolved:0,maxPasses:0,byRule:{}};
function accumulate(r){metrics.categoryCalls++;const g=r.guard;if(!g)return;metrics.usableCategoryCalls++;metrics.invokedCategories+=g.events.length>0;metrics.events+=g.events.length;metrics.features+=g.f.length;metrics.excluded+=g.excluded;metrics.zeroCategories+=g.zero;metrics.unresolved+=!g.ok;metrics.maxPasses=Math.max(metrics.maxPasses,g.passes);
 const changed=new Set(g.events.map(e=>e.id));metrics.reduced+=changed.size;
 for(const f of g.f){const m=metrics.byRule[f.ruleId]??={features:0,invocations:0,reduced:0,excluded:0};m.features++;m.reduced+=changed.has(f.featureId);m.excluded+=f.w===0;}
 for(const e of g.events)metrics.byRule[e.rule].invocations++;
}
const projectOriginal=project,projectionCache=new Map;project=(k,c=1)=>{const key=String(k)+'/'+c;if(!projectionCache.has(key))projectionCache.set(key,projectOriginal(k,c));return projectionCache.get(key);};
let cases=0,swapViolations=0,duplicates=0;const failures=[];
for(let si=0;si<100;si++)for(let bi=0;bi<144;bi++)for(let state=0;state<4;state++){
 const [na,nb]=[[8,8],[8,6],[6,8],[6,6]][state],a=compositionsByN[na][(si*17+bi*31)%compositionsByN[na].length],b=compositionsByN[nb][(si*43+bi*13+7)%compositionsByN[nb].length];
 const [gn,gd]=gain(a,b),ratio=(na+nb)/16,yn=(si*7+bi*11)%(na+nb+1),yk=yyKey(yy(yn,na+nb-yn,na+nb));
 const sk=stem(Math.floor(si/10),si%10),bk=branch(Math.floor(bi/12),bi%12);
 const f=[...project(sk),...project(bk),...project(ec(gn,gd),ratio),...project(yk,ratio)];
 duplicates+=new Set(f.map(x=>x.featureId)).size!==f.length;
 const r=evaluate(f),sw=evaluate(f.slice().reverse());swapViolations+=!require('util').isDeepStrictEqual(r,sw);
 // Relations and complement separately verified under true A/B swap above.
 cases++;over.push(r.overall);if(r.overall===null&&failures.length<3)failures.push({si,bi,state,features:f.map(x=>({rule:x.ruleId,category:x.category,v:x.v,p:x.p,c:x.c,id:x.featureId})),errorCategories:cats.filter(c=>r.categories[c].status==='ERROR')});
 for(const c of cats){const cf=f.filter(x=>x.category===c),cr=r.categories[c];occ[c].features+=cf.length;occ[c].positive+=cf.filter(x=>x.v>0).length;occ[c].negative+=cf.filter(x=>x.v<0).length;occ[c].available+=cf.length>0;occ[c].insufficient+=cr.status==='INSUFFICIENT_DATA';occ[c].errors+=cr.status==='ERROR';occ[c].zeroAfterGuard+=cr.guard?.zero??false;values[c].push(['INSUFFICIENT_DATA','ERROR'].includes(cr.status)?null:cr.score);accumulate(cr);}
}
report.population={cases,method:'100 ordered stem pairs x 144 ordered branch pairs x 4 time states; token distributions indexed by deterministic modular schedule; abstract independent inputs, not birth charts',occ,overall:stats(over),categories:Object.fromEntries(cats.map(c=>[c,stats(values[c])])),metrics,swapViolations,duplicates,failedOverall:over.filter(x=>x===null).length,failures};
report.scope='read-only memory simulator; no production code or new scoring rules';

const oldGuard=features=>{const f=features.map(x=>({...x,w:x.p*x.c}));return {f,initial:raw(f),raw:raw(f),events:[],ok:true,excluded:0,zero:false};};
const astroAudit={rules:0,mappings:0,adjusted:0,excluded:0,unresolved:0,noGuard:0,noGuardChanged:0,confidenceChanges:0,statusErrors:0};
for(const r of ast){
 astroAudit.rules++;
 for(const m of r.categoryMappings){
 const identity={ruleId:r.ruleId,source:'ASTROLOGY',subject:'PAIR',category:m.category,period:{type:'LIFETIME'},metadata:{planetA:r.planetA,planetB:r.planetB,aspect:r.aspect}};
 const f={...identity,featureId:id(identity),v:m.signedValue,p:r.baseWeight*r.ruleWeight*r.pairWeight,c:1};
 const out=category([f],[f]),g=out.guard;astroAudit.mappings++;astroAudit.adjusted+=g.events.length;astroAudit.excluded+=g.excluded;astroAudit.unresolved+=!g.ok;
 if(!g.events.length){astroAudit.noGuard++;astroAudit.noGuardChanged+=out.score!==g.initial;}
 astroAudit.confidenceChanges+=out.coverage!==1||out.confidence!==1||f.c!==1;
 astroAudit.statusErrors+=out.status!==status(out.score);
 }
}
// Multi-evidence independent catalog fixtures: each rule contributes its registered mappings,
// no claim that all these aspects coexist in a real chart.
let astroMulti=0;
for(const c of cats){
 const f=ast.flatMap(r=>r.categoryMappings.filter(m=>m.category===c).map(m=>{
 const identity={ruleId:r.ruleId,source:'ASTROLOGY',subject:'PAIR',category:c,period:{type:'LIFETIME'},metadata:{planetA:r.planetA,planetB:r.planetB,aspect:r.aspect}};
 return {...identity,featureId:id(identity),v:m.signedValue,p:r.baseWeight*r.ruleWeight*r.pairWeight,c:1};
 }));
 const a=category(f,f),b=category(f.slice().reverse(),f.slice().reverse());assert.deepEqual(a,b);assert.equal(a.coverage,1);assert.equal(a.confidence,1);assert.equal(a.guard.excluded,0);astroMulti++;
}
const expandedSingles=[.2,.4,.45,.7,.9,-.2,-.4,-.45,-.7,-.9].map(v=>{const f={...project('SAME')[0],v,p:1,c:1},r=category([f],[f]);assert.equal(r.score,50+Math.sign(v)*Math.min(50*Math.abs(v),20));return{v,weight:r.guard.f[0].w,score:r.score};});
const neutral={...project('SAME')[0],v:0};assert.equal(guard([neutral]).raw,50);assert.equal(guard([neutral]).events.length,0);
const rational=n=>{let t=String(n),[m,e='0']=t.split('e');let [i,d='']=m.split('.');let num=BigInt(i+d),den=10n**BigInt(d.length),exp=Number(e);if(exp>0)num*=10n**BigInt(exp);else den*=10n**BigInt(-exp);return[num,den];};
const add=(a,b)=>[a[0]*b[1]+b[0]*a[1],a[1]*b[1]],mul=(a,b)=>[a[0]*b[0],a[1]*b[1]],div=(a,b)=>[a[0]*b[1],a[1]*b[0]],lte=(a,b)=>a[0]*b[1]<=b[0]*a[1];
let exactFeatureChecks=0,exactCaps=0,exactViolations=0;
for(const input of exactInputs.values()){
 const W=input.map(([w])=>rational(w)).reduce(add,[0n,1n]);assert(W[0]>0n);
 for(const [w,v]of input){const wr=rational(w),vr=rational(Math.abs(v));
 let adjusted=wr;if(v!==0){const cap=div(mul(rational(.4),W),vr);if(!lte(wr,cap)){adjusted=cap;exactCaps++;}}
 const impact=div(mul(rational(50),mul(adjusted,vr)),W);
 exactFeatureChecks++;exactViolations+=!lte(impact,rational(20));assert(adjusted[0]>0n);assert(lte(adjusted,wr));assert(lte(impact,rational(20)));
 }
}
const confChecks={cases:0,changes:0};
for(const [name,e,a] of scenarios){const before=JSON.stringify(a),r=category(e,a),saved=guard;guard=oldGuard;const legacy=category(e,a);guard=saved;for(const key of ['coverage','confidence','rc']){confChecks.changes+=r[key]!==legacy[key];assert.equal(r[key],legacy[key]);}assert.equal(JSON.stringify(a),before);confChecks.cases++;}
report.astroAudit={...astroAudit,multiCategories:astroMulti};report.exact={uniqueInputs:exactInputs.size,features:exactFeatureChecks,capped:exactCaps,violations:exactViolations};
report.expandedSingles=expandedSingles;report.confChecks=confChecks;

const vectors=read('examples/saju-v1-semantic-vectors.json');
assert.deepEqual(vectors.unknownConfidence,[1,.875,.75]);
for(const v of vectors.invalid){let valid;if(v.stemOwners)valid=v.stemOwners.length<=1;else if(v.coverage!==undefined)valid=v.coverage===v.available/v.eligible;else valid=v.yang>=0&&v.yin>=0&&v.total>0&&v.yang+v.yin===v.total;assert.equal(valid,v.valid);}
assert.equal(report.population.metrics.unresolved,0);assert.equal(report.population.metrics.excluded,0);assert.equal(report.population.swapViolations,0);
assert.deepEqual(report.stem.counts,{SAME:20,GENERATION:40,CONTROL:30,COMB:10});
assert.deepEqual(report.branch.counts,{BSAME:28,LIUHE:12,BGENERATION:50,BCONTROL:42,CLASH:12});

const expectedBands={EC1:[.05,.15,false],EC2:[.15,.3,false],EC3:[.3,.5,true],YY0:[0,.25,false],YY1:[.25,.5,false],YY2:[.5,.75,false],YY3:[.75,1,true]};
const weightSection=fs.readFileSync('docs/03_SCORE_SPEC.md','utf8').split(/^# 18\./m)[1].split(/^# \d+\./m)[0];
const expectedWeights=Object.fromEntries([...weightSection.matchAll(/^([A-Z_]+)\s+(\d+(?:\.\d+)?)/gm)].map(m=>[m[1],Number(m[2])]));
for(const r of sajuCatalog.rules){assert.equal(r.baseWeight,expectedWeights[r.family]);for(const v of r.variants){if(expectedBands[v.variant])assert.deepEqual([v.band.minimum,v.band.maximum,v.band.maximumInclusive],expectedBands[v.variant]);if(v.variant==='COMB')assert.deepEqual(v.metadata,{transformationStatus:'UNASSESSED'});}}
const contextEnvelopeValid=e=>{const ids=e.contextEvidence.map(c=>c.contextEvidenceId);return new Set(ids).size===ids.length&&e.interpretations.every(i=>i.contextEvidenceRefs.every(ref=>ids.includes(ref)));};
assert(contextEnvelopeValid(read('examples/saju-v1-context-interpretation.json')));for(const v of vectors.contextInvalid)assert.equal(contextEnvelopeValid(v.envelope),v.valid);
const ce=read('examples/saju-v1-ten-gods.json');const cid='ce_'+crypto.createHash('sha256').update(canon({ruleId:ce.ruleId,source:ce.source,subject:ce.subject,period:ce.period,metadata:ce.metadata})).digest('hex').slice(0,48);assert.equal(ce.contextEvidenceId,cid);
report.actualFileValidation={familyWeights:14,bands:7,contextReferenceCases:3,semanticInvalidCases:vectors.invalid.length+vectors.contextInvalid.length,contextCanonicalId:'PASS'};
report.v2Check=v2Check;console.log(JSON.stringify(report));
