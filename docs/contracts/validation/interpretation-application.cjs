'use strict';
// Release gate. Only --write-report writes execution evidence; no secrets/live provider.
const fs=require('node:fs'),cp=require('node:child_process'),path=require('node:path'),assert=require('node:assert/strict');
const run=(cmd,args)=>cp.execFileSync(cmd,args,{encoding:'utf8',maxBuffer:96*1024*1024});
const root='docs/contracts/';
process.stderr.write('Running retained full Period/Core/PHP/reference/OpenAPI/privacy regression\n');
const retained=JSON.parse(run(process.execPath,[root+'validation/period-application.cjs']));
assert.equal(retained.result,'PERIOD_ORCHESTRATION_IMPLEMENTATION_PASS');
process.stderr.write('Running Interpretation WordPress dispatcher and gateway adapter\n');
const rest=JSON.parse(run('docker',['compose','exec','-T','wordpress','php','/var/www/html/wp-content/plugins/love-fortune-core/tests/wordpress-interpretation-api.php']));
assert.equal(rest.result,'INTERPRETATION_WORDPRESS_E2E_PASS');
const mods=path.resolve('.tools/contract-validation/node_modules'),Ajv=require(path.join(mods,'ajv/dist/2020')).default,formats=require(path.join(mods,'ajv-formats'));
const ajv=new Ajv({strict:false,allErrors:true});formats(ajv);
for(const f of fs.readdirSync(root+'schemas'))ajv.addSchema(JSON.parse(fs.readFileSync(root+'schemas/'+f,'utf8')));
const schema=n=>ajv.getSchema('https://love-fortune.invalid/contracts/schemas/'+n+'.schema.json');
const response=schema('interpretation-response'),request=schema('interpretation-request');
let accepted=0,rejected=0;
function check(v,data,expected){assert.equal(v(data),expected,JSON.stringify(v.errors));expected?accepted++:rejected++;}
for(const data of Object.values(rest.responses))check(response,data,true);
const fixture=rest.responses.Compatibility_ai;
for(const [field,max] of [['summary',1200],['advice',1600]]){for(const n of [max,max+1]){const x=structuredClone(fixture);x[field]='😀'.repeat(n);check(response,x,n===max);}}
for(const field of ['strengths','challenges']){for(const n of [5,6]){const x=structuredClone(fixture);x[field]=Array.from({length:n},()=>({text:'x',evidenceRefs:['ft_synthetic']}));check(response,x,n===5);}for(const n of [500,501]){const x=structuredClone(fixture);x[field]=[{text:'😀'.repeat(n),evidenceRefs:['ft_synthetic']}];check(response,x,n===500);}}
for(const modify of [x=>x.meta.provider='fake',x=>x.meta.model='fake',x=>x.meta.aiPromptVersion='old',x=>x.extra=true]){const x=structuredClone(rest.responses.Daily_fallback);modify(x);check(response,x,false);}
for(const n of [65499,65500]){const token='a'.repeat(n-46)+'.b.'+'c'.repeat(43);check(request,{signedContext:token,locale:'ko-KR'},n===65499);assert.equal(Buffer.byteLength(JSON.stringify({signedContext:token,locale:'ko-KR'})),n+37);}
for(const field of ['question','relationshipType','tone','style','length','provider','model'])check(request,{signedContext:'a.b.'+'c'.repeat(43),[field]:'bad'},false);
run('git',['-c','core.safecrlf=false','diff','--check']);
const result={result:'INTERPRETATION_ROUTE_IMPLEMENTATION_PASS',retained,interpretation:{wordpress:rest.result,signedTypes:rest.signedTypes,responseExamples:Object.keys(rest.responses),schemaAccepted:accepted,schemaExpectedRejections:rejected,rate:rest.rate,privacy:rest.privacy,noStore:rest.noStore,providerCalls:rest.providerCalls,liveProvider:rest.liveProvider},gitDiffCheck:'PASS',commit:'NOT_COMMITTED',push:'NOT_PUSHED',limits:[
 'Controlled localized clause validation is deliberately fail-closed; unsupported free prose is repaired or falls back.',
 'No live vendor configured or contacted. Actual gateway adapter is exercised through WordPress HTTP interception.',
 ...rest.limits,
 'Vendor no-training/retention and infrastructure capture settings require deployment review. Prior nested readiness statements retain their historical scope.'
]};
if(process.argv.includes('--write-report'))fs.writeFileSync(root+'validation-results-interpretation-v1.json',JSON.stringify(result,null,2)+'\n');
console.log(JSON.stringify(result,null,2));
