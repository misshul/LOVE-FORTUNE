'use strict';
const cp=require('node:child_process');
// Existing build-only image, pinned for repeatable compression. Never used at runtime.
const image='python@sha256:47ae396f09c1303b8653019811a8498470603d7ffefc29cb07c88f1f8cb3d19f';
module.exports=arg=>JSON.parse(cp.execFileSync('docker',['run','--rm','--pull=never','--network','none','--mount','type=bind,source='+process.cwd()+',target=/work','-w','/work',image,'python','scripts/package-zip.py',arg],{encoding:'utf8',maxBuffer:1024*1024,timeout:120000}));
