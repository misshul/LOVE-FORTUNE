const fs=require('fs'),assert=require('assert/strict'),crypto=require('crypto'),vm=require('vm'),path=require('path');
const dir=path.resolve('docs/contracts'),read=p=>JSON.parse(fs.readFileSync(path.join(dir,p),'utf8'));
const c=read('rules/astrology-rules.json'),v=read('examples/astrology-v1-semantic-vectors.json'),order=['SUN','MOON','MERCURY','VENUS','MARS','JUPITER','SATURN','URANUS','NEPTUNE','PLUTO'],personal=order.slice(0,5),aspects={C:'CONJUNCTION',T:'TRINE',S:'SEXTILE',Q:'SQUARE',O:'OPPOSITION'};
const key=(a,b)=>[a,b].sort((a,b)=>order.indexOf(a)-order.indexOf(b)).join('_');
assert.deepEqual(c.canonicalPlanetOrder,order);
const overrides=new Map(Object.entries(c.referenceTables.pairOverrides).map(([p,w])=>[key(...p.split('-')),w]));
assert.equal(overrides.size,16);
const approved=new Map;let pair,entry;
for(const line of fs.readFileSync(path.join(dir,'astrology-v1-mapping-approval.md'),'utf8').split(/\r?\n/)){
 let m=line.trim().match(/^([A-Z]+) \u00d7 ([A-Z]+)$/);
 if(m&&order.includes(m[1])&&order.includes(m[2])){pair=[m[1],m[2]];entry=null;continue;}
 m=line.trim().match(/^([CTSQO]):$/);if(m&&pair){const id='ASTRO_'+key(...pair)+'_'+aspects[m[1]];assert(!approved.has(id));entry=[];approved.set(id,entry);continue;}
 m=line.trim().match(/^([A-Z_]+):([+-]\d+\.\d+)$/);if(m&&entry){let value=Number(m[2]);entry.push({category:m[1],direction:value>0?'POSITIVE':value<0?'NEGATIVE':'NEUTRAL',signedValue:value});}
}
const expectedAngles={CONJUNCTION:0,SEXTILE:60,SQUARE:90,TRINE:120,OPPOSITION:180};
function table(section){return Object.fromEntries([...section.matchAll(/^([A-Za-z_]+(?:-[A-Za-z_]+)?)\s+([0-9]+(?:\.[0-9]+)?)/gm)].map(m=>[m[1].toUpperCase(),Number(m[2])]));}
const score=fs.readFileSync(path.resolve('docs/03_SCORE_SPEC.md'),'utf8'),section=n=>score.split(new RegExp('^# '+n+'\\.','m'))[1].split(/^# \d+\./m)[0];
assert.deepEqual(c.referenceTables.planetWeights,table(section(13)));assert.deepEqual(c.referenceTables.pairOverrides,table(section(14)));
for(const [aspect,def]of Object.entries(c.aspectDefinitions)){assert.equal(def.exactAngle,expectedAngles[aspect]);assert.equal(def.baseWeight,table(section(12))[aspect]);assert.equal(def.baseOrb,c.referenceTables.baseOrbs[aspect]);}
const pairWeight=(a,b)=>overrides.get(key(a,b))??Math.sqrt(c.referenceTables.planetWeights[a]*c.referenceTables.planetWeights[b]);
function validate(rows){assert.equal(rows.length,109);const ids=new Set;
 for(const r of rows){assert(!ids.has(r.ruleId));ids.add(r.ruleId);assert(order.indexOf(r.planetA)<=order.indexOf(r.planetB));assert.equal(r.ruleId,'ASTRO_'+key(r.planetA,r.planetB)+'_'+r.aspect);assert.deepEqual(r.categoryMappings,approved.get(r.ruleId));assert(r.enabled&&!r.contextOnly&&r.requiresBirthTime===false);
 assert.equal(new Set(r.categoryMappings.map(m=>m.category)).size,r.categoryMappings.length);assert(r.categoryMappings.length);
 assert.equal(r.baseWeight,c.aspectDefinitions[r.aspect].baseWeight);assert.equal(r.ruleWeight,1);assert.equal(r.pairWeight,pairWeight(r.planetA,r.planetB));assert.equal(r.pairWeightSource,overrides.has(key(r.planetA,r.planetB))?'OVERRIDE':'GENERIC');
 assert.equal(pairWeight(r.planetA,r.planetB),pairWeight(r.planetB,r.planetA));
 assert(personal.includes(r.planetA));assert(order.indexOf(r.planetB)<7);if(r.planetB==='JUPITER')assert(['CONJUNCTION','TRINE','SEXTILE'].includes(r.aspect));if(r.planetB==='SATURN'){assert(r.planetA!=='MARS');assert(!(r.planetA==='MERCURY'&&r.aspect==='CONJUNCTION'));}
 }}
validate(c.rules);
let negative=0;for(const mutate of [r=>r[0].ruleId='ASTRO_WRONG',r=>r[0].pairWeight+=.01,r=>r[0].pairWeightSource='GENERIC',r=>r[1]=structuredClone(r[0]),r=>r[0].categoryMappings.push(structuredClone(r[0].categoryMappings[0])),r=>r[0].categoryMappings[0].signedValue=.123]){let rows=structuredClone(c.rules);mutate(rows);assert.throws(()=>validate(rows));negative++;}
const orb=(A,B,aspect)=>Math.min(10,c.aspectDefinitions[aspect].baseOrb+Math.max(c.referenceTables.planetOrbAdjustments[A],c.referenceTables.planetOrbAdjustments[B]));
function distance(a,b){a=((a%360)+360)%360;b=((b%360)+360)%360;let d=Math.abs(a-b);return Math.min(d,360-d);}
function detect(r,a,b){let delta=Math.abs(distance(a,b)-c.aspectDefinitions[r.aspect].exactAngle),limit=orb(r.planetA,r.planetB,r.aspect);return delta<=limit?{orb:delta,orbCloseness:1-delta/limit}:undefined;}
for(const t of v.angles)assert.equal(distance(t.a,t.b),t.distance);
for(const t of v.orbs)assert.equal(orb(t.planetA,t.planetB,t.aspect),t.expected);
for(const t of v.detection){let r=c.rules.find(r=>r.ruleId===t.ruleId),result=detect(r,t.a,t.b);assert.equal(!!result,t.active);if(t.active)assert.equal(result.orbCloseness,t.orbCloseness);else assert.equal(result,undefined);}
let boundaries=0;for(const r of c.rules){let exact=c.aspectDefinitions[r.aspect].exactAngle,limit=orb(r.planetA,r.planetB,r.aspect);assert(limit>0&&limit<=10);const angle=offset=>exact===180?180-offset:exact+offset;assert.equal(detect(r,0,angle(0)).orbCloseness,1);assert.equal(detect(r,0,angle(limit)).orbCloseness,0);assert.equal(detect(r,0,angle(limit+.001)),undefined);boundaries+=3;}
for(const t of v.unknownTime){let confidence=t.values.length?(1-(Math.max(...t.values)-Math.min(...t.values))/2)*t.values.length/t.total:0;assert.equal(confidence,t.confidence);}
const {canon}=vm.runInNewContext(fs.readFileSync(path.join(__dirname,'canonicalizer.cjs'),'utf8')+';({canon})');
const identityKeys=['planetA','planetB','aspect','pillar','relation','referenceId','ruleVariant'];
function fid(f){let identity={ruleId:f.ruleId,subject:f.subject,category:f.category,period:f.period,source:f.source,metadata:Object.fromEntries(identityKeys.filter(k=>Object.hasOwn(f.metadata,k)).map(k=>[k,f.metadata[k]]))};return'ft_'+crypto.createHash('sha256').update(canon(identity)).digest('hex').slice(0,48);}
function project(r,detection,confidence){if(!detection)return[];return r.categoryMappings.map(m=>{let f={ruleId:r.ruleId,source:r.source,subject:'PAIR',category:m.category,direction:m.direction,signedValue:m.signedValue,rawValue:detection.orb,baseWeight:r.baseWeight,confidence,period:{type:'LIFETIME'},metadata:{planetA:r.planetA,planetB:r.planetB,aspect:r.aspect,orb:detection.orb,orbCloseness:detection.orbCloseness}};return{...f,featureId:fid(f)};});}
let mixed=c.rules.find(r=>r.ruleId==='ASTRO_VENUS_MARS_SQUARE'),features=project(mixed,detect(mixed,0,90),.5);assert.equal(features.length,3);assert.equal(new Set(features.map(f=>f.featureId)).size,3);for(const f of features){assert.equal(f.confidence,.5);assert.equal(fid({...f,metadata:{...f.metadata,orbCloseness:0}}),f.featureId);}assert.equal(project(mixed,undefined,.5).length,0);
let directional=c.rules.find(r=>r.ruleId==='ASTRO_SUN_MOON_TRINE'),df=project(directional,detect(directional,0,120),1)[0];assert.notEqual(fid(df),fid({...df,metadata:{...df.metadata,planetA:'MOON',planetB:'SUN'}}));
const weights=r=>r.baseWeight*r.ruleWeight*r.pairWeight;
// Compare the canonical example; raw IEEE-754 multiplication retains full precision.
assert.equal(weights(c.rules.find(r=>r.ruleId==='ASTRO_SUN_VENUS_TRINE')).toFixed(4),'0.9450');
let weightTests=0;for(const r of c.rules)for(const fc of [0,.5,1]){const exact=project(r,detect(r,0,c.aspectDefinitions[r.aspect].exactAngle),fc);let angle=c.aspectDefinitions[r.aspect].exactAngle,limit=orb(r.planetA,r.planetB,r.aspect);const edge=project(r,detect(r,0,angle===180?angle-limit:angle+limit),fc);for(let i=0;i<exact.length;i++){assert.equal(weights(r)*exact[i].confidence,weights(r)*edge[i].confidence);assert.equal(exact[i].signedValue,edge[i].signedValue);}weightTests++;}
const counts={personal:0,jupiter:0,saturn:0,mappings:0,positive:0,negative:0,neutral:0,mixed:0,override:0,generic:0};for(const r of c.rules){counts[r.planetB==='JUPITER'?'jupiter':r.planetB==='SATURN'?'saturn':'personal']++;counts[r.pairWeightSource.toLowerCase()]++;let pos=false,neg=false;for(const m of r.categoryMappings){counts.mappings++;counts[m.direction.toLowerCase()]++;pos||=m.signedValue>0;neg||=m.signedValue<0;}if(pos&&neg)counts.mixed++;}
assert.deepEqual(counts,{personal:75,jupiter:15,saturn:19,mappings:228,positive:170,negative:58,neutral:0,mixed:26,override:76,generic:33});
console.log(JSON.stringify({rules:c.rules.length,...counts,approvedMatrixComparison:'EXACT',semanticNegativeMutations:negative,angularTests:v.angles.length,orbExamples:v.orbs.length,detectionExamples:v.detection.length,boundaryTests:boundaries,unknownTimeTests:v.unknownTime.length,weightIndependenceTests:weightTests,featureProjection:'PASS',featureIdentity:'PASS',result:'PASS'}));

