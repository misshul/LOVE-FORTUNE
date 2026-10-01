# Lifetime Saju Scoring Orchestration V1

Status: **SAJU_LIFETIME_SCORING_CONTRACT_APPROVED / APPLIED**.
Internal scoringVersion: `SAJU_LIFETIME_SCORING_V1`.
Authority: final version-boundary decision 4fbf4e9c-332c-496e-a0a6-6ff6f8849ccb and production application request 00252611-7fa6-4231-99a9-63a23010dba9. The existing numeric catalog, FE-01..06/FE-COV-01 and SC-07 v2 formulas are unchanged.

## Internal/public version boundary

Internal result dependencies:

| Field | Identity |
|---|---|
| featureExtractorVersion | SAJU_FEATURE_EXTRACTOR_V1 |
| catalogVersion | 1.0.0 |
| guardrailContract | GUARDRAIL_V2 |
| guardrailVersion | 2.0.0 |
| multiIdentityVersion | SAJU_MULTI_VARIANT_IDENTITY_V1 |
| coverageContract | FE-COV-01 |

Register `saju.lifetime_scoring` in the internal engine/version registry. Unknown scoringVersion or incorrect dependency identities fail closed. Service construction validates the code-owned dependency set; score input validates extractor/catalog/branch-polarity identities and each MULTI identity version. Extractor V1 does not itself carry guardrail/scorer metadata, so do not fabricate required upstream fields.

The Guardrail v2 requirement to increase versions applies to the internal source implementation through SAJU_LIFETIME_SCORING_V1 now. Public scoreVersion/configVersion transition occurs when the public combined contract actually changes in the later Combined Orchestration + API/Public Projection phase. Current public `scoreVersion=SCORE_ZODIAC_V1`, public configVersion behavior and schemas remain unchanged. Public and internal identities are different domains; the existing public scoreVersion is not an internal mismatch. Do not relabel historical results or expose these new internal metadata fields through the current public schema. Future public IDs, migration/compatibility window, old-client behavior, signed-context compatibility and cache/version-key policy require the later contract; no new public ID is assigned here. User-derived caches remain prohibited.

## Input and boundary validation

`SajuLifetimeScoringService::score(array $extraction): array` consumes the trusted internal Feature Extraction result. Four Pillars, candidate means, Feature confidence and variant-to-category groups are not recalculated. The actual aggregate input field is `features[].signedValue` (Rational), not a new aggregateSignedValue alias.

Validate registered rule/category groups, PAIR/SAJU/LIFETIME scope, exact Rational types/ranges, fixed catalog weights, canonical single/MULTI identities, duplicate groups/IDs, present variants and contribution provenance, ledger counts and coverage projections, and equality of the top-level Features with their ledger copies. Require the full set of 26 catalog rule/category groups, including no-match groups; omitted groups cannot shrink denominators. All groups share the extractor's candidate-pair universe. Validate the extractor category coverage against its group projections using the existing SajuCoverageProjection. Validation of counts/projections does not reinterpret missingness or re-average candidate signedValues/confidence.

Present MULTI signedValue must lie within its registered present category mapping bounds; single variants equal their one approved mapping. The extractor remains responsible for exact candidate aggregation. This is trusted in-process integration, not authentication of adversarial but internally consistent fabricated arrays. No public feature parser is introduced.

Ten Gods is validated as context and preserved in parallel; it contributes no numeric weight. HIDDEN_STEM_RELATION, PUNISHMENT, HARM and DESTRUCTION remain DEFERRED. Active Lifetime catalog stays 14 families / 9 scoring / 16 variants / 37 mappings / 5 context. No context/deferred family enters category eligibility.

## Categories and coverage

Active family counts per category: ATTRACTION 1, EMOTION 2, PASSION 2, STABILITY 4, HARMONY 9, SUPPORT 4, LONG_TERM 4. COMMUNICATION has 0 mappings. The service returns seven source categories, omitting COMMUNICATION to match the existing LifetimeBlender source-input interface. Do not call that blender in this phase or create a fake neutral category.

For each rule/subject/category/period group, let w be its static preConfidenceWeight, E eligibleCount, C computableCount. For E>0: coverageEligibleWeight=w; coverageAvailableWeight=w*C/E. For E=0 both weights are 0 and ratio is null. Category coverage is sum(available weights)/sum(eligible weights), or 0 if the denominator is zero. Reuse FE-COV-01; never derive coverage solely from Feature object existence. Count w once per group, not per variant or candidate. Missing Hour completeness is already applied to Feature confidence and does not reduce coverage again.

## Category calculation

Actual available Features include confidence=0. Usable Features have confidence>0 and preConfidenceWeight>0. NOT_MATCHED-only and UNAVAILABLE groups have no Feature. For actual Features:

```
p_i = baseWeight * ruleWeight * pairWeight
e_i = p_i * confidence_i
W0 = sum(usable e_i)
diagnosticRawScore = 50 + 50 * sum(e_i * signedValue_i) / W0
```

No source weight, category weight or candidateAvailabilityRatio enters p_i/e_i. The existing exact Rational library is reused.

SC-07 v2 per usable aggregate Feature (single and MULTI follow the same path):

```
impact_i = 50 * e_i * abs(v_i) / W0
adjustedWeight_i = e_i                       if impact_i <= 20
adjustedWeight_i = min(e_i, (2/5)*W0/abs(v_i)) otherwise
guardedScore = 50 + 50 * sum(adjustedWeight_i * v_i) / W0
stabilizedScore = 50 + (guardedScore - 50) * coverage
```

Zero signal retains weight; no division by zero. W0 never changes. Removed mass is neutral, not redistributed; never divide by adjusted-weight sum. Do not guard individual candidate contributions, iterate a binary search, exclude capped Features or alter their original identity/value/confidence. Stabilize the guarded score exactly once, not the diagnostic raw score.

With usable Features:

```
categoryResultConfidence = sum(actual available p_i * confidence_i)
                           / sum(actual available p_i)
resultConfidence = coverage * categoryResultConfidence
```

The pre-confidence denominator includes confidence=0 Features. It excludes NOT_MATCHED-only/UNAVAILABLE pseudo Features and does not use coverageAvailableWeight. Thus FE-COV unavailable attenuation enters confidence once through coverage. SC-07 does not change confidence or coverage.

## Insufficient, precision and result model

No usable Feature: score=null, status=INSUFFICIENT_DATA, resultConfidence=0, preserve computed coverage. This includes all NOT_MATCHED (coverage may be 1), all unavailable (coverage=0), and all confidence=0 (coverage preserved). W0=0 does not divide and raw/guarded/stabilized diagnostic scores are null. Usable Features with coverage=0 are inconsistent input, not a manufactured score 50.

The existing CategoryResult stores stabilized score/null, coverage and final confidence. Its status uses the canonical four-decimal HALF_UP score and existing thresholds; ranking/comparison retains the exact unrounded score. No intermediate rounding, epsilon, hidden clamp or public serialization is added. Valid formulas stay in [0,100]; existing CategoryResult validates the range.

Result array: scoringVersion, dependencies, categories (CategoryResult objects), diagnostics per category, tenGods. Diagnostics contain W0, diagnosticRawScore, guardedScore, stabilizedScore, categoryResultConfidence, usableFeatureCount, canonical sorted usable evidenceRefs, per-Feature original/adjusted effectiveWeight and before/after impacts, and neutralMass. These values are request-local and are not public Feature metadata. Keep all input evidence/identities intact. No new Saju-only overall score or global category-weight aggregation.

M1/N0/U1 unit example: v=2/5, p=6/5, baseConfidence=1 gives Feature confidence=1, coverage=1/2, W0=6/5, impact=20 (not capped), guardedScore=70, stabilizedScore=60, categoryResultConfidence=1, resultConfidence=1/2. Other real category groups remain part of full-envelope coverage; this example is an isolated group/kernel regression.

## Fail-closed errors and privacy

Internal InvalidArgumentException/RuntimeException codes: INVALID_FEATURE_EVIDENCE (scope/type/range/weight/identity/group/projection inconsistency), INVALID_MULTI_IDENTITY, SCORING_CONTRACT_MISMATCH, SCORING_ORCHESTRATION_FAILED (guard invariant failure). Reuse FEATURE_CATALOG_MISMATCH and existing extractor ledger/context errors where raised. These are not newly registered public error codes. No partial success or neutral fallback for errors. Generic lifecycle ErrorHandler is not a scoring fallback and is not invoked by this service.

No request logging, persistence, network, AI, external service, astronomy dependency, random/time/locale behavior, shared user cache or tracking ID. Features and diagnostics stay in memory. Test vectors use synthetic inputs.

## Protected scope and validation

Zodiac scorer/catalog, source weights 4/5 and 1/5, M2, combined overall, public MULTI projection, REST/frontend/AI remain separate. Daily 9/19, K=18, sample aggregation, signed peak, confidence/status are unchanged; do not call this kernel for Daily signals. Existing fixtures are not regenerated.

Production components: SajuLifetimeScoringValidator (input/catalog/identity consistency), SajuCategoryScorer (exact raw/guardrail/stabilization/confidence), SajuLifetimeScoringService (validated seven-category orchestration/context/version result). Reuse Rational, CategoryResult and SajuCoverageProjection rather than duplicate those engines. The low-level scorer accepts validated numeric evidence; catalog and identity checks belong to the service boundary.

Validation: `node docs/contracts/validation/saju-lifetime-scoring.cjs` runs existing full regression plus current scope/version/privacy protections. Synthetic algebraic boundary tests do not imply every test combination is reachable from a Natal chart. Real Natal -> Feature -> Scoring tests separately cover UNIQUE, Fold, Unknown and both multi-candidate scenarios.
