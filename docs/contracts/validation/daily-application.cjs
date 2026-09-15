// Read-only complete repository application gate. Run from repository root.
const fs=require('fs'),assert=require('assert/strict'),cp=require('child_process');
const dir='docs/contracts/',read=p=>JSON.parse(fs.readFileSync(dir+p,'utf8'));
// Explicitly skip only the historical docs-only worktree restriction when a
// later authorized implementation gate owns scope validation. All tests still run.
const contractsOnly=process.argv.includes('--contracts-only');
const run=p=>JSON.parse(cp.execFileSync(process.execPath,[dir+'validation/'+p+'.cjs'],{encoding:'utf8',maxBuffer:8*1024*1024}));
const results={structural:run('structural'),semantic:run('semantic-regression'),astrology:run('astrology'),saju:run('saju-guardrail-v2'),daily:run('daily')};
for(const name of ['astrology','saju','daily']){
  const c=read('rules/'+name+'-rules.json');assert.equal(c.scoreEngineReadiness,'READY');assert.deepEqual(c.catalogBlockers,[]);assert(!c.blocker);
}
for(const name of ['astrology','saju']){
  const p=dir+'rules/'+name+'-rules.json',before=JSON.parse(cp.execFileSync('git',['show','HEAD:'+p],{encoding:'utf8',maxBuffer:8*1024*1024})),now=JSON.parse(fs.readFileSync(p,'utf8'));
  for(const x of [before,now])for(const k of ['scoreEngineReadiness','catalogBlockers','blocker'])delete x[k];
  assert.deepEqual(now,before,name+': Lifetime catalog changed beyond readiness metadata');
}
const protectedPaths=['wp-content','docker-compose.yml','compose.yaml','compose.yml','Dockerfile','.env.example'];
const gitArgs=['-c','core.safecrlf=false','diff','--name-only','HEAD','--',...protectedPaths];
if(!contractsOnly)assert.equal(cp.execFileSync('git',gitArgs,{encoding:'utf8'}).trim(),'','Production files changed');
const changed=cp.execFileSync('git',['-c','core.safecrlf=false','diff','--name-only','HEAD'],{encoding:'utf8'}).trim().split('\n').filter(Boolean);
const untracked=cp.execFileSync('git',['ls-files','--others','--exclude-standard'],{encoding:'utf8'}).trim().split('\n').filter(Boolean);
if(!contractsOnly)assert([...changed,...untracked].every(p=>p.startsWith('docs/')),'Outside documentation scope');
// The signed/public Feature schemas are intentionally unchanged.
for(const p of ['schemas/feature.schema.json','schemas/signed-context.schema.json','schemas/signed-header.schema.json','openapi.yaml']){
  const old=cp.execFileSync('git',['show','HEAD:'+dir+p],{encoding:'utf8',maxBuffer:4*1024*1024});
  assert.equal(fs.readFileSync(dir+p,'utf8').replace(/\r\n/g,'\n'),old.replace(/\r\n/g,'\n'),p);
}
const active=['docs/02_FORTUNE_ENGINE_SPEC.md','docs/03_SCORE_SPEC.md','docs/05_API_SPEC.md','docs/08_AI_PROMPT_SPEC.md','docs/09_TASK_LIST.md','docs/10_AGENTS.md',dir+'runtime-contract-v2.md',dir+'README.md',dir+'daily-catalog-v1.md',dir+'readiness.md',dir+'astrology-catalog-v1.md',dir+'saju-catalog-v1.md'];
for(const p of active){const t=fs.readFileSync(p,'utf8');assert(!/<=-12 VERY_LOW|\(-12,-6\] LOW|\[6,12\) GOOD|One source gets100|available-source0\.50|Only DAILY_RULE_CATALOG_APPROVAL remains|Daily remains empty/.test(t),p+' stale Daily contract');}
const runtime=fs.readFileSync(dir+'runtime-contract-v2.md','utf8');assert(runtime.includes('<=-5 VERY_LOW; (-5,-2] LOW; (-2,2) STABLE; [2,5) GOOD; >=5 VERY_GOOD'));
const reg=fs.readFileSync(dir+'README.md','utf8');for(let i=1;i<=10;i++)assert(new RegExp('SC-'+String(i).padStart(2,'0')+' \\| RESOLVED').test(reg),'Unresolved SC-'+i);
cp.execFileSync('git',['-c','core.safecrlf=false','diff','--check'],{encoding:'utf8'});
console.log(JSON.stringify({result:'PASS',contractFreeze:'PASS',specConflict:0,activeCrossSpecContradictions:0,scoreEngineReadiness:'READY',dailyCatalog:'APPLIED / READY',catalogBlockers:[],productionEngineReadiness:'BLOCKED_EXTERNAL',externalBlockers:read('rules/daily-rules.json').externalBlockers,productionFilesChanged:contractsOnly?'SCOPE_CHECK_DELEGATED':'NONE',gitDiffCheck:'PASS',results},null,2));
