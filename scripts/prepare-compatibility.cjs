'use strict';
// Local Docker fixture credential only; never print or commit the value.
const fs=require('node:fs'),cp=require('node:child_process'),crypto=require('node:crypto');
const target='.tools/compatibility-db-password';fs.mkdirSync('.tools',{recursive:true});
if(!fs.existsSync(target)){
 const name='love-fortune-compatibility-wordpress-1';
 const names=cp.execFileSync('docker',['ps','-a','--format','{{.Names}}'],{encoding:'utf8'}).trim().split(/\r?\n/);
 let secret;
 if(names.includes(name)){
  // Preserve the existing disposable database when upgrading the local Compose fixture.
  // Failure (including a stopped container) aborts; never silently replace its credential.
  secret=cp.execFileSync('docker',['exec',name,'php','-r',"$v=getenv('WORDPRESS_DB_PASSWORD'); if(!$v){$p=getenv('WORDPRESS_DB_PASSWORD_FILE');$v=$p?file_get_contents($p):false;} if(!$v){exit(1);} echo $v;"],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim();
 }else{secret=crypto.randomBytes(32).toString('hex');}
 if(!secret||/[\r\n]/.test(secret)){throw new Error('Invalid local fixture configuration');}
 fs.writeFileSync(target,secret,{flag:'wx',mode:0o600});
}
console.log('LOCAL_COMPATIBILITY_CONFIGURATION_READY');
