# LOVE FORTUNE
# 07_ADMIN_SPEC.md

Version: 1.4.0
Status: CONTRACT FROZEN / IMPLEMENTATION READINESS SEPARATE
Document Type: Admin / Operations Specification

Current V1 authority: [Zodiac V1](contracts/zodiac-catalog-v1.md). SAJU + ZODIAC are active; planetary Astrology and ephemeris are ADVANCED / DEFERRED. This scope supersedes prior source/weight/version gates; preserved Advanced sections do not authorize V1 execution.

Decision Authority: [Final Decision v3](contracts/final-decision-v3.md), then [v2](contracts/final-decision-v2.md) and [approved clarifications](contracts/clarification-v2.md)
Freeze Gate: [Freeze validation and readiness](contracts/README.md)

---

# 1. 목적

LOVE FORTUNE Admin은 사용자 CRM이 아니라
서비스/엔진/AI/콘텐츠/운영 상태를 관리한다.

일반 사용자의 Birth Profile, Partner Profile, Relationship,
Compatibility History는 서버에 존재하지 않는다.

---

# 2. Admin Menu

```text
LOVE FORTUNE
├─ Dashboard
├─ Analytics
├─ Calculation Monitor
├─ Celebrity
├─ Contents
├─ Notifications
├─ AI Management
├─ Engine Management
├─ Score Management
├─ Location Data
├─ Cache
├─ Error Logs
├─ System Status
├─ Privacy Monitor
├─ Audit Logs
└─ Settings
```

---

# 3. Roles / Capabilities

권장 Roles:

```text
LF Administrator
LF Operator
LF Content Manager
LF Technical Manager
```

권장 Capabilities:

```text
lf_view_dashboard
lf_view_analytics
lf_view_calculations
lf_manage_celebrities
lf_manage_contents
lf_manage_notifications
lf_manage_ai
lf_manage_engine
lf_manage_score
lf_manage_locations
lf_manage_cache
lf_view_errors
lf_manage_errors
lf_view_system
lf_manage_settings
lf_view_privacy
lf_view_audit
```

---

# 4. Dashboard

Admin methods/DTOs and exact existing capability assignments: [v2 API details](contracts/api-details-v2.md).

주요 KPI:

```text
오늘 계산 요청
오늘 계산 성공
오늘 계산 실패
Daily Fortune 요청
Celebrity Compatibility 요청
AI 생성 성공/실패
평균 Calculation Time
평균 AI Response Time
```

Raw Birth Data는 표시하지 않는다.

---

# 5. Analytics

가능한 지표:

```text
Main Page Views
Compatibility Starts
Compatibility Requests
Compatibility Success/Failure
Daily/Weekly/Monthly/Yearly Requests
Celebrity Views
Result → Today CTR
Today → 7-day Trend CTR
```

Birth Date/Time/Location 기반 segment는 만들지 않는다.

Stable anonymous ID는 기본 사용하지 않는다.

---

# 6. Calculation Monitor

모니터링 단위:

```text
Request
Version
Error
Performance
Aggregate Metric
```

사용자 단위가 아니다.

표시:

```text
Total Requests
Success
Partial
Failure
Timeout
Average
P50
P95
P99
```

---

# 7. Sanitized Calculation Log

Allowlisted operational metadata only: per-request requestId, endpoint, HTTP status, duration, errorCode and non-personal version identifiers. No Birth Data, coordinates, names/nicknames, request/response bodies, signed context or raw AI prompt/output. No stable anonymous identifier or fingerprint. See 04 retention and SC-10 for exact audit allowlists.

---

# 8. Engine Management

관리 Type:

```text
FORTUNE_ENGINE
SAJU_RULE
ZODIAC_RULE
ZODIAC_DATE_RANGE
```

Status:

```text
DRAFT
TESTING
ACTIVE
DEPRECATED
DISABLED
```

Version activation/rollback은 Audit 대상.

Code/Config에 실제로 존재하지 않는 Version은 활성화할 수 없다.

---

# 9. Score Management

표시:

- category weights
- source weights
- rule weights
- planet weights
- aspect weights
- evidence
- daily rules
- status thresholds
- action weights

Production에서 숫자를 직접 바꿔 즉시 formula를 변경하지 않는다.

권장:

```text
Config
↓
Validation
↓
Test
↓
Version
↓
Admin Activation
```

---

# 10. Golden Dataset

Use synthetic, licensed or explicitly approved public-reference fixtures with documented provenance/permission. Never turn real-user input into Golden fixtures. Same input/provider/software/data/config/version requires exact deterministic output where possible. +/-0.01 is not a global acceptance rule; use a named floating regression tolerance only for explicitly justified runtime/provider differences. Protect existing expected results from automatic regeneration to match code.

---

# 11. AI Management

표시:

```text
Requests
Success
Failure
Timeout
Average Response
Token Usage
Estimated Cost
Provider
Model
Prompt Version
```

API Key는 평문 표시 금지.

Prompt는 versioned draft/test/review/activate flow를 사용한다.

---

# 12. Celebrity Management

필드:

```text
Display Name
Slug
Category
Country
Image
Birth Date
Birth Time
Birth Time Known
Birth Location
Timezone
Latitude
Longitude
Description
Source
Confidence
Published
Sort Order
Profile Version
```

확인되지 않은 Birth Time은 추정하지 않는다.

---

# 13. Content

WordPress CPT/Posts/Pages 활용.

예:

```text
FAQ
Service Guide
Saju Guide
Astrology Guide
Love Content
Notice
Privacy
Terms
Disclaimer
```

---

# 14. Service Notices

Admin manages non-personal Service Notices such as maintenance and announcements. Exclude personalized Push, Web Push subscriptions and Birth Data-based notification targeting. Destructive notice operations require authenticated POST/DELETE with nonce and capability checks.

---

# 15. Location Data

관리:

```text
location_id
country_code
region
city
display_name
timezone_id
latitude
longitude
data_version
status
```

Historical timezone은 IANA 기반.

---

# 16. Cache

우선 관리 대상:

```text
EPHEMERIS
SOLAR_TERM
LOCATION
CONTENT
SAFE_CALCULATION_REFERENCE
```

User-derived calculation and AI caches are prohibited, not configurable opt-ins.

Raw Birth Data key/payload 금지.

---

# 17. Error Logs

Categories:

```text
API
VALIDATION
SAJU
ASTROLOGY
SCORE
DAILY
PERIOD
AI
LOCATION
CACHE
DATABASE
SYSTEM
```

Severity:

```text
INFO
WARNING
ERROR
CRITICAL
```

동일 Error는 aggregate view 제공.

---

# 18. Log Sanitization

Normative contract: [Final Decision v2 SC-10](contracts/runtime-contract-v2.md#sc-10-http-security-and-retention). Operational logs use the exact v2 allowlist; Admin mutation audit uses its separate allowlist. Sanitize before persistence and disable body capture throughout infrastructure. No Birth Data/raw prompt-output/context in backups.

---

# 19. System Status

Components:

```text
WordPress
PHP
MySQL
Plugin
REST API
Saju
Astrology
Score
Ephemeris
AI
Cache
Location
```

Status:

```text
NORMAL
DEGRADED
DOWN
UNKNOWN
```

---

# 20. Privacy Monitor

표시 예:

```text
Raw Birth Data Stored        0
Raw Calculation Payload      0
PII Log Detection            0
Expired Temporary Data       0
Privacy Scan                 NORMAL
```

개인정보 침해 가능성이 발견되면 CRITICAL로 표시.

---

# 21. Retention

Operations/application logs and backups<=30 days; Security/Admin audit and backups<=90 days; aggregates<=13 months; HMAC rate keys<=3600 seconds; Birth retention0. Expire backups. Normative contract: [Final Decision v2 SC-10](contracts/runtime-contract-v2.md#sc-10-http-security-and-retention).

---

# 22. Audit

기록:

```text
Engine Activation/Rollback
Score Activation/Rollback
Prompt Activation
Celebrity Birth Data Change
Celebrity Publish
Cache Clear
Settings Change
Privacy Cleanup
Role/Capability Change
```

---

# 23. Settings

```text
Service Name
Default Locale
Default Fortune Timezone
Supported Locales
Maintenance Mode
Disclaimer Version
Privacy Policy
Terms
Feature Flags
```

MC는 일반 toggle로 활성화할 수 없다.

---

# 24. Emergency Control

지원:

```text
Maintenance Mode
Disable AI
Disable Celebrity
Disable Period Fortune
Disable Calculation
```

AI가 DOWN이어도 deterministic core는 가능한 범위에서 유지.

---

# 25. Admin Test Console

지원:

```text
Saju Test
Astrology Test
Compatibility Test
Daily Test
Period Test
AI Test
```

Production user request를 가져오지 않는다.

Origin:

```text
USER
ADMIN_TEST
GOLDEN_TEST
SYSTEM_HEALTH
```

Admin/System test는 사용자 Analytics에서 제외.

---

# 26. 존재하지 않는 Admin 기능

```text
User Birth Profile Management
Relationship Management
Compatibility History
Daily User History
AI User History
Payment
Point
Attendance
Check-in
```

---

# 27. Admin REST

Admin methods/DTOs and exact existing capability assignments: [v2 API details](contracts/api-details-v2.md).

Namespace /wp-json/love-fortune/v1/admin. Every route requires authenticated WordPress admin, capability and nonce. Use POST/PUT/DELETE for mutations; never GET. Errors401 ADMIN_AUTH_REQUIRED,403 ADMIN_CAPABILITY_REQUIRED,403 ADMIN_NONCE_INVALID. No arbitrary SQL/code or plaintext wp_options secrets. Normative contract: [Final Decision v2 SC-08](contracts/runtime-contract-v2.md#sc-08-public-api-wire). Normative contract: [Final Decision v2 SC-10](contracts/runtime-contract-v2.md#sc-10-http-security-and-retention).

---

# 28. Security

Secrets belong in environment/secret management, never plaintext wp_options. Prohibit arbitrary SQL/PHP/code execution and unrestricted query consoles. Use prepared queries, least-privilege capabilities and non-personal audit logs. Production Admin MFA is recommended. Do not expose personal profile/history management.

---

# 29. Final Principle

Admin이 답할 수 있어야 하는 질문:

```text
서비스가 정상인가?
어떤 Version이 active인가?
계산 실패가 증가했는가?
응답속도가 느린가?
AI가 정상인가?
Score 분포가 바뀌었는가?
Privacy 문제가 없는가?
```

Admin이 답할 수 없어야 하는 질문:

```text
특정 사용자의 생년월일은?
누구와 궁합을 봤는가?
지난 30일 개인 LOVE SCORE는?
```


## Final v3 contract alignment

[Runtime contract](contracts/runtime-contract-v2.md) applies the approved v3 decisions: structural coverage is retained with zero usable confidence; Daily source confidence includes all four samples with missing=0; Weekly is the valid-Daily arithmetic mean; period fallback days are excluded; periodDelta and trend magnitude/direction are separate; Actions use weighted Feature.confidence and cannot alter scores. UI displays deterministic fields; AI cannot alter Action confidence. No storage or new engine rules are introduced. Contract Freeze is independent of BLOCKED_CATALOG (SCORING_RULE_CATALOG_APPROVAL) and ADVANCED-only BLOCKED_EXTERNAL (EPHEMERIS_PROVIDER); V1_EXTERNAL_BLOCKERS=NONE, epoch APPLIED.

END OF DOCUMENT

V1 version administration admits Zodiac rule/date versions; Astrology and ephemeris types remain Advanced-only and cannot activate through the V1 schema. No DB migration is introduced by this contract change.
