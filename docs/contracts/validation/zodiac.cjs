'use strict';
const fs = require('node:fs'), cp = require('node:child_process'), crypto = require('node:crypto'), assert = require('node:assert/strict');
const M = require('./zodiac-model.cjs');
const { catalog, scope, weights, parse, r, str, mul, add, half, pack, pair, date, zodiac, blend, four, rank, cmp } = M;
const fixture = JSON.parse(fs.readFileSync('wp-content/plugins/love-fortune-core/tests/fixtures/zodiac-golden.json', 'utf8'));
cp.execFileSync(process.execPath, ['scripts/generate-zodiac-config.cjs', '--check']);
assert.deepEqual(scope.activeSources, ['SAJU', 'ZODIAC']);
assert.deepEqual(scope.dailySignalSources, ['SAJU']);
assert.deepEqual(scope.sourceWeights, { SAJU: '4/5', ZODIAC: '1/5' });
assert.equal(scope.dailyScale, '18'); assert.equal(catalog.guardrail, 'NO_SC07'); assert.equal(catalog.pairOverrides.length, 0);
const dailySaju = JSON.parse(fs.readFileSync('docs/contracts/rules/daily-rules.json', 'utf8')).rules.filter(x => x.source === 'SAJU');
assert.equal(dailySaju.length, 9);
assert.equal(dailySaju.reduce((n, x) => n + x.categoryMappings.length, 0), 19);
assert(dailySaju.some(x => x.categoryMappings.some(m => m.category === 'COMMUNICATION')), 'Internal Saju Daily COMMUNICATION must remain');
assert.equal(catalog.rules.length, 20); assert.equal(new Set(catalog.rules.map(x => x.ruleId)).size, 20);
const matrix = { SAME_SIGN: [50, 62.5, 50, 57.5], OPPOSITE_SIGN: [75, 40, 70, 37.5], SAME_ELEMENT: [62.5, 65, 57.5, 67.5], COMPATIBLE_ELEMENT: [67.5, 60, 62.5, 62.5], CHALLENGING_ELEMENT: [50, 37.5, 52.5, 35] };
for (const rule of catalog.rules) {
  assert.equal(rule.source, 'ZODIAC'); assert.equal(rule.period, 'LIFETIME'); assert.equal(rule.productScope, 'V1'); assert.equal(rule.enabled, true);
  assert.equal(rule.baseWeight, 1); assert.equal(rule.ruleConfidence, .75);
  assert.equal(rule.ruleId, `ZODIAC_${rule.relation}_${rule.category}`);
  assert.equal(50 + 50 * rule.signedValue, matrix[rule.relation][scope.zodiacScoringCategories.indexOf(rule.category)]);
}
const result = { cases: [], daily: [], round: [], compare: [], ranks: [], precision: [], pairs: [], features: [] };
for (const row of fixture.cases) {
  const saju = Object.fromEntries(Object.entries(row.saju).map(([k, v]) => [k, { score: v.score === null ? null : parse(v.score), cov: parse(v.coverage), q: parse(v.confidence) }]));
  const output = blend(saju, row.zodiacAvailable ? zodiac(...row.signs) : {});
  const packed = { categories: Object.values(output.categories).map(pack), overall: { ...pack(output.overall), literalQ: str(output.pre) } };
  assert.deepEqual(packed, row.expected); result.cases.push(packed);
  assert.equal(output.categories.COMMUNICATION.score, null);
  assert.equal(str(output.overall.q), str(mul(output.overall.cov, output.pre)));
}
for (const row of fixture.daily) {
  const d = four(row.slots.map(s => ({ signal: parse(s.signal), confidence: parse(s.confidence) })));
  const delta = mul(r(18), d.signal); let score = add(parse(row.baseline), delta);
  if (cmp(score, r(0)) < 0) score = r(0); if (cmp(score, r(100)) > 0) score = r(100);
  const packed = { signal: str(d.signal), confidence: str(d.confidence), delta: str(delta), score: str(score), api: half(score) };
  assert.deepEqual(packed, row.expected); result.daily.push(packed);
}
for (const row of fixture.round) { const v = half(parse(row.input)); assert.equal(v, row.expected); result.round.push(v); }
for (const row of fixture.compare) { const v = cmp(...row.input.map(parse)); assert.equal(v, row.expected); result.compare.push(v); }
for (const row of fixture.ranks) { const v = { best: rank(row.input), caution: rank(row.input, true) }; assert.deepEqual(v, row.expected); result.ranks.push(v); }
for (const row of fixture.precision) {
  const x = four(row.slots.map(s => ({ signal: parse(s), confidence: r(1) })));
  assert.equal(str(x.signal), row.signal); assert.equal(x.peakIndex, row.peakIndex);
  result.precision.push({ signal: str(x.signal), peakIndex: x.peakIndex });
}
for (const k of [9, 12, 18]) {
  const score = str(add(r(50), mul(r(k), r(9593, 58608))));
  assert.deepEqual(rank([{ date: '2024-09-08', score, confidence: '227/352' }, { date: '2024-09-02', score, confidence: '293/352' }]), ['2024-09-02', '2024-09-08']);
}
let count = 0; const hash = crypto.createHash('sha256'), reachable = new Set();
for (let d = new Date('1900-01-01T00:00:00Z'); d <= new Date('2099-12-31T00:00:00Z'); d.setUTCDate(d.getUTCDate() + 1)) {
  const text = d.toISOString().slice(0, 10), sign = date(text); reachable.add(sign); hash.update((count ? '\n' : '') + text + ':' + sign); count++;
}
result.dates = { count, sha256: hash.digest('hex') }; assert.deepEqual(result.dates, fixture.dates); assert.equal(reachable.size, 12);
for (const invalid of ['1900-02-29', '2001-02-29', '2000-04-31', '1899-12-31', '2100-01-01', '2000-2-01']) assert.throws(() => date(invalid));
const pairs = new Map();
for (const a of catalog.referenceTables.signs) for (const b of catalog.referenceTables.signs) {
  const row = pair(a.sign, b.sign); assert.deepEqual(row, pair(b.sign, a.sign));
  assert.deepEqual(zodiac(a.sign, b.sign), zodiac(b.sign, a.sign));
  pairs.set(row.pairId, row.primaryRelation); result.pairs.push(row);
  result.features.push(catalog.rules.filter(x => x.relation === row.primaryRelation).map(x => M.feature(x, row)));
}
const distribution = {}; for (const value of pairs.values()) distribution[value] = (distribution[value] || 0) + 1;
assert.equal(pairs.size, 78); assert.deepEqual(distribution, { SAME_SIGN: 12, OPPOSITE_SIGN: 6, SAME_ELEMENT: 12, COMPATIBLE_ELEMENT: 12, CHALLENGING_ELEMENT: 36 });
// No result fixtures are rewritten. Docker executes actual deployed plugin classes.
let crossRuntime = 'NOT_RUN';
if (process.argv.includes('--php')) {
  const stdout = cp.execFileSync('docker', ['compose', 'exec', '-T', 'wordpress', 'php', 'wp-content/plugins/love-fortune-core/tests/zodiac-cross.php'], { encoding: 'utf8', maxBuffer: 8 * 1024 * 1024 });
  assert.deepEqual(JSON.parse(stdout), result); crossRuntime = 'PASS / differences=0';
}
console.log(JSON.stringify({ result: 'PASS', dateCount: count, gaps: 0, overlaps: 0, reachableSigns: reachable.size, unorderedPairs: pairs.size, orderedPairs: result.pairs.length, distribution, mappings: 20, communicationMappings: 0, lifetime: fixture.cases.length, daily: fixture.daily.length, halfUp: fixture.round.length, exactComparisons: fixture.compare.length, rankGroups: fixture.ranks.length, precisionClasses: fixture.precision.length, classification: 'EXPECTED_PRECISION_CORRECTION', crossRuntime }, null, 2));
