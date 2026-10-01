'use strict';
// Read-only full gate: outputs execution evidence, never regenerates protected fixtures.
const fs=require('node:fs'),cp=require('node:child_process'),assert=require('node:assert/strict');
const plugin='wp-content/plugins/love-fortune-core/',root='docs/contracts/';
const exec=(cmd,args)=>cp.execFileSync(cmd,args,{encoding:'utf8',maxBuffer:24*1024*1024});
const protectedPaths=exec('git',['ls-files',root+'schemas',root+'examples',root+'rules',root+'openapi.yaml',root+'product-scope.json',plugin+'config/zodiac.php',plugin+'src/Engine/Zodiac',plugin+'src/Domain/Score',plugin+'src/Engine/Saju']).trim().split(/\r?\n/).filter(Boolean);
// Exclude this phase's newly committed modules on subsequent replays, preserving old implementations.
const added=['src/Domain/Score/SajuCategoryScorer.php','src/Engine/Saju/SajuLifetimeScoringService.php','src/Engine/Saju/SajuLifetimeScoringValidator.php'].map(p=>plugin+p);
for(const p of protectedPaths.filter(p=>!added.includes(p))){
 assert.equal(fs.readFileSync(p,'utf8').replace(/\r\n/g,'\n'),exec('git',['show','HEAD:'+p]).replace(/\r\n/g,'\n'),'Protected change: '+p);
}
for(const p of added){const s=fs.readFileSync(p,'utf8');
 assert(!/\b(?:error_log|file_put_contents|fwrite|print_r|var_dump|wp_remote_\w+|curl_\w+|set_transient|update_option|wpdb|mysqli|PDO|exec|shell_exec)\s*\(/.test(s),p);
 assert(!/Zodiac|LifetimeBlender|DailyAggregator|DateTime|\b(?:float|round|rand|random_int|time)\s*\(/.test(s),p);
}
const full=JSON.parse(exec(process.execPath,[root+'validation/saju-feature-extraction.cjs']));
assert.equal(full.result,'SAJU_FEATURE_EXTRACTION_IMPLEMENTATION_PASS');assert(full.full.php.tests>61);assert(full.full.php.assertions>1260853);
assert.equal(JSON.parse(fs.readFileSync(root+'schemas/common.schema.json','utf8')).$defs.versions.properties.scoreVersion.const,'SCORE_ZODIAC_V1');
exec('git',['diff','--check']);
console.log(JSON.stringify({result:'SAJU_LIFETIME_SCORING_IMPLEMENTATION_PASS',protectedPaths:protectedPaths.filter(p=>!added.includes(p)).length,
 publicVersionAndSchema:'UNCHANGED',privacySourceFiles:added.length,full,commit:'NOT_COMMITTED',push:'NOT_PUSHED'},null,2));
