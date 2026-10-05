'use strict';
// Full public release gate. Only --write-report writes the explicitly requested execution report.
const fs=require('node:fs'),cp=require('node:child_process'),assert=require('node:assert/strict');
const exec=(cmd,args)=>cp.execFileSync(cmd,args,{encoding:'utf8',maxBuffer:32*1024*1024});
const root='docs/contracts/';
if(process.argv.includes('--post-review')){
 const output=exec('powershell.exe',['-NoProfile','-ExecutionPolicy','Bypass','-File','scripts/test.ps1']);
 const match=output.match(/OK \((\d+) tests, ([\d,]+) assertions\)/);assert(match,output);
 const current=JSON.parse(exec(process.execPath,[root+'validation/public-combined.cjs']));
 const rest=exec('docker',['compose','exec','-T','wordpress','php','/var/www/html/wp-content/plugins/love-fortune-core/tests/wordpress-public-api.php']);assert(rest.includes('PUBLIC_COMBINED_WORDPRESS_E2E_PASS'));
 const result=JSON.parse(fs.readFileSync(root+'validation-results-public-combined-v1.json','utf8'));
 result.postReview={php:{lint:'PASS',tests:Number(match[1]),assertions:Number(match[2].replace(/,/g,''))},current,wordpressRest:rest.trim().split(/\r?\n/)};
 exec('git',['diff','--check']);fs.writeFileSync(root+'validation-results-public-combined-v1.json',JSON.stringify(result,null,2)+'\n');
 console.log(JSON.stringify(result.postReview,null,2));process.exit(0);
}
process.stderr.write('Running full retained engine/FP/PHP/reference/WordPress regression\n');
const retained=JSON.parse(exec(process.execPath,[root+'validation/saju-feature-extraction.cjs']));
assert.equal(retained.result,'SAJU_FEATURE_EXTRACTION_IMPLEMENTATION_PASS');
process.stderr.write('Running current public runtime/schema/PHP-JS contract checks\n');
const current=JSON.parse(exec(process.execPath,[root+'validation/public-combined.cjs']));
assert.equal(current.result,'PASS');
const rest=exec('docker',['compose','exec','-T','wordpress','php','/var/www/html/wp-content/plugins/love-fortune-core/tests/wordpress-public-api.php']);
assert(rest.includes('PUBLIC_COMBINED_WORDPRESS_E2E_PASS'));
exec('git',['diff','--check']);
const result={result:'PUBLIC_COMBINED_API_IMPLEMENTATION_PASS',retained,current,wordpressRest:rest.trim().split(/\r?\n/),gitDiffCheck:'PASS',commit:'NOT_COMMITTED',push:'NOT_PUSHED',limits:['Process-local synthetic signing/rate secrets in tests; deployment secrets must be provisioned separately.','Single-node rate counters; deployment idle cleanup required.','Infrastructure body capture not certified.','Daily/period/interpretation business routes remain unimplemented.']};
if(process.argv.includes('--write-report'))fs.writeFileSync(root+'validation-results-public-combined-v1.json',JSON.stringify(result,null,2)+'\n');
console.log(JSON.stringify(result,null,2));
