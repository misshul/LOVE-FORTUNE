'use strict';
// Offline reference validator. No network or writes; supports an explicit staging directory.
const fs=require('node:fs'),path=require('node:path'),zlib=require('node:zlib'),crypto=require('node:crypto'),assert=require('node:assert/strict');
const sha=b=>crypto.createHash('sha256').update(b).digest('hex');
const canonical=x=>JSON.stringify(x,(_,v)=>v&&typeof v==='object'&&!Array.isArray(v)?Object.fromEntries(Object.keys(v).sort().map(k=>[k,v[k]])):v)+'\n';
const gcd=(a,b)=>{a=a<0n?-a:a;while(b){[a,b]=[b,a%b];}return a;};
const q=(n,d=1n)=>{n=BigInt(n);d=BigInt(d);assert(d!==0n);if(d<0n){n=-n;d=-d;}const g=gcd(n,d);return [n/g,d/g];};
const dec=x=>{const s=String(x);if(s.includes('/'))return q(...s.split('/'));const [a,b='']=s.split('.');return q(BigInt(a+(b||'')),10n**BigInt(b.length));};
const add=(a,b)=>q(a[0]*b[1]+b[0]*a[1],a[1]*b[1]),neg=a=>[-a[0],a[1]],sub=(a,b)=>add(a,neg(b)),mul=(a,b)=>q(a[0]*b[0],a[1]*b[1]),div=(a,b)=>q(a[0]*b[1],a[1]*b[0]);
const cmp=(a,b)=>{const n=a[0]*b[1]-b[0]*a[1];return n<0n?-1:n>0n?1:0;};
const ceil=a=>a[0]/a[1]+(a[0]>0n&&a[0]%a[1]!==0n?1n:0n);
const s=x=>BigInt(Date.parse(x.length===10?x+'T00:00:00Z':x+'Z'))/1000n;
const Q=x=>q(x), str=a=>a.join('/');
const load=(r,n)=>JSON.parse(fs.readFileSync(path.join(r,n),'utf8').replace(/^\uFEFF/,''));
const terms='CHUNFEN QINGMING GUYU LIXIA XIAOMAN MANGZHONG XIAZHI XIAOSHU DASHU LIQIU CHUSHU BAILU QIUFEN HANLU SHUANGJIANG LIDONG XIAOXUE DAXUE DONGZHI XIAOHAN DAHAN LICHUN YUSHUI JINGZHE'.split(' ');
function validate(r,allowMissingGoldens=false){
 const manifest=load(r,'source-manifest.json'),dt=load(r,'delta-t-s2020.json'),drift=load(r,'historical-drift.json'),leap=load(r,'leap-seconds.json'),artifact=load(r,'solar-terms.json');
 for(const [n,h] of Object.entries(manifest.files))assert.equal(sha(fs.readFileSync(path.join(r,'sources',n))),h,n);
 const bridge=load(r,'bridge.json');assert.equal(bridge.version,'SAJU_TIME_SCALE_BRIDGE_V1');assert.equal(bridge.comparisonAxis,'SERVICE_PROLEPTIC_POSIX_V1');assert.equal(bridge.futureTaiMinusUtcSeconds,'37');assert.equal(bridge.ttMinusTaiSeconds,'32.184');assert.deepEqual(bridge.runtimeExternalDependencies,[]);assert.equal(dt.version,'NAOJ_S2020_ANNUAL_20260924_V1');assert.equal(drift.version,'USNO_DRIFT_20260924_V1');assert.equal(leap.version,'USNO_TAI_UTC_20260924_V1');
 const raw=JSON.parse(zlib.gunzipSync(fs.readFileSync(path.join(r,'sources/naoj-tt0.json.gz'))));
 const dr=JSON.parse(zlib.gunzipSync(fs.readFileSync(path.join(r,'sources/naoj-s2020.json.gz'))));
 const rawManifest=fs.readFileSync(path.join(r,'sources/tt-raw-manifest.txt'),'utf8');
 assert.equal(sha(rawManifest),'12af0c28a1c8f72ac1ced8deadd0fd3cff66d76eeb364664c18f314a1bae7744');assert.equal(manifest.rawManifestChecksum,sha(rawManifest));
 const source=fs.readFileSync(path.join(r,'sources/tai-utc.dat'),'utf8');assert.equal(sha(source),drift.checksum);assert.equal(sha(source),leap.checksum);
 const copy=structuredClone(artifact),saved=copy.metadata.artifactChecksum;delete copy.metadata.artifactChecksum;assert.equal(sha(canonical(copy)),saved);
 for(const[n,h]of Object.entries(artifact.metadata.inputChecksums))assert.equal(sha(fs.readFileSync(path.join(r,n))),h,n);
 assert.equal(artifact.metadata.comparisonAxis,'SERVICE_PROLEPTIC_POSIX_V1');assert.equal(artifact.metadata.bridgeVersion,'SAJU_TIME_SCALE_BRIDGE_V1');assert.equal(artifact.metadata.referenceVersion,'NAOJ_SOLAR_TERMS_1899_2100_V1');
 assert.equal(artifact.metadata.sourcePrecision,'MINUTE');assert.equal(artifact.metadata.roundingRule,'MATHEMATICAL_CEIL_EXACT_SECONDS_TIMES_1000000');assert.equal(artifact.metadata.coordinateUnit,'SIGNED_INTEGER_MICROSECONDS_DECIMAL_STRING');
 assert.equal(artifact.metadata.futureConvention,'FROZEN_LAST_KNOWN_TAI_MINUS_UTC_V1');assert.equal(artifact.metadata.preUtcConvention,'HISTORICAL_CIVIL_APPROXIMATION');
 assert.equal(leap.futureTaiMinusUtcSeconds,'37');assert.equal(leap.intervals.at(-1).taiMinusUtcSeconds,'37.0');assert.equal(leap.knownSnapshotBoundary,'2026-09-24T00:00:00');
 assert.equal(dt.values.length,202);assert.equal(manifest.entries.length,202);assert.equal(drift.intervals.length,13);assert.equal(leap.intervals.length,28);
 const months='JAN FEB MAR APR MAY JUN JUL AUG SEP OCT NOV DEC'.split(' ');const original=[];
 for(const line of source.trim().split(/\r?\n/)){
  const m=line.match(/(\d{4})\s+([A-Z]{3})\s+(\d+)\s+=JD\s+([\d.]+)\s+TAI-UTC=\s+([\d.]+)\s+S\s+\+\s+\(MJD -\s*([\d.]+)\)\s+X\s+([\d.]+)/);assert(m);
  original.push({date:`${m[1]}-${String(months.indexOf(m[2])+1).padStart(2,'0')}-${m[3].padStart(2,'0')}T00:00:00`,base:m[5],ref:m[6],rate:m[7]});
 }
 for(let i=0;i<41;i++){
  const row=i<13?drift.intervals[i]:leap.intervals[i-13],o=original[i],start=row.effectiveStart||row.effectiveDate;
  assert.equal(start,o.date);assert.equal(row.effectiveEnd,original[i+1]?.date||'2101-01-01T00:00:00');
  assert.equal(i<13?row.baseOffset:row.taiMinusUtcSeconds,o.base);
  if(i<13){assert.equal(row.referenceMJD,o.ref);assert.equal(row.dailyDrift,o.rate);assert.equal(sha(row.sourceRow),row.sourceRowHash);}
 }
 // Independent inversion in microsecond coordinates: solve tUs = serviceUs + drift(serviceUs).
 function driftInverse(t,row){
  const rate=dec(row.dailyDrift),base=dec(row.baseOffset),mjd0=dec(row.referenceMJD);
  const intercept=mul(add(add(dec('32.184'),base),mul(sub(Q(40587),mjd0),rate)),Q(1000000));
  return div(sub(mul(t,Q(1000000)),intercept),add(Q(1),div(rate,Q(86400))));
 }
 function matches(t,y){const out=[];
  if(y>=1899&&y<=2100){const us=mul(sub(t,dec(dt.values[y-1899].deltaTSeconds)),Q(1000000));if(cmp(us,Q(s('1961-01-01')*1000000n))<0)out.push({us,era:'PRE_1961_S2020',key:String(y)});}
  for(const row of drift.intervals){const us=driftInverse(t,row);if(cmp(us,Q(s(row.effectiveStart)*1000000n))>=0&&cmp(us,Q(s(row.effectiveEnd)*1000000n))<0)out.push({us,era:'UTC_1961_1971_DRIFT',key:row.effectiveStart});}
  for(const row of leap.intervals){const us=mul(sub(sub(t,dec('32.184')),dec(row.taiMinusUtcSeconds)),Q(1000000));if(cmp(us,Q(s(row.effectiveDate)*1000000n))>=0&&cmp(us,Q(s(row.effectiveEnd)*1000000n))<0)out.push({us,era:cmp(us,Q(s(leap.knownSnapshotBoundary)*1000000n))>=0?'FROZEN_FUTURE_37':'KNOWN_TAI_UTC',key:row.effectiveDate});}
  return out;
 }
 const expected=[],eraCounts={};let raw24=0;const ids=new Set();let previous=null;
 for(let y=1899;y<=2100;y++){
  const entry=manifest.entries[y-1899],dv=dt.values[y-1899];assert.equal(entry.year,y);assert.equal(dv.year,y);assert.equal(dv.model,'S2020');
  const rawHash=sha(Buffer.from(raw[y],'latin1'));assert.equal(entry.ttRawSha256,rawHash);assert(rawManifest.includes(`${y} ${rawHash}\n`));
  assert.equal(sha(Buffer.from(dr[y],'latin1')),dv.checksum);assert.equal(dv.checksum,entry.deltaTRawSha256);
  assert.equal(dr[y].match(/Delta;T = ([-\d.]+)<sup>s<\/sup> \(S2020\)/)[1],dv.deltaTSeconds);
  assert(raw[y].includes('Delta;T = 0.0'));assert(raw[y].includes('Gregorian Calendar'));assert(raw[y].includes('LST:UT+0'));
  const rows=[...raw[y].matchAll(/(<tr><td>(\d{4}-\d\d-\d\d)<\/td>\s*<td>(\d\d:\d\d)<\/td>\s*<td>Sun<\/td>\s*<td>24 Solar Terms<\/td>\s*<td>.*?<\/td>\s*<td>(.*?) \(&lambda; = (\d+)&deg;\)<\/td><\/tr>)/g)];assert.equal(rows.length,24);
  rows.forEach((row,i)=>{
   const angle=Number(row[5]);assert.equal(angle,(285+i*15)%360);const [h,m]=row[3].split(':').map(Number);assert(h<=24&&m<60&&(h!==24||m===0));if(h===24)raw24++;
   const t=s(row[2])+BigInt(h*3600+m*60),normalized=new Date(Number(t)*1000).toISOString().slice(0,19),solutions=matches(Q(t),Number(normalized.slice(0,4)));assert.equal(solutions.length,1);
   const sol=solutions[0],b=ceil(sol.us),event=artifact.events[expected.length];assert(b>=-(2n**63n)&&b<2n**63n);assert(previous===null||b>previous);previous=b;
   const id=`SOLAR_${y}_${terms[angle/15]}`;assert(!ids.has(id));ids.add(id);
   assert.equal(event.eventId,id);assert.equal(event.year,y);assert.equal(event.termId,terms[angle/15]);assert.equal(event.solarLongitude,angle);assert.equal(event.sourceDate,row[2]);assert.equal(event.sourceHourMinute,row[3]);assert.equal(event.sourceRowHash,sha(Buffer.from(row[1],'latin1')));assert.equal(event.normalizedTT,normalized);
   assert.equal(event.serviceBoundaryUs,String(b));assert.equal(event.exactServiceCoordinateSeconds,str(div(sol.us,Q(1000000))));assert.equal(event.eraConversionType,sol.era);assert.equal(event.conversionIntervalKey,sol.key);
   assert.equal(event.solarReferenceVersion,artifact.metadata.referenceVersion);assert.equal(event.timeScaleBridgeVersion,artifact.metadata.bridgeVersion);assert.equal(event.sourcePrecision,'MINUTE');assert.equal(event.sourceTimeScale,'TT_EQUIVALENT_NAOJ_EXPLICIT_DT_ZERO');assert.equal(event.sourceBoundaryConvention,'FROZEN_TT_DISPLAYED_MINUTE_START');
   assert.equal(event.deltaTSourceVersion,sol.era==='PRE_1961_S2020'?dt.version:null);assert.equal(event.historicalUtcDriftTableVersion,sol.era==='UTC_1961_1971_DRIFT'?drift.version:null);assert.equal(event.leapSecondTableVersion,['KNOWN_TAI_UTC','FROZEN_FUTURE_37'].includes(sol.era)?leap.version:null);assert.equal(event.futureConvention,sol.era==='FROZEN_FUTURE_37'?leap.futureConvention:null);
   assert(cmp(Q(b),sol.us)>=0&&cmp(Q(b-1n),sol.us)<0);eraCounts[sol.era]=(eraCounts[sol.era]||0)+1;expected.push({eventId:id,normalizedTT:normalized,serviceBoundaryUs:String(b),eraConversionType:sol.era,conversionIntervalKey:sol.key,exactServiceCoordinateSeconds:event.exactServiceCoordinateSeconds});
  });
 }
 assert.equal(expected.length,4848);assert.equal(artifact.events.length,4848);assert.equal(artifact.metadata.eventCount,4848);assert.equal(raw24,2);
 const rounding=[['100.2','101'],['100','100'],['-100.2','-100'],['99.999999','100'],['100.000001','101'],['-100.000001','-100'],['-99.999999','-99']];for(const[a,b]of rounding)assert.equal(String(ceil(dec(a))),b);
 // Every drift/leap interval endpoint: forward->inverse; explicit TT overlaps/gaps are rejected rather than hidden.
 const intervals=[...drift.intervals.map(x=>({...x,start:x.effectiveStart})),...leap.intervals.map(x=>({...x,start:x.effectiveDate,baseOffset:x.taiMinusUtcSeconds,dailyDrift:'0',referenceMJD:'0'}))];
 const forward=(u,row)=>add(add(u,dec('32.184')),add(dec(row.baseOffset),mul(sub(add(Q(40587),div(u,Q(86400))),dec(row.referenceMJD)),dec(row.dailyDrift))));
 let transitionProbes=0,ttGaps=0,ttOverlaps=0;
 for(let i=0;i<intervals.length;i++){
  const row=intervals[i];for(const u of [Q(s(row.start)),sub(Q(s(row.effectiveEnd)),dec('0.000001'))]){
   const t=forward(u,row),all=matches(t,1965);assert(all.some(v=>cmp(v.us,mul(u,Q(1000000)))===0));transitionProbes++;
  }
  if(i){const at=Q(s(row.start)),a=forward(at,intervals[i-1]),b=forward(at,row),c=cmp(a,b);if(c){const t=div(add(a,b),Q(2)),n=matches(t,1965).length;if(c<0){assert.equal(n,0);ttGaps++;}else{assert.equal(n,2);ttOverlaps++;}transitionProbes++;}}
 }
 // Include the model/official era seam. It may create a TT gap/overlap under the approved approximation.
 const cut=Q(s('1961-01-01')),before=add(cut,dec(dt.values[1961-1899].deltaTSeconds)),after=forward(cut,intervals[0]);
 const seam=cmp(before,after);if(seam){assert.equal(matches(div(add(before,after),Q(2)),1961).length,seam<0?0:2);transitionProbes++;}
 const future=s(leap.knownSnapshotBoundary);for(const off of [-1n,0n,1n]){const t=add(Q(future+off),dec('69.184')),sol=matches(t,2026);assert.equal(sol.length,1);assert.equal(sol[0].era,off<0n?'KNOWN_TAI_UTC':'FROZEN_FUTURE_37');transitionProbes++;}
 let goldenCount=0;
 if(fs.existsSync(path.join(r,'goldens.json'))){const gold=load(r,'goldens.json');assert(gold.events.length>=23);for(const g of gold.events){assert.deepEqual(expected.find(x=>x.eventId===g.eventId),g);const b=BigInt(g.serviceBoundaryUs);for(const[o,want]of [[-1n,'BEFORE'],[0n,'AFTER'],[1n,'AFTER']])assert.equal(b+o<b?'BEFORE':'AFTER',want);goldenCount++;}for(const y of [1900,1950,1961,1965,1971,1972,1980,2000,2017,2050,2099])assert(gold.events.some(e=>e.eventId===`SOLAR_${y}_LICHUN`));assert.equal(new Set(gold.events.filter(e=>e.eventId.startsWith('SOLAR_2000_')).map(e=>e.eventId)).size,12);}else assert(allowMissingGoldens,'Missing goldens');
 return {report:{result:'PASS',events:4848,years:202,missing:0,duplicate:0,orderingAnomaly:0,identityMismatch:0,raw2400:raw24,eraCounts,roundingCases:rounding.length,transitionProbes,expectedTtGaps:ttGaps,expectedTtOverlaps:ttOverlaps,pre1961Seam:seam<0?'GAP':seam>0?'OVERLAP':'CONTIGUOUS',goldenCount,checksum:saved},expected};
}
module.exports={validate};
if(require.main===module){const r=process.argv[2]||path.join(__dirname,'../references/solar-terms-v1');console.log(JSON.stringify(validate(r).report,null,2));}
