# LOVE FORTUNE
# 05_API_SPEC.md

Version: 2.0.0
Status: FINAL
Document Type: REST API Specification

---

# 1. Base

```text
/wp-json/love-fortune/v1/
```

일반 계산 API는 Stateless다.

---

# 2. Calculation Endpoints

```text
POST /compatibility/calculate
POST /fortune/daily
POST /fortune/daily-range
POST /fortune/weekly
POST /fortune/monthly
POST /fortune/yearly
```

---

# 3. Public Reference Endpoints

```text
GET /celebrities
GET /celebrities/{id}
POST /celebrities/{id}/compatibility
GET /locations/search
GET /config
GET /version   # optional
```

---

# 4. Admin Endpoints

```text
/admin/versions
/admin/celebrities
/admin/calculation/test
```

상세 Admin API는 `07_ADMIN_SPEC.md`를 따른다.

---

# 5. Person Request

```json
{
  "birthDate": "1990-05-10",
  "birthTime": "14:30",
  "birthTimeKnown": true,
  "calendarType": "SOLAR",
  "isLeapMonth": false,
  "birthLocation": {
    "locationId": "KR-SEOUL",
    "countryCode": "KR",
    "region": "Seoul",
    "city": "Seoul",
    "timezoneId": "Asia/Seoul",
    "latitude": 37.5665,
    "longitude": 126.978
  },
  "gender": "UNSPECIFIED"
}
```

Nickname/display label은 가능하면 client-only로 유지한다.

---

# 6. Birth Time Unknown

```json
{
  "birthTime": null,
  "birthTimeKnown": false
}
```

임의 12:00을 넣지 않는다.

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

```text
ko
ja
en
```

---

# 9. Target Timezone

IANA timezone 사용.

예:

```text
Asia/Seoul
Asia/Tokyo
America/New_York
```

Fixed UTC+9만으로 처리하지 않는다.

---

# 10. Compatibility Request

```json
{
  "personA": {},
  "personB": {},
  "relationshipType": "DATING",
  "targetTimezone": "Asia/Seoul",
  "locale": "ko",
  "includeInterpretation": true
}
```

---

# 11. Response Metadata

```json
{
  "meta": {
    "requestId": "req_xxx",
    "versions": {
      "fortuneEngine": "2.0.0",
      "sajuRule": "1.0.0",
      "astrologyRule": "1.0.0",
      "score": "1.0.0",
      "ephemerisProvider": "...",
      "ephemerisData": "..."
    }
  }
}
```

Provider와 ephemeris data/kernel version은 분리한다.

---

# 12. Compatibility Response

```json
{
  "loveScore": {
    "raw": 78.4211,
    "display": 78,
    "status": "VERY_GOOD",
    "confidence": 0.91,
    "coverage": 0.87
  },
  "categories": {},
  "highlights": {
    "positive": [],
    "negative": []
  },
  "context": {},
  "interpretation": {}
}
```

Birth data를 echo하지 않는다.

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

```text
POST /fortune/daily
```

Response:

- lifetimeScore
- dailyScore
- yesterday comparison
- categories
- actions
- highlights
- interpretation
- warnings
- versions

---

# 15. Daily Range

```text
POST /fortune/daily-range
```

목적:

최근 7일 흐름 등.

권장 최대:

```text
31 days
```

한 요청 내에서 natal/lifetime 계산은 재사용한다.

---

# 16. Weekly

```text
POST /fortune/weekly
```

Monday–Sunday.

---

# 17. Monthly

```text
POST /fortune/monthly
```

Calendar month.

---

# 18. Yearly

```text
POST /fortune/yearly
```

Gregorian UI year.
Saju annual energy는 입춘 전환 규칙 사용.

---

# 19. Celebrity

```text
GET /celebrities
GET /celebrities/{id}
POST /celebrities/{id}/compatibility
```

Celebrity는 server reference data,
user input은 transient.

---

# 20. Location Search

```text
GET /locations/search?q=Tokyo
```

Location query는 birth payload와 분리한다.

결과:

```json
{
  "locationId": "...",
  "displayName": "...",
  "timezoneId": "...",
  "latitude": 0,
  "longitude": 0
}
```

locationId와 raw coordinate가 동시에 오면
서버는 precedence/consistency validation을 수행해야 한다.

---

# 21. Error Contract

```json
{
  "error": {
    "code": "INVALID_BIRTH_DATE",
    "messageKey": "error.invalid_birth_date",
    "field": "personA.birthDate",
    "details": null
  }
}
```

Internal exception/stack trace를 반환하지 않는다.

---

# 22. Content Type

Calculation request:

```text
Content-Type: application/json
```

---

# 23. Input Validation

검증:

- type
- max length
- enum
- date format
- time format
- timezone
- coordinate range
- lunar/leap consistency
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

Personal calculation:

```http
Cache-Control: no-store
```

Public reference GET은 적절한 cache 사용 가능.

---

# 27. Logging

허용:

```text
request_id
endpoint
status
duration_ms
version
error_code
```

금지:

```text
raw request body
birthDate
birthTime
birthLocation
person payload
```

---

# 28. AI

AI에는 structured result만 전달하는 것을 기본으로 한다.

AI 실패 시:

```text
CORE SUCCESS
AI FAILED
```

가능.

Response Warning:

```text
AI_INTERPRETATION_UNAVAILABLE
```

---

# 29. Admin Authentication

Admin route:

- WordPress authentication
- nonce
- capability check

---

# 30. General User Authentication

Calculation endpoint는 일반 사용자 account를 요구하지 않는다.

---

# 31. Abuse Prevention

지원:

- rate limiting
- request size limit
- bot detection
- optional CAPTCHA
- WAF

Stable anonymous user ID는 기본적으로 만들지 않는다.

---

# 32. CORS

허용 Origin을 제한한다.

CORS는 CSRF 보호를 대체하지 않는다.

---

# 33. Determinism

Calculation result의 동일성 조건에는 최소 다음이 포함된다.

```text
input
target date
target timezone
engine/rule/score versions
ephemeris provider/data version
```

AI wording은 deterministic requirement 대상이 아니다.

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
