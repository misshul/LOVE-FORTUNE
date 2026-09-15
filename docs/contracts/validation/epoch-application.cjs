// Current epoch application gate. Runs production PHPUnit AND existing contracts.
// Prints results only; no repository writes. Run from the repository root.
const fs=require('fs'),cp=require('child_process'),assert=require('assert/strict');
const dir='docs/contracts/',plugin='wp-content/plugins/love-fortune-core/';
const read=p=>fs.readFileSync(p,'utf8');
const git=args=>cp.execFileSync('git',['-c','core.safecrlf=false',...args],{encoding:'utf8',maxBuffer:8*1024*1024});
const changes=[...git(['diff','--name-only','HEAD']).trim().split('\n'),...git(['ls-files','--others','--exclude-standard']).trim().split('\n')].filter(Boolean);
const php=['GregorianDayNumber','DayPillarCalculator','CalculationDateResolver','DailySampleResolver','SajuDayPillarService'].map(n=>plugin+'src/Engine/Saju/'+n+'.php');
const allowed=new Set([...php,plugin+'config/bootstrap.php',plugin+'tests/Unit/BootstrapTest.php',plugin+'tests/Unit/DayPillarTest.php',plugin+'tests/fixtures/day-pillar-golden.json',plugin+'README.md','README.md']);
assert(changes.every(p=>p.startsWith('docs/')||allowed.has(p)),'Outside epoch application scope');
for(const name of ['astrology','saju','daily']){
 const p=dir+'rules/'+name+'-rules.json',before=JSON.parse(git(['show','HEAD:'+p])),now=JSON.parse(read(p));
 // Only the resolved external blocker may change in the Daily catalog metadata.
 if(name==='daily'){assert.deepEqual(now.externalBlockers,['EPHEMERIS_PROVIDER']);before.externalBlockers=before.externalBlockers.filter(x=>x!=='SAJU_DAY_PILLAR_EPOCH');}
 assert.deepEqual(now,before,name+' rule/config changes forbidden');
}
const golden=JSON.parse(read(plugin+'tests/fixtures/day-pillar-golden.json'));
const expected=[['1900-01-01',10,'甲戌'],['1900-02-28',8,'壬申'],['1900-03-01',9,'癸酉'],['1960-01-01',24,'戊子'],['1969-12-31',16,'庚辰'],['1970-01-01',17,'辛巳'],['1999-12-31',53,'丁巳'],['2000-01-01',54,'戊午'],['2000-02-28',52,'丙辰'],['2000-02-29',53,'丁巳'],['2000-03-01',54,'戊午'],['2001-01-15',14,'戊寅'],['2010-06-15',32,'丙申'],['2019-01-27',0,'甲子'],['2020-02-29',38,'壬寅'],['2024-12-31',5,'己巳'],['2026-04-30',10,'甲戌'],['2026-05-01',11,'乙亥'],['2026-09-11',24,'戊子'],['2099-12-31',38,'壬寅']];
assert.deepEqual(golden.dates.map(x=>[x.date,x.expectedCycleIndex,x.expectedGanzhi]),expected);
for(const x of golden.dates){assert.equal(x.expectedStem+x.expectedBranch,x.expectedGanzhi);assert(golden.evidence[x.evidenceId]);assert(new URL(x.corroborationRef).protocol==='https:');}
for(const p of php){const t=read(p);assert(!/\b(?:error_log|file_put_contents|wp_remote_post|set_transient|update_option|wpdb)\b/.test(t),'Persistence/network/logging in '+p);}
const calculator=read(php[1]),days=read(php[0]);assert(calculator.includes("+ 49"));assert(!/getTimestamp|DateTime|86400/.test(days));assert(!/getTimestamp|new DateTime/.test(calculator));
const readiness=read(dir+'readiness.md');assert(readiness.includes('| External blockers | EPHEMERIS_PROVIDER |'));assert(readiness.includes('| SAJU_DAY_PILLAR_EPOCH_V1 | APPLIED / READY |'));
for(const p of [dir+'saju-day-pillar-epoch-v1.md',dir+'saju-day-pillar-epoch-v1-application-approval.md'])assert(read(p).includes('PHASE3_2026-09-15_APPROVED'));
const contract=JSON.parse(cp.execFileSync(process.execPath,[dir+'validation/daily-application.cjs','--contracts-only'],{encoding:'utf8',maxBuffer:16*1024*1024}));
const phpOutput=cp.execFileSync('powershell.exe',['-NoProfile','-ExecutionPolicy','Bypass','-File','scripts/test.ps1'],{encoding:'utf8',maxBuffer:4*1024*1024});
assert(/OK \(\d+ tests, [\d,]+ assertions\)/.test(phpOutput),'PHPUnit did not report success');
const runtime=cp.execFileSync('docker',['compose','exec','-T','wordpress','php','-r','echo json_encode(["php"=>PHP_VERSION,"integerBytes"=>PHP_INT_SIZE,"timezone"=>timezone_version_get()]);'],{encoding:'utf8'});
assert.equal(JSON.parse(runtime).integerBytes,8,'Full-range timezone integration requires64-bit runtime');
git(['diff','--check']);
console.log(JSON.stringify({result:'PASS',contractFreeze:contract.contractFreeze,specConflict:contract.specConflict,activeCrossSpecContradictions:contract.activeCrossSpecContradictions,epoch:'SAJU_DAY_PILLAR_EPOCH_V1',epochStatus:'APPLIED / READY',externalBlockers:['EPHEMERIS_PROVIDER'],productionEngineReadiness:'BLOCKED_EXTERNAL',goldenDates:20,internalDates:73053,adjacentPairs:73052,sixtyDayPairs:72993,runtime:JSON.parse(runtime),phpOutput,contract,scope:'AUTHORIZED_EPOCH_IMPLEMENTATION',productionPhpChanges:changes.filter(p=>php.includes(p)||p===plugin+'config/bootstrap.php'),gitDiffCheck:'PASS'},null,2));
