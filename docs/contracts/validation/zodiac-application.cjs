'use strict';
// Current application gate. Reads repository contracts; tests may write only ignored tool/OS temp files.
const fs = require('node:fs'), cp = require('node:child_process'), assert = require('node:assert/strict');
const root = 'docs/contracts/', plugin = 'wp-content/plugins/love-fortune-core/';
const run = (name, args = []) => {
  process.stderr.write('Validating ' + name + '\n');
  return JSON.parse(cp.execFileSync(process.execPath, [root + 'validation/' + name + '.cjs', ...args], { encoding: 'utf8', maxBuffer: 16 * 1024 * 1024 }));
};
const git = args => cp.execFileSync('git', ['-c', 'core.safecrlf=false', ...args], { encoding: 'utf8', maxBuffer: 16 * 1024 * 1024 });
const protectedPaths = ['saju', 'astrology', 'daily'].map(n => root + 'rules/' + n + '-rules.json').concat([plugin + 'tests/fixtures/day-pillar-golden.json']);
for (const p of protectedPaths) assert.deepEqual(JSON.parse(fs.readFileSync(p, 'utf8')), JSON.parse(git(['show', 'HEAD:' + p])), 'Protected catalog/epoch changed: ' + p);
const versions = JSON.parse(fs.readFileSync(root + 'product-scope.json', 'utf8'));
assert.deepEqual(versions.externalBlockers, []); assert.deepEqual(versions.advancedBlockers, ['EPHEMERIS_PROVIDER']);
const readiness = fs.readFileSync(root + 'readiness.md', 'utf8');
for (const marker of ['| V1_EXTERNAL_BLOCKERS | NONE |', '| FOUR_PILLARS | PARTIAL;', '| SCORE_ENGINE | PARTIAL;', '| API | NOT_IMPLEMENTED;', 'NOT_READY_IMPLEMENTATION']) assert(readiness.includes(marker));
// New production calculation code has no persistence, remote calls or raw logging.
const paths = [plugin + 'src/Engine/Zodiac', plugin + 'src/Domain/Score', plugin + 'src/Support/Rational.php', plugin + 'src/Support/DecimalInteger.php'];
let checked = 0;
for (const p of paths) for (const file of fs.statSync(p).isDirectory() ? fs.readdirSync(p).filter(x => x.endsWith('.php')).map(x => p + '/' + x) : [p]) {
  const text = fs.readFileSync(file, 'utf8');
  assert(!/\b(?:error_log|file_put_contents|wp_remote_post|curl_exec|set_transient|update_option|wpdb|mysqli|PDO)\b/.test(text), file);
  if (file.endsWith('ExactRanking.php')) assert(!/halfUp4|round\(|float|epsilon/i.test(text), 'Rounded or floating ranking');
  checked++;
}
const contractsOnly = process.argv.includes('--contracts-only');
const results = {
  structural: run('structural'), semantic: run('semantic-regression'),
  advancedAstrology: run('astrology'), saju: run('saju-guardrail-v2'),
  retainedDaily: run('daily'), zodiac: run('zodiac', contractsOnly ? [] : ['--php']),
  activeApi: run('zodiac-api'),
};
let php = 'NOT_RUN';
if (!contractsOnly) {
  const output = cp.execFileSync('powershell.exe', ['-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', 'scripts/test.ps1'], { encoding: 'utf8', maxBuffer: 4 * 1024 * 1024 });
  const match = output.match(/OK \((\d+) tests, ([\d,]+) assertions\)/); assert(match, output);
  php = { lint: 'PASS', tests: Number(match[1]), assertions: Number(match[2].replace(/,/g, '')) };
}
git(['diff', '--check']);
console.log(JSON.stringify({ result: 'PASS', mode: contractsOnly ? 'CONTRACTS_ONLY' : 'FULL_APPLICATION', v1ExternalBlockers: [], production: 'NOT_READY_IMPLEMENTATION', protectedCatalogsAndEpoch: 'UNCHANGED', privacyFilesChecked: checked, php,
  structural: results.structural, semantic: results.semantic, advancedAstrology: results.advancedAstrology,
  saju: { failures: results.saju.population?.failures ?? [], exact: results.saju.exact, guardrail: results.saju.v2Check },
  daily: results.retainedDaily, zodiac: results.zodiac, activeApi: results.activeApi, gitDiffCheck: 'PASS' }, null, 2));
