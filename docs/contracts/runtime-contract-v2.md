# LOVE FORTUNE runtime contract v2

Status: CONTRACT FROZEN (v3 + approved Saju v1 / SC-07 v2)

Current authority: [Saju application](saju-v1-application-approval.md), [Saju v1](saju-catalog-v1.md), [Guardrail v2](guardrail-v2.md). Historical Saju placeholders and guardrail search are superseded; other contracts remain unchanged.

Authority: [Final Decision v3](final-decision-v3.md), then [Final Decision v2](final-decision-v2.md) and [approved clarifications](clarification-v2.md), superseding v1 where changed. Freeze and readiness: [register](README.md). Approved Astrology-specific additions are in [Astrology Catalog v1](astrology-catalog-v1.md); its final mapping/application approval supersedes prior Astrology placeholders.

# SC-10 HTTP, security and retention

1KB=1024 bytes. Core32768 bytes; Interpretation65536 bytes, measured on decoded body. v1 rejects any Content-Encoding with415 UNSUPPORTED_CONTENT_ENCODING. Accept application/json or application/json; charset=utf-8 (charset case-insensitive); other parameters/types give415 UNSUPPORTED_MEDIA_TYPE. Oversize gives413.

Rate source: validated client IP from an explicitly allowlisted trusted reverse proxy, otherwise direct remote address. Never unconditionally trust X-Forwarded-For. Fixed600-second windows; Core60 and AI10 requests/source/window. 429 Retry-After is integer seconds to window end. Store HMAC(short-lived infrastructure secret,normalized source IP), never raw IP; TTL<=3600 seconds. Protocol normalization: windowStart=floor(unixSeconds/600)*600; Retry-After=ceil(windowStart+600-nowSeconds), minimum1 on a429. Normalize IPv4 to unpadded dotted decimal and IPv6 to lowercase compressed text; normalize IPv4-mapped IPv6 to its IPv4 address. Reject invalid addresses; do not use forwarded values unless a trusted proxy has validated the client identity. Default trusted-proxy allowlist is empty. Use HMAC-SHA256 with an infrastructure secret rotated on3600-second UTC boundaries; rate keys are scoped by Core/AI bucket and600-second window and expire by the3600-second maximum. Neither raw IP nor the HMAC identifier belongs in logs/analytics. These are protocol serialization choices, not fortune rules.

Admin unauthenticated:401 ADMIN_AUTH_REQUIRED; missing capability:403 ADMIN_CAPABILITY_REQUIRED; missing/invalid nonce:403 ADMIN_NONCE_INVALID. All Admin routes require auth+capability+nonce. CORS is not CSRF protection. Production MFA recommended.

Operational allowlist: timestamp,requestId,endpoint,method,httpStatus,durationMs,errorCode,engineVersion,scoreVersion,configVersion. Sanitizer removes all other fields before persistence. Admin mutation security audit allowlist: timestamp,adminUserId,action,resourceType,resourceId,result,requestId. No raw payload. Operations30 days; security/admin audit90 days; corresponding backup maximum30/90 days then deletion. Non-personal aggregate<=13 months; raw Birth retention0. Birth/request-response body/context/prompt/AI output must not exist in logs or backups. Disable infrastructure capture. No tracking/fingerprinting/user-derived caches/background Birth transmission. Core/AI/context no-store; reference-only shared caches.

---

# SC-01 Confidence and coverage

featureConfidence = baseConfidence * candidateAgreement; baseConfidence defaults to1. candidateAgreement=1-(max(candidate signedValue)-min(candidate signedValue))/2; one candidate gives1. Where feature existence differs, multiply candidateAgreement by availabilityRatio=presentCount/totalCount (default baseConfidence1).

eligibleEvidence is the evidence theoretically evaluable under the approved catalog and input conditions. Supported missing-time rules remain eligible; unsupported/disabled rules are excluded. availableEvidence is eligible evidence with an actual calculated Feature object, including confidence=0. usableEvidence is available evidence with featureConfidence>0 AND preConfidenceWeight>0. Only usableEvidence enters score aggregation. preConfidenceWeight=baseWeight*ruleWeight*pairWeight, subject to the individual outer cap.

coverage=clamp(sum(available preConfidenceWeight)/sum(eligible preConfidenceWeight),0,1); a zero eligible-weight denominator gives0. Coverage measures structural availability, independently of confidence. A zero available numerator also gives0. Zero confidence does not force coverage to0.

If usableEvidence is empty: categoryScore=50.0000, resultConfidence=0, status=INSUFFICIENT_DATA; retain computed coverage. Exclude insufficient categories from overall aggregation; all insufficient gives null overall. Do not use ambiguous E=0 as a coverage reset. SC-07 v2 never forces positive usable weight to zero and does not alter structural coverage.

With usable evidence, categoryResultConfidence=sum(available preConfidenceWeight*featureConfidence)/sum(available preConfidenceWeight); the category wire resultConfidence=coverage*categoryResultConfidence. No available/usable evidence gives confidence0. Overall resultConfidence retains the literal v2 categoryWeight-weighted mean of pre-coverage categoryResultConfidence over categories not INSUFFICIENT_DATA.

Daily source confidence uses exactly four nominal samples06:00/12:00/18:00/23:00. Each sample confidence is in[0,1]; unavailable samples contribute0. sourceDailyConfidence=sum(all four sampleConfidence)/4. Signal aggregation still excludes unavailable samples. Both sources available: dailyResultConfidence=0.50*sajuDailyConfidence+0.50*astrologyDailyConfidence; one available: use its confidence; neither:0. Use the same source availability as signal aggregation. Weekly/Monthly confidence is the arithmetic mean of valid Daily confidence; Yearly is the mean of valid Monthly confidence; empty inputs give0. Missing lifetime retains INSUFFICIENT_DATA/confidence0.

---

# SC-02 Rule catalog and candidates

Saju has14 family records,9 enabled scoring families with16 variants/37 decimal mappings and5 context-only families. See saju-catalog-v1.md. Astrology109/228 remains unchanged. Mapping signs match decimal signedValues. Daily C21-R contains134 enabled rules/285 mappings; catalog blockers NONE. Daily INTERNAL sample identity is defined in daily-catalog-v1.md; Lifetime/public identity remains unchanged.

Group candidates by ruleId+subject+category+period. Mean signedValue and numeric rawValue over present candidates. Non-numeric rawValue becomes null and metadata.candidates preserves present candidate scalar values. The user explicitly approved adding candidates to the allowlist. Availability ratio is present/total, combined with candidateAgreement.

---

# SC-03 Outer cap

Only Uranus, Neptune and Pluto are outer planets. Jupiter/Saturn are excluded. An Astrology pair involving an outer planet is outer evidence. Individual outer preConfidenceWeight=min(0.50,baseWeight*ruleWeight*pairWeight). Multiply by featureConfidence once. Immediately before category scoring apply alpha=min(1,(0.15/0.85)*N/O) for O>0, where N/O sum non-outer/outer Astrology effective weights. O=0 requires no scale; N=0 retains outer context only. Then apply the single-feature guardrail. Before aggregation recheck the final outer-share invariant; shrinking non-outer weights can otherwise break the15% bound. If it is broken, return500 SCORE_CAP_INVARIANT_FAILED rather than an invalid score. Do not silently reapply a different weighting formula. Do not apply the0.50 ceiling separately to factors.

---

# SC-04 Feature and canonical identity

Feature uses ruleId; subject is PERSON_A, PERSON_B or PAIR (pair compatibility defaults to PAIR). rawValue is number|string|boolean|null, never array/object. Feature confidence wire name remains confidence. signedValue represents the registered or aggregated category signal.

Period forms: LIFETIME{type}; DAY{type,date,timezone}; WEEK{type,startDate,endDate,timezone}; MONTH{type,year,month,timezone}; YEAR{type,year,timezone}. Dates are Gregorian and timezone is IANA.

Metadata allowlist: planetA,planetB,aspect,orb,pillar,element,relation,candidateCount,availabilityRatio,sampleCount,referenceId,ruleVariant,candidates,orbCloseness,transformationStatus. transformationStatus is non-identity and only UNASSESSED is accepted. orbCloseness is optional numeric[0,1], active Astrology evidence only, non-identity, not a score/confidence multiplier or logging permission. candidates is a scalar array and is NOT identity-relevant. All other keys fail validation unless a later explicit decision amends this list. Identity-relevant subset: planetA,planetB,aspect,pillar,relation,referenceId,ruleVariant.

Canonical featureId input: ruleId,subject,category,period,source and the identity-relevant metadata subset. Exclude signedValue, confidence, input Birth Data, names, rawValue and other metadata. UTF-8, lexicographically sorted keys, no whitespace, NFC normalized strings, canonical JSON decimal numbers; preserve schema-present nulls. featureId='ft_'+first48 lowercase hex characters of SHA-256(canonical JSON). This is48 hex characters (24 digest bytes), not24 characters. Never use it for tracking. Canonical examples document bytes and expected digests; token serialization uses the same canonicalizer.

---

# SC-05 Daily sampling and DST

Nominal local samples are06:00,12:00,18:00,23:00 in targetTimezone. Evaluate each source per sample. Exclude unavailable samples; at least one sample makes a source available, zero makes it unavailable. Mean is arithmetic mean of valid signals. Peak has greatest absolute value; ties select the earlier nominal local sample time. sourceDailySignal=clamp(0.75*meanSignal+0.25*peakSignal,-1,1).

If nominal local time does not exist, use the first valid instant after that nominal local time in the timezone. If it maps to two instants, choose the earlier UTC instant. The former 'earlier offset' terminology is superseded. Pin timezoneDataVersion. Overall daily signal uses structurally eligible fixed source0.50/0.50 weights (M1-ELIGIBILITY-AWARE); runtime missing does not reweight the other source; delta=18*signal; clamp lifetime+delta to[0,100]. With no sources use lifetime/delta0/INSUFFICIENT_PERIOD_DATA.

---

# SC-06 Dates, periods and statuses

Gregorian supported date range1900-01-01..2099-12-31, narrowed by verified provider coverage. Outside verified coverage return UNSUPPORTED_DATE; never silently fallback. daily-range is inclusive, max31 calendar days. start>end gives422 INVALID_DATE_RANGE; length>31 gives422 DATE_RANGE_TOO_LARGE. Weekly uses ISO Monday through Sunday in targetTimezone.

Lifetime null propagates null scores and INSUFFICIENT_DATA to Daily/Weekly/Monthly/Yearly. Empty valid Daily set gives score null/status INSUFFICIENT_DATA/confidence0. A valid Daily has non-null dailyScore, no Core calculation error, non-null lifetimeScore, period status other than INSUFFICIENT_PERIOD_DATA, and a date within verified supported coverage. dailyPeriodStatus in v3 denotes the existing dailyStatus wire field; no duplicate field is introduced. Numeric lifetime fallback without period evidence can be displayed but is excluded from all period aggregates. Yearly aggregates valid Monthly results derived from these valid days; empty/null Monthly results are excluded.

Daily status uses dailyDelta: <=-5 VERY_LOW; (-5,-2] LOW; (-2,2) STABLE; [2,5) GOOD; >=5 VERY_GOOD. Missing period data overrides with INSUFFICIENT_PERIOD_DATA; missing lifetime overrides with INSUFFICIENT_DATA. No old absolute daily-score bands.

Weekly score is the arithmetic mean of valid full-precision Daily scores in the ISO week. Weekly/Monthly/Yearly score status uses lifetime canonical-score bands: [0,45) CAUTION; [45,60) BALANCED; [60,75) GOOD; [75,85) VERY_GOOD; [85,100] EXCELLENT; null INSUFFICIENT_DATA. Daily delta status is unchanged. periodDelta=periodScore-lifetimeScore using internal full precision. If either is null, periodDelta=null, trendStatus=INSUFFICIENT_DATA, trendDirection=UNKNOWN. Otherwise abs(delta)<3 gives STABLE, [3,8) NOTICEABLE, >=8 SIGNIFICANT. Direction is STABLE for abs(delta)<3, UP for delta>=3, DOWN for delta<=-3. These three fields are required on Weekly/Monthly/Yearly responses. Trend uses internal delta; it is not derived from rounded API score differences. OLS slope is secondary points/day context only and never determines trendStatus.

Selection maxima: Weekly best/caution2 each, Monthly5 each, Yearly3 each. Best score DESC, caution score ASC, then resultConfidence DESC and chronological ASC. Compare full-precision internal scores before API rounding. Monthly means full-precision valid Daily scores; Yearly means full-precision valid Monthly scores; volatility is population standard deviation. Round only API scores to four decimals HALF_UP; status uses the canonical score before display formatting.

---

# SC-07 Daily category, Actions and guardrail

Category daily signal uses category-specific evidence with the same source/sample pipeline. categoryDailyDelta=18*categoryDailySignal; categoryDailyScore=clamp(lifetimeCategoryScore+categoryDailyDelta,0,100). No computable category period data gives delta0 and periodStatus=INSUFFICIENT_PERIOD_DATA. Do not copy overall signal into categories.

Action is a separate deterministic recommendation layer; Action adjustment=0 and it never changes LOVE SCORE. Every Action has at least one evidenceRef to an actual Structured Feature featureId. Feature.confidence is the wire name of featureConfidence. Exclude referenced Features with preConfidenceWeight<=0. actionConfidence=sum(preConfidenceWeight*featureConfidence)/sum(preConfidenceWeight) over the remaining referenced Features; none means no Action. Zero confidence alone does not exclude a positive-weight Action reference. AI may explain the deterministic Action but cannot change its confidence. Existing category weight tables are retained only as recommendation reference, never score modifiers. Complexity is optional context only, not a numeric modifier.

Weekly slope is OLS sum((x-meanX)*(y-meanY))/sum((x-meanX)^2), units points/day, x is the calendar-day offset in the week (gaps are retained); fewer than2 valid days gives null.

Lifetime-only SC-07 v2 freezes W0=sum(pre-guardrail usable effectiveWeight) after individual caps/confidence/outer cap. impact=50*effectiveWeight*abs(signedValue)/W0; above20 use adjustedWeight=min(effectiveWeight,0.4*W0/abs(signedValue)); zero signal retains weight. Raw=50+50*sum(adjustedWeight*signedValue)/W0, never adjustedWeight sum. No search, passes, exclusion or confidence/coverage change. Legacy SCORE_GUARDRAIL_UNSATISFIED is deprecated. See guardrail-v2.md.

---

# SC-08 Public API wire

Person required fields: birthDate,birthLocationId. birthTime is optional, default null, HH:MM24-hour without seconds. Remove gender, birthTimeKnown, calendarType,isLeapMonth,locationId,raw coordinates and birth timezone from Public calculation Person. Public birth dates are Gregorian1900..2099. Resolve birthLocationId through the server reference; never send labels/nicknames.

targetTimezone is REQUIRED on every Core calculation request. There is no server calculation fallback. Asia/Tokyo is the service/UI prefill default only. Optional locale defaults to ko-KR; supported ko-KR,ja-JP,en-US; unsupported gives422 UNSUPPORTED_LOCALE. Retain relationshipType as required from the current pair request schema; no base score bias.

Core POST paths are compatibility/calculate and fortune/daily,daily-range,weekly,monthly,yearly under /wp-json/love-fortune/v1. AI is separate POST interpretation/generate. Public read-only reference paths: /locations,/locations/{locationId},/service-config,/celebrities,/celebrities/{celebrityId}. Remove previous /locations/search,/config,/version aliases from this public list. Celebrity DTO fields: celebrityId,displayName,displayNameLocalized,publicBirthPrecision,availableFeatures,imageReference,sourceSummary; no raw birth profile. Retain the existing celebrity calculation operation as POST /celebrities/{celebrityId}/compatibility. Request uses personA,relationshipType,targetTimezone and optional locale; personB is resolved internally from the approved celebrity reference. Response is the same compatibility contract. No public raw celebrity birth profile is needed.

All public calculation request objects reject unknown properties. Admin namespace /wp-json/love-fortune/v1/admin; authenticate WordPress admin, capability and nonce for EVERY Admin endpoint. Mutations use POST/PUT/DELETE, never GET. No arbitrary SQL/code execution or plaintext wp_options secrets. Detailed methods, DTOs and existing capability mappings are in api-details-v2.md and schemas/admin.schema.json.

---

# SC-09 Signed context and AI execution

LF signed-context format is base64url(headerCanonicalJson).base64url(payloadCanonicalJson).base64url(signature), with RFC4648 URL-safe alphabet and no '=' padding. Header: alg=HS256,typ=LFIC,kid required,v=1. Signing input is ASCII(firstSegment+'.'+secondSegment). Signature is HMAC-SHA256 over those bytes. This does not require a JWT library. Use constant-time signature comparison. Current and immediately previous key verify; issue only with current kid. Secrets remain in environment/secret store, never wp_options.

Payload required: contextVersion,purpose,locale,issuedAt,expiresAt,engineVersions,scoreVersion,configVersion,result,evidence. purpose=INTERPRETATION; locale must equal the AI request's locale. Retain the existing ISO8601 UTC timestamp wire representation; compare parsed instants. expiresAt-issuedAt=300 seconds. now>=expiresAt:422 INTERPRETATION_CONTEXT_EXPIRED; signature failure:422 INVALID_INTERPRETATION_CONTEXT; purpose mismatch:422 INVALID_INTERPRETATION_PURPOSE; locale mismatch:422 INTERPRETATION_LOCALE_MISMATCH. No token DB/replay store, no strict one-time claim. TTL/rate/payload limits constrain abuse. No raw Birth/labels in payload.

Provider total budget8 seconds, call timeout6 seconds. At most one network/5xx retry and one schema repair, all inside8 seconds. Retry/repair cannot reset the budget. On AI OFF/timeout/outage/schema/evidence/safety failure, return200 if deterministic fallback can be produced. Metadata interpretationMode=AI|FALLBACK. Only inability to produce interpretation/fallback returns503 INTERPRETATION_UNAVAILABLE. Core remains valid regardless.

Deterministic comparison excludes requestId,generatedAt,issuedAt,expiresAt,signedInterpretationContext; exact-compare the remaining canonical calculation payload. Interpretation prose is not a Core deterministic output. AI outputs summary,strengths,challenges,advice with evidenceRefs; no numeric score fields, no raw prompt/output logging or personal cache.


Current Daily authority: [Daily C21-R](daily-catalog-v1.md), superseding earlier Daily signal denominators/status boundaries. Structural eligibility is distinct from runtime unavailable and computed-no-event. STABILITY uses only Saju in its category source denominator; overall uses fixed .50/.50. Source internal signal uses fixed eligible category denominator. Confidence is unchanged. Daily SC-07: NOT_APPLIED_TO_DAILY_V1. Internal sample identity/candidate grouping uses sampleRef; public Feature and signed wire unchanged.
