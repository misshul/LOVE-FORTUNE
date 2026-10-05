# Public Combined / API V1

Status: PUBLIC_COMBINED_API_CONTRACT_APPROVED. Authority: PC-01..PC-08 decision b8a1a152-a38d-4e83-b246-1e02a40c62ea and implementation request 0c07f510-e54c-4845-a5ce-14d797e7a105. This release supersedes earlier public-version deferrals for compatibility only. The implementation phase did not authorize commit/push; the subsequent final checkpoint request authorizes one local commit only, never push.

## Release and config

Public scoreVersion SCORE_COMBINED_LIFETIME_V1, configVersion CONFIG_COMBINED_LIFETIME_V1, sajuEngineVersion SAJU_LIFETIME_SCORING_V1. Existing SCORE_ZODIAC_V1 already included SAJU+ZODIAC math; this is the production pipeline/projection/evidence/signing release identity, not a new scoring formula. Server-owned PublicRelease manifest binds Saju catalog1.0.0, Zodiac catalog/date identities, category weights2.0.0, product scope ZODIAC_V1, weights4/5 and1/5, structural eligibility, existing M2, thresholds45/60/75/85 and LIFETIME_COMBINED_SCORING_V1. No locale/copy/user-derived identity. Other internal dependencies remain internal. Existing synthetic version identities stay historical.

## Request and projection

POST /wp-json/love-fortune/v1/compatibility/calculate, operationId calculateCompatibility. Existing personA/personB/relationshipType/targetTimezone plus optional locale (ko-KR) only. Person birthDate/birthLocationId and optional null birthTime. No raw coordinates, labels, source envelopes or client versions. Original birthDate drives Zodiac; Natal-adjusted dates never do.

The route runs Natal A/B -> extraction -> Saju scoring and Zodiac -> Combined -> public projection. Category confidence maps to resultConfidence; overall.score maps to top-level overallScore. All eight categories remain, COMMUNICATION null/coverage0/confidence0/INSUFFICIENT_DATA. Null score retains actual coverage and confidence0. No fake50 or numeric recalculation. Result score/coverage/confidence use maximum four-decimal HALF_UP at serialization only, no exponents/negative zero/trailing zeros. Internal pre-confidence, ledger, denominators, source envelopes and diagnostics are omitted.

Public features are an approved evidence subset, not the complete scoring ledger. Publish only current-schema-compatible, canonical-ID-verifiable single evidence. Exact decimal evidence that cannot be represented losslessly is omitted, never approximated into a new rule value. MULTI is excluded without representative selection or opaque-only IDs. No contributions/candidate lineage or Ten Gods transport. Existing Zodiac context remains separately bound. Omitted evidence cannot be claimed by future AI; ce_ does not substitute for scoring ft_ evidence.

## Signing and cutover

Numeric external success requires signedInterpretationContext; insufficient external success omits it. Embedded signed result is unsigned and uses compatibility-result.schema.json; no recursive token requirement. LFIC HS256, unpadded base64url, canonical JSON, current key signing/current+previous verification, TTL300 seconds and purpose/locale binding remain. contextVersion ZODIAC_CONTEXT_V1 remains; payload versions match result.meta.versions, evidence is an exact public Feature subset, and optional Zodiac contexts are identical.

Hard semantic cutover: only current score/config are issued/accepted. Old score or config ->422 INVALID_INTERPRETATION_CONTEXT even with an accepted signing key. No selector/dual scorer/compatibility window. Existing expiry/purpose/locale errors remain. Signing unavailable ->503 SERVICE_UNAVAILABLE, never unsigned numeric200. Interpretation provider route itself remains unimplemented.

## HTTP and privacy

Core JSON body32768 bytes; reject Content-Encoding, unsupported content type/charset parameters, unknown properties and query input. Required response Cache-Control:no-store on success and errors. Errors use code/messageKey/optional field/details empty object; no internals. New generic map:400 INVALID_REQUEST,404 REFERENCE_NOT_FOUND,413 PAYLOAD_TOO_LARGE,422 INVALID_REQUEST_SEMANTICS,429 RATE_LIMITED,500 CALCULATION_FAILED,503 SERVICE_UNAVAILABLE. Existing specific errors take precedence. messageKey is errors.<lowercase code>. Server-owned invariant failures never become insufficient.

Core60/600-second atomic infrastructure rate counter, HMAC-SHA256 source key with hourly derived infrastructure secret and window identity. IPv4-mapped IPv6 is normalized. Trusted proxy allowlist is empty: only REMOTE_ADDR is used. Retry-After integer seconds. No raw IP/body/result is stored. Local counter files expire logically within3600 seconds and expired files are purged on access; deployments must run the documented periodic cleanup to enforce physical retention during idle traffic. This infrastructure state is not a calculation cache.

No account/profile/history persistence, raw-body/debug logging, analytics or fingerprint. Random requestId only. Local reference objects and request-local engine reuse remain permitted. AI provider failure is separate from deterministic calculation. Daily/period/celebrity/AI routes are not activated.

## Configuration and deployment limits

Supply environment secrets to the PHP/Apache process (not wp_options or repository): LOVE_FORTUNE_SIGNING_KID, LOVE_FORTUNE_SIGNING_KEYS (JSON object of kid->secret, each at least32 bytes), optional LOVE_FORTUNE_SIGNING_PREVIOUS_KID, LOVE_FORTUNE_RATE_SECRET (at least32 bytes). Keys must be generated by a cryptographic secret manager; fixture keys must never be deployed. Missing infrastructure/signing configuration fails closed with503. Existing Docker files and the private .env are unchanged; an operator must inject these environment values into the running service before ordinary HTTP calculation succeeds.

Run a deployment-level periodic cleanup of the dedicated system-temp love-fortune-rate directory so expired .rate files are physically removed within3600 seconds of creation even when no requests arrive. Multi-node deployments require a shared atomic rate backend or a single authoritative gateway before release; this implementation is local/single-node. Disable request/response body capture in Apache/proxy/CDN/WAF/APM. Application tests do not certify those external settings.

## Migration and validation

34 active schemas include unsigned compatibility-result; 31 required previous Zodiac schemas are immutable historical snapshots under historical/zodiac-v1/schemas. Original example bytes and synthetic versions are preserved. Existing structural/zodiac-api gates explicitly validate historical examples in that scope. New current examples/validator validate current runtime response, signatures, identities and hard cutover. No Feature MULTI extension or Saju context binding.

Primary current checks: structural.cjs, zodiac-api.cjs, public-combined.cjs; full retained numeric/FP regression via saju-feature-extraction.cjs and scripts/test.ps1. Historical phase gates that forbid any schema/version/route change retain their prior scope; underlying numeric regressions remain active. PublicCombinedApiTest compares production exact Combined output with projection; illustrative public examples are not replacements for independent engine goldens. wordpress-public-api.php dispatches the real registered WordPress route with process-local synthetic secrets. A valid birth pair always has computable fixed-date Zodiac evidence, so the all-insufficient branch is tested through a controlled all-unavailable Combined result, not a fabricated birth E2E.

Activation requires approved contract, migration, route/projection/signing implementation and passing public regression. Overall service is not production-ready: interpretation, Daily/period and operational provisioning remain separate.

Application verification: [implementation report](public-combined-implementation-report.md). Core rate accounting runs before parsing/validation, including invalid requests. CORS allows only the configured WordPress home origin; it is not a CSRF authorization mechanism.
