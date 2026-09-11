# LOVE FORTUNE
# 09_TASK_LIST.md

Version: 1.3.0
Status: CONTRACT FROZEN / IMPLEMENTATION READINESS SEPARATE
Document Type: Development Task / Implementation Plan

Decision Authority: [Final Decision v3](contracts/final-decision-v3.md), then [v2](contracts/final-decision-v2.md) and [approved clarifications](contracts/clarification-v2.md)
Freeze Gate: [Freeze validation and readiness](contracts/README.md)

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
T13 Core Calculation Orchestrator
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

Task scope: version registry and immutable manifests.

- [ ] serviceVersion / apiVersion
- [ ] sajuEngineVersion / astrologyEngineVersion / scoreVersion / configVersion
- [ ] locationReferenceVersion / timezoneDataVersion
- [ ] ephemerisProviderVersion / ephemerisDataVersion
- [ ] Interpretation-only aiPromptVersion and provider/model metadata
- [ ] Exact canonical output under identical versions
- [ ] No silent release-definition overwrite

Use Final Decision v2 metadata/token projection; obey the current Freeze gate in contracts/README.md.

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

- [ ] birthLocationId-only calculation input and server reference resolution
- [ ] /locations and /locations/{locationId}
- [ ] targetTimezone required; Asia/Tokyo UI-only default
- [ ] /service-config locale ko-KR/ja-JP/en-US
- [ ] historical timezone and versioned data

Normative contract: [Final Decision v2 SC-08](contracts/runtime-contract-v2.md#sc-08-public-api-wire).

---

# 8. T05 Calendar / Saju Foundation

- [ ] Gregorian date range1900..2099
- [ ] leap month
- [ ] solar terms
- [ ] 12 Jie
- [ ] Julian Day
- [ ] sexagenary cycle
- [ ] golden references (synthetic/licensed/approved only)
- [ ] BLOCKED_EXTERNAL: verified Day Pillar epoch / epoch identifier

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
- [ ] BLOCKED_EXTERNAL: ephemeris provider/license/data selection
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

- [ ] Versioned Saju/Astrology feature catalogs
- [ ] featureId, ruleId, source, subject, category, direction, rawValue, baseWeight, confidence, period, metadata
- [ ] confidence means featureConfidence
- [ ] Stable feature instances without tracking identifiers
- [ ] Registered category/direction/MIXED signed values and weight factors
- [ ] Candidate uncertainty propagation

Follow v2 confidence/identity/candidate contracts and approved clarifications. Incomplete rule catalogs remain disabled and are tracked separately in contracts/README.md.

---

# 13. T10 LOVE SCORE

- [ ] signed value
- [ ] effective weight
- [ ] category raw R
- [ ] evidence stabilization
- [ ] missing source reweight
- [ ] coverage/resultConfidence
- [ ] category
- [ ] lifetime
- [ ] display
- [ ] status
- [ ] explainability
- [ ] A/B symmetry

---

# 14. T11 Daily

- [ ] Samples06/12/18/23; deterministic DST earlier UTC instant
- [ ] Missing samples and0.75mean+0.25peak
- [ ] Source weights0.50/0.50, delta18, clamp0..100
- [ ] Category-specific evidence
- [ ] Delta status bands and lifetime-null propagation

Normative contract: [Final Decision v2 SC-05](contracts/runtime-contract-v2.md#sc-05-daily-sampling-and-dst). Normative contract: [Final Decision v2 SC-06](contracts/runtime-contract-v2.md#sc-06-dates-periods-and-statuses).

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

# 16. T13 Core Calculation Orchestrator

Normalized Input -> Saju/Astrology -> Structured Feature Set -> Score -> Daily/Period -> Result DTO -> immediate Frontend display. The same features feed Score and approved AI evidence selection. Core must not call AI. A five-minute signed context supports the separate Interpretation request. Core and AI have independent API lifecycles.

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

- [ ] One My Profile and one Partner Profile
- [ ] Independent opt-in, default OFF
- [ ] Memory when OFF; IndexedDB when ON
- [ ] Explicit save/edit/replace/delete/delete-all
- [ ] Client-only name/nickname labels
- [ ] No automatic transmission on load/navigation/timer/prefetch/service worker/background sync/analytics/error reporting
- [ ] Birth Data transmitted only after explicit calculation action

---

# 19. T16 User Input UI

- [ ] my info
- [ ] partner info
- [ ] birth date/time
- [ ] unknown time
- [ ] Gregorian date range1900..2099
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

- [ ] Separate /interpretation/generate and LFIC signed context
- [ ] Key rotation, purpose/locale binding,300-second TTL
- [ ] Provider6-second call/8-second total budget
- [ ] Bounded retry/repair,200 fallback,503 only unavailable fallback
- [ ] EvidenceRefs, scoreless output, privacy/safety

Normative contract: [Final Decision v2 SC-09](contracts/runtime-contract-v2.md#sc-09-signed-context-and-ai-execution).

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

- [ ] No personal DB/history/cache, stable IDs or fingerprints
- [ ] Disable request/response/context/prompt/output capture throughout infrastructure
- [ ] Retention: raw Birth0, operational30 days, security90 days, aggregate<=13 months, rate raw key<=1 hour
- [ ] WordPress authentication + nonce + capability for Admin mutations
- [ ] No destructive GET, arbitrary SQL/code execution or plaintext wp_options secrets
- [ ] CORS does not replace CSRF protection; production Admin MFA recommended
- [ ] no-store and body/limit/rate/error contract checks
- [ ] Synthetic/licensed/approved-public fixture provenance; no real-user Golden data

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
Gregorian boundaries; rejected lunar/calendar flags
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

---

# 37. Freeze / Production Gates

Apply Final Decision v3 and retained v2 decisions. Freeze requires SC-01..SC-10 resolved and successful Schema/OpenAPI/arithmetic/canonical/signing/period/DST/privacy checks; actual status is contracts/README.md. SAJU_DAY_PILLAR_EPOCH and EPHEMERIS_PROVIDER separately block production enablement, not Freeze by themselves. No code changes or commit/push in this task.

---


## Final v3 contract alignment

[Runtime contract](contracts/runtime-contract-v2.md) applies the approved v3 decisions: structural coverage is retained with zero usable confidence; Daily source confidence includes all four samples with missing=0; Weekly is the valid-Daily arithmetic mean; period fallback days are excluded; periodDelta and trend magnitude/direction are separate; Actions use weighted Feature.confidence and cannot alter scores. UI displays deterministic fields; AI cannot alter Action confidence. No storage or new engine rules are introduced. Contract Freeze is independent of BLOCKED_CATALOG (SCORING_RULE_CATALOG_APPROVAL) and BLOCKED_EXTERNAL (SAJU_DAY_PILLAR_EPOCH, EPHEMERIS_PROVIDER).

END OF DOCUMENT


Current Daily authority: [Daily Catalog v1 C21-R](contracts/daily-catalog-v1.md). Daily134/285 APPLIED/READY; S2 thresholds +/-2 / +/-5; M1-ELIGIBILITY-AWARE signal aggregation. SC-07 applies to Lifetime only. Score Engine catalog readiness READY; catalog blockers NONE; Production BLOCKED_EXTERNAL (SAJU_DAY_PILLAR_EPOCH, EPHEMERIS_PROVIDER). Public/signed wire and privacy remain unchanged.
