# Period orchestration V1

Status: PERIOD_ORCHESTRATION_CONTRACT_APPROVED / APPLIED
Authority: user final product decision 26e33b2b-9e19-409d-943c-91dca8a9fcaa and implementation authorization cfa6c140-349c-4b0e-b6c6-6c8cc8b1aebe.

Internal version: SAJU_PERIOD_ORCHESTRATION_V1. Public scoreVersion remains SCORE_COMBINED_LIFETIME_V1. All four period operations use CONFIG_PERIOD_V1; Compatibility and standalone Daily retain their existing configs. PeriodRelease::manifest binds Daily semantics, catalog/reference identities, membership, aggregation, statistics, rankings and signing. This is the current authority for these operations; earlier pending and period-formula proposals remain historical.

## Membership and reuse

Range is inclusive 1..31 dates; reversed ranges are INVALID_DATE_RANGE, excessive ranges DATE_RANGE_TOO_LARGE, unsupported members UNSUPPORTED_DATE. Weekly requires the supplied Monday and includes seven dates without adjustment. Monthly includes every Gregorian date; Yearly contains twelve Gregorian Monthly results, not a direct daily aggregate. Supported public years remain 1900..2099.

Each member uses the existing frozen Daily resolver and four logical slots with candidate multiplicity unchanged. Natal, feature extraction and Combined Lifetime are calculated once per request. Immutable reader instances may be reused within a request; no personal shared cache, network, OS timezone authority or logging is introduced.

## Exact arithmetic

Weekly/Monthly score is the unweighted mean of valid exact clamped Daily scores. Numeric INSUFFICIENT_PERIOD_DATA fallback and null Daily scores are excluded. Yearly score is the equally weighted mean of numeric Monthly scores. Any internal error fails the whole request. Delta is exact period score minus exact Lifetime baseline; either null gives null.

Coverage averages ALL calendar children (7 days, all month dates, or 12 months), retaining actual coverage of null/fallback children. Confidence averages only valid numeric children, empty=0, without coverage multiplication. Empty periods have null score/delta/volatility, INSUFFICIENT_DATA status/trend, UNKNOWN direction and empty rankings. One valid child has zero population volatility and Weekly slope null.

Status uses canonical HALF_UP4 score bands <45 CAUTION, <60 BALANCED, <75 GOOD, <85 VERY_GOOD, otherwise EXCELLENT. Trend uses exact unrounded delta: absolute <3 STABLE; [3,8) NOTICEABLE; >=8 SIGNIFICANT. Direction is STABLE in the stable band, otherwise UP/DOWN. Thus exact 2.99996 may serialize as3 with STABLE; exact7.99996 as8 with NOTICEABLE. Wire validation must not recompute trend from rounded delta.

Variance is exact population variance. Deterministic integer comparison rounds its square root HALF_UP4. OLS uses exact rationals and original calendar spacing. Wire score/delta/coverage/confidence/slope use HALF_UP4; ranking uses exact values first.

Best sorts score DESC, confidence DESC, date ASC, then Caution sorts score ASC, confidence DESC, date ASC excluding Best selections. Limits are2/5/3 respectively. Yearly ranked dates are YYYY-MM-01 representing monthly results.

## Public boundary

Range preserves meta/startDate/endDate/targetTimezone/days only; nested unsigned Daily results retain CONFIG_DAILY_V1 and existing optional static Zodiac context. No aggregate, top-level context or token; no nested token. Range works without signing keys.

W/M/Y preserve existing response fields, features=[], signed evidence=[], optional static Zodiac context once. Numeric and partial numeric responses require a token; empty responses omit it. Required signing failure is503 SERVICE_UNAVAILABLE. ZODIAC_CONTEXT_V1 embeds an unsigned period-result with CONFIG_PERIOD_V1. Root, direct $defs, OpenAPI and signing constraints reject cross-route configs. No public Saju context, MULTI or AI implementation.

Each new operation has an independent existing60/600-second rate bucket, 32768-byte body bound, Retry-After on429 and no-store on success/error. No public path bump. Existing numeric goldens and historical examples are preserved. New release examples are synthetic illustrative wires; independent arithmetic assertions remain in PHPUnit.

## Scope

These core/API changes do not implement interpretation, UI or Admin. Deployment secrets, operational capture settings and production performance require separate verification. See period-implementation-report.md for actual results and limitations.
