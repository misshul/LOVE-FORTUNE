# SAJU DAY PILLAR EPOCH V1 APPLICATION AND IMPLEMENTATION

Current result: PASS. SAJU_DAY_PILLAR_EPOCH_V1 APPLIED / READY; SAJU_DAY_PILLAR_EPOCH RESOLVED. Sole external blocker: EPHEMERIS_PROVIDER. Production Engine readiness remains BLOCKED_EXTERNAL. Authority: [application approval](saju-day-pillar-epoch-v1-application-approval.md), [contract](saju-day-pillar-epoch-v1.md). Historical Daily report below retains its original scope/results; its old epoch gate is superseded.

## Current execution evidence

Command: `node docs/contracts/validation/epoch-application.cjs`. Exit0. Runs all existing contract validators, PHP lint and actual PHPUnit through the existing pinned test script. Saved actual output: [validation-results-epoch-v1.json](validation-results-epoch-v1.json). Runtime PHP8.3.33,64-bit, PHP timezone data2026.1; Node24.21.0 timezone data2026c. Different runtime timezone releases are recorded, not silently claimed identical. Deployments must pin the applicable timezone release.

| Check | Actual result |
|---|---|
| PHP lint / PHPUnit | PASS;15 tests,1,216,008 assertions |
| Actual WordPress smoke | PASS; active plugin discovered/rendered, no table/option changes, no business REST routes |
| Golden absolute dates / anchor | 20/20;2019-01-27 JDN2458511/index0/甲子 |
| Internal range | 73,053 dates; independent cumulative ordinal comparison PASS |
| Adjacent /60-day pairs | 73,052 /72,993 PASS |
| Leap / modulo | 1900 and2100 non-leap;2000 leap; -10000..10000 PASS |
| Production date adapters |23:29/30/31, historical offset, natal local reference, Daily gap/fold/skipped date PASS |
| Unknown time | Supplied calculation-date candidate order/multiplicity retained; no fabricated time |
| Public range | Validator and Daily entry reject outside1900..2099; internal boundaries accepted |
| Lifetime Saju |14 families:9 scoring/5 context; existing population regression PASS |
| Daily |134 rules/285 mappings;256,000 synthetic cases; hard failures0 |
| Lifetime Astrology |109 rules/228 mappings; exact approved matrix PASS |
| SC-07 v2 |Lifetime only; impact violations0/exclusions0; Daily unchanged |
| Schemas / examples |29 schemas;203 examples:113 valid accepted/90 invalid rejected |
| OpenAPI |PASS;20 operations; public/signed wire unchanged |
| Arithmetic/period/DST |80 vectors including4 DST PASS |
| Canonical / signed context |6 canonical;2 valid and8 invalid signed PASS |
| HTTP/privacy |19 existing vectors PASS; new PHP has no persistence/cache/network/logging |
| Contract Freeze / SPEC_CONFLICT |PASS /0 |
| Effective cross-spec contradictions |0; active epoch gates updated; historical records retain context |
| git diff --check |PASS |

## Implementation review and limits

Production adds GregorianDayNumber, DayPillarCalculator, CalculationDateResolver, DailySampleResolver and SajuDayPillarService under src/Engine/Saju, plus explicit bootstrap registrations. Domain logic has no WordPress dependency. Formula uses integer arithmetic and floorMod; strict date validation rejects normalization; immutable inputs are not mutated. Wall-clock correction/boundary is separate and applied once. Historical offset seconds are preserved. No overflow in JDN arithmetic; full timezone coverage is tested on64-bit PHP. No new public API field, frontend, DB schema, Docker or WordPress UI change.

Natal integration starts after location/reference and gap/fold resolution. Unknown-time candidate generation remains upstream. API range tests exercise the real internal validator/entry, not an unimplemented HTTP controller. This is not certification of the complete four-pillar/score/API engines or deployed infrastructure capture settings. The previous synthetic Daily population remains unchanged and is not mixed with real epoch results.

Initial PHPUnit run found one incorrect newly-authored Natal integration probe: New York23:29 plus the approved34-minute adjustment is already next date. The same-date contrast input was corrected to22:55 (+34=23:29). Production formula and all20 approved Golden expected values were untouched. Final suite passed.

No commit/push. Current application file list is reported with the final Git status; the list below belongs only to the historical Daily application.

---

# Historical: DAILY CATALOG V1 REPOSITORY APPLICATION REPORT

Status: PASS. Authority: [C21-R application approval](daily-v1-application-approval.md).
Scope: documentation, catalogs, schemas, examples and documentation validators only. No PHP/WordPress/Frontend/DB/Docker implementation, commit or push.

## Recovery and gates

Initial git status --short, git diff --stat and git diff --check were clean. No partial modifications required recovery or reset. Approved matrix and recovered synthetic input generators were preserved in repository files so future runs do not depend on in-memory state.

| Gate | Result |
|---|---|
| Contract Freeze | PASS |
| SPEC_CONFLICT / effective cross-spec contradictions | 0 / 0 |
| Daily Catalog | APPLIED / READY |
| Astrology / Saju catalogs | READY / READY |
| Score Engine catalog readiness | READY |
| Remaining catalog blockers | NONE |
| Production Engine readiness | BLOCKED_EXTERNAL |
| External blockers | SAJU_DAY_PILLAR_EPOCH, EPHEMERIS_PROVIDER |

Catalog readiness does not certify implemented production engines or verified runtime astronomy/calendar providers.

## Applied contracts

- Daily Astrology125 rules/266 mappings; Saju9 rules/19 mappings; total134/285. All approved scoring rules enabled. Duplicate IDs, sign mismatches and numeric matrix deviations0.
- Daily Astrology orb1 degree inclusive;750 exact/inside/boundary/outside probes plus wrap-around pass. No planet orb adjustment, natal weight, Lifetime pair override or orbCloseness multiplier.
- Saju100 stem pairs and144 branch pairs pass ownership precedence; synthetic civil23:29/23:30/23:31 and historical-offset adjustment boundary checks pass without an epoch.
- S2 thresholds: <=-5 VERY_LOW; (-5,-2] LOW; (-2,2) STABLE; [2,5) GOOD; >=5 VERY_GOOD. Existing fallback/null and canonical precision contracts retained.
- M1-ELIGIBILITY-AWARE applies fixed structurally eligible denominators to overall source, category source and source-internal category signals. Runtime unavailable retains denominator mass. Structural ineligibility is excluded. STABILITY Saju.4 -> .4; EMOTION with only runtime-available Saju.4 -> .2.
- No-event is signal0/confidence1 without fake Features; unavailable is not computed neutral. Four logical sample slots remain; computable-only mean/peak and fixed-four-slot confidence remain separate. Pair projection occurs after person/category/sample aggregation.
- Daily INTERNAL sampleRef/lineage identity schema and tests prevent cross-sample candidate merging. Same semantic sample produces the same ID; different sample produces a different ID. Public Feature, LFIC signed wire and OpenAPI file are unchanged. Public category Daily fields/context export remain deferred/non-blocking.
- SC-07 remains Lifetime-only. Daily guardrail/secondary/outer/MC scoring0. Existing privacy/no-store/stateless restrictions retained. Directional bias and sample-missing amplification remain accepted uncalibrated v1 characteristics.

## Executed validation

Command: node docs/contracts/validation/daily-application.cjs

This executes structural.cjs, semantic-regression.cjs, astrology.cjs, saju-guardrail-v2.cjs and daily.cjs; all exited0. Complete actual output: [validation-results-daily-v1.json](validation-results-daily-v1.json).

| Check | Result |
|---|---|
| JSON Schemas | 29 PASS |
| Manifest examples | 203: 113 valid accepted / 90 invalid rejected |
| OpenAPI | PASS;20 operations |
| Existing arithmetic / period / DST regression | 80 vectors PASS;4 DST vectors |
| Public canonicalization | 6 vectors PASS |
| Signed context | 2 valid /8 invalid PASS |
| HTTP / privacy | 19 vectors PASS |
| Lifetime Astrology | 109/228 exact approved-matrix match |
| Lifetime Saju | 14 families /9 scoring /5 context;57,600 population cases PASS |
| Lifetime catalogs | Rules/reference data equal HEAD; readiness metadata only changed |
| SC-07 v2 | Impact violations0; exclusions0; exact rational violations0 |
| Daily C21-R population | 256,000 cases; hard failures0 |
| Full-data M0 equivalence | 108,000 cases /864,000 category checks; differences0 |
| Nonzero full-data STABILITY | 101,844 cases; attenuation0 |
| Repeat / A-B swap / iteration order | 10,000 /100 /100 exact checks PASS |
| Daily semantic negative mutations | 8 rejected; additional mismatched identity/period rejection PASS |
| Accepted sample-missing six cases | All six original deltas EXACT |

## Reproducibility limit ? do not equate the two populations

The previous read-only run's full in-memory script and availability mode selector were not retained after interruption. Its 128 Astrology and1000 Saju base generators were recoverable from retained execution text. The current validator explicitly persists a missing-source schedule and runs256,000 cases. This is not claimed to be the identical previous full population, although recovered full-data cases and all six retained sample-missing deltas match exactly. No numeric catalog or input tuning was performed to force the old distribution.

| Overall delta metric | Previous reported C21-R | Current persisted regression |
|---|---:|---:|
| Valid cases | 213001 | 213001 |
| Mean | 1.4646925148243761 | 1.464795523512044 |
| Median | 1.364226669934554 | 1.3645284505800146 |
| P5 | -0.0848303237858032 | -0.0848303237858032 |
| P95 | 3.336192942360532 | 3.336192942360532 |
| Min | -1.877977922091247 | -1.877977922091247 |
| Max | 5.091481584773692 | 5.091481584773692 |
| STABLE | 151420 | 151416 |
| GOOD | 61569 | 61573 |
| VERY_GOOD | 12 | 12 |
| Unavailable | 42999 | 42999 |

The complete same-fixture replay was requested where possible, rather than as an unconditional gate. Contract arithmetic, full-data equality and the accepted six-case regression pass. Historical population equality is unverified, not reported as a calculation regression or silently adjusted expectation.

## Scope / Git

Production code changes NONE. Public/signed schemas and OpenAPI equal HEAD. Git diff --check PASS. All changes are under docs/. No commit/push.

## Files changed

Total: 72 files (31 modified tracked, 41 new untracked).

- docs/02_FORTUNE_ENGINE_SPEC.md
- docs/03_SCORE_SPEC.md
- docs/05_API_SPEC.md
- docs/08_AI_PROMPT_SPEC.md
- docs/09_TASK_LIST.md
- docs/10_AGENTS.md
- docs/contracts/README.md
- docs/contracts/astrology-catalog-v1.md
- docs/contracts/astrology-v1-application-approval.md
- docs/contracts/clarification-v2.md
- docs/contracts/daily-catalog-v1.md
- docs/contracts/daily-v1-application-approval.md
- docs/contracts/daily-v1-approved-matrix.json
- docs/contracts/examples/daily-evidence-candidate-object-invalid.json
- docs/contracts/examples/daily-evidence-missing-sample-invalid.json
- docs/contracts/examples/daily-evidence-public-wire-invalid.json
- docs/contracts/examples/daily-evidence-saju-valid.json
- docs/contracts/examples/daily-evidence-stem-invalid.json
- docs/contracts/examples/daily-evidence-time-invalid.json
- docs/contracts/examples/daily-evidence-valid.json
- docs/contracts/examples/daily-rule-astro-valid.json
- docs/contracts/examples/daily-rule-context-invalid.json
- docs/contracts/examples/daily-rule-empty-invalid.json
- docs/contracts/examples/daily-rule-jupiter-invalid.json
- docs/contracts/examples/daily-rule-mc-invalid.json
- docs/contracts/examples/daily-rule-orb-invalid.json
- docs/contracts/examples/daily-rule-outer-invalid.json
- docs/contracts/examples/daily-rule-pair-weight-invalid.json
- docs/contracts/examples/daily-rule-range-invalid.json
- docs/contracts/examples/daily-rule-saju-valid.json
- docs/contracts/examples/daily-rule-sign-invalid.json
- docs/contracts/examples/daily-rule-time-null-invalid.json
- docs/contracts/examples/daily-rule-unknown-field-invalid.json
- docs/contracts/examples/daily-s2--1.9999.json
- docs/contracts/examples/daily-s2--2.0001.json
- docs/contracts/examples/daily-s2--2.json
- docs/contracts/examples/daily-s2--4.9999.json
- docs/contracts/examples/daily-s2--5.0001.json
- docs/contracts/examples/daily-s2--5.json
- docs/contracts/examples/daily-s2-1.9999.json
- docs/contracts/examples/daily-s2-2.0001.json
- docs/contracts/examples/daily-s2-2.json
- docs/contracts/examples/daily-s2-4.9999.json
- docs/contracts/examples/daily-s2-5.0001.json
- docs/contracts/examples/daily-s2-5.json
- docs/contracts/examples/delta-11_9999.json
- docs/contracts/examples/delta-5_9999.json
- docs/contracts/examples/delta-6.json
- docs/contracts/examples/delta-neg11_9999.json
- docs/contracts/examples/delta-neg5_9999.json
- docs/contracts/examples/delta-neg6.json
- docs/contracts/examples/manifest.json
- docs/contracts/final-decision-v2.md
- docs/contracts/final-decision-v3.md
- docs/contracts/freeze-decision-v1.md
- docs/contracts/guardrail-v2.md
- docs/contracts/readiness.md
- docs/contracts/rules/astrology-rules.json
- docs/contracts/rules/daily-rules.json
- docs/contracts/rules/saju-rules.json
- docs/contracts/runtime-contract-v2.md
- docs/contracts/saju-catalog-v1.md
- docs/contracts/saju-v1-application-approval.md
- docs/contracts/schemas/daily-evidence.schema.json
- docs/contracts/schemas/daily-response.schema.json
- docs/contracts/schemas/daily-rule.schema.json
- docs/contracts/validation-report.md
- docs/contracts/validation-results-daily-v1.json
- docs/contracts/validation/README.md
- docs/contracts/validation/daily-application.cjs
- docs/contracts/validation/daily.cjs
- docs/contracts/validation/structural.cjs
