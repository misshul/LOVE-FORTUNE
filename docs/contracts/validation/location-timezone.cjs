'use strict';
// Offline, read-only reference validator. Optional argument selects a staging directory.
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto'),assert=require('node:assert/strict');
const root=process.argv[2]||path.resolve(__dirname,'../references/location-timezone-v1');
const load=n=>JSON.parse(fs.readFileSync(path.join(root,n),'utf8').replace(/^\uFEFF/,''));
const canon=v=>JSON.stringify(v,(_,x)=>x&&typeof x==='object'&&!Array.isArray(x)?Object.fromEntries(Object.entries(x).sort(([a],[b])=>a<b?-1:a>b?1:0)):x)+'\n';
const sha=b=>crypto.createHash('sha256').update(b).digest('hex');
function sealed(v,field='artifactChecksum'){const c=structuredClone(v),h=c[field];delete c[field];assert.equal(sha(canon(c)),h);}
const loc=load('locations.json'),tz=load('timezones.json'),manifest=load('location-manifest.json'),registry=load('location-registry.json'),gold=load('goldens.json'),only=load('validation-only-locations.json'),long=load('longitude-goldens.json'),src=load('source-manifest.json');
const replay=load('replay-validation.json');
for(const [n,h]of Object.entries({...replay.files,...replay.inputFiles}))assert.equal(sha(fs.readFileSync(path.join(root,n))),h,'Pinned replay file: '+n);
sealed(loc);sealed(tz);assert.equal(loc.version,'SAJU_LOCATION_REFERENCE_V1');assert.equal(tz.version,'SAJU_TIMEZONE_REFERENCE_V1');assert.equal(tz.ianaTzdbVersion,'2026b');assert.equal(tz.sourceArchiveChecksum,'114543d9f19a6bfeb5bca43686aea173d38755a3db1f2eec112647ae92c6f544');
for(const [n,h]of Object.entries(src.files))assert.equal(sha(fs.readFileSync(path.join(root,'sources',n))),h,n);
assert.equal(loc.records.length,129);assert.equal(loc.recordCount,129);assert.equal(new Set(loc.records.map(r=>r.locationId)).size,129);assert.equal(new Set(loc.records.map(r=>r.sourceGeoNamesId)).size,129);
assert.equal(manifest.p3,'DEFERRED');assert.equal(manifest.p2CandidateCount,69);assert.equal(manifest.p2AcceptedCount,65);assert.equal(manifest.excludedCandidates.length,4);
assert.deepEqual(manifest.excludedCandidates.map(r=>r.sourceGeoNamesId).sort(),['5110266','5110302','5125771','5133273']);
for(const e of manifest.excludedCandidates){assert.equal(e.retainedPrimaryLocationId,'LOC000003');assert(!loc.records.some(r=>r.sourceGeoNamesId===e.sourceGeoNamesId));}
assert.deepEqual(registry.reservedNonPublicIds,['LOC000005','LOC000006']);
const productionZones=new Set(loc.records.map(r=>r.timezoneId));const zones=new Map(tz.zones.map(z=>[z.timezoneId,z]));assert.equal(zones.size,tz.zoneCount);
const begin=BigInt(tz.supportedFromUs),end=BigInt(tz.supportedToExclusiveUs);assert.equal(begin,-2240524800000000n);assert.equal(end,4133980800000000n);
let transitions=0;
for(const z of zones.values()){assert.equal(BigInt(z.transitions[0].serviceStartUs),begin);let prev=begin-1n;for(const t of z.transitions){const at=BigInt(t.serviceStartUs);assert(at>prev&&at<end);assert(Number.isInteger(t.offsetSeconds));assert.equal(typeof t.isDst,'boolean');prev=at;transitions++;}}
assert.equal(transitions,tz.transitionCount);
const micro=s=>{assert.match(s,/^-?\d+(?:\.\d{1,6})?$/);const sign=s.startsWith('-')?-1n:1n;const [a,b='']=s.replace('-','').split('.');const v=sign*(BigInt(a)*1000000n+BigInt(b.padEnd(6,'0')));assert(v>=-180000000n&&v<=180000000n);return v;};
const round=v=>{const n=4n*(v-127500000n);return (n<0?-1n:1n)*(((n<0?-n:n)+500000n)/1000000n);};
for(const s of ['180.000001','-180.000001','127.1234567','NaN','1e2',''])assert.throws(()=>micro(s));
const counts={};for(const r of loc.records){sealed(r,'referenceChecksum');assert.match(r.locationId,/^LOC\d{6}$/);assert(!registry.reservedNonPublicIds.includes(r.locationId));assert.equal(registry.entries.find(x=>x.locationId===r.locationId)?.sourceGeoNamesId,r.sourceGeoNamesId);const m=manifest.records.find(x=>x.locationId===r.locationId);assert(m);assert.equal(m.sourceGeoNamesId,r.sourceGeoNamesId);assert.equal(m.longitudeDecimal,r.longitudeDecimal);assert.equal(m.countryCode,r.countryCode);assert.equal(tz.aliases[m.sourceTimezoneId]||m.sourceTimezoneId,r.timezoneId);assert(zones.has(r.timezoneId));assert(!tz.aliases[r.timezoneId]);assert.equal(micro(r.longitudeDecimal),BigInt(r.longitudeMicrodegrees));const g=long.find(x=>x.locationId===r.locationId);assert.equal(round(micro(r.longitudeDecimal)),BigInt(g.correctionMinutes));assert.equal(g.longitudeMicrodegrees,r.longitudeMicrodegrees);counts[r.countryCode]=(counts[r.countryCode]||0)+1;}
assert.deepEqual(counts,{JP:47,KR:17,US:36,GB:7,AU:8,CA:12,NZ:2});
assert.equal(new Set(loc.records.filter(r=>r.countryCode==='JP').map(r=>r.admin1)).size,47);
assert.deepEqual(loc.records.filter(r=>r.countryCode==='JP').map(r=>r.admin1).sort(),Array.from({length:47},(_,i)=>String(i+1).padStart(2,'0')));
assert.deepEqual(loc.records.filter(r=>r.countryCode==='KR').map(r=>r.sourceGeoNamesId).sort(),'1835848 1838524 1843564 1835329 1835235 1841811 1833747 11523293 1846266 1835553 1845136 1845604 1845457 1840982 1844174 1846986 1846326'.split(' ').sort());
for(const r of loc.records){assert.equal(r.supportedFrom,'1900-01-01');assert.equal(r.supportedTo,'2099-12-31');assert(Number.isFinite(Number(r.latitudeDecimal))&&Math.abs(Number(r.latitudeDecimal))<=90);const m=manifest.records.find(x=>x.locationId===r.locationId);assert(['PPLC','PPLA','PPLA2','PPL'].includes(m.featureCode));if(!['KR','JP'].includes(r.countryCode)){assert(m.featureCode==='PPLC'||m.populationFromFrozenSource>=500000);assert.equal(m.selectionReason,m.featureCode==='PPLC'?'CAPITAL':'POPULATION_GE_500000');}}
for(const [id,g]of Object.entries({'LOC000001':'1850147','LOC000002':'1835848','LOC000003':'5128581','LOC000004':'2643743'}))assert.equal(loc.records.find(r=>r.locationId===id)?.sourceGeoNamesId,g);
assert.equal(only.length,2);for(const r of only){assert.equal(r.publicSelectable,false);assert(!('locationId'in r));assert(zones.has(r.timezoneId));assert(!loc.records.some(x=>x.sourceGeoNamesId===r.sourceGeoNamesId));}
const time=s=>BigInt(Date.parse(s+'Z'))*1000n;
function mappings(zone,s){const n=time(s),ts=zones.get(zone).transitions;return ts.flatMap((t,i)=>{const a=BigInt(t.serviceStartUs),b=i+1<ts.length?BigInt(ts[i+1].serviceStartUs):end,u=n-BigInt(t.offsetSeconds)*1000000n;return a<=u&&u<b?[{serviceStartUs:u.toString(),offsetSeconds:t.offsetSeconds}]:[];});}
for(const g of gold.pointMappings)assert.deepEqual(mappings(g.zone,g.local),g.expected);
for(const g of gold.skippedDates){const a=time(g.date+'T00:00:00'),b=a+86400000000n,ts=zones.get(g.zone).transitions;for(let i=0;i<ts.length;i++){const t=ts[i],off=BigInt(t.offsetSeconds)*1000000n,s=BigInt(t.serviceStartUs),e=i+1<ts.length?BigInt(ts[i+1].serviceStartUs):end;assert((s>a-off?s:a-off)>=(e<b-off?e:b-off));}}
for(const g of gold.longitudeBoundaries)assert.equal(Number(round(micro(g.longitudeDecimal))),g.correctionMinutes);
console.log(JSON.stringify({result:'PASS',locations:129,countryCounts:counts,productionZones:productionZones.size,totalZones:zones.size,transitionRows:transitions,pointGoldens:gold.pointMappings.length,skippedDates:gold.skippedDates.length,longitudeCases:long.length,negativeLongitudeCases:6,scope:'REFERENCE_ONLY_NOT_NATAL_E2E'},null,2));
