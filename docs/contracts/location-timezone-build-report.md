# LOVE FORTUNE — V1 CURATED LOCATION + TIMEZONE REFERENCE FULL BUILD REPORT

Review completed:2026-09-28. Sources frozen/retrieved:2026-09-25. [Authority and contract](location-timezone-reference-v1.md). [Complete execution evidence](validation-results-location-timezone-v1.json). Source identities and two-build evidence are retained under references/location-timezone-v1/.

| Section | Verified result |
|---|---|
| A. Result | LOCATION_TIMEZONE_REFERENCE_BUILD_VALIDATED_READY_FOR_APPLICATION; subsequently APPLIED |
| B. Initial State | CLEAN; diff --check PASS; HEAD f363fbbe197ff7a8a8da2d6737b333009c06f3bf |
| C. Final V1 Scope |129 selectable records; P3 DEFERRED, not selected |
| D. South Korea |17 approved source IDs preserved; populated administrative-center points; no province-coordinate substitution |
| E. Japan |47 explicitly mapped prefectural-capital locations,47 unique admin1; no all-Japan-city coverage claim |
| F. P2 Countries |69 candidates,4 excluded,65 accepted: US36/CA12/GB7/AU8/NZ2 |
| G. Excluded / Deduplicated |Bronx5110266, Brooklyn5110302, Manhattan5125771, Queens5133273; subordinate NYC boroughs; primary LOC000003. No independent neighboring municipality merged |
| H. Golden-only |Lord Howe2159558 and Apia4035413 have fixture identities, no public locationId |
| I. Stable Registry |LOC000001..004 preserved;005/006 reserved non-public;125 new active IDs007..131; no reassignment/reuse |
| J. Location Artifact |SAJU_LOCATION_REFERENCE_V1;129 records; explicit manifest+registry; newly built, not candidate rename |
| K. Attribution / License |GeoNames CC BY4.0 attribution/link/modification statement, original notices and archives preserved |
| L. Longitude |All129 exact decimal/microdegree/half-up checks PASS; invalid precision/range rejected |
| M. Timezone Artifact |SAJU_TIMEZONE_REFERENCE_V1;5,041 transition rows including23 effective-start sentinels |
| N. Required Zones |21 production +2 Golden-only =23; missing0 |
| O. TZDB Identity |IANA2026b archive114543d9f19a6bfeb5bca43686aea173d38755a3db1f2eec112647ae92c6f544; PHP2026.1 separate |
| P. Coverage |[1899-01-01,2101-01-01); public1900..2099; no interval gap;1,706,953 TZif cross-check probes per build |
| Q. Alias Normalization |Pinned2026b Link graph, build-time only; no canonical location uses an alias |
| R. Historical Precision |Integer-second offset preserved; Seoul1900 +30,472 seconds; no minute rounding/current-offset fallback |
| S. Fold / Gap |NY2020-11-01 01:30 maps twice;2020-03-08 02:30 maps zero times; PASS |
| T. Non-1H Golden |Lord Howe2020-04-05 01:45 two mappings,2020-10-04 02:15 none;30-minute transition PASS |
| U. Date Skip |Apia2011-12-30 entire local date has empty valid domain; PASS |
| V. Deterministic Replay |Two full outside-repository builds; all six generated artifact/result files byte-identical; fixed manifest/registry inputs preserved |
| W. PHP / JS |129 production longitudes +8 signed boundary cases +6 invalid cases; differences0 |
| X. Reproducibility |Pinned archives/manifest/registry/builder/image/binary hashes; offline rebuild instructions and full inputs included |
| Y. Runtime Dependency |External runtime dependency NONE in design; local reference reader remains implementation work |
| Z. Repository Application |APPLIED after staging JS/PHP/Goldens/replay PASS; docs-only scope |
| AA. Regression |Full Zodiac application gate PASS; PHP lint/34 PHPUnit tests/1,257,453 assertions; retained Saju/SC07/Daily/OpenAPI/privacy PASS; FP01 JS/PHP PASS; actual WordPress smoke PASS |
| AB. Files Changed |Complete list below; no production/plugin/API/frontend/DB/Docker changes |
| AC. Remaining Blockers |No location/timezone reference build blockers. FP06 runtime reader/resolver/candidate integration still NOT_IMPLEMENTED |
| AD. Commit / Push |NONE / NONE; validated application left in working tree |

## Execution and limits

Executed build-reference.py twice against the same explicit inputs using pinned Python image47ae396f09c1303b8653019811a8498470603d7ffefc29cb07c88f1f8cb3d19f and zic/zdump GLIBC2.41. Compared all generated canonical files. Ran staging and applied `node docs/contracts/validation/location-timezone.cjs`, PHP longitude harness, `node docs/contracts/validation/zodiac-application.cjs` (FULL_APPLICATION, invokes scripts/test.ps1), `node docs/contracts/validation/solar-reference.cjs`, solar-reference-php.php and actual wordpress-smoke.php without activation cycling. PHPUnit includes existing Year/Month/Day/Hour regressions. No existing Golden expectation or production module was regenerated or edited.

Immutable selected records derive from the original frozen cities500 archive; full source archives include unselected global entries solely for reproducibility. P3 entries are absent from the selectable artifact/registry. All47 Japan source IDs/admin1/city pairs and every P2 input are visible in location-manifest.json. Source population defines selection only, not current demographic accuracy. Tokyo retains the explicitly approved reference point.

Reference daily/boundary probes use two readers of the same pinned TZif; they do not establish independent historical truth. Fixed authored fold/gap/skip/offset Goldens add independent expected cases. Full FP06 birth-input-to-ordered-candidate/Four-Pillars E2E is not implemented or certified. Runtime reader must enforce pinned artifact identity and never fall back to OS tzdb. Existing public Location schema remains unchanged; a future API adapter must project its exact five fields.

Byte checksum protection includes a local .gitattributes disabling line-ending normalization, so Windows checkout cannot silently change frozen hashes. Top-level artifactChecksum excludes itself; replay-validation.json also provides complete file hashes:

```text
locations.json 5261b7cd37776a6d6b25aea4d32e0dd8c5c073e9bcde29b6daec08800049b152
timezones.json ee74cc16f926c0076ff0de5acf3df72070681e0422cdcf25076e141c5fc8a79f
```

## Final answers

1. V1 selectable scope finalized: YES.
2. Final Location build validated: YES.
3. Final Timezone build validated: YES.
4. Repository application complete: YES.
5. Required regression PASS: YES.
6. May the next task implement approved FP06 Natal Resolver: YES. This task has not implemented it and production remains NOT_READY_IMPLEMENTATION.

## Files changed

See [complete file list](location-timezone-files-changed.md).
