# DAILY ORCHESTRATION V1

Status: DAILY_ORCHESTRATION_CONTRACT_APPROVED. Authority: approved DO-01..DO-09 decisions and production application request 947f9ef6-21d2-4419-968d-e65de15cdeff. This scoped contract supersedes prior statements that single-date Daily orchestration is pending. Other period routes remain pending.

## Version and dependencies

Internal SAJU_DAILY_ORCHESTRATION_V1; public scoreVersion SCORE_COMBINED_LIFETIME_V1; configVersion CONFIG_DAILY_V1. Server-owned DailyRelease manifest binds CONFIG_COMBINED_LIFETIME_V1 dependencies, LIFETIME_COMBINED_SCORING_V1, Daily catalog 1.0.0, DAILY_SAJU_REFERENCE_V1, SAJU_TIMEZONE_REFERENCE_V1, four slots, exact aggregation, K=18, S2 and DO-01/02/05/09 semantics. Compatibility retains CONFIG_COMBINED_LIFETIME_V1. Cross-route config combinations are rejected. No public dailyEngineVersion field.

The deployment copy config/references/daily-rules-v1.json is byte-identical to rules/daily-rules.json; production selects only the existing nine SAJU rules / nineteen mappings. No catalog values or Lifetime goldens change.

## Frozen time and candidates

Use the existing byte-pinned timezone reader for target-zone resolution and Seoul conversion. All 23 artifact zones and artifact aliases are accepted; aliases normalize to canonical names. An IANA name absent from the artifact is unsupported. No OS/PHP transition authority or network fallback. Public dates remain 1900..2099.

Four logical local slots: 06:00,12:00,18:00,23:00. Fold chooses earlier instant; gap chooses first valid instant after the gap, retaining four nominal slots even when instants coincide. Convert each instant using frozen Asia/Seoul, longitude127.5; apply existing historical-offset correction once, then approved23:30 Day boundary and unchanged epoch.

Natal candidates retain atomic lineage, order identity and multiplicity. Unknown-time candidates are not replaced by invented hours. Evaluate sample Day against each person's natal Day; stem ownership COMBINATION then SAME then GENERATION/CONTROL; branch LIUHE then CLASH then element fallback. Feature-first presence is presentCount/totalCount; fixed rule values have agreement1. Do not substitute Lifetime FE M/C or average final candidate scores. No A×B Daily product.

Per-person category signal uses effective rule weights including feature confidence; category confidence is effective/pre-confidence weight. Computable no-event is signal0/confidence1, unavailable is null. Project A/B per category per sample by arithmetic mean of signal/confidence only when both are computable. Then aggregate time; never project already aggregated person Daily scores.

## Exact arithmetic and availability

Eligible categories: ATTRACTION, EMOTION, COMMUNICATION, PASSION, STABILITY, HARMONY, SUPPORT. Their weights are .12,.17,.14,.10,.15,.12,.08; fixed denominator .88.

Temporal signal =3/4 computable sample mean +1/4 signed peak. Equal absolute peaks select earlier nominal slot using exact rational comparison. Missing samples are excluded from signal mean/peak, retained as zero in confidence denominator4. Category coverage=computable pair slots/4; confidence=sum sample confidence/4.

Overall sample signal=sum available category weight*signal/.88. Overall sample confidence=sum available weight*confidence/sum available weights. Overall coverage=mean over four slots of available weight/.88. Overall signal is temporal aggregation of these overall samples, not a weighted sum of final category signals. Overall confidence=sum overall sample confidence/4. No Lifetime confidence or coverage multiplication. SUPPORT-only all slots legitimately yields coverage1/11 and confidence1.

Consume exact Combined Lifetime overall/category baselines once per request, never rounded compatibility output. Delta=18*signal; score=clamp(baseline+delta,0,100). No extra SC-07 or M2 pass. Null baseline wins: score null,delta0,INSUFFICIENT_DATA,confidence0,actual Daily coverage. Unavailable period with numeric baseline: unchanged baseline,delta0,INSUFFICIENT_PERIOD_DATA,confidence0. LONG_TERM has no signal,coverage0 and follows this fallback. COMMUNICATION keeps internal Daily signal but final score null/confidence0.

S2 evaluates canonical HALF_UP4 delta: <=-5 VERY_LOW;(-5,-2] LOW;(-2,2) STABLE;[2,5) GOOD;>=5 VERY_GOOD. Score/delta/coverage/confidence serialize at approved precision; exact arithmetic precedes rounding.

## Public API and signing

POST /wp-json/love-fortune/v1/fortune/daily, operationId calculateDaily. Existing daily-request shape; targetTimezone explicit. Existing Daily response fields and optional eight-category numeric/null categoryScores. features=[]; signed evidence=[]; optional existing static zodiacContext. No sample/candidate lineage, MULTI, Ten Gods or birth fields in response/token.

daily-result.schema.json is unsigned embedded result. daily-response.schema.json requires signedInterpretationContext only for numeric score with valid period evaluation; null and period fallback omit it. ZODIAC_CONTEXT_V1 binds SCORE_COMBINED_LIFETIME_V1 / CONFIG_DAILY_V1, unsigned result, empty evidence, existing locale/TTL/key rules. No recursive token. Required signing failure503; unsupported timezone422 INVALID_REQUEST_SEMANTICS; unsupported date existing UNSUPPORTED_DATE; reference corruption500 CALCULATION_FAILED. Reuse transport/rate/error registry and no-store on success/error.

No user-derived persistence/cache, birth/candidate logging, analytics or stable fingerprint. Request-local reuse only. No AI, frontend, DB migration or other period route implementation. Legacy DateTimeZone Daily helper remains for its historical tests; the new production route exclusively uses FrozenDailySampleResolver.

## Verification

Run node docs/contracts/validation/daily-orchestration-application.cjs. It retains the complete prior engine/reference/PHP/Compatibility gates and adds Daily schema/signature and actual WordPress dispatcher checks. --write-report writes the new execution evidence only. Existing synthetic256,000-case catalog validation is not production E2E. Fault/null/fallback dispatcher injection is reported separately from full numeric production execution.
