# Contract and engine readiness

Current authority: [Epoch v1 application](saju-day-pillar-epoch-v1-application-approval.md), [epoch contract](saju-day-pillar-epoch-v1.md), [Daily C21-R application](daily-v1-application-approval.md), [Daily contract](daily-catalog-v1.md).

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
| SAJU_DAY_PILLAR_EPOCH | RESOLVED |
| SAJU_DAY_PILLAR_EPOCH_V1 | APPLIED / READY |
| External blockers | EPHEMERIS_PROVIDER |

Catalog READY denotes approved, validated scoring contracts, not implemented score engines. Epoch READY additionally covers the production Day Pillar calculator and resolved-date adapters. Location/reference lookup, full natal unknown-time candidate generation, complete four-pillar and score/API engines are not certified here. Ephemeris remains the sole external blocker. Daily context/export/public category fields remain deferred and non-blocking.

Run `node docs/contracts/validation/epoch-application.cjs` for the current application gate (production PHPUnit plus all existing contracts). See [validation report](validation-report.md) for evidence and the interrupted Daily simulation's reproducibility limit. Historical reports/approvals preserve their original readiness statements as history; this register and current catalogs govern implementation.
