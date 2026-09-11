# Contract and engine readiness

Current authority: [Daily C21-R application](daily-v1-application-approval.md), [Daily contract](daily-catalog-v1.md).

| Gate | Current state |
|---|---|
| Contract Freeze | PASS |
| SPEC_CONFLICT | 0 |
| Effective cross-spec contradictions | 0 |
| Astrology Catalog v1 | APPLIED / READY; 109 rules / 228 mappings |
| Saju Catalog v1 | APPLIED / READY; 14 families, 9 scoring / 5 context |
| Daily Catalog v1 | APPLIED / READY; 134 rules / 285 mappings |
| Score Engine catalog readiness | READY |
| Remaining catalog blockers | NONE |
| Production Engine readiness | BLOCKED_EXTERNAL |
| External blockers | SAJU_DAY_PILLAR_EPOCH, EPHEMERIS_PROVIDER |

READY denotes approved, validated scoring contracts/catalogs, not implemented PHP engines. The Daily approval component is closed by this repository application. External epoch/provider verification remains required before production calculation. Daily context/export/public category fields remain deferred and non-blocking.

Run `node docs/contracts/validation/daily-application.cjs` for the application gate. See [validation report](validation-report.md) for evidence and the interrupted simulation's reproducibility limit. Historical reports/approvals preserve their original readiness statements as history; this register and current catalogs govern implementation.
