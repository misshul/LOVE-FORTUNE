# SOLAR-TERM REFERENCE + TIME-SCALE BRIDGE BUILD REPORT

Date:2026-09-24. Result: REFERENCE_BUILD_VALIDATED_READY_FOR_APPLICATION. Repository application: APPLIED after outside-repository validation. No commit/push.
Authority: [FP-01](fp01-solar-reference-v1.md). Actual machine results: [validation-results-fp01-v1.json](validation-results-fp01-v1.json). Complete change list: [files](fp01-files-changed.md).

| Item | Actual result |
|---|---|
| Initial repository | Clean; HEAD5cf6b7bc8221cc5b13b94774c5307ba79179ef51; diff check PASS |
| Raw source | Reused202 previously retrieved HTML bodies; manifest12af0c28a1c8f72ac1ced8deadd0fd3cff66d76eeb364664c18f314a1bae7744 and every body verified |
| Explicit S2020 |202 annual values1899..2100; no missing/duplicate;62 annual values used for pre1961 events |
| Official drift |13 intervals1961..1971; original coefficient strings, source rows and hashes retained |
| Known TAI-UTC |28 effective entries1972..2017; original USNO41-row source preserved |
| Future |37s frozen through2100; snapshot-day lineage cut2026-09-24; not future physical UTC |
| Timezone identity | PHP8.3.33 embedded DB2026.1;598-zone transition digest; OS tzdata2026b-0+deb13u1 separately identified |
| Events |4,848 /202 years /24 each; missing0/duplicate0/order anomalies0/term mismatch0 |
| Era routing | Pre1961:1,488; drift:264; known TAI-UTC:1,314; frozen future:1,782 |
| Coordinates | All unique, strictly ordered, signed64-bit; unresolved event conversions0 |
|24:00 provenance |2 originals retained:1969 Lichun and2096 Xiaoshu; normalized dates advanced only in calculation |
| Ceil |7 exact positive/negative/integer/fractional cases PASS; every event additionally satisfies boundary-1 < exact <= boundary |
| Era/transition probes |123 PASS; service intervals ordered/no gaps/no overlaps; TT-domain step gaps35/overlaps2 plus the pre1961 model seam are explicit expected rejection cases |
| Goldens |24 newly frozen public/synthetic cases; all requested representative years, all12 Jie, both24:00 cases; before/exact/after PASS |
| Replay | Two Python3.12.11 Fraction builds byte-identical; no environment clock reads in normalized build |
| PHP/JS | All4,848 coordinates match; ordering and14,544 comparisons PASS; differences0 |
| Negative probes |6 malformed/rehashed artifacts rejected: missing event, reorder, changed boundary, wrong era, wrong bridge, precision relabel; temporary baseline restored/PASS |
| Existing regression | FULL_APPLICATION gate PASS; structural, semantic/period/DST, Advanced, Saju/SC07, Daily, Zodiac, API/signing/privacy |
| PHP | Existing lint PASS; PHPUnit20 tests /1,217,548 assertions; new validation-only PHP harness lint PASS |
| Day Pillar | No production changes; original epoch, adapters, approved fixtures, longitude correction and23:30 untouched |
| Runtime | No new runtime dependency or runtime loader; artifacts are local references, generation tooling is documentation-only |
| Git | diff check PASS; changes confined to docs; immutable reference byte attributes prevent checkout newline translation |

## Exactness and reproducibility evidence

Artifact payload checksum (canonical bytes excluding only metadata.artifactChecksum):
`6fc6165b76951a0267d46fe2a7e7d4da190c7e2119dc8d1ffdef12fed399848c`.

Whole-file SHA256, equal for both final builds:
`948f243c049ede8519b8e998d191d6e2a9a49c837120836d101e38b67f332943`.

PHP and JS coordinate stream SHA256:
`7db5a988655c2534d4a9b9d8a1e149adc9175e036f425117d9fa490e7d092c6a`.

The Python standard-library builder parses original TT source rows and solves exact rational seconds. The independent Node validator re-parses official inputs, solves in exact BigInt microseconds, validates interval membership and checks every stored fraction/ceiling. PHP uses the unchanged existing Rational/DecimalInteger primitives to independently reconstruct coordinates; it is not a production Solar-Term implementation. The initial temporary PHP harness rejected an official `37300.` coefficient spelling; normalizing that spelling to `37300` fixed the harness only. No source coefficients, output coordinates or production arithmetic were changed to satisfy it.

Goldens were frozen once from the independent JS calculation and checked against the Python build. They are not outputs regenerated from a new production engine. No existing approved fixture was regenerated. The temporary six-case mutation experiment recomputed payload checksums, confirming semantic validation rejects corruption even when the artifact self-checksum is internally consistent.

## Executed commands

- Outside repository: freeze verified raw inputs; run `build.py` twice against the same stage; independent Node validator; temporary PHP cross-check. Final metadata-inclusive replay and goldens passed before the first repository write.
- Applied reference: `node docs/contracts/validation/solar-reference.cjs` PASS.
- `node docs/contracts/validation/zodiac-application.cjs` FULL_APPLICATION PASS (includes actual scripts/test.ps1/PHPUnit).
- Ephemeral existing WordPress image, network disabled, repository mounted read-only: `php -l .../solar-reference-php.php` and `php .../solar-reference-php.php <reference-directory> <plugin-directory>` PASS. No plugin activation cycle or DB mutation was performed.
- `git diff --check` PASS; no changes under wp-content, Docker, schemas or existing catalogs/goldens.

## Scope and remaining work

FP-01 build/application blockers: NONE. No repeat of prior4,848-event Skyfield research was necessary because the approved raw manifest and every source checksum matched. New comparisons validate the bridge, not an independently observed historical/future civil timeline. Source minute uncertainty and the approved approximation/future freeze remain explicit.

No Year/Month/Hour calculator, natal resolver UX, birthLocation database, REST, frontend, DB or new persistence. Complete production readiness remains NOT_READY_IMPLEMENTATION. FP-02/03 formula contracts and FP-06 remain separate prerequisites; the FP-01 reference prerequisite is now satisfied. Do not treat reference approval as permission to invent those formulas. Frozen timezone identity does not claim a completed location reference or deployment enforcement.

Final answers: Reference build validated YES. FP-01 reference/bridge applied YES. Unqualified Year/Month production implementation readiness NO: establish the remaining FP-02/03 formula contracts first; no further FP-01 redesign is required.
