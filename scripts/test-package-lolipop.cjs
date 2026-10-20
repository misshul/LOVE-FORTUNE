'use strict';
const assert=require('node:assert/strict'),p=require('./package-lolipop.cjs');
let checks=0;const reject=f=>{assert.throws(f);checks++;};
p.validateList(p.required);checks++;
for(const name of ['RuntimeConfig','PrivateJsonConfig','RuntimeConfigException']){
  assert(p.allowed('src/Support/'+name+'.php'));checks++;
}
for(const file of ['config/private.json','private/love-fortune.json','operational/cron-wrapper.php']){
  reject(()=>p.validateList([...p.required,file]));
}
for(const f of p.required)reject(()=>p.validateList(p.required.filter(x=>x!==f)));
for(const f of ['tests/fixture.php','assets/js/.env','assets/js/test-secret.json','.git/config','config/references/private.env','.tools/a.log','assets/screenshots/a.png','src/tests/probe.php','../evil.php'])reject(()=>p.validateList([...p.required,f]));
reject(()=>p.validateList([...p.required,p.required[0]]));
const gate=()=>{let r={result:'SAJU_FEATURE_EXTRACTION_IMPLEMENTATION_PASS',full:{result:'PASS',mode:'FULL_APPLICATION',php:{lint:'PASS',tests:143,assertions:1263182},structural:{openapi:'PASS',schemaFiles:36}}};for(const result of ['PUBLIC_COMBINED_API_IMPLEMENTATION_PASS','DAILY_ORCHESTRATION_IMPLEMENTATION_PASS','PERIOD_ORCHESTRATION_IMPLEMENTATION_PASS','INTERPRETATION_ROUTE_IMPLEMENTATION_PASS'])r={result,retained:r};return r;};
const root=r=>{while(r.retained)r=r.retained;return r;};
p.validateGate(gate());checks++;
const future=gate();root(future).full.php={lint:'PASS',tests:999,assertions:2000000};p.validateGate(future);checks++;
for(const mutation of [r=>r.result='FAIL',r=>r.retained.result='FAIL',r=>root(r).full.mode='CONTRACTS_ONLY',r=>root(r).full.php.lint='FAIL',r=>root(r).full.php.tests=0,r=>root(r).full.structural.openapi='FAIL',r=>root(r).full.structural.schemaFiles=0]){const r=gate();mutation(r);reject(()=>p.validateGate(r));}
reject(()=>p.runJson(()=>{throw new Error('PHPUnit exit 1');},'node',[]));
reject(()=>p.runJson(()=>'{bad','node',[]));
reject(()=>p.scan(Buffer.from('C:/Users/example/private'),'probe'));
reject(()=>p.scan(Buffer.from('-----BEGIN RSA PRIVATE KEY-----'),'probe'));
p.scan(Buffer.from("getenv('LOVE_FORTUNE_SIGNING_KEYS')"),'loader.php');checks++;
console.log(JSON.stringify({result:'PACKAGING_TESTS_PASS',checks,futurePassingCounts:'ACCEPTED',nonzeroExit:'REJECTED',missingRequired:'REJECTED',unexpectedFiles:'REJECTED'}));
