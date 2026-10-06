'use strict';
const fs=require('node:fs'),cp=require('node:child_process'),assert=require('node:assert/strict'),path=require('node:path');
const run=(cmd,args)=>cp.execFileSync(cmd,args,{encoding:'utf8',maxBuffer:64*1024*1024});
const root='docs/contracts/';
const retained=JSON.parse(run(process.execPath,[root+'validation/daily-orchestration-application.cjs']));
assert.equal(retained.result,'DAILY_ORCHESTRATION_IMPLEMENTATION_PASS');
const period=JSON.parse(run(process.execPath,[root+'validation/period-orchestration.cjs']));
const rest=JSON.parse(run('docker',['compose','exec','-T','wordpress','php','/var/www/html/wp-content/plugins/love-fortune-core/tests/wordpress-period-api.php']));
assert.equal(rest.result,'PERIOD_WORDPRESS_E2E_PASS');
const mods=path.resolve('.tools/contract-validation/node_modules');const Ajv=require(path.join(mods,'ajv/dist/2020')).default,formats=require(path.join(mods,'ajv-formats')),M=require('./zodiac-model.cjs');const ajv=new Ajv({strict:false,allErrors:true});formats(ajv);ajv.removeKeyword('multipleOf');ajv.addKeyword({keyword:'multipleOf',type:'number',schemaType:'number',validate:(s,v)=>M.div(M.parse(v),M.parse(s))[1]===1n});
for(const f of fs.readdirSync(root+'schemas'))ajv.addSchema(JSON.parse(fs.readFileSync(root+'schemas/'+f,'utf8')));
let responses=0;for(const [name,v] of Object.entries(rest.examples)){const s=ajv.getSchema('https://love-fortune.invalid/contracts/schemas/'+('days'in v?'daily-range-response':'period-response')+'.schema.json');assert(s(v),name+JSON.stringify(s.errors));responses++;}
const range=rest.examples.range1;const validate=ajv.getSchema('https://love-fortune.invalid/contracts/schemas/daily-range-response.schema.json');
for(const mutate of [x=>x.signedInterpretationContext='bad',x=>x.days[0].signedInterpretationContext='bad',x=>x.days[0].meta.versions.configVersion='CONFIG_PERIOD_V1',x=>x.meta.versions.configVersion='CONFIG_DAILY_V1']){const b=structuredClone(range);mutate(b);assert(!validate(b));}
run('git',['-c','core.safecrlf=false','diff','--check']);
const files=run('git',['status','--short']).trim().split(/\r?\n/);
const result={result:'PERIOD_ORCHESTRATION_IMPLEMENTATION_PASS',retained,period,rest:{result:rest.result,metrics:rest.metrics,validatedResponses:responses,negativeRangeMutations:4},gitDiffCheck:'PASS',files,commit:'NOT_COMMITTED',push:'NOT_PUSHED',limits:['Nested retained report readiness statements describe their historical release scope.','WordPress CLI dispatcher uses real registered routes; partial/empty/error and required-signing failure paths use explicit injected outputs/faults.','Performance metrics are local dispatcher elapsed/CPU/peak memory, not production network SLO certification.','Operational secrets and CDN/WAF/APM body capture require separate deployment verification.']};
if(process.argv.includes('--write-report'))fs.writeFileSync(root+'validation-results-period-v1.json',JSON.stringify(result,null,2)+'\n');
console.log(JSON.stringify(result,null,2));
