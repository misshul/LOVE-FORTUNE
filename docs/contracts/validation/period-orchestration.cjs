'use strict';
// Production wire checks. Synthetic runtime output is not an independent numeric golden.
const fs=require('node:fs'),path=require('node:path'),cp=require('node:child_process'),assert=require('node:assert/strict'),crypto=require('node:crypto');
const root='docs/contracts/',plugin='wp-content/plugins/love-fortune-core/';
const mods=path.resolve('.tools/contract-validation/node_modules');
const Ajv=require(path.join(mods,'ajv/dist/2020')).default,formats=require(path.join(mods,'ajv-formats'));
const ajv=new Ajv({strict:false,allErrors:true});formats(ajv);
const M=require('./zodiac-model.cjs');ajv.removeKeyword('multipleOf');ajv.addKeyword({keyword:'multipleOf',type:'number',schemaType:'number',validate:(s,v)=>M.div(M.parse(v),M.parse(s))[1]===1n});
for(const f of fs.readdirSync(root+'schemas'))ajv.addSchema(JSON.parse(fs.readFileSync(root+'schemas/'+f,'utf8')));
const schema=n=>ajv.getSchema('https://love-fortune.invalid/contracts/schemas/'+n+'.schema.json');

const output=JSON.parse(cp.execFileSync('docker',['compose','exec','-T','wordpress','php','/var/www/html/'+plugin+'tests/period-fixtures.php'],{encoding:'utf8',maxBuffer:16*1024*1024}));
let accepted=0,rejected=0;const check=(name,v,expected)=>{const validate=schema(name);assert.equal(validate(v),expected,name+' '+JSON.stringify(validate.errors));expected?accepted++:rejected++;};
for(const [name,x] of Object.entries(output.periods)){
 check('period-result',x.unsigned,true);check('period-response',x.external,true);
 const def={WEEK:'Weekly',MONTH:'Monthly',YEAR:'Yearly'}[name.split('_')[0]];
 assert(ajv.getSchema('https://love-fortune.invalid/contracts/schemas/period-response.schema.json#/$defs/'+def)(x.external));
 for(const config of ['CONFIG_DAILY_V1','CONFIG_COMBINED_LIFETIME_V1']){const bad=structuredClone(x.external);bad.meta.versions.configVersion=config;check('period-response',bad,false);assert(!ajv.getSchema('https://love-fortune.invalid/contracts/schemas/period-response.schema.json#/$defs/'+def)(bad));}
 if(x.payload){check('signed-context',x.payload,true);check('period-response',x.unsigned,false);const bad=structuredClone(x.payload);bad.result.signedInterpretationContext=x.external.signedInterpretationContext;check('signed-context',bad,false);
 const parts=x.external.signedInterpretationContext.split('.');assert.equal(Buffer.from(M.canon(x.payload)).toString('base64url'),parts[1]);assert.equal(crypto.createHmac('sha256','SYNTHETIC_PERIOD_KEY_NOT_FOR_PRODUCTION_0001').update(parts[0]+'.'+parts[1]).digest('base64url'),parts[2]);
 for(const config of ['CONFIG_DAILY_V1','CONFIG_COMBINED_LIFETIME_V1']){const b=structuredClone(x.payload);b.configVersion=b.result.meta.versions.configVersion=config;check('signed-context',b,false);}
 }
 if(!x.payload){const b=structuredClone(x.external);b.signedInterpretationContext='fake';check('period-response',b,false);}
 const privateField=structuredClone(x.unsigned);privateField.candidateId='C0';check('period-result',privateField,false);
 if(name.endsWith('trend3')){assert.equal(x.unsigned.periodDelta,3);assert.equal(x.unsigned.trendStatus,'STABLE');}
 if(name.endsWith('trend8')){assert.equal(x.unsigned.periodDelta,8);assert.equal(x.unsigned.trendStatus,'NOTICEABLE');}
}
// Independent BigInt square-root rounding against PHP exact rational implementation.
function sqrt4(text){const [n,d]=M.parse(text);const scaled=n*100000000n;let lo=0n,hi=500001n;while(hi-lo>1n){const m=(lo+hi)/2n;if(m*m*d<=scaled)lo=m;else hi=m;}if(4n*scaled>=(2n*lo+1n)**2n*d)lo++;return String(lo/10000n)+'.'+String(lo%10000n).padStart(4,'0');}
for(const [v,result] of Object.entries(output.sqrt))assert.equal(sqrt4(v),result);
if(process.argv.includes('--write-examples')){const dir=root+'examples/period-v1/';fs.mkdirSync(dir,{recursive:true});for(const [n,x] of Object.entries(output.periods))fs.writeFileSync(dir+n.toLowerCase()+'.json',JSON.stringify(x,null,2)+'\n');fs.writeFileSync(dir+'sqrt-golden.json',JSON.stringify(output.sqrt,null,2)+'\n');}
for(const [n,x] of Object.entries(output.periods)){const file=root+'examples/period-v1/'+n.toLowerCase()+'.json';if(fs.existsSync(file))assert.deepEqual(x,JSON.parse(fs.readFileSync(file,'utf8')),'Frozen illustrative release example drift');}
console.log(JSON.stringify({result:'PASS',accepted,rejected,sqrtCrossRuntime:Object.keys(output.sqrt).length,canonicalHmac:'PASS',directDefsConfig:'PASS',exactTrendWire:'PASS'},null,2));
