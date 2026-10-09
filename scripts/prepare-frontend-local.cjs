'use strict';
// Explicit local development setup; never run on hosting or commit generated secrets.
const fs=require('node:fs'),crypto=require('node:crypto');
fs.mkdirSync('.tools',{recursive:true});const target='.tools/frontend-local.env';
if(!fs.existsSync(target))fs.writeFileSync(target,[
 'LOVE_FORTUNE_SIGNING_KID=local-frontend',
 'LOVE_FORTUNE_SIGNING_KEYS='+JSON.stringify({'local-frontend':crypto.randomBytes(32).toString('hex')}),
 'LOVE_FORTUNE_RATE_SECRET='+crypto.randomBytes(32).toString('hex'),
 'LOVE_FORTUNE_RATE_ROOT=/var/lib/love-fortune-rate',
 'LOVE_FORTUNE_RATE_SITE=frontend-local',
 'LOVE_FORTUNE_AI_ENABLED=0'
].join('\n')+'\n',{flag:'wx',mode:0o600});
fs.writeFileSync('.tools/frontend.compose.yaml','services:\n  wordpress:\n    env_file:\n      - ./.tools/frontend-local.env\n');
console.log('LOCAL_FRONTEND_CONFIGURATION_READY (values not printed)');
