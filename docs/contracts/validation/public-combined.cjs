'use strict';
// Current public contract gate. --write-examples creates illustrative synthetic wire examples, never engine goldens.
const fs=require('node:fs'),path=require('node:path'),cp=require('node:child_process'),assert=require('node:assert/strict'),crypto=require('node:crypto');
const root='docs/contracts/',dir=root+'examples/public-combined/',plugin='wp-content/plugins/love-fortune-core/';
const mods=path.resolve('.tools/contract-validation/node_modules');
const Ajv=require(path.join(mods,'ajv/dist/2020')).default,formats=require(path.join(mods,'ajv-formats'));
const ajv=new Ajv({strict:false,allErrors:true});formats(ajv);
const M=require('./zodiac-model.cjs');ajv.removeKeyword('multipleOf');ajv.addKeyword({keyword:'multipleOf',type:'number',schemaType:'number',validate:(s,v)=>M.div(M.parse(v),M.parse(s))[1]===1n});
for(const file of fs.readdirSync(root+'schemas'))ajv.addSchema(JSON.parse(fs.readFileSync(root+'schemas/'+file,'utf8')));
const schema=n=>ajv.getSchema('https://love-fortune.invalid/contracts/schemas/'+n+'.schema.json');
const output=JSON.parse(cp.execFileSync('docker',['compose','exec','-T','wordpress','php','/var/www/html/'+plugin+'tests/public-fixtures.php'],{encoding:'utf8',maxBuffer:4*1024*1024}));
if(process.argv.includes('--write-examples')){
 fs.mkdirSync(dir,{recursive:true});const put=(f,x)=>fs.writeFileSync(dir+f,JSON.stringify(x,null,2)+'\n');
 put('request-valid.json',output.request);put('compatibility-valid.json',output.response);put('signed-valid.json',output.payload);put('unsigned-valid.json',output.payload.result);
 const insufficient=structuredClone(output.payload.result);insufficient.overallScore=null;insufficient.status='INSUFFICIENT_DATA';insufficient.resultConfidence=0;insufficient.features=[];
 for(const c of Object.values(insufficient.categories)){c.score=null;c.status='INSUFFICIENT_DATA';c.resultConfidence=0;}put('insufficient-valid.json',insufficient);
 put('numeric-unsigned-invalid.json',output.payload.result);
 const bad=structuredClone(output.response);bad.meta.versions.scoreVersion='SCORE_ZODIAC_V1';put('old-version-invalid.json',bad);
 const lineage=structuredClone(output.response);lineage.features[0].candidateId='C0';put('lineage-invalid.json',lineage);
 const cases=[['request-valid','compatibility-request',true],['compatibility-valid','compatibility-response',true],['signed-valid','signed-context',true],['unsigned-valid','compatibility-result',true],['insufficient-valid','compatibility-response',true],['numeric-unsigned-invalid','compatibility-response',false],['old-version-invalid','compatibility-response',false],['lineage-invalid','compatibility-response',false]].map(([f,s,v])=>({file:f+'.json',schema:s,valid:v}));put('manifest.json',{provenance:'Synthetic illustrative API examples; engine regression expectations are independently preserved.',cases});
}
let count=0;for(const c of JSON.parse(fs.readFileSync(dir+'manifest.json','utf8')).cases){const validate=schema(c.schema);assert.equal(validate(JSON.parse(fs.readFileSync(dir+c.file,'utf8'))),c.valid,c.file+' '+JSON.stringify(validate.errors));count++;}
for(const [s,v] of [['compatibility-response',output.response],['signed-context',output.payload],['compatibility-result',output.payload.result]])assert(schema(s)(v),JSON.stringify(schema(s).errors));
assert(schema('compatibility-result')(output.singleResult),JSON.stringify(schema('compatibility-result').errors));
assert(output.singleResult.features.some(f=>f.source==='SAJU'));
for(const f of output.singleResult.features)assert.equal(f.featureId,M.featureId(f));
assert.deepEqual(output.response,JSON.parse(fs.readFileSync(dir+'compatibility-valid.json','utf8')),'Frozen illustrative response drift');
const token=output.response.signedInterpretationContext,parts=token.split('.');assert.equal(parts.length,3);
const payload=JSON.parse(Buffer.from(parts[1],'base64url'));assert.equal(Buffer.from(M.canon(payload)).toString('base64url'),parts[1]);
assert.equal(crypto.createHmac('sha256','SYNTHETIC_PUBLIC_KEY_NEVER_FOR_DEPLOYMENT_0001').update(parts[0]+'.'+parts[1]).digest('base64url'),parts[2]);
assert.deepEqual(payload.evidence,payload.result.features);assert.deepEqual(payload.zodiacContext,payload.result.zodiacContext);
for(const feature of payload.result.features){assert.equal(feature.featureId,M.featureId(feature));assert(!('identityVersion' in feature));}
const bad=structuredClone(payload);bad.scoreVersion='SCORE_ZODIAC_V1';assert(!schema('signed-context')(bad));
const oldConfig=structuredClone(payload);oldConfig.configVersion='synthetic-config-v1';assert(!schema('signed-context')(oldConfig));
const precise=structuredClone(output.response);precise.categories.ATTRACTION.coverage=.12345;assert(!schema('compatibility-response')(precise));
const recursive=structuredClone(payload);recursive.result.signedInterpretationContext=token;assert(!schema('signed-context')(recursive));
const YAML=require(path.join(mods,'yaml'));const api=YAML.parse(fs.readFileSync(root+'openapi.yaml','utf8'));let openapiExamples=0;
for(const item of Object.values(api.paths))for(const op of Object.values(item)){if(!op.responses)continue;for(const response of Object.values(op.responses))for(const media of Object.values(response.content||{}))for(const example of Object.values(media.examples||{})){if(!example.externalValue)continue;const dto=JSON.parse(fs.readFileSync(path.join(root,example.externalValue),'utf8'));const ref=media.schema.$ref;const check=schema(path.basename(ref,'.schema.json'));assert(check(dto),JSON.stringify(check.errors));openapiExamples++;}}
const protectedPaths=cp.execFileSync('git',['ls-files',plugin+'src/Engine',plugin+'src/Domain',plugin+'tests/fixtures',root+'rules',root+'product-scope.json',plugin+'config/zodiac.php'],{encoding:'utf8'}).trim().split(/\r?\n/).filter(Boolean);
for(const p of protectedPaths.filter(p=>!p.endsWith('/TimezoneReferenceRepository.php')))assert.equal(fs.readFileSync(p,'utf8').replace(/\r\n/g,'\n'),cp.execFileSync('git',['show','HEAD:'+p],{encoding:'utf8',maxBuffer:24*1024*1024}).replace(/\r\n/g,'\n'),p);
console.log(JSON.stringify({result:'PASS',currentExamples:count,openapiExamples,runtimeSchemas:3,phpJsCanonicalHmac:'PASS',hardCutover:'PASS',protectedEngineCatalogFixtureFiles:protectedPaths.length,publicFeatureCount:payload.result.features.length},null,2));

// Daily adds pinned alias access; preserve the pre-existing Natal interval conversion exactly.
{const p=plugin+'src/Engine/Saju/TimezoneReferenceRepository.php';const old=cp.execFileSync('git',['show','HEAD:'+p],{encoding:'utf8'});const current=fs.readFileSync(p,'utf8');const interval=s=>s.replace(/\r\n/g,'\n').split('public function intervals')[1].split('return $result;')[0].replace(/^.*\$this->aliases = \$data\['aliases'\];.*\n/gm,'');assert.equal(interval(current),interval(old));}
