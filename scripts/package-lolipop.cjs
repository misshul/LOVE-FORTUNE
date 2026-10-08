'use strict';
// Local runtime-only package and previous-HEAD rollback. Run only after successful regression.
const fs=require('node:fs'),path=require('node:path'),cp=require('node:child_process'),crypto=require('node:crypto'),assert=require('node:assert/strict');
const raw=fs.readFileSync('.tools/lolipop-full-regression.json');
const evidence=JSON.parse(raw.toString(raw[0]===255&&raw[1]===254?'utf16le':'utf8').replace(/^\uFEFF/,''));
assert.equal(evidence.result,'INTERPRETATION_ROUTE_IMPLEMENTATION_PASS');
assert.equal(JSON.parse(fs.readFileSync('.tools/lolipop-additional.json','utf8')).result,'LOCAL_ADDITIONAL_PASS');
const phpRaw=fs.readFileSync('.tools/lolipop-php-final.txt');
const php=phpRaw.toString(phpRaw[0]===255&&phpRaw[1]===254?'utf16le':'utf8');
assert(/OK \(136 tests, 1263096 assertions\)/.test(php),'Final PHP regression evidence required');
const plugin='wp-content/plugins/love-fortune-core',head=cp.execFileSync('git',['rev-parse','HEAD'],{encoding:'utf8'}).trim();
const destination=path.resolve('.tools/releases/lolipop-'+Date.now());fs.mkdirSync(destination,{recursive:true});
const allowed=f=>['love-fortune-core.php','autoload.php','config/bootstrap.php','config/zodiac.php'].includes(f)||f.startsWith('src/')&&f.endsWith('.php')||f.startsWith('bin/')&&f.endsWith('.php')||f.startsWith('config/references/')&&/\.(json|md)$/.test(f);
function walk(dir){return fs.readdirSync(dir,{withFileTypes:true}).flatMap(e=>e.isDirectory()?walk(path.join(dir,e.name)):[path.join(dir,e.name)]);}
const files=walk(plugin).map(f=>path.relative(plugin,f).replaceAll('\\','/')).filter(allowed).sort();
const previous=cp.execFileSync('git',['ls-tree','-r','--name-only',head,'--',plugin],{encoding:'utf8'}).trim().split(/\r?\n/).map(f=>f.slice(plugin.length+1)).filter(allowed).sort();
const hashes={};
for(const [label,list] of [['current',files],['rollback',previous]]){
 const out=path.join(destination,label,'love-fortune-core');fs.mkdirSync(out,{recursive:true});const manifest={};
 for(const name of list){const bytes=label==='current'?fs.readFileSync(path.join(plugin,name)):cp.execFileSync('git',['show',head+':'+plugin+'/'+name],{maxBuffer:32*1024*1024});
  const target=path.join(out,name);fs.mkdirSync(path.dirname(target),{recursive:true});fs.writeFileSync(target,bytes);manifest[name]=crypto.createHash('sha256').update(bytes).digest('hex');
 }
 fs.writeFileSync(path.join(destination,label+'-sha256.json'),JSON.stringify(manifest,null,2)+'\n');
 const quote=s=>"'"+s.replaceAll("'","''")+"'";
 const zip=path.join(destination,label+'.zip');cp.execFileSync('powershell.exe',['-NoProfile','-Command','Compress-Archive -LiteralPath '+quote(out)+' -DestinationPath '+quote(zip)],{stdio:'pipe'});
 // Byte-exact verification of the extracted ZIP, not merely its staging directory.
 const extracted=path.join(destination,label+'-verified');cp.execFileSync('powershell.exe',['-NoProfile','-Command','Expand-Archive -LiteralPath '+quote(zip)+' -DestinationPath '+quote(extracted)],{stdio:'pipe'});
 const actual=walk(extracted).map(f=>path.relative(path.join(extracted,'love-fortune-core'),f).replaceAll('\\','/')).sort();assert.deepEqual(actual,list);
 for(const name of list)assert.equal(crypto.createHash('sha256').update(fs.readFileSync(path.join(extracted,'love-fortune-core',name))).digest('hex'),manifest[name]);
 hashes[label]={files:list.length,sha256:crypto.createHash('sha256').update(fs.readFileSync(zip)).digest('hex')};
}
for(const name of ['solar-terms-v1.json','locations-v1.json','timezones-v1.json','saju-rules-v1.json','daily-rules-v1.json']){
 const f=plugin+'/config/references/'+name;assert.deepEqual(fs.readFileSync(f),cp.execFileSync('git',['show',head+':'+f],{maxBuffer:32*1024*1024}));
}
console.log(JSON.stringify({result:'RUNTIME_PACKAGE_PASS',directory:path.relative(process.cwd(),destination).replaceAll('\\','/'),rollbackCommit:head,packages:hashes,referenceIdentity:'PASS'},null,2));
