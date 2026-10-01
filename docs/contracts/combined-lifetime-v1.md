# Combined Lifetime Orchestration V1

Status: **COMBINED_LIFETIME_CONTRACT_APPROVED / APPLIED**.
Authority: CL-01..CL-05 final decision `9775706d-ff65-4569-9e70-e7f765b19888` and implementation request `1c8156a7-1b02-4ab9-88f4-1eecfa408af6`.
Internal version: `LIFETIME_COMBINED_SCORING_V1`. No numeric catalog, public version or schema change.

## Responsibilities and existing math

`Application/Score/CombinedLifetimeService` validates versioned source inputs, adapts their category maps to the existing `LifetimeBlender`, and assembles versions/context. It never calculates Four Pillars, extracts Features, re-averages candidates, classifies Zodiac pairs, applies SC-07 or duplicates blend/coverage/confidence/status formulas. `LifetimeSourceEnvelope` supplies the normalization and source validation boundary. No raw MULTI Feature is required.

The unchanged [Zodiac V1 math](zodiac-catalog-v1.md#lifetime-blending-and-m2) is normative:

```
sourceWeights = SAJU:4/5, ZODIAC:1/5
D[c] = sum(structurally eligible source weights)
categoryScore[c] = 50 + sum(numeric sourceWeight * (sourceScore - 50)) / D[c]
categoryCoverage[c] = sum(eligible sourceWeight * sourceCoverage) / D[c]
categoryWireConfidence[c] = sum(eligible sourceWeight * sourceWireConfidence) / D[c]
categoryPreCoverageConfidence[c] = wire / coverage, or 0 when coverage=0
```

Runtime insufficient remains in D and contributes no score deviation. Null source score does not erase its actual coverage. No computable source yields null score/confidence0, retaining coverage. Computed neutral50 is different from missing data. Saju score already includes its SC-07/stabilization; do not repeat either. Do not multiply coverage or confidence into score again.

Overall score and pre-confidence use **computable category weight sum**, not fixed .86. Overall coverage uses **43/50** eligible structural weight, including runtime-missing categories. Overall wire confidence=overallCoverage*overallPreCoverageConfidence once. Empty computable set gives null overall/confidence0 with actual coverage retained. COMMUNICATION has no denominator mass.

## CL-01 and dependency binding

| Dependency | Identity |
|---|---|
| Saju scorer | SAJU_LIFETIME_SCORING_V1 |
| Saju catalog | 1.0.0 |
| Zodiac source/model | FIXED_DATE_SINGLE_SIGN_V1 |
| Zodiac catalog | ZODIAC_CATALOG_V1 |
| Zodiac date ranges | ZODIAC_DATE_RANGE_V1 |
| Product scope | ZODIAC_V1 |
| Category weights | 2.0.0 |

These are internal code-owned dependencies, not arbitrary request-selected configuration. Unknown/missing/extra dependency identity fails closed. Existing generated Zodiac configuration remains unchanged. It carries the category weights but not their JSON contractVersion; the Combined code-owned manifest records2.0.0, and the validation gate verifies it against the authoritative category-weights.json plus the checked generated projection. No new M2 identifier is invented. Source weights are still exact4/5 and1/5, applied by the existing blender once.

## CL-02 / CL-03 source envelope and normalization

The typed readonly envelope stores `sourceId`, `versions`, `categories`, optional `sourceContext`. `versions` holds `sourceVersion`, `catalogVersion`, plus `dateRangeVersion` for Zodiac. Saju sourceVersion is the scorer version; Zodiac sourceVersion is the model identity, not public SCORE_ZODIAC_V1. Source version/catalog/date identifiers are required and checked, not inferred from an unlabelled incoming map.

Canonical category order: ATTRACTION, EMOTION, COMMUNICATION, PASSION, STABILITY, HARMONY, SUPPORT, LONG_TERM.

- Saju eligible subset: all except COMMUNICATION (7).
- Zodiac eligible subset: ATTRACTION, EMOTION, PASSION, HARMONY (4).
- Full envelope: exactly8 keys for either source.

`fromEligible(sourceId, versions, categories, context)` first requires the exact eligible subset and typed CategoryResult values. Only structurally ineligible keys are filled with null/coverage0/confidence0. It never fills missing eligible results. `fromSaju(result)` checks the existing scorer's full dependency identity and preserves Ten Gods; Zodiac callers pass already calculated category results and declared identities, optionally with the existing static context. The envelope constructor requires the complete8-key map. Lists are not supported: duplicate-category row lists are rejected before any map conversion. PHP maps cannot represent duplicate keys after an upstream overwrite; this internal interface does not claim to detect duplicates already lost outside its boundary.

Combined input is a list of exactly two typed envelopes, one SAJU and one ZODIAC. Duplicate, unknown, unexpected or missing source fails closed. Before calling LifetimeBlender the adapter removes only structural ineligible slots, preserving the original7/4 eligible subsets. Existing source scorers and blender need no math/shape change.

Missing key is an error; explicit CategoryResult(score=null, actualCoverage, confidence0) is normal insufficient. Ineligible slots must be null/0/0. Current CategoryResult is final/readonly and validates0..100 score,0..1 coverage and0<=confidence<=coverage, requires confidence0 when score=null and positive coverage when score is numeric. Status is derived by its canonical method; this interface accepts no separately stored status. Exact Rational scores are preserved, HALF_UP4 used only for canonical status/serialization, not intermediate blend or ranking. No new clamp.

Internal errors: INVALID_COMBINED_SOURCE, SOURCE_VERSION_MISMATCH, MISSING_REQUIRED_CATEGORY, INVALID_COMBINED_CATEGORY, INELIGIBLE_COMBINED_CATEGORY, COMBINED_CONTRACT_MISMATCH, SOURCE_CONTEXT_MISMATCH, INVALID_COMBINED_CONTEXT. Existing type/CategoryResult errors propagate. No errors become insufficient and no new public error registry is introduced.

## CL-04 public boundary

Internal registry key `score.lifetime_combined` maps to LIFETIME_COMBINED_SCORING_V1. Public SCORE_ZODIAC_V1/configVersion/schema remain unchanged. Public activation requires Combined implementation plus approved public projection/API contract, schema readiness and compatibility policy. New public IDs, old-client/signed-context compatibility, activation/migration and version-key policy are future PUBLIC COMBINED/API CONTRACT decisions. User-derived shared caches remain prohibited.

## CL-05 result/context envelope

Output: `combinedVersion`, `dependencies`, `categories`, `overall`, `overallPreCoverageConfidence`, `contexts`. Numeric fields are the existing blender's objects; CategoryResult is not overloaded. Source contexts are keyed SAJU then ZODIAC and bind sourceId plus version provenance to the preserved context. Optional absent context stays absent. Object keys are recursively sorted, semantic list order retained; no candidate or Feature content is invented. Zodiac context source/model/date identity is checked against its envelope. Saju Ten Gods comes from the trusted internal scorer adapter. Context is never supplied to the blender.

These are trusted in-process results, not an authenticated public input format. Context canonical ordering does not certify arbitrary user-supplied context or implement public signing/projection. No logging, persistence, shared user cache, tracking, network, AI, current-time or random dependency is added. Context may retain the existing extractor's request-local lineage; Combined math does not inspect it or use it as user identity.

## Daily boundary and verification

Future overall Daily consumes `combined.overall.score`; category Daily consumes `combined.categories[c].score`. No generic revisedCombinedLifetimeScore field. Null stays null; overall signal must not be copied into categories. Daily orchestration, Public MULTI, REST, AI, frontend and Admin remain outside this implementation.

New tests exercise required Golden values, partial confidence, null coverage, overall denominators, source/version/shape failures, order independence, context binding, status precision, all188 retained Lifetime goldens and actual Natal/Saju+original-date Zodiac→Combined E2E. Existing fixtures are never regenerated. Gate: `node docs/contracts/validation/combined-lifetime.cjs`.
