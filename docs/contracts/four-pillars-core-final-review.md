# LOVE FORTUNE
# FP-01 ~ FP-04 FOUR PILLARS CORE
# FINAL REGRESSION + COMMIT REPORT

## A. Result

FOUR_PILLARS_CORE_REGRESSION_PASS_READY_TO_COMMIT. Final pre-commit evidence: [actual machine results](validation-results-four-pillars-core.json). This review adds only execution/readiness documentation; production code, formulas, numeric values and fixture expectations were not changed.

## B. Files reviewed

All43 initial changed/untracked files reviewed by scope, source inspection, metadata/source-hash validation and regression. Existing FP01~04 work preserved; no reset/checkout/clean. No unexpected temporary files, local absolute paths, unfinished TODO/FIXME or debug code found in the added implementation. A TODO/debug mention in the historical report is prose, not executable code. Gzip files are intentional public NAOJ source snapshots; all entries and byte hashes are validated. Validation-only Python/JS/PHP tooling remains under docs; no runtime package was added. Full categorized file list follows.

## C. FP-01 regression

PASS. NAOJ_SOLAR_TERMS_1899_2100_V1, SAJU_TIME_SCALE_BRIDGE_V1, SERVICE_PROLEPTIC_POSIX_V1 unchanged.4,848 events, missing/duplicate/order/identity mismatches0;24 Goldens,123 transition probes. Runtime deployment copy equals authoritative docs bytes; both pinned hash and explicit single-source documentation prevent independent edits. Source snapshots/provenance verified offline. PHP/JS stream SHA256:7db5a988655c2534d4a9b9d8a1e149adc9175e036f425117d9fa490e7d092c6a.

## D. Year regression

PASS: [Lichun(Y),Lichun(Y+1)),1984甲子 anchor, floorMod(sajuYear-1984,60). Exact boundary starts new year; January1 does not. Seven approved fixed years and full1900..2099 boundary properties retained.

## E. Month regression

PASS: twelve Jie only, [start,next), monthIndex0寅..11丑, Five Tiger formula and same-coordinate YearPillarResult.stemIndex. All2,400 Jie and2,400 Zhongqi checks retained; no Zhongqi month transition.

## F. Day regression

PASS. GregorianDayNumber, DayPillarCalculator, CalculationDateResolver, DailySampleResolver and SajuDayPillarService remain unchanged against HEAD. Existing epoch Goldens and correction/23:30 behavior retained.

## G. Hour regression

PASS: SAJU_HOUR_PILLAR_V1, adjusted-civil input, integer microseconds, twelve half-open intervals, supplied same-candidate day stem, approved Five Rat formula. No repeated timezone/DST/longitude adjustment.

## H. Critical cases

2019-01-27 adjusted23:00 gives甲子 Hour while Day remains甲子.23:30 gives丙子 Hour with乙丑 Day; branch remains子. Intentional approved policy, unchanged.

## I. UNKNOWN

PASS: known=false; hourIndex/stemIndex/branchIndex/stem/branch/ganzhi/dayStemIndexUsed/calculationDateUsed null. No fabricated representative time.

## J. Candidate limitation

Same-date candidate lineage is a higher-layer responsibility: existing Day DTO lacks identifying provenance. Date/pillar inconsistency is rejected locally. Limitation documented in FP04 contract and code; no new public identifier/schema. Gap/fold resolution is not implemented here.

## K. PHP tests

PASS: lint;34 tests /1,257,453 assertions, baseline unchanged. Includes existing Year/Month/Day/Zodiac and Hour Golden/120-combination/UNKNOWN/microsecond/invalid-input tests. No new tests or expectation changes in this final review.

## L. PHP/JS cross-check

PASS: Zodiac differences0; all4,848 FP01 coordinates match reference and PHP/JS digests. The FP01 PHP harness was run in a network-disabled container with repository read-only. Year/Month/Hour production are verified by PHP tests, not claimed to have separate production JS implementations.

## M. OpenAPI / privacy / WordPress

PASS: full zodiac-application gate, including schemas33, retained examples203, active examples54,20 OpenAPI operations, signed-context/HTTP/privacy checks and Saju/Daily regressions. Actual WordPress plugin discovered/active/rendered; no table/plugin-option changes or business routes. Privacy review found no new raw timestamp logging/persistence/network. Deployment CDN/WAF/APM configuration is outside this code checkpoint.

## N. External runtime dependency

NONE. Local frozen artifact only; no NAOJ/Skyfield/JPL/external time API. Domain has no WordPress/DB/network dependency. Existing PHP64-bit requirement retained.

## O. Readiness

Year/Month/Day/Hour individually IMPLEMENTED. FOUR_PILLARS_CORE_CALCULATORS=IMPLEMENTED; FOUR_PILLARS_END_TO_END=NOT_COMPLETE. Natal resolver PARTIAL (resolved-input adapters only); candidate generation NOT_IMPLEMENTED; aggregate orchestration PARTIAL. Not production ready.

## P. Commit

Approved commit message: `feat: implement saju four pillars core`. This report records the passing pre-commit checkpoint; the resulting commit hash is supplied in the final response and Git history rather than embedded into its own content.

## Q. Working tree

Initial tree contained intended FP01~04 changes. Final staging must include those and this review, with diff checks passing. Post-commit status is verified separately and reported in the final response.

## R. Push

NOT_PUSHED. No push is authorized or executed.

Staged whitespace review found a preserved CRLF at the end of frozen bridge.json. Its bytes/checksum remain unchanged; a file-specific cr-at-eol whitespace attribute accepts that existing terminator without suppressing other whitespace checks. No artifact regeneration or numerical change.

## Commands executed

- node docs/contracts/validation/zodiac-application.cjs (FULL_APPLICATION, includes scripts/test.ps1)
- node docs/contracts/validation/solar-reference.cjs
- validation/solar-reference-php.php in network-disabled PHP container, read-only repository
- docker compose exec -T wordpress php /var/www/html/wp-content/plugins/love-fortune-core/tests/wordpress-smoke.php
- source/deployment identity, source scans, protected HEAD comparisons, git diff --check and staged review

Privacy Impact: NONE. Version Impact: approved FP01~04 identities only. SPEC Deviations: NONE. No new features or contracts designed in this review.

## Categorized commit file inventory

Total: 45 files (including final review records).

### FP-01 reference/contracts

- docs/contracts/fp01-build-report.md
- docs/contracts/fp01-files-changed.md
- docs/contracts/fp01-final-approval.md
- docs/contracts/fp01-solar-reference-v1.md
- docs/contracts/references/solar-terms-v1/.gitattributes
- docs/contracts/references/solar-terms-v1/bridge.json
- docs/contracts/references/solar-terms-v1/delta-t-s2020.json
- docs/contracts/references/solar-terms-v1/goldens.json
- docs/contracts/references/solar-terms-v1/historical-drift.json
- docs/contracts/references/solar-terms-v1/leap-seconds.json
- docs/contracts/references/solar-terms-v1/solar-terms.json
- docs/contracts/references/solar-terms-v1/source-manifest.json
- docs/contracts/references/solar-terms-v1/sources/naoj-s2020.json.gz
- docs/contracts/references/solar-terms-v1/sources/naoj-tt0.json.gz
- docs/contracts/references/solar-terms-v1/sources/tai-utc.dat
- docs/contracts/references/solar-terms-v1/sources/tt-raw-manifest.txt
- docs/contracts/references/solar-terms-v1/timezone-identity.json
- docs/contracts/validation-results-fp01-v1.json
- docs/contracts/validation/solar-reference-build.py
- docs/contracts/validation/solar-reference-php.php
- docs/contracts/validation/solar-reference.cjs
- wp-content/plugins/love-fortune-core/config/references/.gitattributes
- wp-content/plugins/love-fortune-core/config/references/README.md
- wp-content/plugins/love-fortune-core/config/references/solar-terms-v1.json

### FP-02 Year

- wp-content/plugins/love-fortune-core/src/Engine/Saju/YearPillarCalculator.php

### FP-03 Month

- wp-content/plugins/love-fortune-core/src/Engine/Saju/MonthPillarCalculator.php

### FP-04 Hour

- docs/contracts/fp04-hour-pillar-v1.md
- wp-content/plugins/love-fortune-core/src/Engine/Saju/HourPillarCalculator.php

### Tests/validation/docs and registration

- docs/contracts/four-pillars-core-final-review.md
- docs/contracts/fp02-fp03-implementation-report.md
- docs/contracts/fp02-fp03-year-month-v1.md
- docs/contracts/fp04-implementation-report.md
- docs/contracts/validation-results-four-pillars-core.json
- docs/contracts/validation-results-fp02-fp03.json
- wp-content/plugins/love-fortune-core/src/Engine/Saju/SolarTermReference.php
- wp-content/plugins/love-fortune-core/tests/Unit/HourPillarTest.php
- wp-content/plugins/love-fortune-core/tests/Unit/YearMonthPillarTest.php
- wp-content/plugins/love-fortune-core/tests/fixtures/hour-pillar-golden.json
- wp-content/plugins/love-fortune-core/tests/fixtures/year-month-golden.json
- docs/02_FORTUNE_ENGINE_SPEC.md
- docs/contracts/README.md
- docs/contracts/readiness.md
- docs/contracts/validation-report.md
- docs/contracts/validation/README.md
- wp-content/plugins/love-fortune-core/config/bootstrap.php
