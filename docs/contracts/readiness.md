Current single-date Daily authority: [DO-01..DO-09](daily-orchestration-v1.md). SAJU_DAILY_ORCHESTRATION_V1 / CONFIG_DAILY_V1 implement POST fortune/daily alongside compatibility/calculate; other period and interpretation routes remain pending. Earlier pending statements are historical scope.

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
| SAJU_TIME_SCALE_BRIDGE_V1 | REFERENCE BUILD VALIDATED; pinned local runtime reader and FP06 natal integration implemented |
| FP02_YEAR_PILLAR | IMPLEMENTED; resolved-coordinate pure calculator |
| FP03_MONTH_PILLAR | IMPLEMENTED; same-coordinate FP02 dependency, twelve Jie |
| FP04_HOUR_PILLAR | IMPLEMENTED; adjusted-civil pure calculator, same-candidate Day result; [contract](fp04-hour-pillar-v1.md) |
| FOUR_PILLARS | PARTIAL; Natal Year/Month/Day/Hour candidate pipeline implemented; non-Natal/period application orchestration remains separate |
| FOUR_PILLARS_CORE_CALCULATORS | IMPLEMENTED |
| FOUR_PILLARS_END_TO_END | IMPLEMENTED_FOR_NATAL; birth input to ordered atomic candidates; Combined Lifetime compatibility application implemented; single-date Daily implemented; other period application remains pending |
| SAJU_LOCATION_REFERENCE_V1 | APPLIED / BUILD VALIDATED; 129 curated selectable records; [contract](location-timezone-reference-v1.md) |
| SAJU_TIMEZONE_REFERENCE_V1 | APPLIED / BUILD VALIDATED; IANA2026b; 23 zones including two Golden-only zones |
| FP06_REFERENCE_PREREQUISITE | RESOLVED; pinned production readers implemented |
| NATAL_RESOLVER | IMPLEMENTED; SAJU_NATAL_RESOLVER_V1; [contract](fp06-natal-resolver-v1.md) |
| LOCATION_TIMEZONE_REFERENCE | IMPLEMENTED; approved artifacts and pinned runtime readers |
| BIRTH_INPUT_TO_FOUR_PILLARS | IMPLEMENTED; Natal ordered atomic candidate pipeline |
| SAJU_FEATURE_EXTRACTION | IMPLEMENTED; SAJU_FEATURE_EXTRACTOR_V1; pre-SC07 evidence / FE-COV-01; [contract](saju-feature-extraction-v1.md) |
| LIFETIME_SAJU_SCORING_ORCHESTRATION | IMPLEMENTED; SAJU_LIFETIME_SCORING_V1; [contract](saju-lifetime-scoring-v1.md) |
| NATAL_TO_SAJU_CATEGORY_RESULTS | IMPLEMENTED; internal seven-category source result, no overall |
| PUBLIC_MULTI_FEATURE_PROJECTION | NOT_IMPLEMENTED |
| COMBINED_LIFETIME_ORCHESTRATION | IMPLEMENTED; LIFETIME_COMBINED_SCORING_V1; [contract](combined-lifetime-v1.md) |
| SCORING_ORCHESTRATION | PARTIAL; Combined Lifetime implemented; single-date Daily implemented; other period integration remains |
| CANDIDATE_GENERATION | IMPLEMENTED_FOR_NATAL; UNIQUE/FOLD/GAP/UNKNOWN; ordered atomic contexts |
| SCORE_ENGINE | PARTIAL; exact M2 and versioned Combined Lifetime implemented; single-date Daily implemented; other period orchestration pending |
| API | PARTIAL; compatibility/calculate implemented; public release regression PASS; deployment secrets required; Daily implemented; interpretation/other period routes remain pending |
| Production Engine readiness | NOT_READY_IMPLEMENTATION |
| Lifetime Advanced Astrology | PRESERVED / DEFERRED; 109 rules / 228 mappings |
| Daily Advanced Astrology | PRESERVED / DEFERRED; 125 rules / 266 mappings |
| EPHEMERIS_PROVIDER | NOT_REQUIRED_FOR_V1 / DEFERRED_ADVANCED_BLOCKER |

An approved catalog is not a complete production engine. Natal location/reference integration and atomic Four Pillars candidate generation are implemented by FP06. Versioned Combined Lifetime reuses the existing blender. Public Combined release authority: [PC-01..PC-08](public-combined-api-v1.md). Compatibility REST validation/signing is implemented. V1 still requires other period public orchestration, interpretation/AI/fallback integration and application/security/privacy deployment work. Those are implementation dependencies, not unresolved Zodiac numeric decisions or V1 ephemeris blockers.

FP-01 removes the Solar-Term reference/bridge build prerequisite. Subsequent [FP-02/03 approval and implementation](fp02-fp03-year-month-v1.md) supplies Year/Month calculators. [Location/timezone reference V1](location-timezone-reference-v1.md) supplies the validated FP06 static reference prerequisite. [FP06](fp06-natal-resolver-v1.md) now integrates pinned runtime readers and Natal candidate generation; compatibility is the only implemented public business route. SAJU_LOCATION_REFERENCE_V1 is bound to public locationReferenceVersion. Day Pillar is unchanged. Offline reference gates: node docs/contracts/validation/solar-reference.cjs and node docs/contracts/validation/location-timezone.cjs. [Solar build evidence](fp01-build-report.md). All production tests run through scripts/test.ps1.

Current gate: node docs/contracts/validation/public-combined-application.cjs. PHP/JS goldens use synthetic inputs; approved Day Pillar reference fixtures are unchanged. Historical application gates/reports retain original scopes and are not current V1 readiness authorities. See [validation report](validation-report.md).
