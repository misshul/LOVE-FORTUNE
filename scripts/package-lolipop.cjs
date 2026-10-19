'use strict';
// Local candidate only. Always execute fresh approved gates; never infer production rollback.
const fs=require('node:fs'),path=require('node:path'),cp=require('node:child_process'),crypto=require('node:crypto'),assert=require('node:assert/strict');
const plugin='wp-content/plugins/love-fortune-core';
const required=['love-fortune-core.php','autoload.php','src/Frontend/templates/service-page.php','assets/css/frontend.css','assets/js/frontend.js','config/bootstrap.php','config/zodiac.php','bin/rate-cleanup.php',...['solar-terms-v1.json','locations-v1.json','timezones-v1.json','saju-rules-v1.json','daily-rules-v1.json','location-timezone-LICENSE.md','README.md'].map(x=>'config/references/'+x)];
const allowed=f=>!f.split('/').some(x=>x.startsWith('.')||/^(tests?|fixtures?|logs?|node_modules|vendor)$/i.test(x))&&(['love-fortune-core.php','autoload.php','config/bootstrap.php','config/zodiac.php'].includes(f)||/^src\/.*\.php$/.test(f)||/^bin\/.*\.php$/.test(f)||/^assets\/(css\/[^/]+\.css|js\/[^/]+\.js)$/.test(f)||/^config\/references\/[^/]+\.(json|md)$/.test(f));
function walk(dir){return fs.readdirSync(dir,{withFileTypes:true}).flatMap(e=>{assert(!e.isSymbolicLink(),'Symlink rejected');return e.isDirectory()?walk(path.join(dir,e.name)):[path.join(dir,e.name)];});}
const names=dir=>walk(dir).map(f=>path.relative(dir,f).replaceAll('\\','/')).sort();
const sha=bytes=>crypto.createHash('sha256').update(bytes).digest('hex');
function validateList(files){assert.equal(new Set(files).size,files.length);for(const f of required)assert(files.includes(f),'Missing runtime file: '+f);for(const f of files)assert(allowed(f),'Unexpected runtime file: '+f);}
function scan(bytes,name){const s=bytes.toString('utf8');assert(!/C:[\\/]Users[\\/]|\/mnt\/data\//i.test(s),'Machine path: '+name);assert(!/-----BEGIN .*PRIVATE KEY-----|eyJ[A-Za-z0-9_-]{30,}\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/.test(s),'Credential/token candidate: '+name);assert(!/(?:sk-proj-|sk-live-|AKIA)[A-Za-z0-9_-]{16,}/.test(s),'Credential candidate: '+name);}
function validateGate(r){assert.equal(r.result,'INTERPRETATION_ROUTE_IMPLEMENTATION_PASS');for(const expected of ['PERIOD_ORCHESTRATION_IMPLEMENTATION_PASS','DAILY_ORCHESTRATION_IMPLEMENTATION_PASS','PUBLIC_COMBINED_API_IMPLEMENTATION_PASS','SAJU_FEATURE_EXTRACTION_IMPLEMENTATION_PASS']){r=r.retained;assert.equal(r?.result,expected);}assert.equal(r.full.mode,'FULL_APPLICATION');assert.equal(r.full.result,'PASS');assert.equal(r.full.php.lint,'PASS');assert(r.full.php.tests>0&&r.full.php.assertions>0);assert.equal(r.full.structural.openapi,'PASS');assert(r.full.structural.schemaFiles>0);return r.full.php;}
function runJson(run,command,args){return JSON.parse(run(command,args));}
function main(){
 const run=(command,args)=>cp.execFileSync(command,args,{encoding:'utf8',maxBuffer:128*1024*1024,env:{...process.env,COMPOSE_FILE:'compose.compatibility.yaml'}});
 const head=run('git',['rev-parse','HEAD']).trim();
 const files=names(plugin).filter(allowed);validateList(files);
 const snapshot=()=>Object.fromEntries(files.map(f=>[f,sha(fs.readFileSync(path.join(plugin,f)))]));
 const manifest=snapshot();for(const f of files)scan(fs.readFileSync(path.join(plugin,f)),f);
 process.stderr.write('Running fresh full PHP/backend/schema/reference gate\n');
 const regression=runJson(run,process.execPath,['docs/contracts/validation/interpretation-application.cjs']);const php=validateGate(regression);
 process.stderr.write('Running retained local cache/concurrent Yearly checks (no install/activation)\n');
 const additional=runJson(run,process.execPath,['scripts/validate-lolipop.cjs']);assert.equal(additional.result,'LOCAL_ADDITIONAL_PASS');
 assert.deepEqual(names(plugin).filter(allowed),files);assert.deepEqual(snapshot(),manifest,'Runtime changed during validation');
 const destination=fs.mkdtempSync(path.resolve('.tools/releases')+path.sep+'frontend-v1-');
 const out=path.join(destination,'candidate','love-fortune-core');
 for(const f of files){const to=path.join(out,f);fs.mkdirSync(path.dirname(to),{recursive:true});fs.copyFileSync(path.join(plugin,f),to);}
 fs.writeFileSync(path.join(destination,'candidate-sha256.json'),JSON.stringify(manifest,null,2)+'\n');
 const quote=s=>"'"+s.replaceAll("'","''")+"'";
 const zip=path.join(destination,'candidate.zip'),extracted=path.join(destination,'extracted');
 run('powershell.exe',['-NoProfile','-Command','Compress-Archive -LiteralPath '+quote(out)+' -DestinationPath '+quote(zip)]);
 run('powershell.exe',['-NoProfile','-Command','Expand-Archive -LiteralPath '+quote(zip)+' -DestinationPath '+quote(extracted)]);
 assert.deepEqual(fs.readdirSync(extracted),['love-fortune-core']);
 const artifact=path.join(extracted,'love-fortune-core');const actual=names(artifact);validateList(actual);assert.deepEqual(actual,files);
 for(const f of actual){const bytes=fs.readFileSync(path.join(artifact,f));assert.equal(sha(bytes),manifest[f]);scan(bytes,f);}
 for(const f of files.filter(f=>f.startsWith('config/references/'))){assert.equal(manifest[f],sha(cp.execFileSync('git',['show',head+':'+plugin+'/'+f],{maxBuffer:32*1024*1024})),'Frozen reference changed: '+f);}
 for(const f of ['assets/css/frontend.css','assets/js/frontend.js'])assert(fs.statSync(path.join(artifact,f)).mtimeMs>0);
 const remote='/tmp/lf-package-'+crypto.randomBytes(8).toString('hex');
 run('docker',['compose','-f','compose.compatibility.yaml','cp',artifact,'wordpress:'+remote]);
 const lint='$n=0;foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($argv[1])) as $f){if($f->isFile()&&$f->getExtension()==="php"){exec(escapeshellarg(PHP_BINARY)." -l ".escapeshellarg($f->getPathname()),$out,$code);if($code!==0){exit(1);}$n++;}}echo json_encode(["result"=>"PASS","files"=>$n]);';
 const artifactLint=runJson(run,'docker',['compose','-f','compose.compatibility.yaml','exec','-T','wordpress','php','-r',lint,remote]);assert.equal(artifactLint.result,'PASS');assert.equal(artifactLint.files,files.filter(x=>x.endsWith('.php')).length);
 const probe='require $argv[1]."/autoload.php";(new LoveFortune\\Core\\Engine\\Saju\\LocationReferenceRepository())->get("LOC000002");(new LoveFortune\\Core\\Engine\\Saju\\TimezoneReferenceRepository())->intervals("Asia/Tokyo");(new LoveFortune\\Core\\Engine\\Saju\\SolarTermReference())->provenance();new LoveFortune\\Core\\Engine\\Saju\\SajuFeatureCatalog();new LoveFortune\\Core\\Engine\\Saju\\SajuDailyEvaluator();LoveFortune\\Core\\Engine\\Zodiac\\ZodiacCatalog::configuration();echo json_encode(["result"=>"PASS","readers"=>6]);';
 const reference=runJson(run,'docker',['compose','-f','compose.compatibility.yaml','exec','-T','wordpress','php','-r',probe,remote]);assert.equal(reference.result,'PASS');
 assert.deepEqual(snapshot(),manifest,'Runtime changed during packaging');
 const result={result:'FRONTEND_V1_PACKAGING_REPAIR_PASS',sourceCommit:head,artifact:path.relative(process.cwd(),zip).replaceAll('\\','/'),bytes:fs.statSync(zip).size,sha256:sha(fs.readFileSync(zip)),fileCount:files.length,php,artifactLint,reference,manifest:'PASS',frontendAssets:'PASS',secretAndPathScan:'PASS / heuristic scan plus reviewed runtime source; environment variable names are required loaders, not secret values',rollback:'NOT_CREATED / known production artifact must be selected separately',productionApproved:false,installTest:'NOT_RUN / no active plugin or DB mutation',commit:false,push:false,deployment:false};
 fs.writeFileSync(path.join(destination,'validation.json'),JSON.stringify({summary:result,regression,additional},null,2)+'\n');
 console.log(JSON.stringify(result,null,2));
}
module.exports={required,allowed,validateList,validateGate,runJson,scan};
if(require.main===module){try{fs.mkdirSync('.tools/releases',{recursive:true});main();}catch(e){console.error('PACKAGING_FAILED: '+e.message);process.exitCode=1;}}
