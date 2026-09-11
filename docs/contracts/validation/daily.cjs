// Documentation-only contract validator. No provider, epoch, application writes or user data.
const fs = require('fs'), assert = require('assert/strict'), crypto = require('crypto'), vm = require('vm');
const dir = 'docs/contracts/', read = p => JSON.parse(fs.readFileSync(dir + p, 'utf8'));
const dc = read('rules/daily-rules.json'), ac = read('rules/astrology-rules.json'), sc = read('rules/saju-rules.json');
const approval = read('daily-v1-approved-matrix.json'), cw = read('rules/category-weights.json').weights;
const { canon } = vm.runInNewContext(fs.readFileSync(dir + 'validation/canonicalizer.cjs', 'utf8') + ';({canon})');
const digest = x => crypto.createHash('sha256').update(canon(x)).digest('hex');
const cats = Object.keys(cw), w = cats.map(c => cw[c]), sum = xs => xs.reduce((a, b) => a + b, 0);
const planets = ['MOON', 'MERCURY', 'VENUS', 'SUN', 'MARS'], natal = ['SUN', 'MOON', 'MERCURY', 'VENUS', 'MARS'];
const maps = cell => cell.split(' ').map(t => ({ category: approval.categoryCodes[t[0]], signedValue: Number(t.slice(1)), direction: t[1] === '+' ? 'POSITIVE' : 'NEGATIVE' }));
const expectedMaps = new Map(approval.core.flatMap(([t, n, ...cells]) => cells.map((cell, i) => [`DAILY_ASTRO_${t}_${n}_${approval.aspectOrder[i]}`, maps(cell)])));
for (const [name, cell] of approval.saju) expectedMaps.set('DAILY_SAJU_' + name, maps(cell));
const sajuWeights = [.9, 1.2, .85, .8, 1, 1, 1.2, .85, .8];
function validateCatalog(c) {
  assert.equal(c.rules.length, 134);
  assert.equal(new Set(c.rules.map(r => r.ruleId)).size, 134);
  assert.equal(c.rules.filter(r => r.source === 'ASTROLOGY').length, 125);
  assert.equal(c.rules.filter(r => r.source === 'SAJU').length, 9);
  assert.equal(sum(c.rules.map(r => r.categoryMappings.length)), 285);
  for (const r of c.rules) {
    assert.equal(r.enabled, true); assert.equal(r.contextOnly, false); assert.equal(r.requiresBirthTime, false);
    assert.equal(r.pairWeight, 1); assert.equal(r.sampleIdentity, 'NOMINAL_LOCAL_SLOT');
    assert.deepEqual(r.categoryMappings, expectedMaps.get(r.ruleId), r.ruleId + ': approval mismatch');
    assert.equal(new Set(r.categoryMappings.map(m => m.category)).size, r.categoryMappings.length);
    for (const m of r.categoryMappings) {
      assert(cats.includes(m.category)); assert(m.signedValue >= -1 && m.signedValue <= 1);
      assert.equal(m.direction, m.signedValue > 0 ? 'POSITIVE' : m.signedValue < 0 ? 'NEGATIVE' : 'NEUTRAL');
    }
    if (r.source === 'ASTROLOGY') {
      assert(planets.includes(r.transitPlanet) && natal.includes(r.natalPlanet));
      assert.equal(r.ruleId, `DAILY_ASTRO_${r.transitPlanet}_${r.natalPlanet}_${r.aspect}`);
      assert.deepEqual(r.orbPolicy, { degrees: 1, inclusive: true, planetAdjustment: 'NONE', closenessUsage: 'METADATA_ONLY' });
      assert.equal(r.baseWeight, ac.aspectDefinitions[r.aspect].baseWeight);
      assert.equal(r.ruleWeight, c.referenceTables.dailyPlanetWeights[r.transitPlanet]);
    } else {
      const i = approval.saju.findIndex(([name]) => 'DAILY_SAJU_' + name === r.ruleId);
      assert(i >= 0); assert.equal(r.baseWeight, sajuWeights[i]); assert.equal(r.ruleWeight, 1);
      assert.equal(r.baseWeight, sc.rules.find(x => x.ruleId === r.weightSource).baseWeight);
      assert.equal(r.ruleId, `DAILY_SAJU_${r.relationOwner}_${r.relation}`);
      assert.equal(r.referenceId, 'DAILY_SAJU_REFERENCE_V1');
    }
  }
  assert.deepEqual(c.referenceTables.sourceWeights, { SAJU: .5, ASTROLOGY: .5 });
  assert.deepEqual(c.referenceTables.dailyPlanetWeights, { MOON: 1, VENUS: .9, MERCURY: .75, MARS: .8, SUN: .6, JUPITER: .3, SATURN: .35, OUTER: 0 });
  assert.deepEqual(c.referenceTables.sampleLocalTimes, ['06:00', '12:00', '18:00', '23:00']);
  assert.deepEqual(c.referenceTables.sourceAggregation, { meanWeight: .75, signedPeakWeight: .25 });
  assert.deepEqual(c.referenceTables.aspectDefinitions, {
    CONJUNCTION:{exactAngle:0,baseWeight:1},SEXTILE:{exactAngle:60,baseWeight:.7},
    SQUARE:{exactAngle:90,baseWeight:.85},TRINE:{exactAngle:120,baseWeight:.9},OPPOSITION:{exactAngle:180,baseWeight:.9}
  });
  assert.deepEqual(c.referenceTables.sajuReference,{referenceId:'DAILY_SAJU_REFERENCE_V1',timezone:'Asia/Seoul',longitude:127.5,dayBoundary:'23:30',tablesRef:'saju-rules.json#/referenceTables'});
  assert.equal(c.aggregation.policy, 'M1-ELIGIBILITY-AWARE');
  assert.deepEqual(c.aggregation.statusThresholds, { inner: 2, outer: 5 });
  assert.equal(c.aggregation.deltaScale, 18); assert.equal(c.aggregation.sampleConfidenceDenominator, 4);
  assert.equal(c.aggregation.guardrail, 'NOT_APPLIED_TO_DAILY_V1');
  for (const k of ['overallSourceDenominator','categorySourceDenominator','sourceCategoryDenominator']) assert.equal(c.aggregation[k], 'STRUCTURALLY_ELIGIBLE');
}
validateCatalog(dc);
const eligible = ['ASTROLOGY', 'SAJU'].map(src => cats.map((_, i) => i).filter(i => dc.rules.some(r => r.source === src && r.categoryMappings.some(m => m.category === cats[i]))));
assert.deepEqual(eligible, [[0,1,2,3,5,6], [0,1,2,3,4,5,6]]);
assert.deepEqual(dc.structuralEligibility, Object.fromEntries(['ASTROLOGY','SAJU'].map((s, i) => [s, eligible[i].map(c => cats[c])])));
const ar = dc.rules.filter(r => r.source === 'ASTROLOGY').map(r => ({ id:r.ruleId, t:planets.indexOf(r.transitPlanet), n:natal.indexOf(r.natalPlanet), angle:dc.referenceTables.aspectDefinitions[r.aspect].exactAngle, weight:r.baseWeight*r.ruleWeight*r.pairWeight, maps:r.categoryMappings.map(m => ({cat:cats.indexOf(m.category),v:m.signedValue})) }));
const sr = approval.saju.map(([name]) => dc.rules.find(r => r.ruleId === 'DAILY_SAJU_' + name)).map(r => ({ id:r.ruleId, weight:r.baseWeight*r.ruleWeight*r.pairWeight, maps:r.categoryMappings.map(m => ({cat:cats.indexOf(m.category),v:m.signedValue})) }));
const unavailable = () => ({ v:null, c:0 }), noevent = () => ({ v:0, c:1 });
function person(features, source, faults = []) {
  features = [...features].sort((a,b) => a.id.localeCompare(b.id));
  return cats.map((_, cat) => {
    if (!eligible[source].includes(cat) || faults.includes(cat)) return unavailable();
    const f = features.filter(f => f.cat === cat), u = f.filter(f => f.conf > 0 && f.pre > 0);
    if (!f.length) return noevent(); if (!u.length) return unavailable();
    return { v:sum(u.map(f => f.pre*f.conf*f.v))/sum(u.map(f => f.pre*f.conf)), c:sum(f.map(f => f.pre*f.conf))/sum(f.map(f => f.pre)) };
  });
}
function pair(a,b) { return a.map((x,i) => x.v === null || b[i].v === null || x.c === 0 || b[i].c === 0 ? unavailable() : {v:(x.v+b[i].v)/2,c:(x.c+b[i].c)/2}); }
function sample(cat, source, legacy = false) {
  const available = eligible[source].filter(i => cat[i].v !== null);
  if (!available.length) return {...unavailable(),cats:cat};
  const signalDen = sum((legacy ? available : eligible[source]).map(i => w[i]));
  return {v:sum(available.map(i => w[i]*cat[i].v))/signalDen,c:sum(available.map(i => w[i]*cat[i].c))/sum(available.map(i => w[i])),cats:cat};
}
function four(slots) {
  assert.equal(slots.length, dc.aggregation.sampleConfidenceDenominator);
  const valid = slots.filter(x => x.v !== null); if (!valid.length) return unavailable();
  let peak = valid[0]; for (const x of valid.slice(1)) if (Math.abs(x.v) > Math.abs(peak.v)) peak = x;
  const a = dc.referenceTables.sourceAggregation;
  const v = a.meanWeight*sum(valid.map(x => x.v))/valid.length+a.signedPeakWeight*peak.v;
  assert(Number.isFinite(v) && Math.abs(v) <= 1);
  return {v,c:sum(slots.map(x => x.c))/dc.aggregation.sampleConfidenceDenominator};
}
function day(samples) { return {...four(samples),cats:cats.map((_,i) => four(samples.map(s => s.cats[i]))),samples}; }
const emptyDay = () => ({...unavailable(),cats:cats.map(unavailable)});
function blend(a,b,den=1,legacy=false) {
  if (a.v === null && b.v === null) return unavailable();
  const numerator = (a.v === null ? 0 : .5*a.v)+(b.v === null ? 0 : .5*b.v);
  return {v:numerator/(legacy ? (a.v===null?0:.5)+(b.v===null?0:.5) : den),c:a.v===null?b.c:b.v===null?a.c:(a.c+b.c)/2};
}
function status(d) {const {inner,outer}=dc.aggregation.statusThresholds;return d<=-outer?'VERY_LOW':d<=-inner?'LOW':d<inner?'STABLE':d<outer?'GOOD':'VERY_GOOD';}
function combined(a,b,legacy=false) {
  const overall=blend(a,b,1,legacy),delta=overall.v===null?0:dc.aggregation.deltaScale*overall.v;
  return {...overall,delta,status:overall.v===null?'INSUFFICIENT_PERIOD_DATA':status(delta),cats:cats.map((_,c) => blend(a.cats[c],b.cats[c],.5*eligible.filter(e=>e.includes(c)).length,legacy))};
}
const norm=x=>((x%360)+360)%360;
const orb=(a,b,angle)=>{const d=Math.abs(norm(a)-norm(b));return Math.abs(Math.min(d,360-d)-angle);};
function astroFeatures(pos,nat,ambiguity,baseConf=1,reverse=false) {
  const candidates=ambiguity?[nat.map(x=>norm(x-.8)),nat.map(x=>norm(x+.8))]:[nat],out=[];
  for(const r of reverse?[...ar].reverse():ar){const hits=candidates.filter(n=>orb(pos[r.t],n[r.n],r.angle)<=1).length;if(!hits)continue;
    for(const m of r.maps)out.push({id:r.id+'|'+m.cat,cat:m.cat,v:m.v,pre:r.weight,conf:baseConf*hits/candidates.length});}
  return out;
}
// Recovered deterministic longitude and pillar generators from the previous read-only run.
function astroConfig(i,offset=0) {
  const target=i%5,focus=Math.floor(i/5)%5,pat=Math.floor(i/25)%5;
  const na=natal.map((_,j)=>norm(19*i+47*j+11)),nb=natal.map((_,j)=>norm(na[j]+[0,.6,90,120,17][(i+j)%5]));
  const base=planets.map((_,j)=>norm(na[(j+i)%5]+[0,60,90,120,180][(i+j)%5]+(i%4===0?3:0)));
  const pattern=[[0,0,0,0],[0,3,3,3],[3,0,0,3],[0,90,0,90],[17,17,17,17]][pat];
  const positions=pattern.map(shift=>base.map((x,j)=>norm(j===focus?na[target]+shift+offset*(j===0?7:2):x+offset*(j===0?7:2))));
  return {na,nb,positions,ambiguity:i%3,partial:i%16===15,missing:i%16===14,zero:i%32===13};
}
function buildAstro(i,offset=0,swap=false,reverse=false,legacy=false) {
  const cfg=astroConfig(i,offset),samples=[];
  for(let k=0;k<4;k++) {const fa=astroFeatures(cfg.positions[k],cfg.na,cfg.ambiguity>0,cfg.zero?0:1,reverse),fb=astroFeatures(cfg.positions[k],cfg.nb,cfg.ambiguity>1,1,reverse),faults=cfg.partial?[1,2,5]:[];
    let a=person(fa,0,faults),b=person(fb,0,faults);if(cfg.missing&&k!==0)a=cats.map(unavailable);
    samples.push(sample(swap?pair(b,a):pair(a,b),0,legacy));}
  return {...day(samples),complete:!cfg.partial&&!cfg.missing&&!cfg.zero};
}
const rt=sc.referenceTables;
const rel=(a,b)=>a===b?'SAME':((a-b+5)%5===1||(b-a+5)%5===1)?'GENERATION':'CONTROL';
const has=(list,a,b)=>list.some(p=>p[0]===a&&p[1]===b||p[0]===b&&p[1]===a);
function owners(d,n){const ds=rt.stems[d%10],ns=rt.stems[n%10],db=rt.branches[d%12],nb=rt.branches[n%12];
  return [has(rt.stemCombinations,ds,ns)?0:({SAME:1,GENERATION:2,CONTROL:3}[rel(rt.elements.indexOf(rt.stemElements[d%10]),rt.elements.indexOf(rt.stemElements[n%10]))]),has(rt.liuhe,db,nb)?4:has(rt.clash,db,nb)?5:({SAME:6,GENERATION:7,CONTROL:8}[rel(rt.elements.indexOf(rt.branchElements[d%12]),rt.elements.indexOf(rt.branchElements[n%12]))])];}
function sajuFeatures(d,n,ambiguous) {const ns=ambiguous?[n,(n+1)%60]:[n],hits=new Map();for(const x of ns)for(const i of owners(d,x))hits.set(i,(hits.get(i)||0)+1);
  return [...hits].flatMap(([i,count])=>sr[i].maps.map(m=>({id:sr[i].id+'|'+m.cat,cat:m.cat,v:m.v,pre:sr[i].weight,conf:count/ns.length})));}
function buildSaju(i,offset=0,swap=false,legacy=false){const d=(Math.floor(i/100)+offset)%60,na=i%60,nb=Math.floor(i/10)%60,amb=i%3,pattern=[[0,0,0,0],[0,0,0,1],[0,0,1,1],[0,1,1,1]][Math.floor(i/3)%4],memo=new Map(),samples=[];
  for(let k=0;k<4;k++){const dp=(d+pattern[k])%60;let value=memo.get(dp);if(!value){const a=person(sajuFeatures(dp,na,amb>0),1),b=person(sajuFeatures(dp,nb,amb>1),1);value=sample(swap?pair(b,a):pair(a,b),1,legacy);memo.set(dp,value);}samples.push(value);}return day(samples);}
function identity(f){const keys=f.source==='ASTROLOGY'?['sampleRef','transitPlanet','natalPlanet','aspect']:['sampleRef','referenceId','relation','lineage'];const metadata=Object.fromEntries(keys.map(k=>[k,f.metadata[k]]));if(metadata.lineage)metadata.lineage=[...metadata.lineage].sort((a,b)=>canon(a).localeCompare(canon(b)));return{ruleId:f.ruleId,subject:f.subject,category:f.category,period:f.period,source:f.source,metadata};}
const fid=f=>'ft_'+digest(identity(f)).slice(0,48);
function validateEvidence(f){const r=dc.rules.find(r=>r.ruleId===f.ruleId);assert(r);assert.equal(f.source,r.source);assert(r.categoryMappings.some(m=>m.category===f.category));assert.equal(f.metadata.sampleRef.date,f.period.date);assert.equal(f.metadata.sampleRef.timezone,f.period.timezone);assert.equal(f.featureId,fid(f));if(r.source==='ASTROLOGY'){assert.equal(f.metadata.transitPlanet,r.transitPlanet);assert.equal(f.metadata.natalPlanet,r.natalPlanet);assert.equal(f.metadata.aspect,r.aspect);}else for(const l of f.metadata.lineage){assert.equal(Object.hasOwn(l,'dailyStem'),r.relationOwner==='STEM');}}
function candidateGroups(features){const groups=new Map();for(const f of features){const k=canon({ruleId:f.ruleId,subject:f.subject,category:f.category,period:f.period,sampleRef:f.metadata.sampleRef});if(!groups.has(k))groups.set(k,[]);groups.get(k).push(f);}return groups;}
function stats(xs){if(!xs.length)return null;const a=[...xs].sort((a,b)=>a-b),q=p=>a[Math.max(0,Math.ceil(p*a.length)-1)];return{n:a.length,mean:sum(xs)/xs.length,median:q(.5),p5:q(.05),p95:q(.95),min:a[0],max:a.at(-1)};}
function run(){
  let orbChecks=0;for(const r of ar){for(const x of [0,.5,.9999,1,1.0001,3]){assert.equal(orb(r.angle===180?r.angle-x:r.angle+x,0,r.angle)<=1,x<=1);orbChecks++;}}
  assert(orb(359.5,.2,0)<=1);assert(orb(359,1.1,0)>1);
  const stemCounts=Array(4).fill(0),branchCounts=Array(5).fill(0);for(let d=0;d<10;d++)for(let n=0;n<10;n++)stemCounts[owners(d,n)[0]]++;
  for(let d=0;d<12;d++)for(let n=0;n<12;n++)branchCounts[owners(d,n)[1]-4]++;
  assert.deepEqual(stemCounts,[10,20,40,30]);assert.deepEqual(branchCounts,[12,12,28,50,42]);
  const a=buildAstro(30),known=[14,120,233,554,665,785],expected=[5.053892422023684,5.023264501322234,5.034820730847214,5.085175844483577,5.086124966384403,5.091481584773692];
  const extreme=known.map((si,i)=>{const v=combined(a,buildSaju(si));assert.equal(v.delta,expected[i]);assert.equal(v.status,'VERY_GOOD');return{ai:30,si,delta:v.delta};});
  const A=Array.from({length:128},(_,i)=>buildAstro(i)),S=Array.from({length:1000},(_,i)=>buildSaju(i));
  const A0=Array.from({length:128},(_,i)=>buildAstro(i,0,false,false,true)),S0=Array.from({length:1000},(_,i)=>buildSaju(i,0,false,true));
  let cases=0,full=0,categoryChecks=0,nonzeroStability=0,unavailableCount=0;const deltas=[],counts={};
  // New explicit, persisted availability schedule. Old mode selector was not persisted.
  // Retain the recovered 128x1000 base inputs; do not claim identical old 256k distribution.
  for(let ai=0;ai<128;ai++)for(let si=0;si<1000;si++)for(let mode=0;mode<2;mode++){
    const kind=(ai+si)%3,aa=mode&&kind!==1?emptyDay():A[ai],ss=mode&&kind!==0?emptyDay():S[si],v=combined(aa,ss);cases++;
    counts[v.status]=(counts[v.status]||0)+1;if(v.v===null)unavailableCount++;else{assert(Number.isFinite(v.delta)&&Math.abs(v.delta)<=18);deltas.push(v.delta);}
    if(mode===0&&A[ai].complete){const baseline=combined(A0[ai],S0[si],true);assert.deepEqual(v,baseline);full++;categoryChecks+=8;if(v.cats[4].v)nonzeroStability++;}
    if(ss.v!==null){assert.equal(v.cats[4].v,ss.cats[4].v);}assert.equal(v.cats[7].v,null);
  }
  assert.equal(cases,256000);assert.equal(full,108000);assert.equal(nonzeroStability,101844);
  let repeat=0;for(let i=0;i<100;i++){const ai=i%128,si=(i*37)%1000,base=combined(A[ai],S[si]);
    assert.deepEqual(combined(buildAstro(ai,0,true),buildSaju(si,0,true)),base);
    assert.deepEqual(combined(buildAstro(ai,0,false,true),buildSaju(si)),base);
    for(let j=0;j<100;j++){assert.deepEqual(combined(A[ai],S[si]),base);repeat++;}}
  assert.equal(blend(unavailable(),{v:.4,c:1}).v,.2);assert.equal(blend(unavailable(),{v:.4,c:1},.5).v,.4);
  assert.equal(blend(unavailable(),unavailable()).v,null);
  assert.deepEqual(person([],0)[0],noevent());assert.deepEqual(pair([{v:.6,c:1}],[noevent()]),[{v:.3,c:1}]);
  assert.equal(pair([unavailable()],[{v:.6,c:1}])[0].v,null);
  assert.equal(pair([{v:.6,c:0}],[{v:.6,c:1}])[0].v,null);
  assert.deepEqual(four([{v:.4,c:1},unavailable(),unavailable(),unavailable()]),{v:.4,c:.25});
  assert.equal(four([{v:.4,c:1},{v:-.4,c:1},{v:0,c:1},{v:0,c:1}]).v,.1);
  assert.deepEqual(four(Array.from({length:4},()=>({v:.4,c:1}))),{v:.4,c:1});
  // Synthetic civil-time boundary tests do not select or verify a Day Pillar epoch.
  const adjusted=(civil,offset)=>new Date(Date.parse(civil+'Z')+(540-offset+Math.round((dc.referenceTables.sajuReference.longitude-127.5)*4))*60000);
  const pillarDate=dt=>new Date(dt.getTime()+30*60000).toISOString().slice(0,10);
  for(const [civil,offset,date]of [['2000-01-01T23:29:00',540,'2000-01-01'],['2000-01-01T23:30:00',540,'2000-01-02'],['2000-01-01T23:31:00',540,'2000-01-02'],['1955-01-01T22:59:00',510,'1955-01-01'],['1955-01-01T23:00:00',510,'1955-01-02']]) assert.equal(pillarDate(adjusted(civil,offset)),date);
  const partial=cats.map(noevent);partial[1]=unavailable();
  for(const i of eligible[0])if(i!==1)partial[i]={v:.4,c:1};
  const attenuated=sample(partial,0);assert.equal(attenuated.v,sum(eligible[0].filter(i=>i!==1).map(i=>w[i]*.4))/sum(eligible[0].map(i=>w[i])));
  assert(attenuated.v<sample(partial,0,true).v);
  const ev=read('examples/daily-evidence-valid.json');validateEvidence(ev);const other=structuredClone(ev);other.metadata.sampleRef.time='12:00';other.featureId=fid(other);validateEvidence(other);
  validateEvidence(read('examples/daily-evidence-saju-valid.json'));
  const badPeriod=structuredClone(ev);badPeriod.period.date='2000-01-02';badPeriod.featureId=fid(badPeriod);assert.throws(()=>validateEvidence(badPeriod));
  const badRole=structuredClone(ev);badRole.metadata.transitPlanet='MARS';badRole.featureId=fid(badRole);assert.throws(()=>validateEvidence(badRole));
  assert.notEqual(ev.featureId,other.featureId);assert.equal(candidateGroups([ev,structuredClone(ev),other]).size,2);
  const noise=structuredClone(ev);noise.confidence=.1;noise.signedValue=.1;noise.metadata.orbCloseness=.1;assert.equal(fid(noise),ev.featureId);
  const mutants=[r=>r.rules.pop(),r=>r.rules[0].categoryMappings[0].signedValue=.51,r=>r.rules[0].pairWeight=2,r=>r.rules[0].orbPolicy.degrees=2,r=>r.rules[0].transitPlanet='SATURN',r=>r.aggregation.statusThresholds.inner=6,r=>r.aggregation.sourceCategoryDenominator='AVAILABLE_ONLY',r=>r.aggregation.guardrail='APPLIED'];
  for(const mut of mutants){const x=structuredClone(dc);mut(x);assert.throws(()=>validateCatalog(x));}
  return{result:'PASS',rules:134,astrologyRules:125,astrologyMappings:266,sajuRules:9,sajuMappings:19,mappings:285,orbChecks,stemCounts,branchCounts,cases,fullDataCases:full,fullCategoryChecks:categoryChecks,nonzeroStability,fullOverallDiff:0,fullCategoryDiff:0,stabilityAttenuation:0,hardFailures:0,repeat,swaps:100,orderChecks:100,semanticMutationsRejected:mutants.length,structuralEligibility:dc.structuralEligibility,knownSix:extreme,delta:stats(deltas),statusCounts:counts,unavailable:unavailableCount,originalPopulationIdentity:'NOT_CLAIMED: prior in-memory availability selector unavailable; recovered base generators plus persisted availability schedule',fakeNeutral:0,crossSampleMerge:0,dailyGuardrail:0,secondary:0,outer:0,mc:0,catalogDigest:digest(dc),node:process.version};
}
module.exports={fid,validateEvidence,validateCatalog,run};
if(require.main===module)console.log(JSON.stringify(run(),null,2));
