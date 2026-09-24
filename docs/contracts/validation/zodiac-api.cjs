'use strict';
const fs = require('node:fs'), path = require('node:path'), assert = require('node:assert/strict'), crypto = require('node:crypto');
const M = require('./zodiac-model.cjs');
assert.equal(M.canon({label: 'Cafe\u0301'}), M.canon({label: 'Caf\u00e9'}));
assert.throws(() => M.canon({'e\u0301': 1, '\u00e9': 2}));
const root = fs.existsSync('.tools/contract-validation/node_modules/ajv') ? path.resolve('.tools/contract-validation/node_modules') : path.join(process.env.TEMP, 'love-fortune-contract-validation/node_modules');
const Ajv = require(path.join(root, 'ajv/dist/2020')).default, formats = require(path.join(root, 'ajv-formats'));
const ajv = new Ajv({ strict: false, allErrors: true }); formats(ajv);
ajv.removeKeyword('multipleOf'); ajv.addKeyword({ keyword: 'multipleOf', type: 'number', schemaType: 'number', validate: (s, v) => { const x = M.div(M.parse(v), M.parse(s)); return x[1] === 1n; } });
const read = p => JSON.parse(fs.readFileSync('docs/contracts/' + p, 'utf8'));
for (const name of fs.readdirSync('docs/contracts/schemas')) ajv.addSchema(read('schemas/' + name));
const schema = n => ajv.getSchema('https://love-fortune.invalid/contracts/schemas/' + n + '.schema.json');
const cases = read('examples/zodiac/manifest.json').cases;
for (const c of cases) { const check = schema(c.schema); assert.equal(check(read('examples/zodiac/' + c.file)), c.valid, c.file + ' ' + JSON.stringify(check.errors?.slice(0, 3))); }
let contexts = 0, features = 0;
for (const a of M.catalog.referenceTables.signs) for (const b of M.catalog.referenceTables.signs) {
  const ctx = M.pair(a.sign, b.sign); assert(schema('zodiac-context')(ctx)); contexts++;
  for (const rule of M.catalog.rules.filter(r => r.relation === ctx.primaryRelation)) {
    const f = M.feature(rule, ctx); assert(schema('feature')(f), JSON.stringify(schema('feature').errors)); assert.equal(f.featureId, M.feature(rule, M.pair(b.sign, a.sign)).featureId); features++;
  }
}
for (const rule of M.catalog.rules) assert(schema('zodiac-rule')(rule));
assert(schema('zodiac-catalog')(M.catalog));
for (const versionType of ['ZODIAC_RULE', 'ZODIAC_DATE_RANGE']) assert(schema('admin')({versionType, version: 'synthetic-v1', action: 'ACTIVATE'}));
for (const versionType of ['ASTROLOGY_RULE', 'EPHEMERIS_PROVIDER', 'EPHEMERIS_DATA']) assert(!schema('admin')({versionType, version: 'synthetic-v1', action: 'ACTIVATE'}));
// Public fixture key, never a deployment secret. Entire result/context is authenticated.
const key = 'SYNTHETIC_ZODIAC_KEY_NOT_FOR_PRODUCTION';
const header = { alg: 'HS256', typ: 'LFIC', kid: 'fixture-zodiac', v: 1 };
const payload = read('examples/zodiac/signed-valid.json');
const encode = x => Buffer.from(M.canon(x)).toString('base64url');
function sign(p, h = header) { const head = encode(h) + '.' + encode(p); return head + '.' + crypto.createHmac('sha256', key).update(head).digest('base64url'); }
function verify(token, now = '2024-09-02T00:01:00Z', locale = 'ko-KR') {
  try {
    const segments = token.split('.'); assert.equal(segments.length, 3); assert(segments.every(s => /^[\w-]+$/.test(s)));
    const [h, p] = segments.slice(0, 2).map(s => JSON.parse(Buffer.from(s, 'base64url')));
    assert.deepEqual(h, header); assert.equal(encode(h), segments[0]); assert.equal(encode(p), segments[1]);
    const mac = crypto.createHmac('sha256', key).update(segments[0] + '.' + segments[1]).digest(), given = Buffer.from(segments[2], 'base64url');
    assert.equal(given.length, mac.length); assert(crypto.timingSafeEqual(given, mac));
    assert(schema('signed-context')(p)); assert.equal(p.locale, locale);
    assert.equal(Date.parse(p.expiresAt) - Date.parse(p.issuedAt), 300000); assert(Date.parse(now) >= Date.parse(p.issuedAt) && Date.parse(now) < Date.parse(p.expiresAt));
    assert.deepEqual({ ...p.engineVersions, scoreVersion: p.scoreVersion, configVersion: p.configVersion }, p.result.meta.versions);
    assert.deepEqual(p.zodiacContext, p.result.zodiacContext);
    if (p.zodiacContext) assert.deepEqual(p.zodiacContext, M.pair(p.zodiacContext.signA, p.zodiacContext.signB));
    const registered = new Map(p.result.features.map(f => [f.featureId, f]));
    for (const f of [...p.result.features, ...p.evidence]) {
      assert.equal(f.featureId, M.featureId(f));
      if (f.source === 'ZODIAC') {
        assert(p.zodiacContext); assert.equal(f.metadata.pairId, p.zodiacContext.pairId); assert.equal(f.metadata.relation, p.zodiacContext.primaryRelation);
      }
    }
    for (const f of p.evidence) assert.deepEqual(f, registered.get(f.featureId));
    return true;
  } catch { return false; }
}
assert(verify(sign(payload)));
let invalidSigned = 0;
for (const mutate of [
  p => p.zodiacContext.contextEvidenceId = 'ce_' + '0'.repeat(48),
  p => { p.zodiacContext = M.pair('ARIES', 'LIBRA'); p.result.zodiacContext = p.zodiacContext; },
  p => delete p.zodiacContext,
  p => { delete p.zodiacContext; delete p.result.zodiacContext; },
  p => p.evidence[0].featureId = 'ft_' + '0'.repeat(48),
  p => p.evidence[0].signedValue = .2,
  p => p.engineVersions.configVersion = 'wrong',
  p => p.result.meta.versions.configVersion = 'wrong',
  p => p.expiresAt = '2024-09-02T00:06:00Z',
  p => p.result.zodiacContext.birthDate = '2000-04-01',
  p => p.evidence[0].metadata.nickname = 'synthetic',
]) { const p = structuredClone(payload); mutate(p); assert(!verify(sign(p))); invalidSigned++; }
assert(!verify(sign(payload), '2024-09-02T00:05:00Z')); invalidSigned++;
assert(!verify(sign(payload), undefined, 'ja-JP')); invalidSigned++;
assert(!verify(sign(payload).slice(0, -3) + 'aaa')); invalidSigned++;
assert(!verify(sign(payload, { ...header, alg: 'none' }))); invalidSigned++;
console.log(JSON.stringify({ result: 'PASS', activeExamples: cases.length, valid: cases.filter(x => x.valid).length, invalid: cases.filter(x => !x.valid).length, contextCases: contexts, featureCases: features, catalogMappings: M.catalog.rules.length, signedValid: 1, signedInvalid: invalidSigned, privacy: 'PASS', canonicalization: 'PASS' }, null, 2));
