# SAJU Day Pillar Epoch v1 application approval

Decision date: 2026-09-15. User-authorized scope: apply the Phase3 approved epoch contract and implement/test production PHP Day Pillar calculation. No commit/push. Frontend, database schema, Docker, WordPress UI and ephemeris provider changes are prohibited.

Approved identifier SAJU_DAY_PILLAR_EPOCH_V1; formulaVersion NAOJ_JDN_GANZHI_V1; evidenceVersion PHASE3_2026-09-15_APPROVED. Normative formula is `floorMod(JDN+49,60)`;2019-01-27甲子/JDN2458511 is only a golden reference. Exact approved metadata, EP-01..07, evidence and integration scope are in [the contract](saju-day-pillar-epoch-v1.md).

Acceptance:20 fixed golden dates, complete73053-date internal range, independent ordinal/JDN, invalid/range/leap/modulo/cycle tests, actual production boundary and Daily/Natal adapter tests, existing contract suite and privacy checks. No golden expected changes to accommodate implementation. Remove SAJU_DAY_PILLAR_EPOCH from active external blockers only after passing. Keep Production BLOCKED_EXTERNAL with EPHEMERIS_PROVIDER alone. Do not claim the full four-pillar engine, location service, API or infrastructure audit is complete.

Prior application validators contained documentation-only scope checks for earlier tasks. The Daily gate's explicit `--contracts-only` mode runs the same regressions but omits that historical worktree scope restriction. The new epoch application gate checks the current authorized scope, unchanged rules/public wire, epoch contract and readiness. Default historical Daily scope checks remain available.

Execution evidence and actual applied status: [validation report](validation-report.md), [readiness](readiness.md). This approval supersedes earlier unresolved epoch readiness statements, without rewriting historical approvals or saved results.
