# LOVE FORTUNE Contract Freeze v3

Version: 3.1.0
Status: CONTRACT FROZEN

Current Saju/Guardrail authority: [Saju v1](saju-catalog-v1.md), [Guardrail v2](guardrail-v2.md), [ContextEvidence](context-evidence.md), [approval](saju-v1-application-approval.md).

Astrology authority: [Applied Catalog v1](astrology-catalog-v1.md), [mapping approval](astrology-v1-mapping-approval.md), [application approval](astrology-v1-application-approval.md).

Authority: [Final Decision v3](final-decision-v3.md), then [Final Decision v2](final-decision-v2.md), superseding [v1](freeze-decision-v1.md) where changed. Explicit user clarifications are recorded in [clarification-v2.md](clarification-v2.md). Do not confuse external production dependencies with unresolved specification decisions.

## Decision register

| ID | Status | Applied decisions / remaining boundary |
|---|---|---|
| SC-01 | RESOLVED | Eligible/available/usable evidence, retained structural coverage, four-sample source confidence. |
| SC-02 | RESOLVED | Astrology109 APPLIED/READY; Saju14 families (9 scoring/5 context) APPLIED/READY; Daily0. Remaining: DAILY_RULE_CATALOG_APPROVAL. |
| SC-03 | RESOLVED | Outer definition, product0.50 cap,15% aggregate cap. |
| SC-04 | RESOLVED | PAIR/scalar/period/canonical identity; approved metadata.candidates scalar array and active-only orbCloseness[0,1]. |
| SC-05 | RESOLVED | Samples06/12/18/23, mean/peak, missing samples, earlier UTC instant and gaps. |
| SC-06 | RESOLVED | Valid-day exclusion, Weekly arithmetic mean, lifetime bands, full-precision periodDelta, trendStatus/trendDirection. |
| SC-07 | RESOLVED | Weighted referenced Feature.confidence; Action adjustment0; SC-07 v2 fixed-W0 direct20-point marginal cap; legacy search deprecated. |
| SC-08 | RESOLVED | birthLocationId DTO, required timezone, locales, reference/celebrity/Admin wire. |
| SC-09 | RESOLVED | LFIC signing bytes/kid/binding/TTL/errors, retry/repair budget,200 fallback/503 contract. |
| SC-10 | RESOLVED | Exact byte/media/encoding/rate/header/log/retention/backup rules. |

Astrology contains109 enabled pair-aspect rules with228 mappings; five generic aspects are reference definitions. Saju contains14 families:9 enabled scoring,5 context-only; historical weights retained. Daily contains existing planet/source/sample reference tables but no fully specified transit activation/category/direction rules. No interpretations were invented. A disabled inventory is not an implemented or usable fortune engine.

## Artifacts

- [Runtime contract](runtime-contract-v2.md): calculation, time, Feature, token and HTTP rules.
- [API details](api-details-v2.md): endpoint/DTO/capability normalization.
- [OpenAPI](openapi.yaml):6 principal Core POSTs,1 existing celebrity calculation,1 Interpretation POST,5 public reference GETs and Admin operations.
- [Schemas](schemas/): JSON Schema2020-12. Structural validity is distinct from semantic validity.
- [Rule catalogs](rules/): approved Astrology v1 rules and references; approved Saju and empty Daily catalogs.
- [Schema example manifest](examples/manifest.json): synthetic valid/invalid cases.
- [Canonicalization](examples/canonicalization-vectors.json), [LFIC signing](examples/signed-context-vectors.json), [arithmetic/period/DST](examples/arithmetic-period-dst-vectors.json), [HTTP/privacy](examples/http-privacy-vectors.json) vectors.
- [Validation report](validation-report.md) and [current execution results](validation-results-saju-v1.json): actual checks and Freeze judgment.
- [v3 arithmetic vectors](examples/arithmetic-v3-vectors.json): approved confidence, validity, Weekly, Action and trend boundaries.

Example rules, versions, references and keys are synthetic documentation fixtures. They are not real-user data, production keys or engine Golden results. Never use the public fixture secrets in any environment. A structurally valid score fixture is not a computed engine result.

## External production gates

- SAJU_DAY_PILLAR_EPOCH
- EPHEMERIS_PROVIDER

Do not enable production engines before verification. These are BLOCKED_EXTERNAL, not SPEC_CONFLICT; they alone must not fail Contract Freeze. Astrology Catalog v1 is APPLIED; Astrology Catalog Readiness READY. Overall Score Engine Readiness is BLOCKED_CATALOG: DAILY_RULE_CATALOG_APPROVAL. Empty active catalogs are valid frozen contracts, not SPEC_CONFLICT. Contract Freeze: PASS; Production Engine Readiness: BLOCKED_EXTERNAL.

## Privacy and verification limits

No general-user profile/history/cache, stable anonymous ID, fingerprinting, body/context/prompt/raw-output logging or automatic/background stored-Birth transmission. Keep My1/Partner1 independent opt-in and client-only labels. Core/AI/context responses are no-store. Operations/application backups30 days, audit/security backups90 days, non-personal aggregates<=13 months, rate keys<=3600 seconds, raw Birth retention0.

AI explains approved evidenceRefs without generating scores. Deterministic fallback is independent of Core. Provider training/retention and all-ages/celebrity safety rules still apply. Document tests do not prove deployed WordPress nonce checks, infrastructure capture settings or provider configuration.

OpenAPI uses [3.1.1](https://spec.openapis.org/oas/v3.1.1.html), schemas use [2020-12](https://json-schema.org/draft/2020-12/release-notes). Schema IDs under love-fortune.invalid are identifiers, not service URLs. Resolve them through the local schema registry.
