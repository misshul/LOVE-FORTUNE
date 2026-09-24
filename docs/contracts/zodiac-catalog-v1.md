# Zodiac V1 contract and catalog

Version: 1.0.0
Status: APPROVED / APPLIED
Authority: final Zodiac simulation decision, corrected confidence decision, and the user's Zodiac V1 repository application/resumption instructions (2026-09-18).

This decision supersedes the earlier active Astrology source model, unified Lifetime feature aggregation, overall confidence wire label, and dual-source Daily calculation **for product V1**. Unchanged Saju, privacy, HTTP, period, status and SC-07 v2 contracts remain authoritative. Earlier Astrology approvals are preserved as ADVANCED / DEFERRED, not revoked research.

## One authoritative reference

[rules/zodiac-rules.json](rules/zodiac-rules.json) owns the canonical twelve signs, inclusive date ranges, elements, modalities, polarities, opposites and twenty relation/category mappings. It owns the model, date range, catalog and score identifiers. [product-scope.json](product-scope.json) owns source eligibility, rational source weights and Daily coefficients. [category-weights.json](rules/category-weights.json) remains the category-weight authority.

`node scripts/generate-zodiac-config.cjs` projects those three JSON inputs into the deployed plugin's `config/zodiac.php`. `--check` compares the entire generated output without writing. PHP decimal configuration values are strings, not binary floats. The generated file is packaged with WordPress because production must not read repository documentation. It is not an independently editable reference. The context/evidence schema branches are validation projections of the same catalog; `zodiac-api.cjs` checks every pair and every mapping. Golden fixtures are frozen expectations, not runtime configuration.

## Date and pair contract

`ZODIAC_DATE_RANGE_V1` / `FIXED_DATE_SINGLE_SIGN_V1` uses only the validated **original Gregorian birthDate**, 1900-01-01 through 2099-12-31. Reject invalid dates. Do not use birthTime, location, timezone, UTC-converted date, Saju calculation date, actual solar ingress or ephemeris. Capricorn wraps the year; leap day belongs to Pisces. Birth time unknown does not reduce the fixed Zodiac confidence.

Canonical order is the sign-array order in the catalog. Sort both signs by that order; `pairId=ZODIAC_PAIR_<SIGN1>_<SIGN2>`. `signA/signB` in context are canonical positions, not person identities. A/B swaps preserve context, scoring, pairId and scoring evidence identity.

Primary precedence: SAME_SIGN, OPPOSITE_SIGN, SAME_ELEMENT, COMPATIBLE_ELEMENT, CHALLENGING_ELEMENT. Compatible elements are FIRE/AIR and EARTH/WATER; remaining different-element pairs are challenging. Choose exactly one relation. Element/modality/polarity descriptions never stack bonuses. Expected unordered counts: 12/6/12/12/36; 78 unordered, 144 ordered.

## Approved numeric mapping

This table is a readable projection of the catalog. `signedValue=(sourceScore-50)/50`. Each mapping has normalized unit `baseWeight=1`, fixed `ruleConfidence=3/4`, `NO_SC07`, no pair override. Unit weight represents one selected relation; it is not an additional fortune weight. Direction follows signedValue; zero is real neutral evidence.

| Relation | ATTRACTION | EMOTION | PASSION | HARMONY |
|---|---:|---:|---:|---:|
| SAME_SIGN | 50 | 62.5 | 50 | 57.5 |
| OPPOSITE_SIGN | 75 | 40 | 70 | 37.5 |
| SAME_ELEMENT | 62.5 | 65 | 57.5 | 67.5 |
| COMPATIBLE_ELEMENT | 67.5 | 60 | 62.5 | 62.5 |
| CHALLENGING_ELEMENT | 50 | 37.5 | 52.5 | 35 |

Twenty numeric mappings; COMMUNICATION has zero. Zodiac STABILITY/SUPPORT/LONG_TERM are structurally ineligible. PASSION means all-ages chemistry, energy and activation.

## Lifetime blending and M2

Pipeline: Saju Features → existing SC-07 v2 → Saju source category score; Zodiac primary relation → Zodiac source category score; then category-level source blend → category-weighted overall. Saju guardrail is applied before blending, never after. Zodiac never passes through SC-07. Source weights apply exactly once here: SAJU=4/5, ZODIAC=1/5.

For each category, let `D` be the sum of **structurally eligible** source weights. Runtime unavailable sources remain in D but add no deviation, coverage or confidence. Structurally ineligible sources are excluded from D. If any source is computable:

```text
categoryScore = 50 + sum(available sourceWeight * (sourceScore - 50)) / D
categoryCoverage = sum(sourceWeight * sourceCoverage) / D
categoryWireConfidence = sum(sourceWeight * sourceWireConfidence) / D
categoryPreCoverageConfidence = categoryWireConfidence / categoryCoverage
```

If no source has a computable score, category score is null, wire confidence is zero, status is INSUFFICIENT_DATA; retain actual structural coverage. Never create neutral Features for missing data. Source all-zero confidence can retain coverage while remaining uncomputable. Real computed neutral mappings still score 50.

Lifetime COMMUNICATION is CONTEXT_ONLY because neither active source has Lifetime numeric evidence for it. Its score is null (never 0 or 50), coverage/confidence are zero, and its weight .14 participates in none of the score, coverage or confidence denominators. Numeric eligible structural category weight is .86. Overall score is the category-weighted mean over computable categories, not a fixed .86 score denominator.

## SC-01 confidence

```text
overallCoverage = sum(eligible categoryWeight * categoryCoverage) / .86
overallPreCoverageConfidence =
  sum(computable categoryWeight * categoryPreCoverageConfidence)
  / sum(computable categoryWeight)
overallWireConfidence = overallCoverage * overallPreCoverageConfidence
```

Empty computable set gives null overall score and zero confidence. `resultConfidence` is the wire confidence. Coverage is independent of confidence. M2 is only source/category blending; it must not replace the computable-category denominator of overall pre-coverage confidence.

Final simulation reference means: pre-coverage ≈0.691385; wire ≈0.573655. These are population statistics, not constants or the product of population means. The old “0.691385 = wire” label is corrected. Fixed-denominator ≈0.571541 was rejected; intermediate ≈0.589748 is not wire confidence.

## Daily and period precision

V1 dynamic Daily source is SAJU ONLY, with the unchanged nine rules/nineteen mappings. Zodiac is STATIC context. Preserve source-internal category eligibility, candidate aggregation, no-event semantics, nominal slots 06/12/18/23 and existing gap/fold resolution.

```text
sajuDailySignal = 3/4 * mean(valid nominal signals) + 1/4 * signedPeak
dailyConfidence = sum(four nominal confidence values, missing=0) / 4
dailyDelta = 18 * sajuDailySignal
dailyScore = clamp(revisedCombinedLifetimeScore + dailyDelta, 0, 100)
```

Absolute-magnitude peak comparisons are exact; ties retain the earlier nominal slot. Missing slots never reorder the nominal indices. Zero valid samples retain existing INSUFFICIENT_PERIOD_DATA fallback; null Lifetime stays null. Daily internal Saju COMMUNICATION evidence still contributes to its approved internal signal. Its **final category score remains null**, because Lifetime COMMUNICATION has no numeric baseline. Optional closed `categoryScores` exposes final scores and requires COMMUNICATION=null; it does not expose an internal signal as a score.

Keep Daily thresholds: <=-5 VERY_LOW; (-5,-2] LOW; (-2,2) STABLE; [2,5) GOOD; >=5 VERY_GOOD. No recalibration. The positive Daily skew is an accepted product characteristic. Existing Weekly/Monthly/Yearly mean, validity, trend and fallback contracts remain.

Exact rationals/integers govern signed values, weights, coverage, confidence, score and rank. Compare by integer cross multiplication. Best uses score DESC, confidence DESC, date ASC; caution uses score ASC, confidence DESC, date ASC. No epsilon, binary-double equality or API-rounded score in ranking. At serialization use four-decimal HALF_UP, signed ties away from zero, without negative zero. Lifetime score status uses the canonical four-decimal score; ranking retains unrounded values.

The prior binary-double simulation differs in 120 Daily values: EXPECTED_PRECISION_CORRECTION. Opposite-sign peaks have exactly equal magnitude, so earlier nominal selection is mandatory. Representative frozen fixtures include `[4/55,-4/55,-4/55,-4/55] → -1/110` and the 7/352 class. Old-double actual signal maximum difference was 0.03636363636 (delta18 difference 0.654545455); 514 peak-index differences included 120 actual signal differences. These are historical population diagnostics; the application reruns representative classes, not a claim of rerunning all 3,952,800 population cases.

2024-09-02 and 2024-09-08 share exact signal 9593/58608; confidence 293/352 versus 227/352 selects September 2 first. HALF_UP and beyond-native-integer comparisons have independent PHP/JS goldens.

## Evidence, signed context and AI

Active V1 Feature.source is SAJU or ZODIAC. Zodiac numeric Features have LIFETIME period, PAIR subject and only the four numeric categories. Zodiac metadata is closed: signA,signB,pairId,relation,modelVersion,dateRangeVersion; all six are identity-relevant. Feature identity retains canonical `{ruleId,subject,category,period,source,metadata}` and `ft_` plus 48 SHA-256 hex digits. No raw Birth or labels enter identity.

`zodiac-context.schema.json` is a separate closed static context: source=ZODIAC, contextRole=STATIC, canonical signs/pair, primary/element/modality/polarity relations, model/date versions and contextEvidenceId. Context identity is sorted canonical JSON of all those fields except contextEvidenceId, with `ce_` plus 48 SHA-256 hex digits. IDs identify semantic evidence, never users or tracking.

Core responses may contain `zodiacContext`. Signed LFIC payload uses contextVersion=ZODIAC_CONTEXT_V1 and, when present, contains the **identical** context as its result. Zodiac Features require matching pair/relation and recomputed identities; evidence is an exact approved subset of result.features. Verify closed schema, HMAC, canonical bytes, purpose, locale, 300-second TTL, versions and context/evidence binding. Never accept unsigned extra context. LFIC header/algorithm/key management are unchanged. Production signing/REST/AI orchestration remains a subsequent implementation task; this application supplies schemas and semantic signing vectors.

AI may explain “별자리 성향 기준으로” or “12별자리 궁합 모델에서는”. Do not claim actual planet positions, Venus/Mars/Moon transit, aspects, orbs or actual Sun longitude in V1. COMMUNICATION explanations remain context-only; no numeric score, confidence, dailyDelta or Action score influence. `ce_` cannot replace scoring `ft_` evidenceRefs or Action dependencies. Scoreless output, deterministic fallback, minor/celebrity safety and no prompt/output logging remain mandatory.

## API, versions, scope and privacy

Routes and request DTOs remain unchanged. V1 metadata adds zodiacEngineVersion, zodiacCatalogVersion and zodiacDateRangeVersion, with scoreVersion=SCORE_ZODIAC_V1. Active metadata rejects astrologyEngineVersion, ephemerisProviderVersion and ephemerisDataVersion. Advanced schema snapshots retain the earlier model and tokens; manifest entries explicitly identify that scope. New V1 examples include positive/negative schema cases, boundaries and token semantic rejection tests.

SAJU and ZODIAC are V1 ACTIVE; ASTROLOGY is ADVANCED / DEFERRED. Never use enabled=true alone without source/period/product-scope gating. Preserve Lifetime Astrology109/228 and Daily Astrology125/266, all earlier research/fixtures, and Saju rule values unchanged. EPHEMERIS_PROVIDER is NOT_REQUIRED_FOR_V1 and DEFERRED_ADVANCED_BLOCKER.

No Swiss/JPL/Skyfield/Astropy or remote astronomical dependency is added. Original project-owned explanatory text only. BirthDate is transient input and must not reach URLs, logs, analytics or AI context. No general-user storage/cache, stable anonymous IDs, fingerprinting or background transmission. No new tables, routes, frontend or Admin features. Deployed infrastructure capture still needs independent operational verification.

See [readiness](readiness.md) for precise implementation boundaries and [validation report](validation-report.md) for executed tests.
