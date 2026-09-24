'use strict';
// Independent BigInt reference arithmetic for validation, never frontend scoring code.
const fs = require('node:fs'), crypto = require('node:crypto'), assert = require('node:assert/strict'), vm = require('node:vm');
const { canon } = vm.runInNewContext(fs.readFileSync(__dirname + '/canonicalizer.cjs', 'utf8') + ';({canon});');
const catalog = JSON.parse(fs.readFileSync('docs/contracts/rules/zodiac-rules.json', 'utf8'));
const scope = JSON.parse(fs.readFileSync('docs/contracts/product-scope.json', 'utf8'));
const weights = JSON.parse(fs.readFileSync('docs/contracts/rules/category-weights.json', 'utf8')).weights;
const abs = x => x < 0n ? -x : x;
function gcd(a, b) { a = abs(a); b = abs(b); while (b) [a, b] = [b, a % b]; return a; }
function r(n, d = 1n) { n = BigInt(n); d = BigInt(d); assert(d !== 0n); if (d < 0n) { n = -n; d = -d; } const g = gcd(n, d); return [n / g, d / g]; }
function parse(value) {
  const s = String(value); assert(/^-?\d+(?:\.\d+|\/\d+)?$/.test(s), s);
  if (s.includes('/')) return r(...s.split('/'));
  const [integer, fraction = ''] = s.split('.'); return r(integer + fraction, 10n ** BigInt(fraction.length));
}
const add = (a, b) => r(a[0] * b[1] + b[0] * a[1], a[1] * b[1]);
const sub = (a, b) => add(a, [-b[0], b[1]]);
const mul = (a, b) => r(a[0] * b[0], a[1] * b[1]);
const div = (a, b) => r(a[0] * b[1], a[1] * b[0]);
const cmp = (a, b) => { const d = a[0] * b[1] - b[0] * a[1]; return d < 0n ? -1 : d > 0n ? 1 : 0; };
const sum = xs => xs.reduce(add, r(0));
const str = x => x === null ? null : x.join('/');
function half(x) {
  if (x === null) return null;
  const n = abs(x[0]) * 10000n, q = n / x[1] + (n % x[1] * 2n >= x[1] ? 1n : 0n), s = String(q).padStart(5, '0');
  return (x[0] < 0n && q !== 0n ? '-' : '') + s.slice(0, -4) + '.' + s.slice(-4);
}
function status(score) { if (score === null) return 'INSUFFICIENT_DATA'; const rounded = parse(half(score)); for (const [v, s] of [[45, 'CAUTION'], [60, 'BALANCED'], [75, 'GOOD'], [85, 'VERY_GOOD']]) if (cmp(rounded, r(v)) < 0) return s; return 'EXCELLENT'; }
const pack = x => ({ score: str(x.score), cov: str(x.cov), q: str(x.q), api: half(x.score), status: status(x.score) });
const empty = () => ({ score: null, cov: r(0), q: r(0) });
const digest = (prefix, x) => prefix + crypto.createHash('sha256').update(canon(x)).digest('hex').slice(0, 48);
function pair(a, b) {
  const signs = catalog.referenceTables.signs, names = signs.map(x => x.sign);
  const ai = names.indexOf(a), bi = names.indexOf(b); assert(ai >= 0 && bi >= 0);
  const [x, y] = ai <= bi ? [signs[ai], signs[bi]] : [signs[bi], signs[ai]];
  const element = x.element === y.element ? 'SAME_ELEMENT' : x.polarity === y.polarity ? 'COMPATIBLE_ELEMENT' : 'CHALLENGING_ELEMENT';
  const context = { source: 'ZODIAC', contextRole: 'STATIC', signA: x.sign, signB: y.sign, pairId: `ZODIAC_PAIR_${x.sign}_${y.sign}`,
    primaryRelation: x.sign === y.sign ? 'SAME_SIGN' : x.opposite === y.sign ? 'OPPOSITE_SIGN' : element,
    elementRelation: element, modalityRelation: x.modality === y.modality ? 'SAME_MODALITY' : 'DIFFERENT_MODALITY', polarityRelation: x.polarity === y.polarity ? 'SAME_POLARITY' : 'DIFFERENT_POLARITY',
    modelVersion: catalog.modelVersion, dateRangeVersion: catalog.dateRangeVersion };
  return { ...context, contextEvidenceId: digest('ce_', context) };
}
function date(text) {
  assert(/^(19|20)\d\d-\d\d-\d\d$/.test(text)); const instant = new Date(text + 'T00:00:00Z'); assert.equal(instant.toISOString().slice(0, 10), text);
  const md = text.slice(5), matches = catalog.referenceTables.signs.filter(x => x.start <= x.end ? md >= x.start && md <= x.end : md >= x.start || md <= x.end);
  assert.equal(matches.length, 1, 'Date gap/overlap'); return matches[0].sign;
}
function zodiac(a, b) {
  const context = pair(a, b);
  return Object.fromEntries(catalog.rules.filter(x => x.enabled && x.productScope === 'V1' && x.relation === context.primaryRelation).map(x => [x.category, { score: add(r(50), mul(r(50), parse(x.signedValue))), cov: r(1), q: parse(x.ruleConfidence) }]));
}
function blend(saju, z) {
  const categories = {};
  for (const c of Object.keys(weights)) {
    if (c === 'COMMUNICATION') { categories[c] = empty(); continue; }
    const sources = scope.zodiacScoringCategories.includes(c) ? ['SAJU', 'ZODIAC'] : ['SAJU'];
    const den = sum(sources.map(s => parse(scope.sourceWeights[s])));
    const rows = sources.map(s => ({ w: parse(scope.sourceWeights[s]), row: (s === 'SAJU' ? saju : z)[c] || empty() }));
    categories[c] = { score: rows.some(x => x.row.score !== null) ? add(r(50), div(sum(rows.map(x => x.row.score === null ? r(0) : mul(x.w, sub(x.row.score, r(50))))), den)) : null,
      cov: div(sum(rows.map(x => mul(x.w, x.row.cov))), den), q: div(sum(rows.map(x => mul(x.w, x.row.q))), den) };
  }
  const eligible = Object.keys(weights).filter(c => c !== 'COMMUNICATION'), computable = eligible.filter(c => categories[c].score !== null), cw = sum(computable.map(c => parse(weights[c])));
  const cov = div(sum(eligible.map(c => mul(parse(weights[c]), categories[c].cov))), sum(eligible.map(c => parse(weights[c]))));
  const pre = cw[0] ? div(sum(computable.map(c => mul(parse(weights[c]), div(categories[c].q, categories[c].cov)))), cw) : r(0);
  return { categories, overall: { score: cw[0] ? div(sum(computable.map(c => mul(parse(weights[c]), categories[c].score))), cw) : null, cov, q: mul(cov, pre) }, pre };
}
function four(slots) {
  assert.equal(slots.length, 4); const valid = slots.map((x, index) => x && ({ ...x, index })).filter(Boolean);
  if (!valid.length) return { signal: r(0), confidence: r(0), peakIndex: null };
  let peak = valid[0]; for (const x of valid) if (cmp([abs(x.signal[0]), x.signal[1]], [abs(peak.signal[0]), peak.signal[1]]) > 0) peak = x;
  return { signal: add(mul(parse(scope.meanWeight), div(sum(valid.map(x => x.signal)), r(valid.length))), mul(parse(scope.peakWeight), peak.signal)), confidence: div(sum(valid.map(x => x.confidence)), r(4)), peakIndex: peak.index };
}
const rank = (rows, caution = false) => [...rows].sort((a, b) => (caution ? 1 : -1) * cmp(parse(a.score), parse(b.score)) || -cmp(parse(a.confidence), parse(b.confidence)) || (a.date < b.date ? -1 : a.date > b.date ? 1 : 0)).map(x => x.date);
function feature(rule, context) {
  const f = { ruleId: rule.ruleId, source: 'ZODIAC', subject: 'PAIR', category: rule.category, direction: rule.signedValue > 0 ? 'POSITIVE' : rule.signedValue < 0 ? 'NEGATIVE' : 'NEUTRAL',
    signedValue: rule.signedValue, rawValue: rule.signedValue, baseWeight: 1, confidence: .75, period: { type: 'LIFETIME' }, metadata: {
      signA: context.signA, signB: context.signB, pairId: context.pairId, relation: context.primaryRelation, modelVersion: context.modelVersion, dateRangeVersion: context.dateRangeVersion } };
  f.featureId = featureId(f); return f;
}
function featureId(f) {
  const keys = f.source === 'ZODIAC' ? ['signA', 'signB', 'pairId', 'relation', 'modelVersion', 'dateRangeVersion'] : ['pillar', 'relation', 'referenceId', 'ruleVariant'];
  return digest('ft_', { ruleId: f.ruleId, subject: f.subject, category: f.category, period: f.period, source: f.source, metadata: Object.fromEntries(keys.filter(k => Object.hasOwn(f.metadata, k)).map(k => [k, f.metadata[k]])) });
}
module.exports = { catalog, scope, weights, abs, r, parse, add, sub, mul, div, cmp, sum, str, half, pack, empty, canon, digest, pair, date, zodiac, blend, four, rank, feature, featureId };
