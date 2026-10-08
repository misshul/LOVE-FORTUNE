'use strict';
const cp=require('node:child_process'),fs=require('node:fs'),assert=require('node:assert/strict');
const prefix=['compose','-f','compose.compatibility.yaml','exec','-T','wordpress','php'];
const base='/var/www/html/wp-content/plugins/love-fortune-core/tests/';
const run=(name,...args)=>JSON.parse(cp.execFileSync('docker',[...prefix,base+name,...args],{encoding:'utf8',maxBuffer:8*1024*1024}));
const runtime=run('local-install.php');assert.equal(runtime.WordPress,'6.6.2');assert(runtime.PHP_VERSION.startsWith('8.3.'));assert.equal(runtime.PHP_INT_SIZE,8);
const cache=run('wordpress-predeployment.php','cache');assert.equal(cache.result,'LOCAL_CACHE_PASS');
const year365=run('wordpress-predeployment.php','365'),year366=run('wordpress-predeployment.php','366');
function concurrent(){return new Promise((resolve,reject)=>{cp.execFile('docker',[...prefix,base+'wordpress-predeployment.php','366'],{encoding:'utf8',maxBuffer:1024*1024},(e,out)=>{if(e)reject(e);else{try{resolve(JSON.parse(out));}catch(x){reject(x);}}});});}
(async()=>{
 const parallel=await Promise.all([concurrent(),concurrent()]);
 for(const r of parallel){assert.equal(r.result,'YEARLY_PASS');assert.equal(r.metrics.hash,year366.metrics.hash);}
 const output={result:'LOCAL_ADDITIONAL_PASS',runtime,cache,year365:year365.metrics,year366:year366.metrics,parallelYearly:parallel.map(r=>r.metrics),concurrentCanonicalDifferences:0,liveProvider:'NOT_RUN'};
 fs.writeFileSync('.tools/lolipop-additional.json',JSON.stringify(output,null,2)+'\n');console.log(JSON.stringify(output,null,2));
})().catch(e=>{console.error(e.message);process.exitCode=1;});
