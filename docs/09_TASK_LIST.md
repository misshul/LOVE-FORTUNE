# LOVE FORTUNE
# 09_TASK_LIST.md

Version: 1.0.0
Status: FINAL
Document Type: Development Task / Implementation Plan

---

# 1. 목적

01~08 SPEC을 실제 개발 작업 순서로 변환한다.

본 문서는 새로운 사양을 만들지 않는다.

---

# 2. 전체 순서

```text
T00 Project Bootstrap
T01 WordPress Core Plugin
T02 Configuration / Version System
T03 Database / Migration
T04 Location / Timezone
T05 Calendar / Saju Foundation
T06 Saju Engine
T07 Ephemeris Provider
T08 Astrology Engine
T09 Compatibility Feature Engine
T10 LOVE SCORE Engine
T11 Daily Fortune Engine
T12 Weekly / Monthly / Yearly Engine
T13 Reading Orchestrator
T14 REST API
T15 Browser Privacy Storage
T16 User Input UI
T17 Compatibility Result UI
T18 Daily Fortune UI
T19 Period Fortune UI
T20 Celebrity Compatibility
T21 AI Interpretation
T22 Sharing
T23 Admin
T24 Analytics / Observability
T25 Security / Privacy
T26 Automated Testing
T27 Performance
T28 Deployment
T29 Production Verification
```

---

# 3. T00 Project Bootstrap

- [ ] Git
- [ ] DEV/STAG/PROD
- [ ] WordPress
- [ ] PHP/MySQL version
- [ ] Composer
- [ ] Node build
- [ ] env/secrets
- [ ] lint/static analysis
- [ ] PHPUnit
- [ ] CI

완료: WordPress 실행, plugin 활성화, tests/CI 실행 가능.

---

# 4. T01 love-fortune-core

권장 구조:

```text
src/
  Api/
  Application/
  Domain/
  Engine/
    Saju/
    Astrology/
    Compatibility/
    Score/
    Period/
  AI/
  Admin/
  Database/
  Location/
  Privacy/
  Support/
config/
prompts/
tests/
```

- [ ] bootstrap
- [ ] autoload
- [ ] DI
- [ ] config loader
- [ ] route loader
- [ ] admin loader
- [ ] migration runner
- [ ] engine registry
- [ ] error handler
- [ ] version registry

---

# 5. T02 Version System

관리:

```text
engine
saju rule
astrology rule
score
period rule
ephemeris provider
ephemeris data
AI prompt
AI interpretation rule
```

동일 Input + 동일 Version = 동일 계산.

---

# 6. T03 Database

- [ ] engine versions
- [ ] celebrities
- [ ] location/reference
- [ ] safe shared cache
- [ ] audit
- [ ] migration

User Birth/Relationship/History table 생성 금지.

---

# 7. T04 Location / Timezone

- [ ] stable location ID
- [ ] country/region/city
- [ ] lat/lon
- [ ] IANA timezone
- [ ] historical timezone
- [ ] search API
- [ ] cache

---

# 8. T05 Calendar / Saju Foundation

- [ ] solar/lunar
- [ ] leap month
- [ ] solar terms
- [ ] 12 Jie
- [ ] Julian Day
- [ ] sexagenary cycle
- [ ] golden references

---

# 9. T06 Saju Engine

- [ ] four pillars
- [ ] five elements
- [ ] day master
- [ ] ten gods
- [ ] hidden stems
- [ ] seasonal weight
- [ ] 합/충/형/해/파
- [ ] relationship features
- [ ] confidence
- [ ] unknown time

Official mode:

```text
KOREAN_LONGITUDE_2330
```

---

# 10. T07 Ephemeris

- [ ] interface
- [ ] provider adapter
- [ ] UTC input
- [ ] geocentric longitude
- [ ] tropical
- [ ] metadata
- [ ] data version
- [ ] health/error

---

# 11. T08 Astrology

- [ ] planets
- [ ] synastry
- [ ] aspects
- [ ] orbs
- [ ] strength
- [ ] pair/category mapping
- [ ] Moon uncertainty
- [ ] ASC optional
- [ ] House optional
- [ ] Placidus/Porphyry
- [ ] MC excluded

---

# 12. T09 Compatibility Features

공통 DTO:

```text
source
rule
categories
direction
strength
confidence
```

- [ ] Saju adapter
- [ ] Astrology adapter
- [ ] validation
- [ ] top positive/negative

---

# 13. T10 LOVE SCORE

- [ ] signed value
- [ ] effective weight
- [ ] source score
- [ ] evidence stabilization
- [ ] missing source reweight
- [ ] coverage/confidence
- [ ] category
- [ ] lifetime
- [ ] display
- [ ] status
- [ ] explainability
- [ ] A/B symmetry

---

# 14. T11 Daily

- [ ] daily Saju
- [ ] 00/06/12/18 transits
- [ ] signal
- [ ] delta
- [ ] daily score
- [ ] yesterday
- [ ] category
- [ ] actions

---

# 15. T12 Period

- [ ] weekly
- [ ] monthly
- [ ] yearly
- [ ] trend
- [ ] volatility
- [ ] best/caution
- [ ] Lichun boundary

---

# 16. T13 Reading Orchestrator

Flow:

```text
Validate
↓
Resolve Location
↓
Normalize Birth
↓
Saju
↓
Astrology
↓
Features
↓
Lifetime
↓
Daily / Period
↓
Actions
↓
AI
↓
Response
```

---

# 17. T14 REST API

공식 endpoints는 `05_API_SPEC.md`를 따른다.

- [ ] schema
- [ ] validation
- [ ] DTO
- [ ] errors
- [ ] versions
- [ ] rate limit
- [ ] CORS
- [ ] no-store
- [ ] no raw logging

---

# 18. T15 Browser Privacy Storage

- [ ] IndexedDB adapter
- [ ] schema version
- [ ] my profile
- [ ] partner profile
- [ ] independent toggles
- [ ] restore
- [ ] update
- [ ] delete
- [ ] migration
- [ ] corrupt data handling

Default OFF.

Auto-submit 금지.

---

# 19. T16 User Input UI

- [ ] my info
- [ ] partner info
- [ ] birth date/time
- [ ] unknown time
- [ ] solar/lunar
- [ ] leap month
- [ ] location
- [ ] relationship type
- [ ] privacy copy
- [ ] browser save toggle
- [ ] validation

---

# 20. T17 Compatibility UI

- [ ] LOVE SCORE
- [ ] status
- [ ] 8 categories
- [ ] highlights
- [ ] Saju/Astrology detail
- [ ] confidence/coverage
- [ ] AI summary
- [ ] share

---

# 21. T18 Daily UI

- [ ] today score
- [ ] yesterday difference
- [ ] today summary
- [ ] actions
- [ ] categories
- [ ] recent 7-day trend

---

# 22. T19 Period UI

Weekly / Monthly / Yearly 전체 구현.

---

# 23. T20 Celebrity

- [ ] data model
- [ ] admin CRUD
- [ ] source/confidence
- [ ] list/search
- [ ] compatibility
- [ ] AI
- [ ] share

---

# 24. T21 AI

- [ ] provider interface
- [ ] prompt registry
- [ ] prompt versions
- [ ] dictionaries
- [ ] ko/ja/en
- [ ] structured output
- [ ] validation
- [ ] safety
- [ ] retry
- [ ] fallback
- [ ] metrics

---

# 25. T22 Sharing

기본은 client-side.

Birth Data를 공유하지 않는다.

Server share token은 별도 spec 없이는 구현하지 않는다.

---

# 26. T23 Admin

`07_ADMIN_SPEC.md` 구현.

사용자 Birth/Profile/History 화면 없음.

---

# 27. T24 Analytics / Observability

수집:

```text
reading_started
reading_completed
reading_failed
daily_viewed
weekly_viewed
monthly_viewed
yearly_viewed
celebrity_viewed
share_clicked
```

Birth Data 금지.

---

# 28. T25 Security / Privacy

- [ ] HTTPS
- [ ] CSP
- [ ] XSS
- [ ] CSRF
- [ ] SQL injection
- [ ] validation
- [ ] rate limit
- [ ] capability
- [ ] secret management
- [ ] log redaction
- [ ] WAF/CDN/APM audit
- [ ] AI provider privacy

---

# 29. T26 Testing

Layers:

```text
Unit
Integration
Golden
API
Frontend
E2E
Security
Performance
```

필수 boundary:

```text
23:29 / 23:30
Lichun before / after
Jie before / after
DST before / after
Orb inside / outside
Score threshold
Month end
Leap day
```

---

# 30. T27 Performance

- [ ] Saju
- [ ] ephemeris
- [ ] synastry
- [ ] score
- [ ] 7-day
- [ ] monthly
- [ ] yearly
- [ ] AI
- [ ] total API
- [ ] concurrency

공통 non-personal cache부터 최적화.

---

# 31. T28 Deployment

```text
DEV
STAG
PROD
```

- [ ] code
- [ ] migration
- [ ] config
- [ ] ephemeris data
- [ ] prompt
- [ ] assets
- [ ] health
- [ ] rollback

---

# 32. T29 Production Verification

Smoke:

```text
Main
Input
Solar/Lunar
Leap Month
Unknown Time
Location
Compatibility
Daily
7-day
Weekly
Monthly
Yearly
Celebrity
AI
Share
Browser Save/Delete
Admin
```

Privacy:

```text
URL
DB
Application Log
Error Log
Analytics
AI Log
CDN Cache
```

에 Raw Birth Data 없음.

---

# 33. Task ID

```text
LF-BOOT-###
LF-DB-###
LF-LOC-###
LF-SAJU-###
LF-ASTRO-###
LF-FEATURE-###
LF-SCORE-###
LF-DAILY-###
LF-PERIOD-###
LF-API-###
LF-WEB-###
LF-AI-###
LF-ADMIN-###
LF-SEC-###
LF-TEST-###
LF-OPS-###
```

---

# 34. Task Template

```text
ID:
Title:
Source:
Dependencies:
Description:
Input:
Output:
Acceptance Criteria:
Tests:
Files:
Do Not:
```

---

# 35. 완료 조건

Task:

```text
Implementation
+
Tests
+
Static Analysis
+
SPEC Compliance
+
Privacy Check
```

---

# 36. Final Acceptance

AI Provider OFF 상태에서도:

```text
Compatibility
LOVE SCORE
Daily
Weekly
Monthly
Yearly
Actions
Trend
```

계산되어야 한다.

동일 Input+Version은 동일 deterministic output을 생성한다.

Browser Storage 삭제 후 서버에서 profile 복구가 불가능해야 한다.

END OF DOCUMENT
