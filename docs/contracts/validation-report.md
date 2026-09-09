# SC-07 V2 + SAJU CATALOG V1 APPLICATION REPORT

Scope: docs/02_FORTUNE_ENGINE_SPEC.md, docs/03_SCORE_SPEC.md, docs/08_AI_PROMPT_SPEC.md and docs/contracts/** only. No production implementation, commit or push.
Authority: [Application approval](saju-v1-application-approval.md). Product distribution characteristics are APPROVED v1 behavior, not an outstanding product decision.

| Item | Actual validation result |
|---|---|
| Contract Freeze | PASS |
| SC-07 Guardrail | V2 APPLIED |
| Saju Catalog | APPLIED |
| Saju Catalog Readiness | READY |
| Astrology Catalog Readiness | READY;109 rules /228 mappings unchanged |
| Overall Score Engine Readiness | BLOCKED_CATALOG |
| Remaining catalog blocker | DAILY_RULE_CATALOG_APPROVAL |
| Production Engine Readiness | BLOCKED_EXTERNAL |
| External blockers | SAJU_DAY_PILLAR_EPOCH, EPHEMERIS_PROVIDER |

## Validation against actual repository files

| Check | Result |
|---|---|
| Family classification | 14 / scoring9 / context5;16 scoring variants /37 mappings |
| Original family weights | 14 matched docs/03 section18 |
| Decimal mapping / approved matrix | PASS; no invented signedValues |
| Day Stem | 100 ordered cases: SAME20, GENERATION40, CONTROL30, COMB10 |
| Day Branch | 144: SAME28, GENERATION50, CONTROL42, LIUHE12, CLASH12 |
| Duplicate owner / Feature | 0 |
| Complement | 497,025 count-distribution pairs; exact gain range0..0.50; boundaries PASS |
| Yin-Yang | 45 count cases; valid bands and invalid-count rejection PASS |
| Unknown-time | 1 / .875 / .75; candidate agreement and no double penalty PASS |
| Context | Separate closed schema; Ten Gods valid; dangling/duplicate refs rejected; canonical context ID PASS |
| Schema | 27 schemas PASS; OpenAPI PASS |
| Structural examples | 171:97 valid accepted,74 invalid rejected |
| Additional semantic invalid cases | 5 rejected (owner, coverage, token counts, context refs) |
| Existing semantic regression | 80 vectors PASS; SC-07 vectors explicitly superseded by v2 |
| Canonicalization | 6 cases PASS; existing Feature identity unchanged |
| Signed context | 2 valid /8 invalid PASS; public wire/signing/TTL unchanged |
| HTTP/privacy | 19 cases PASS |
| DST | 4 cases PASS; local ICU78.2 / tzdb2025c |
| Astrology | 109/228 exact catalog comparison PASS; v2 regression228 single fixtures +8 multi-category fixtures |
| Saju population | 57,600 identical synthetic combinations;0 calculation failures |
| Guardrail evaluated usable categories | 281,786 |
| Guardrail activated categories / adjusted Features | 59,428 /59,428 |
| Individual impact >20 / exclusion / unresolved / induced insufficient | 0 /0 /0 /0 |
| Exact rational guardrail cross-check | 1,117 unique inputs /3,391 Features;0 violations |
| SPEC_CONFLICT / effective cross-spec contradictions | 0 /0 |
| Production code files changed | NONE |
| git diff --check | PASS |

## Method and contract boundaries

[Actual execution results](validation-results-saju-v1.json) contain complete metrics and distributions, generated from command output. Validation commands:
- node docs/contracts/validation/structural.cjs
- node docs/contracts/validation/semantic-regression.cjs
- node docs/contracts/validation/astrology.cjs
- node docs/contracts/validation/saju-guardrail-v2.cjs

The Saju validator reads catalog families, mappings, reference tables and schema/semantic examples; it compares mappings to the approved matrix and weights to docs/03. Count/whole-chart fixtures are abstract synthetic data, not actual birth charts or real users. Population sampling matches the previously approved simulation. No external provider or epoch is used. No production engine, WordPress security or infrastructure behavior is certified.

SC-07 v2 uses original W0 and direct marginal moderation. The old single-positive raw50 and same-sign raw100 regression expectations were superseded explicitly (now70 and90); original vectors remain in legacy-guardrail-v1-vectors.json as historical evidence. Other semantic expectations remain unchanged. Generic MIXED legacy fixtures are retained; Saju uses its own decimal-sign schema.

ContextEvidence and contextEvidenceRefs are defined in a separate INTERNAL explanation envelope. Existing public API and signed LFIC schema are unchanged. The new envelope cannot be injected as unsigned context into existing requests. No public transport implementation is claimed. Four unapproved context detectors remain DEFERRED and have no invented tables.

Historical approvals retain their original text with explicit supersession notices. Current runtime, score and catalog registers govern implementation. A stored legacy failure vector or former readiness report is not an active blocker.

## Scope verification

38 tracked files outside the allowed scope matched their initial clean Git blob hashes after normal Git line-ending filters. No tracked or untracked changed file is outside the allowed scope. Ignored local files are not part of the Git baseline; no operation wrote to them. Commit/push was not performed.

## Files changed

See the complete list below. Validation scripts are documentation tooling, not production engine code.

- docs/02_FORTUNE_ENGINE_SPEC.md
- docs/03_SCORE_SPEC.md
- docs/08_AI_PROMPT_SPEC.md
- docs/contracts/README.md
- docs/contracts/astrology-catalog-v1.md
- docs/contracts/clarification-v2.md
- docs/contracts/context-evidence.md
- docs/contracts/examples/arithmetic-period-dst-vectors.json
- docs/contracts/examples/legacy-guardrail-v1-vectors.json
- docs/contracts/examples/manifest.json
- docs/contracts/examples/saju-v1-branch_clash.json
- docs/contracts/examples/saju-v1-branch_combination.json
- docs/contracts/examples/saju-v1-context-interpretation.json
- docs/contracts/examples/saju-v1-day_branch_relation.json
- docs/contracts/examples/saju-v1-day_master_relation.json
- docs/contracts/examples/saju-v1-element_complement.json
- docs/contracts/examples/saju-v1-element_control.json
- docs/contracts/examples/saju-v1-element_support.json
- docs/contracts/examples/saju-v1-feature-BCONTROL.json
- docs/contracts/examples/saju-v1-feature-BGENERATION.json
- docs/contracts/examples/saju-v1-feature-BSAME.json
- docs/contracts/examples/saju-v1-feature-CLASH.json
- docs/contracts/examples/saju-v1-feature-COMB.json
- docs/contracts/examples/saju-v1-feature-CONTROL.json
- docs/contracts/examples/saju-v1-feature-EC1.json
- docs/contracts/examples/saju-v1-feature-EC2.json
- docs/contracts/examples/saju-v1-feature-EC3.json
- docs/contracts/examples/saju-v1-feature-GENERATION.json
- docs/contracts/examples/saju-v1-feature-LIUHE.json
- docs/contracts/examples/saju-v1-feature-SAME.json
- docs/contracts/examples/saju-v1-feature-YY0.json
- docs/contracts/examples/saju-v1-feature-YY1.json
- docs/contracts/examples/saju-v1-feature-YY2.json
- docs/contracts/examples/saju-v1-feature-YY3.json
- docs/contracts/examples/saju-v1-input.json
- docs/contracts/examples/saju-v1-invalid-branch.json
- docs/contracts/examples/saju-v1-invalid-context-as-feature.json
- docs/contracts/examples/saju-v1-invalid-context-score.json
- docs/contracts/examples/saju-v1-invalid-cross-product.json
- docs/contracts/examples/saju-v1-invalid-element.json
- docs/contracts/examples/saju-v1-invalid-empty-mapping.json
- docs/contracts/examples/saju-v1-invalid-range.json
- docs/contracts/examples/saju-v1-invalid-sign.json
- docs/contracts/examples/saju-v1-invalid-stem.json
- docs/contracts/examples/saju-v1-invalid-ten-god.json
- docs/contracts/examples/saju-v1-invalid-time-null.json
- docs/contracts/examples/saju-v1-invalid-token-count.json
- docs/contracts/examples/saju-v1-invalid-transformation.json
- docs/contracts/examples/saju-v1-semantic-vectors.json
- docs/contracts/examples/saju-v1-stem_combination.json
- docs/contracts/examples/saju-v1-ten-gods.json
- docs/contracts/examples/saju-v1-yin_yang_balance.json
- docs/contracts/final-decision-v2.md
- docs/contracts/final-decision-v3.md
- docs/contracts/freeze-decision-v1.md
- docs/contracts/guardrail-v2-approval.md
- docs/contracts/guardrail-v2.md
- docs/contracts/rules/saju-rules.json
- docs/contracts/runtime-contract-v2.md
- docs/contracts/saju-catalog-v1.md
- docs/contracts/saju-v1-application-approval.md
- docs/contracts/saju-v1-final-decisions.md
- docs/contracts/schemas/context-evidence.schema.json
- docs/contracts/schemas/context-interpretation.schema.json
- docs/contracts/schemas/feature.schema.json
- docs/contracts/schemas/rule.schema.json
- docs/contracts/schemas/saju-rule.schema.json
- docs/contracts/schemas/saju-synthetic-input.schema.json
- docs/contracts/validation-report.md
- docs/contracts/validation-results-saju-v1.json
- docs/contracts/validation/README.md
- docs/contracts/validation/saju-guardrail-v2.cjs
- docs/contracts/validation/semantic-regression.cjs
- docs/contracts/validation/structural.cjs
