'use strict';
// Read-only gate. No fixture/report regeneration; caller may save stdout as execution evidence.
const fs=require('node:fs'),cp=require('node:child_process'),assert=require('node:assert/strict');
const root='docs/contracts/',plugin='wp-content/plugins/love-fortune-core/';
const exec=(command,args)=>cp.execFileSync(command,args,{encoding:'utf8',maxBuffer:24*1024*1024});
const protectedPaths=exec('git',['ls-files',root+'schemas',root+'examples',root+'rules',root+'product-scope.json',root+'openapi.yaml',plugin+'config/zodiac.php',plugin+'src/Domain',plugin+'src/Engine',plugin+'tests/fixtures']).trim().split(/\r?\n/).filter(Boolean);
for(const p of protectedPaths)assert.equal(fs.readFileSync(p,'utf8').replace(/\r\n/g,'\n'),exec('git',['show','HEAD:'+p]).replace(/\r\n/g,'\n'),p);
assert.equal(JSON.parse(fs.readFileSync(root+'rules/category-weights.json','utf8')).contractVersion,'2.0.0');
exec(process.execPath,['scripts/generate-zodiac-config.cjs','--check']);
const sources=['CombinedLifetimeService.php','LifetimeSourceEnvelope.php'];
for(const name of sources){const s=fs.readFileSync(plugin+'src/Application/Score/'+name,'utf8');
 assert(!/\b(?:error_log|file_put_contents|fwrite|print_r|var_dump|wp_remote_\w+|curl_\w+|set_transient|update_option|wpdb|mysqli|PDO|exec|shell_exec|rand|random_int|time)\s*\(/.test(s),name);
 assert(!/->(?:add|subtract|multiply|divide|clamp|halfUp4)\(/.test(s),'Duplicated arithmetic in '+name);
}
const full=JSON.parse(exec(process.execPath,[root+'validation/saju-lifetime-scoring.cjs']));
assert.equal(full.result,'SAJU_LIFETIME_SCORING_IMPLEMENTATION_PASS');
assert(full.full.full.php.tests>72);assert(full.full.full.php.assertions>1261436);
exec('git',['diff','--check']);
console.log(JSON.stringify({result:'COMBINED_LIFETIME_IMPLEMENTATION_PASS',protectedPaths:protectedPaths.length,
 publicVersionsAndSchemas:'UNCHANGED',blender:'UNCHANGED / REUSED',privacyFilesChecked:sources.length,full,
 commit:'NOT_COMMITTED',push:'NOT_PUSHED'},null,2));
