# LOVE FORTUNE — ZIP LINUX PERMISSION FIX
# FINAL REGRESSION + CHECKPOINT COMMIT REPORT

Date: 2026-10-22 (Asia/Tokyo). Authority: request fec14f44-f5d8-4c01-a860-b515fd22352e.
Result: **LOVE_FORTUNE_ZIP_LINUX_PERMISSION_FINAL_REGRESSION_PASS_READY_TO_COMMIT**.
One checkpoint commit is authorized after staged validation; no push, deployment or Lolipop connection. Earlier no-commit statements below describe the preceding implementation phase and are superseded only for this checkpoint. Actual commit hash and post-commit clean state are reported from Git after execution.

| Section | Verified result |
|---|---|
| A. Result | LOVE_FORTUNE_ZIP_LINUX_PERMISSION_FINAL_REGRESSION_PASS_READY_TO_COMMIT |
| B. Initial Git State | HEAD e40230e6fd723a683f8d1a349e3567b7335aa82d; six intended changes (two modified, four new); diff --check PASS |
| C. Changed Files | Same six packaging/test/report files listed below; all reviewed, no new implementation change in this final phase |
| D. Root Cause Confirmation | Old ZIP had 119 backslash entries, DOS creator-system and absent Unix mode bits; Windows-only extraction did not establish Linux portability |
| E. ZIP Path Normalization | Fresh ZIP has 139 forward-slash entries, backslash count 0; root love-fortune-core/ retained |
| F. Unix Directory Modes | 24/24 explicit directory entries 0755; actual Linux modes match |
| G. Unix File Modes | 115/115 regular file entries 0644; actual Linux modes match |
| H. Extraction Without chmod | PASS as www-data uid 33 through Linux PHP/libzip; no chmod repair |
| I. Runtime File Count | 115 |
| J. ZIP / Runtime Diff | 0; source, manifest, host-extracted and Linux-extracted bytes agree |
| K. PHP Suite | Fresh 156 tests / 1,263,293 assertions PASS |
| L. Backend Regression | Full retained engine/reference/Compatibility/Daily/Period/Interpretation regression PASS; additional cache and Yearly concurrency PASS, differences 0 |
| M. Schema / OpenAPI | 36 schemas, 203 examples (113 accepted / 90 expected rejection), 20 operations PASS; no diff |
| N. Frontend / Scoring | Unchanged |
| O. RuntimeConfig | Unchanged from e40230e6; signing/rate/cleanup/runtime code unchanged |
| P. Packaging Regression | 59 checks PASS; existing expectations unchanged |
| Q. Fresh Candidate | New frontend-v1-DSL06E/candidate.zip; 553601 bytes; generated after fresh regression, not reused |
| R. Candidate SHA-256 | 053be3bcd149d150df444ac29f123789c8cbe3d7d42c6ff28fb068704bc1b451 |
| S. Artifact PHP Lint | All 106 Linux-extracted PHP files PASS; six runtime reference readers PASS |
| T. Security Exclusions | Existing runtime allowlist and fresh scans PASS; no private JSON, operator wrapper, tests/dev/logs/.git/temp files in ZIP |
| U. Server Path Search | No specified account path or hardcoded production dependency in changed files or fresh package |
| V. Secret Search | No real credential discovered; header-pattern hits are existing scanner/test text, not key material |
| W. Manifest Validation | Fresh manifest and ZIP SHA independently verified; PASS |
| X. Staged Files | Exactly six intended text files eligible; cached --check required before commit; no .tools or release artifact |
| Y. Commit | Authorized message: fix: make release zip linux-safe; actual hash reported after execution |
| Z. Working Tree | Post-commit clean verification required; no reset/discard |
| AA. Push | NO |
| AB. Deployment | NO |

Fresh evidence: `.tools/releases/frontend-v1-DSL06E/validation.json` and `candidate-sha256.json`; command output `.tools/zip-linux-final-build.json` with separate progress log. `node scripts/test-package-lolipop.cjs` and the full `node scripts/package-lolipop.cjs` flow both exited 0. A new release directory was created. Its ZIP digest matches the earlier independent build because paths, timestamps, order, modes and compression are deterministic; the old ZIP was not copied or reused. The candidate sourceCommit remains the pre-checkpoint HEAD and includes the reviewed build-tool changes in its creation process. It is not a production deployment or approved rollback.

Final questions 1, 3, 4, 6–18: YES; 2 and 5: NO; 19: digest above. Staged/commit/clean answers 20–23 are reported with actual Git evidence after execution. 24–25: NO / NO. Secret scans and local extraction do not certify remote hosting settings or the exact remote Info-ZIP implementation.

---

# Historical implementation report

Date: 2026-10-22 (Asia/Tokyo).
Result: **LOVE_FORTUNE_ZIP_LINUX_PERMISSION_FIX_PASS**. Local implementation and fresh regression completed; no commit, push or deployment.

| Section | Result |
|---|---|
| A. Result | LOVE_FORTUNE_ZIP_LINUX_PERMISSION_FIX_PASS |
| B. Root Cause | Windows Compress-Archive emitted backslash names and no Unix mode metadata; Windows-only extraction masked Linux portability failure |
| C. Files Changed | Six packaging/test/report files listed below; no application runtime changes |
| D. ZIP Path Normalization | Explicit POSIX names and all parent-directory entries; PASS |
| E. Unix Permission Metadata | Unix creator system 3; directories S_IFDIR + 0755, files S_IFREG + 0644; PASS |
| F. New Regression Tests | 13 checks PASS: metadata, reverse-order deterministic rebuild, backslash/DOS host/0000/0600 directory/unreadable file/missing root rejection and unsafe input path rejection |
| G. PHP Suite | Fresh 156 tests / 1,263,293 assertions PASS; unchanged baseline |
| H. Backend Regression | Full Interpretation/Period/Daily/Public Combined/Feature/engine/reference gate PASS; additional cache/Yearly concurrency PASS, differences 0 |
| I. Schema/OpenAPI | 36 schemas, 203 examples (113 accepted / 90 expected rejection), 20 OpenAPI operations PASS; unchanged |
| J. Packaging Regression | 59 checks PASS: prior 46 plus 13 ZIP checks |
| K. Runtime File Count | 115 files plus 24 explicit directories |
| L. Artifact PHP Lint | All 106 Linux-extracted PHP files PASS; six reference readers PASS |
| M. ZIP / Runtime Difference | 0; host-extracted and Linux-extracted bytes match source manifest |
| N. Backslash Entry Check | Old ZIP: 119/119; new ZIP: 0/139 |
| O. Directory Mode Check | 24/24 metadata and actual Linux directories 0755, traversable as uid 33 |
| P. File Mode Check | 115/115 metadata and actual Linux files 0644, readable as uid 33; no chmod |
| Q. Security Exclusions | Fresh allowlist/content/path checks PASS; no private JSON, operational wrapper, test/dev files, account paths or discovered real credentials |
| R. Working Tree | Six intended uncommitted files; node syntax and git diff --check PASS |
| S. Commit | NONE |
| T. Push | NONE |
| U. Deployment | NONE |

## Observed root cause

Inspected the central directory of the original `.tools/releases/frontend-v1-SqqUdE/candidate.zip` (previous SHA-256 820f1e70a992bb09d4f51791593957a24d6413088d822ac8659bba158a58b295). It contains 119 entries, all using backslashes: 115 files and four explicit directory entries. Every entry uses DOS creator-system 0 and has zero upper Unix mode bits. This means Unix permissions were absent, not an intentional Unix 0000 mode. Other parent directories were implicit in Windows paths. These properties explain why relying on host-specific extraction did not establish a safe Linux package; the exact server extractor's permission synthesis has not been reproduced locally yet.

## Implementation

Replace Compress-Archive/Expand-Archive with Python standard-library zipfile using the already available build-only Python image pinned to `python@sha256:47ae396f09c1303b8653019811a8498470603d7ffefc29cb07c88f1f8cb3d19f`. No runtime dependency, WSL requirement or package installation is added. Docker uses --pull=never and --network none. Sorted POSIX entry names, fixed 1980 timestamps, explicit file type/mode metadata and pinned compression make equal byte inputs reproducible independent of host filesystem metadata. Runtime byte differences such as Git line-ending conversion remain inputs and are not normalized.

Python inspects every entry and checks CRC/content before host-side extraction for byte comparison. Separately, the original ZIP is copied into the existing Linux WordPress container and extracted by PHP ZipArchive as www-data with umask 0022. The gate checks actual root, all directory/file modes, traversal/readability and SHA-256 values. There is no chmod repair. Artifact lint and reference probes then operate on that Linux-extracted tree. PHP extraction and independent metadata inspection are complementary; no claim of an Info-ZIP server test is made.

The candidate.zip, candidate-sha256.json and validation.json outputs and runtime root remain unchanged. The new scripts are outside the plugin and cannot enter the runtime ZIP through its allowlist.

## Files

- scripts/package-lolipop.cjs
- scripts/test-package-lolipop.cjs
- scripts/package-zip-runner.cjs
- scripts/package-zip.py
- scripts/check-linux-zip.php
- docs/contracts/zip-linux-permission-fix-report.md

## Execution evidence and recovery

Initial HEAD was e40230e6fd723a683f8d1a349e3567b7335aa82d with a clean working tree. The prior attempt was blocked by a missing/unresponsive Docker Linux engine. On October 22 Docker Desktop was started successfully, engine 29.7.2 responded, MySQL became healthy and the existing compatibility WordPress container started. Failed prior attempts are not used as PASS evidence.

Executed `node scripts/test-package-lolipop.cjs`: exit 0, 59 checks PASS. Executed the approved `node scripts/package-lolipop.cjs` flow through a Node child with separate output file descriptors: exit 0. This includes fresh full PHP/backend/schema/reference regression and additional cache/concurrent Yearly checks, followed by the new builder and Linux extraction gate. Node syntax and git diff --check PASS; runtime, frontend, scoring, RuntimeConfig, cleanup, schemas and OpenAPI diff is empty. No existing test expectation was changed.

Fresh candidate: `.tools/releases/frontend-v1-iJZxI3/candidate.zip`, **553601 bytes**.
SHA-256: `053be3bcd149d150df444ac29f123789c8cbe3d7d42c6ff28fb068704bc1b451`.
Manifest: `.tools/releases/frontend-v1-iJZxI3/candidate-sha256.json`.
Complete results: `.tools/releases/frontend-v1-iJZxI3/validation.json`.
Command output: `.tools/zip-linux-fix-build.json`; progress: `.tools/zip-linux-fix-build.stderr.txt`.
These artifacts remain ignored, outside the commit scope. The packager's historical FRONTEND_V1_PACKAGING_REPAIR_PASS label is retained; zipMetadata and linuxExtraction now carry the additional gates.

Independent final SHA/manifest verification confirmed 115 files, differences 0, specified forbidden path/name hits 0. Existing credential-pattern scans plus source review found no real secrets; such scans are not an unlimited proof about arbitrary strings. Runtime bytes match the approved checkpoint; ZIP bytes change because names, directory entries, metadata, timestamp and compression are now controlled. The builder verifies a second in-memory rebuild with reversed input order is byte-identical; no separate Windows-native Python run is claimed. Linux verification used PHP/libzip in the existing container, not the remote server's Info-ZIP executable. Production hosting validation remains separate.

Final questions 1, 2, 4, 5 and 7–19: YES. Questions 3 and 6: NO. Questions 20–22: NO / NO / NO. Ready for the separately authorized final regression/checkpoint phase; this task does not commit or deploy.
