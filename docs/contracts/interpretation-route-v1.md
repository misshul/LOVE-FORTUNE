# Interpretation business route V1

Contract: INTERPRETATION_ROUTE_CONTRACT_APPROVED.
Authority: user Final Product Decision af831d36-3461-4836-8a8e-6a8fa09659df and implementation approval cdfe6981-1eb8-4c17-afd7-5aa8c9f565c3.
This release supersedes earlier interpretation placeholders and relationship-personalization wording. No Core formulas, score/config/context versions or signed payload fields change.

## Request and verification

POST `/wp-json/love-fortune/v1/interpretation/generate`, operationId `generateInterpretation`.
Closed request: required signedContext, optional locale default ko-KR. Supported locales ko-KR/ja-JP/en-US must equal signed locale. No question, relationshipType, tone, style, length, provider/model or Birth input.
Body limit 65536 UTF-8 bytes including wrapper; public token maxLength 65499 ASCII characters. Minimal wrapper with locale is 37 bytes. The existing shared internal verifier retains 65536; this is not the public request limit. Body overflow takes precedence over token length. Content-Type application/json with optional charset=utf-8 only; any Content-Encoding rejected.
Interpretation uses its own 10/600-second source-HMAC rate bucket, not Core60/600. Retry-After is integer seconds. Existing hourly rotation/3600-second retention applies; no raw IP storage. Single-node counters require deployment cleanup and coordinated limiting for multi-node deployments.

Verify LFIC header/canonical bytes/HMAC current or previous key, TTL300/no grace, purpose INTERPRETATION, locale, ZODIAC_CONTEXT_V1, current semantic versions, typed result/signability, evidence subset and static Zodiac identity before provider calls. Previous keys never authorize old semantics. Token reuse within TTL is allowed without replay storage.

| Type | Score | Config |
|---|---|---|
| Compatibility | SCORE_COMBINED_LIFETIME_V1 | CONFIG_COMBINED_LIFETIME_V1 |
| Daily | SCORE_COMBINED_LIFETIME_V1 | CONFIG_DAILY_V1 |
| Weekly / Monthly / Yearly | SCORE_COMBINED_LIFETIME_V1 | CONFIG_PERIOD_V1 |

Daily-range and unsigned results are unsupported. Discriminator is derived only from the verified result. No Core recalculation in the interpretation service.

## Provider input allowlist

Common: resultType, locale, aiPromptVersion, signed evidence, optional validated zodiacContext. Project explicitly; never serialize the entire result/token.

| Type | Result fields |
|---|---|
| Compatibility | overallScore, status, coverage, resultConfidence, categories |
| Daily | date, targetTimezone, lifetimeScore, dailyScore, dailyDelta, dailyStatus, coverage, resultConfidence, categoryScores if present |
| Weekly | period, weeklyScore, periodDelta, status, trendStatus, trendDirection, coverage, resultConfidence, bestDays, cautionDays, volatility, optional slope |
| Monthly | period, monthlyScore, periodDelta, status, trendStatus, trendDirection, coverage, resultConfidence, bestDays, cautionDays, volatility |
| Yearly | period, yearlyScore, periodDelta, status, trendStatus, trendDirection, coverage, resultConfidence, bestMonths, cautionMonths, yearlyVolatility |

Never include raw token, requestId, issuedAt/expiresAt, kid/signature, birthDate/time/locationId, name/nickname, candidate IDs, sampleRef, internal source envelope/dependencies/lineage or secrets. Relationship personalization deferred; do not infer it. Public Ten Gods/Saju context and MULTI deferred. Static Zodiac context is descriptive only; no planets/aspects/transits or numeric effects.

## Output and AI_PROMPT_V1

Public shape stays meta, summary, strengths, challenges, advice; closed. Provider returns only the four content fields; server attaches metadata.
Summary 1..1200, advice 1..1600, item text 1..500 Unicode code points; strengths/challenges 0..5 each. Item shape text/evidenceRefs, unique refs min1 resolving exclusively to signed evidence. Empty evidence means both arrays empty; summary/advice still allowed. No hidden Daily/Saju cause explanations.
AI metadata: configured nonempty provider/model, interpretationMode AI. Fallback: provider/model null, interpretationMode FALLBACK. Both use aiPromptVersion AI_PROMPT_V1 and existing Core versions. No interpretationVersion or numeric output fields.
AI_PROMPT_V1 covers system instruction, DTO semantics, grounding/numeric/date/output/safety rules and locale fallback family. Semantic changes require a new prompt version candidate; cosmetic corrections alone do not. Provider/model selection is separate.

Numeric claims must match the signed field AND its meaning, not merely appear somewhere in the DTO. No invented scores/probabilities/percentages. Dates only from Daily date or signed rankings. Yearly YYYY-MM-01 means month, not the first day. Preserve rank order and exact-derived trend; never recompute from rounded delta. Coverage means evaluation availability; confidence is contractual, not real-world probability.
Every output includes a generic locale-specific limitation framing, without numeric thresholds. Plain text only; reject markup, do not sanitize it into a valid interpretation. Preserve all-ages, non-fatalistic and celebrity privacy safety rules.

### Fail-closed implementation acceptance grammar

Arbitrary prose cannot be proven grounded by keyword checks. V1's implementation accepts only code-owned localized sentences and typed signed-value clauses, separated by newlines. It accepts full ordered ranking clauses, field-labelled numeric/status/trend clauses, neutral guidance, and category/subject/direction-bound evidence sentences. Unsupported paraphrases, invented dates/numbers, alternate-language sentences, markup and hidden claims are repaired or fall back. This is a conservative acceptance subset of the approved output contract, not unrestricted natural-language safety certification. The static system prompt describes the same grammar; no signed field is interpolated as instructions.
Generic framing appears in summary/advice. Fallback uses the same validator, one typed result clause and neutral advice; strengths/challenges may remain empty even for Compatibility. Padding whitespace counts toward code-point limits; no grapheme/byte substitution.

## Provider activation and execution

ProviderInterface separates business orchestration from transport. No vendor is selected by this approval. The implemented vendor-neutral JSON gateway adapter requires server-owned environment values:

- LOVE_FORTUNE_AI_ENABLED=1
- LOVE_FORTUNE_AI_ENDPOINT: approved HTTPS gateway URL, no redirects/userinfo
- LOVE_FORTUNE_AI_KEY: secret-manager/environment bearer credential
- LOVE_FORTUNE_AI_PROVIDER and LOVE_FORTUNE_AI_MODEL: nonempty configured IDs

Unset/incomplete configuration means AI OFF and deterministic fallback. Nothing is persisted in wp_options. For Docker, provision these explicitly in the deployment/container environment; changing the project .env alone does not inject them. This task does not change Docker or provision credentials.
Gateway protocol: HTTPS POST JSON `{model,system,input,validationErrors}`; response200 is the content DTO itself, not a vendor-specific envelope. `input` is exactly the allowlisted DTO. `system` is code-owned AI_PROMPT_V1; repair adds only safe error categories. No rejected raw output is sent back. A direct vendor API requiring another protocol needs a separately configured adapter/gateway. The adapter uses WP safe HTTP, TLS verification, no redirects, bounded timeout and 65536-byte response cap; no SDK or runtime download.
Total8-second monotonic budget shared by initial call/retry/repair, each timeout min(6,remaining). Network/5xx retry max1; schema/content repair max1. Provider429 falls back and never becomes user429. Late output is discarded. An adapter must enforce its timeout; arbitrary injected adapters are not preempted by PHP. Validation/fallback is local work; no new provider budget after expiry.
AI OFF/timeout/outage/429/invalid output -> fallback200 if possible; otherwise503 INTERPRETATION_UNAVAILABLE. Core remains valid. No raw provider errors returned or logged.

## Privacy and errors

No request/token/context/DTO/prompt/repair/raw response/output/provider error capture, personal cache/history or analytics. No provider/model/token usage logging added. Only existing operational allowlist/retention applies. All successes/errors no-store.
Vendor no-training/secondary-training settings, retention, region, subprocessors, infrastructure HTTP debug hooks and CDN/WAF/APM capture remain deployment checks before live activation. Local mocked HTTP tests do not certify vendor/infrastructure behavior.
Errors:400 INVALID_REQUEST;413 PAYLOAD_TOO_LARGE;415 UNSUPPORTED_MEDIA_TYPE/UNSUPPORTED_CONTENT_ENCODING;422 UNSUPPORTED_LOCALE/INVALID_INTERPRETATION_CONTEXT/INTERPRETATION_CONTEXT_EXPIRED/INVALID_INTERPRETATION_PURPOSE/INTERPRETATION_LOCALE_MISMATCH;429 RATE_LIMITED;503 INTERPRETATION_UNAVAILABLE.

## Validation

Unit tests cover five types/three locales, DTO capture, evidence subset/direction, length/count/markup, grounded numbers/dates/rankings/trend, replay/key/config/expiry, empty evidence, fallback, provider failures, repair, shared deadline and rate isolation. WordPress dispatcher tests cover registered route/default configuration and actual gateway adapter with HTTP interception. Full retained period gate covers Core/FP/Saju/Zodiac/reference/schema/OpenAPI/privacy regressions.
Run `node docs/contracts/validation/interpretation-application.cjs`; `--write-report` explicitly saves machine evidence. Existing structural examples updated only from synthetic prompt version to approved AI_PROMPT_V1; these legacy examples test schema shape, not current semantic text acceptance.
