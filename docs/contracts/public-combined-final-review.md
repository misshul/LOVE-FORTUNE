# LOVE FORTUNE — PUBLIC COMBINED / API
# FINAL REGRESSION + CHECKPOINT COMMIT REPORT

Result: **PUBLIC_COMBINED_API_REGRESSION_PASS_READY_TO_COMMIT**.
Authority: final checkpoint request 32798a9d-1b7b-43dc-9e60-51d458926e71. One local commit is authorized after staged checks; **DO NOT PUSH**. Earlier no-commit statements describe the implementation phase and are superseded for this checkpoint only.

[Actual full execution evidence](validation-results-public-combined-v1.json), [categorized file inventory](public-combined-files-changed.md), [individual snapshot review](historical/zodiac-v1/README.md).

| Section | Verified result |
|---|---|
| A. Result | PUBLIC_COMBINED_API_REGRESSION_PASS_READY_TO_COMMIT |
| B. Initial State | HEAD 64396abe085a5a0ec8adce4bcb749c65e147252f; 82 intended modified/new files, unstaged; diff check PASS |
| C. Files Reviewed | Contracts, active/historical schemas, examples, validators, 10 API classes, bootstrap and tests; no Engine/Domain/catalog/golden changes |
| D. Snapshot Review | 33 reviewed; 31 required copies retained; unused daily-range-response and interpretation-response copies removed; baseline identity and closed relative reference graph PASS |
| E. Contract Application | PC-01 through PC-08 applied; no new score policy |
| F. Schema Migration | 34 active schemas; unsigned embedded result separated from signed external numeric response; null overall omits token |
| G. OpenAPI | PASS, 20 operations; calculateCompatibility current example and 503 contract; no v2 path |
| H. Runtime Versions | SCORE_COMBINED_LIFETIME_V1 / CONFIG_COMBINED_LIFETIME_V1 / SAJU_LIFETIME_SCORING_V1; apiVersion v1 and server-owned references |
| I. Config Manifest | PublicRelease binds approved source dependencies, 4/5 and 1/5 weights, eligibility, M2 and thresholds; internal manifest is not returned as public metadata |
| J. REST Route | Actual WordPress registered POST /love-fortune/v1/compatibility/calculate; no Daily/period/interpretation business activation |
| K. Pipeline | Real Natal -> extraction -> Saju + original-birth-date Zodiac -> existing Combined -> projection; real numeric dispatcher E2E PASS |
| L. Public Projection | Eight categories; COMMUNICATION null/0/0; approved overall/confidence field names; no ledger/lineage/denominators |
| M. Feature Subset | Lossless, schema-compatible canonical single evidence only; signed evidence equals emitted public subset |
| N. MULTI Exclusion | Both-MULTI numeric path preserved, internal MULTI evidence omitted without representative or opaque-ID substitute |
| O. Signing | HS256 LFIC, unpadded base64url, TTL 300 seconds; current issue/current+previous verification; numeric signing required |
| P. Hard Cutover | Current and previous keys both reject old score/config; previous key with current semantics accepted |
| Q. Error Mapping | 400/404/413/415/422/429/500/503 PASS; approved specific purpose/locale/expiry errors retained; no internal exception details |
| R. Precision | Existing exact internal calculation; max-four-decimal HALF_UP projection and canonical decimals; status after approved rounding |
| S. Privacy / no-store | All requested dispatcher statuses have no-store; no Birth persistence/logging/analytics; application-level scope only |
| T. Rate Limiting | Atomic local HMAC counters, 60/600 seconds, Retry-After, no raw IP or Birth in counter contents/names; request-path expiration cleanup |
| U. Operational Requirements | Provision real signing/rate secrets; idle physical counter cleanup; shared rate enforcement for multiple nodes; verify Apache/CDN/WAF/APM capture settings |
| V. Determinism | Repeated numeric/category/evidence/version output identical; requestId and token timing excluded |
| W. Public Goldens | Known/unknown/fold/both-MULTI/Zodiac boundary compare to exact production Combined output; independent existing numeric goldens unchanged |
| X. Signed Context E2E | Real dispatcher token verifies; tamper/expiry/old score/old config/wrong purpose/wrong locale rejected; secretless signing returns 503 |
| Y. PHP Tests | Lint PASS; 88 tests / 1,262,236 assertions; no deleted/skipped tests |
| Z. Structural/OpenAPI | 34 active schemas, 265 examples, 20 operations PASS; after snapshot cleanup structural and Zodiac API checks rerun PASS |
| AA. Existing Engine Regression | Combined/Saju/extraction/Zodiac/Daily/SC07/semantic/Natal/Year/Month/Day/Hour/reference PASS; 53 protected engine/catalog/fixture files unchanged |
| AB. Secret Scan | Working content scan has no workstation paths/private keys/debug payloads/runtime junk; synthetic test keys explicitly non-production; staged scan is mandatory before commit |
| AC. Staged Files | 82 intended files after removing 2 unused snapshots and adding snapshot/final-review reports; stage and whitespace checks required before commit |
| AD. Commit | Approved message: feat: implement public combined compatibility api; final hash reported from Git after commit |
| AE. Working Tree | Must verify clean after commit; no reset/checkout/clean |
| AF. Push Status | NOT_PUSHED |
| AG. Remaining Work | Daily orchestration, period routes, interpretation business route, public Saju context and public MULTI projection; deployment requirements above |

## Executed validation and limits

Executed `node docs/contracts/validation/public-combined-application.cjs --write-report` successfully. This includes full PHPUnit/lint, all retained underlying numeric and FP regressions, location/timezone and solar references, structural/OpenAPI/privacy vectors, WordPress plugin smoke, current public PHP/JS validation and actual REST dispatcher tests. After removal of the two unused snapshot copies, reran `structural.cjs` and `zodiac-api.cjs`: PASS. All retained snapshot JSON objects equal baseline schemas except the explicitly historical `$id`; their references cannot drift with current active schemas.

The checkpoint adds exactly three PHPUnit assertions to the 1,262,233 baseline: previous key rejects old score, previous key rejects old config, and wrong purpose rejects with its approved code. Tests remain 88. Dispatcher checks additionally cover insufficient/429/500 and all required no-store paths, generated-token mutations and previous-key semantic cutover. No existing numeric expectation, fixture or production formula changed.

The numeric dispatcher path executes the real Natal/Saju/Zodiac/Combined pipeline. Insufficient, 429 and 500 use controlled injected inputs/failures through the actual dispatcher and production handler; these are transport branch tests, not fabricated real-birth outputs or load tests. The real CoreRateLimit 60/61 boundary is covered separately by PHPUnit. There is no implemented interpretation HTTP business route: signed-context E2E verifies calculate-issued tokens directly with the production verifier.

Full retained results include 80 arithmetic/period vectors (4 DST), 256,000 Daily cases, 73,049 Zodiac dates, 188 Lifetime/150 exact Daily application fixtures, 129 locations/23 timezone zones/5,041 transitions, and 4,848 solar events. Current PHP/JS canonical HMAC and feature identity checks pass. Historical no-schema-change phase wrappers remain scoped to their original release; their underlying numeric tests remain active.

No production secret is provisioned by this checkpoint. CLI-only harnesses inject synthetic keys in their own processes; bootstrap has no fixture-key default. No source/reference download, DB migration, new network dependency or artifact regeneration occurs. No request/response body capture by deployed infrastructure is certified. Whole-service readiness remains NOT_READY_IMPLEMENTATION.

Readiness: NATAL_RESOLUTION, SAJU_FEATURE_EXTRACTION, LIFETIME_SAJU_SCORING, COMBINED_LIFETIME and PUBLIC_COMPATIBILITY_API are IMPLEMENTED. The next separately scoped task may complete the DAILY ORCHESTRATION CONTRACT.
