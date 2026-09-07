# LOVE FORTUNE
# 07_ADMIN_SPEC.md

Version: 1.0.0
Status: FINAL
Document Type: Admin / Operations Specification

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

허용:

```json
{
  "request_id": "req_xxx",
  "type": "COMPATIBILITY",
  "status": "SUCCESS",
  "duration_ms": 842,
  "fortune_engine_version": "2.0.0",
  "score_version": "1.0.0"
}
```

금지:

```text
name
nickname
birthDate
birthTime
latitude
longitude
partner information
raw request body
```

---

# 8. Engine Management

관리 Type:

```text
FORTUNE_ENGINE
SAJU_RULE
ASTROLOGY_RULE
EPHEMERIS_PROVIDER
EPHEMERIS_DATA
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

Admin Test Console에서 synthetic/golden fixtures를 사용할 수 있다.

실사용자 payload를 가져오지 않는다.

Version activation 전에 Golden Test PASS를 gate로 사용할 수 있다.

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

# 14. Notifications

현재 일반 사용자 account/email/push token 저장을 전제로 하지 않는다.

Service Notice는 가능:

```text
INFO
WARNING
MAINTENANCE
EMERGENCY
```

개인화 Push/Email은 별도 Privacy 설계가 필요하다.

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

개인 궁합 전체 cache는 기본 OFF.

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

제거:

```text
birthDate
birthTime
name
nickname
latitude
longitude
location
partner data
Authorization
API Key
Cookie
```

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

관리:

```text
Calculation Log Retention
Error Log Retention
Audit Log Retention
Temporary Cache TTL
Analytics Retention
```

Raw Birth Data Retention:

```text
NOT ALLOWED
```

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

Base:

```text
/wp-json/love-fortune/v1/admin/
```

모든 endpoint:

- authentication
- capability
- nonce
- input validation

---

# 28. Security

- least privilege
- output escaping
- CSRF protection
- no secrets in UI
- no arbitrary SQL
- destructive action confirmation
- PROD stronger confirmation

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

END OF DOCUMENT
