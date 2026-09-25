# Contract and engine readiness

Current authority: [Zodiac V1 application](zodiac-catalog-v1.md), retained [Saju v1](saju-catalog-v1.md), [SC-07 v2](guardrail-v2.md), and [Day Pillar epoch](saju-day-pillar-epoch-v1.md).

| Gate | Current state |
|---|---|
| Contract Freeze | PASS |
| SPEC_CONFLICT | 0 |
| Effective current-scope contradictions | 0 |
| V1_EXTERNAL_BLOCKERS | NONE |
| ZODIAC_CONTRACT | APPLIED |
| ZODIAC_CATALOG | APPLIED; 20 numeric mappings, COMMUNICATION 0 |
| ZODIAC_IMPLEMENTATION | IMPLEMENTED: date, canonical pair/context and source scoring |
| Lifetime Saju catalog | READY; 14 families, 9 scoring / 5 context, 37 scoring mappings |
| Daily Saju catalog | READY; 9 rules / 19 mappings, unchanged |
| Score Engine catalog readiness | READY |
| Catalog blockers | NONE |
| SAJU_DAY_PILLAR_EPOCH_V1 | APPLIED / READY |
| FP01_SOLAR_REFERENCE | APPLIED / VALIDATED; 4,848 boundaries; [contract](fp01-solar-reference-v1.md) |
| SAJU_TIME_SCALE_BRIDGE_V1 | REFERENCE BUILD VALIDATED; pinned local runtime reader implemented; natal integration pending |
| FP02_YEAR_PILLAR | IMPLEMENTED; resolved-coordinate pure calculator |
| FP03_MONTH_PILLAR | IMPLEMENTED; same-coordinate FP02 dependency, twelve Jie |
| FP04_HOUR_PILLAR | IMPLEMENTED; adjusted-civil pure calculator, same-candidate Day result; [contract](fp04-hour-pillar-v1.md) |
| FOUR_PILLARS | PARTIAL; Year/Month/Day/Hour calculators implemented; full Natal resolver/candidate generation/aggregate integration pending |
| FOUR_PILLARS_CORE_CALCULATORS | IMPLEMENTED |
| FOUR_PILLARS_END_TO_END | NOT_COMPLETE |
| NATAL_RESOLVER | PARTIAL; resolved-input adapters only, full location/gap/fold resolution pending |
| CANDIDATE_GENERATION | NOT_IMPLEMENTED |
| SCORE_ENGINE | PARTIAL; exact M2 blend, confidence, Daily aggregation/ranking implemented; extraction/orchestration pending |
| API | NOT_IMPLEMENTED; route registry empty; schemas/OpenAPI are contracts |
| Production Engine readiness | NOT_READY_IMPLEMENTATION |
| Lifetime Advanced Astrology | PRESERVED / DEFERRED; 109 rules / 228 mappings |
| Daily Advanced Astrology | PRESERVED / DEFERRED; 125 rules / 266 mappings |
| EPHEMERIS_PROVIDER | NOT_REQUIRED_FOR_V1 / DEFERRED_ADVANCED_BLOCKER |

An approved catalog is not a complete production engine. V1 still requires location/reference integration, complete Four Pillars and unknown-time candidate generation, feature extraction, full Daily/period/Core orchestration, REST validation/signing, AI/fallback integration and application/security/privacy deployment work. Those are implementation dependencies, not unresolved Zodiac numeric decisions or V1 ephemeris blockers.

FP-01 removes the Solar-Term reference/bridge build prerequisite. Subsequent [FP-02/03 approval and implementation](fp02-fp03-year-month-v1.md) supplies Year/Month calculators; FP-06 Natal resolver UX remains separate and locationReferenceVersion is not fabricated. Day Pillar is unchanged. Offline reference gate: node docs/contracts/validation/solar-reference.cjs. [Build evidence](fp01-build-report.md). Year/Month production tests run through scripts/test.ps1.

Current gate: node docs/contracts/validation/zodiac-application.cjs. PHP/JS goldens use synthetic inputs; approved Day Pillar reference fixtures are unchanged. Historical application gates/reports retain original scopes and are not current V1 readiness authorities. See [validation report](validation-report.md).
