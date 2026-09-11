# LOVE FORTUNE
# 05_API_SPEC.md

Version: 3.2.0
Status: CONTRACT FROZEN / IMPLEMENTATION READINESS SEPARATE
Document Type: REST API Specification

Decision Authority: [Final Decision v3](contracts/final-decision-v3.md), then [v2](contracts/final-decision-v2.md) and [approved clarifications](contracts/clarification-v2.md)
Freeze Gate: [Freeze validation and readiness](contracts/README.md)

---

# 1. Base

/wp-json/love-fortune/v1. Core and AI are separate lifecycles. v2 supersedes earlier wire drafts; use contracts/openapi.yaml and schemas, with actual validation/conflicts in contracts/README.md.

---

# 2. Calculation Endpoints

POST /compatibility/calculate
POST /fortune/daily
POST /fortune/daily-range
POST /fortune/weekly
POST /fortune/monthly
POST /fortune/yearly

Separate AI endpoint: POST /interpretation/generate. Core must not call AI. Remove includeInterpretation from requests and interpretation from Core responses. Reject unknown top-level fields.

---

# 3. Public Reference Endpoints

GET /locations
GET /locations/{locationId}
GET /service-config
GET /celebrities
GET /celebrities/{celebrityId}

Read-only. Old /locations/search,/config,/version paths are not aliases. Normative contract: [Final Decision v2 SC-08](contracts/runtime-contract-v2.md#sc-08-public-api-wire).

---

# 4. Admin Endpoints

Admin methods/DTOs and exact existing capability assignments: [v2 API details](contracts/api-details-v2.md).

```text
/admin/versions
/admin/celebrities
/admin/calculation/test
```

상세 Admin API는 `07_ADMIN_SPEC.md`를 따른다.

---

# 5. Person Request

Required birthDate,birthLocationId. Optional birthTime defaults null, HH:MM24-hour, no seconds. Gregorian date1900-01-01..2099-12-31 with narrower verified provider coverage. Reject gender,birthTimeKnown,calendarType,isLeapMonth,locationId,name,nickname,coordinates and client birth timezone. Normative contract: [Final Decision v2 SC-08](contracts/runtime-contract-v2.md#sc-08-public-api-wire).

---

# 6. Birth Time Unknown

Omit birthTime or send null. No birthTimeKnown flag, invented noon or midnight. Candidate rules use v2 SC-01/SC-02.

---

# 7. Relationship Type

```text
COUPLE
MARRIED
DATING
CRUSH
FRIEND
UNKNOWN
```

Base Score를 변경하지 않는다.

---

# 8. Locale

Optional locale defaults ko-KR. Supported ko-KR,ja-JP,en-US. Unsupported gives422 UNSUPPORTED_LOCALE.

---

# 9. Target Timezone

REQUIRED on every Core calculation request. Validate IANA timezone. No service fallback during calculation. Asia/Tokyo is UI/service-config default only. Browser may prefill; send selected timezone explicitly.

---

# 10. Compatibility Request

personA/personB use section5; retain required relationshipType. targetTimezone required; locale optional with ko-KR default. Reject every unknown field, including includeInterpretation. See compatibility-request.schema.json.

---

# 11. Versions / Deterministic Projection

Core meta versions: serviceVersion,apiVersion,sajuEngineVersion,astrologyEngineVersion,scoreVersion,configVersion,locationReferenceVersion,timezoneDataVersion,ephemerisProviderVersion,ephemerisDataVersion. No AI dependency in Core. Exact comparison excludes requestId,generatedAt,issuedAt,expiresAt,signedInterpretationContext. Normative contract: [Final Decision v2 SC-09](contracts/runtime-contract-v2.md#sc-09-signed-context-and-ai-execution).

---

# 12. Compatibility Response

Core fields: meta,overallScore,status,resultConfidence,coverage,categories,features,warnings and optional signedInterpretationContext. No interpretation or raw input echoes. Features use ruleId and v2 identity/period wire. No usable evidence gives category50/confidence0/INSUFFICIENT_DATA with computed coverage retained; all insufficient gives null overall. Period responses require periodDelta, trendStatus and trendDirection per v3. Feature.confidence supplies weighted Action confidence, never Feature.resultConfidence. See schemas and runtime contract.

---

# 13. Warnings

예:

```text
BIRTH_TIME_UNKNOWN
MOON_POSITION_UNCERTAIN
ASC_UNAVAILABLE
HOUSE_UNAVAILABLE
HOUSE_FALLBACK_POLAR
LOCATION_APPROXIMATED
TIMEZONE_RESOLVED
LIMITED_ASTROLOGY_CONTEXT
LIMITED_SAJU_HOUR_ANALYSIS
```

---

# 14. Daily

POST /fortune/daily. date is Gregorian targetTimezone date. dailyStatus derives from dailyDelta, never absolute score. Lifetime null propagates null/INSUFFICIENT_DATA. Missing sources give lifetime/delta0/INSUFFICIENT_PERIOD_DATA. Normative contract: [Final Decision v2 SC-06](contracts/runtime-contract-v2.md#sc-06-dates-periods-and-statuses).

---

# 15. Daily Range

POST /fortune/daily-range. Inclusive startDate/endDate, max31 calendar days. Reversed gives422 INVALID_DATE_RANGE; >31 gives422 DATE_RANGE_TOO_LARGE. Stateless recomputation, no stored history. Normative contract: [Final Decision v2 SC-06](contracts/runtime-contract-v2.md#sc-06-dates-periods-and-statuses).

---

# 16. Weekly

POST /fortune/weekly; weekStartDate is ISO Monday. targetTimezone required. Use Weekly schema branch, valid Daily aggregate, OLS slope, best/caution max2. Normative contract: [Final Decision v2 SC-06](contracts/runtime-contract-v2.md#sc-06-dates-periods-and-statuses).

---

# 17. Monthly

POST /fortune/monthly; year/month targetTimezone period. Mean full-precision valid Daily scores, population standard deviation, lifetime status bands, best/caution max5. Normative contract: [Final Decision v2 SC-06](contracts/runtime-contract-v2.md#sc-06-dates-periods-and-statuses).

---

# 18. Yearly

POST /fortune/yearly; year1900..2099, targetTimezone required. Mean full-precision Monthly scores, population standard deviation, lifetime status bands, best/caution max3. Normative contract: [Final Decision v2 SC-06](contracts/runtime-contract-v2.md#sc-06-dates-periods-and-statuses).

---

# 19. Celebrity

GET /celebrities and GET /celebrities/{celebrityId} expose celebrityId,displayName,displayNameLocalized,publicBirthPrecision,availableFeatures,imageReference,sourceSummary, never raw birth profile. Retain POST /celebrities/{celebrityId}/compatibility: personA is the user input, personB is the internally resolved approved celebrity reference. It uses celebrity-compatibility-request.schema.json and the standard compatibility response.

---

# 20. Location / Service Config

GET /locations, GET /locations/{locationId}, GET /service-config. Reference DTO uses locationId/displayName/timezoneId/latitude/longitude; calculation accepts birthLocationId only. Default timezone Asia/Tokyo and default locale ko-KR are public configuration. Unknown reference404; invalid timezone422. No Birth Data in search queries.

---

# 21. Error Contract

Envelope {error:{code,messageKey,field optional,details:{}}}; no stack/SQL/secrets/raw provider output. HTTP400 malformed,404 reference,413 size,415 media/encoding,422 semantics,429 rate,500 internal. Interpretation fallback failure503 INTERPRETATION_UNAVAILABLE. Exact v2 codes and Admin401/403 follow Normative contract: [Final Decision v2 SC-09](contracts/runtime-contract-v2.md#sc-09-signed-context-and-ai-execution). Normative contract: [Final Decision v2 SC-10](contracts/runtime-contract-v2.md#sc-10-http-security-and-retention).

---

# 22. Content Type / Payload

Core32768 bytes; AI65536 bytes. Any Content-Encoding gives415 UNSUPPORTED_CONTENT_ENCODING. Accept application/json and application/json; charset=utf-8 (charset case-insensitive); other parameters415 UNSUPPORTED_MEDIA_TYPE. Oversize413. Unknown properties rejected. Normative contract: [Final Decision v2 SC-10](contracts/runtime-contract-v2.md#sc-10-http-security-and-retention).

---

# 23. Input Validation

검증:

- type
- max length
- enum
- date format
- time format
- timezone
- server reference coordinate range; reject Public input coordinates
- Gregorian leap-day validity; reject lunar/calendar flags
- nested depth
- payload size

---

# 24. Debug

`includeDebug`는 client가 강제로 활성화할 수 없다.

Server capability/admin permission이 있어야 한다.

---

# 25. Privacy

Birth Data:

```text
POST JSON Body only
```

금지:

- query string
- URL
- path
- analytics
- raw logs

---

# 26. Cache

Core, AI Interpretation and signed context responses require Cache-Control: no-store and no CDN shared cache. No user-derived calculation/AI cache. Only non-personal reference/rule/config/calendar/ephemeris data may be cached.

---

# 27. Logging

Admin methods/DTOs and exact existing capability assignments: [v2 API details](contracts/api-details-v2.md).

Operational allowlist and30-day retention, Admin mutation audit allowlist and90-day retention, backup limits and prohibited fields follow Normative contract: [Final Decision v2 SC-10](contracts/runtime-contract-v2.md#sc-10-http-security-and-retention). No request-response body/context/prompt/raw AI logging.

---

# 28. Interpretation / Signed Context

POST /interpretation/generate after Core display. LFIC three-segment unpadded base64url HMAC-SHA256 format, current/previous kid verification, current signing, purpose INTERPRETATION, locale binding,300-second TTL. No raw Birth or labels. Provider6-second call/8-second total budget, max1 network/5xx retry and max1 schema repair within budget. Valid fallback200 with meta.interpretationMode=FALLBACK; failure to generate any fallback503. Normative contract: [Final Decision v2 SC-09](contracts/runtime-contract-v2.md#sc-09-signed-context-and-ai-execution).

---

# 29. Admin Authentication

EVERY Admin endpoint requires WordPress authentication, capability and nonce. Errors401 ADMIN_AUTH_REQUIRED,403 ADMIN_CAPABILITY_REQUIRED,403 ADMIN_NONCE_INVALID. Mutations POST/PUT/DELETE; no GET mutation. Normative contract: [Final Decision v2 SC-10](contracts/runtime-contract-v2.md#sc-10-http-security-and-retention).

---

# 30. General User Authentication

Calculation endpoint는 일반 사용자 account를 요구하지 않는다.

---

# 31. Abuse Prevention

Fixed600-second window: Core60, AI10 per source. Trusted allowlisted proxy verified client IP else remote address. HMAC temporary infrastructure secret+normalized source IP as key; never raw IP, TTL<=3600 seconds. 429 Retry-After integer seconds to window end. Normative contract: [Final Decision v2 SC-10](contracts/runtime-contract-v2.md#sc-10-http-security-and-retention).

---

# 32. CORS

허용 Origin을 제한한다.

CORS는 CSRF 보호를 대체하지 않는다.

---

# 33. Determinism

Exclude requestId,generatedAt,issuedAt,expiresAt,signedInterpretationContext from exact canonical Core comparison. Same provider/software/data/config/version/input requires identical remaining output. AI prose is separate. No global +/-0.01 allowance. Normative contract: [Final Decision v2 SC-09](contracts/runtime-contract-v2.md#sc-09-signed-context-and-ai-execution).

---

# 34. API에서 존재하지 않는 Resources

```text
/users
/register
/profile
/relationships
/history
/payments
/points
/attendance
```

일반 사용자 resource API를 만들지 않는다.

---

# 35. Final Principle

Birth Data는:

```text
Calculation Input
```

이며:

```text
Persistent Resource
```

가 아니다.

END OF DOCUMENT


Current Daily authority: [Daily Catalog v1 C21-R](contracts/daily-catalog-v1.md). Daily134/285 APPLIED/READY; S2 thresholds +/-2 / +/-5; M1-ELIGIBILITY-AWARE signal aggregation. SC-07 applies to Lifetime only. Score Engine catalog readiness READY; catalog blockers NONE; Production BLOCKED_EXTERNAL (SAJU_DAY_PILLAR_EPOCH, EPHEMERIS_PROVIDER). Public/signed wire and privacy remain unchanged.
