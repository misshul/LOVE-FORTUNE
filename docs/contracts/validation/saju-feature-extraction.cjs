'use strict';
// Read-only gate. No production HTTP calls, user inputs, fixture regeneration or report writes.
const fs=require('node:fs'),cp=require('node:child_process'),assert=require('node:assert/strict'),crypto=require('node:crypto');
const plugin='wp-content/plugins/love-fortune-core/',root='docs/contracts/';
const exec=(command,args)=>cp.execFileSync(command,args,{encoding:'utf8',maxBuffer:24*1024*1024});
const run=name=>{process.stderr.write('Validating '+name+'\n');return JSON.parse(exec(process.execPath,[root+'validation/'+name+'.cjs']));};
const source=fs.readFileSync(root+'rules/saju-rules.json'),runtime=fs.readFileSync(plugin+'config/references/saju-rules-v1.json');
assert(source.equals(runtime));assert.equal(crypto.createHash('sha256').update(runtime).digest('hex'),'3d580c733e91c9aff32373c684deda4063895d7671cc54b1f6cb7ed0b6bd2c3f');
const legacy=fs.readdirSync(root+'examples').filter(n=>/^saju-v1-feature-.*\.json$/.test(n)).map(n=>JSON.parse(fs.readFileSync(root+'examples/'+n,'utf8')));
assert.equal(legacy.length,16);assert.deepEqual(JSON.parse(fs.readFileSync(plugin+'tests/fixtures/saju-feature-legacy-identities.json','utf8')),legacy);
// Byte/parsed protection of pre-existing fixtures and numeric contracts against HEAD.
const paths=[root+'rules/saju-rules.json',root+'rules/daily-rules.json',root+'rules/zodiac-rules.json',root+'rules/astrology-rules.json'];
for(const p of exec('git',['ls-files',plugin+'tests/fixtures',root+'fixtures']).trim().split(/\r?\n/).filter(Boolean))paths.push(p);
for(const p of paths)assert.deepEqual(JSON.parse(fs.readFileSync(p,'utf8')),JSON.parse(exec('git',['show','HEAD:'+p])),p);
const files=fs.readdirSync(plugin+'src/Engine/Saju').filter(n=>/^Saju(?:Feature|Branch|Candidate|Relation|Evidence|Coverage|Context)/.test(n)&&n.endsWith('.php'));
assert.equal(files.length,10);
for(const name of files){const text=fs.readFileSync(plugin+'src/Engine/Saju/'+name,'utf8');
 assert(!/\b(?:error_log|file_put_contents|fwrite|print_r|var_dump|wp_remote_\w+|curl_\w+|set_transient|update_option|wpdb|mysqli|PDO|exec|shell_exec)\s*\(/.test(text),name);
 assert(!/\b(?:float|round)\b|DateTimeZone|new DateTime/.test(text),name);
}
const contract=fs.readFileSync(root+'saju-feature-extraction-v1.md','utf8');
for(const marker of ['SAJU_FEATURE_EXTRACTION_CONTRACT_APPROVED','FE-01','FE-02','FE-03','FE-04','FE-05','FE-06','FE-COV-01','SAJU_FEATURE_EXTRACTOR_V1'])assert(contract.includes(marker));
const full=run('zodiac-application');assert.equal(full.mode,'FULL_APPLICATION');assert(full.php.tests>48);assert(full.php.assertions>1259575);
const locationTimezone=run('location-timezone'),solar=run('solar-reference');
process.stderr.write('Verifying active WordPress plugin\n');
const smoke=exec('docker',['compose','exec','-T','wordpress','php','/var/www/html/'+plugin+'tests/wordpress-smoke.php']);
assert(!smoke.includes('FAIL:'));assert(smoke.includes('PASS:'));
exec('git',['diff','--check']);
console.log(JSON.stringify({result:'SAJU_FEATURE_EXTRACTION_IMPLEMENTATION_PASS',protectedPaths:paths.length,
 catalogByteIdentity:'PASS',legacyIdentityFixtures:16,privacyFilesChecked:files.length,
 full,locationTimezone,solar,wordpressSmoke:smoke.trim().split(/\r?\n/),gitDiffCheck:'PASS',commit:'NOT_COMMITTED',push:'NOT_PUSHED'},null,2));
