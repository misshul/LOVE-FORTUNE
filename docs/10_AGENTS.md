# LOVE FORTUNE
# 10_AGENTS.md

Version: 1.3.0
Status: CONTRACT FROZEN / IMPLEMENTATION READINESS SEPARATE
Document Type: Codex / AI Coding Agent Development Rules

Decision Authority: [Final Decision v3](contracts/final-decision-v3.md), then [v2](contracts/final-decision-v2.md) and [approved clarifications](contracts/clarification-v2.md)
Freeze Gate: [Freeze validation and readiness](contracts/README.md)

---

# 1. 목적

본 문서는 LOVE FORTUNE을 개발하는 Codex/AI Coding Agent의
최상위 행동 규칙이다.

Codex의 역할은 새로운 사양을 만드는 것이 아니라
이미 정의된 사양을 정확하게 구현하는 것이다.

---

# 2. Source of Truth

```text
/docs/01_SERVICE_SPEC.md
/docs/02_FORTUNE_ENGINE_SPEC.md
/docs/03_SCORE_SPEC.md
/docs/04_DATABASE_SPEC.md
/docs/05_API_SPEC.md
/docs/06_SCREEN_SPEC.md
/docs/07_ADMIN_SPEC.md
/docs/08_AI_PROMPT_SPEC.md
/docs/09_TASK_LIST.md
/docs/10_AGENTS.md
```

관련 Task 시작 전에 반드시 관련 SPEC을 읽는다.

---

# 3. SPEC 역할

```text
01 Service
02 Fortune Engine
03 Score
04 Database
05 API
06 Screen/UX
07 Admin
08 AI
09 Task
10 Agent Rules
```

---

# 4. Conflict Resolution

Explicit approved Final Decision v3 supersedes v2/v1 where changed; the approved v2 clarifications remain valid. Never invent Saju/Astrology rules. Unregistered/incomplete rules are disabled. Freeze and readiness are separate: empty active catalog means BLOCKED_CATALOG/SCORING_RULE_CATALOG_APPROVAL; epoch/provider gates mean BLOCKED_EXTERNAL. Neither alone fails Freeze. Report actual conflicts, never invent approved rules.

---

# 5. 추측 금지

다음은 임의 추정하지 않는다.

```text
Saju formula
Day Pillar epoch
Solar-term boundary
Astrology orb
Planet weight
Score weight
Daily formula
API field
DB column
Privacy rule
AI rule
```

---

# 6. Task 시작 전

확인:

```text
Task ID
Related SPEC
Dependencies
Layer
Input
Output
Acceptance Criteria
Tests
Privacy Impact
Version Impact
```

---

# 7. 최소 변경

Task 범위를 넘어선 대규모 refactoring 금지.

하나의 Task는 한 가지 목적에 집중한다.

---

# 8. Architecture

```text
Frontend
↓
REST API
↓
Application Service
↓
Domain Engine
↓
Infrastructure
```

Domain은 WordPress/$wpdb/wp_remote_post 등에 직접 의존하지 않는다.

---

# 9. Determinism

동일 Input + 동일 Version에서 다음은 동일해야 한다.

```text
Saju
Planet Position
Aspect
Feature
Category Score
LOVE SCORE
Daily
Action
Weekly
Monthly
Yearly
```

계산에 random 사용 금지.

---

# 10. Time

Target date/timezone이 있는 계산에서
시스템 현재 시간을 암묵적으로 사용하지 않는다.

Historical timezone을 current UTC offset으로 단순화하지 않는다.

---

# 11. Saju

Official:

```text
KOREAN_LONGITUDE_2330
```

```text
adjustment_minutes =
(540 - historical_utc_offset_minutes)
+
round((longitude - 127.5) * 4)
```

Day Boundary:

```text
23:30
```

Equation of Time 사용 금지.

Double correction 금지.

검증되지 않은 Day Pillar epoch 사용 금지.

---

# 12. Birth Time Unknown

Do not invent birth times. UNKNOWN Hour/ASC/House and boundary candidates follow v2 candidate mean/agreement/availability rules. Normative contract: [Final Decision v2 SC-01](contracts/runtime-contract-v2.md#sc-01-confidence-and-coverage). Normative contract: [Final Decision v2 SC-02](contracts/runtime-contract-v2.md#sc-02-rule-catalog-and-candidates).

---

# 13. Astrology

Core:

```text
Sun Moon Mercury Venus Mars
```

Secondary:

```text
Jupiter Saturn
```

Supplementary:

```text
Uranus Neptune Pluto
```

Excluded:

```text
MC Nodes Chiron
```

ASC/House score weight = 0.

---

# 14. Ephemeris

Domain은:

```text
EphemerisProviderInterface
```

에 의존.

Swiss/JPL 등 특정 provider를 Domain rule에 hardcode하지 않는다.

Provider software version과 data/kernel version을 분리한다.

---

# 15. Score

공식 Category:

```text
ATTRACTION
EMOTION
COMMUNICATION
PASSION
STABILITY
HARMONY
SUPPORT
LONG_TERM
```

Neutral = 50.

`03_SCORE_SPEC.md` 공식/가중치를 임의 수정하지 않는다.

Missing data를 자동 penalty로 처리하지 않는다.

---

# 16. Symmetry / Bias

Lifetime:

```text
score(A,B) = score(B,A)
```

Gender/Relationship Type에 따라 base score weight 변경 금지.

이성애 고정 Venus/Mars rule 금지.

---

# 17. Stateless

일반 사용자는 server resource가 아니다.

생성 금지:

```text
User Birth Profile
Partner Profile
Relationship Resource
Compatibility History
Fortune History
```

일반 사용자 WordPress account를 강제하지 않는다.

---

# 18. Browser Storage

```text
내 정보 저장
상대 정보 저장
```

독립적이며 default OFF.

Server API에 `saveMyProfile` 같은 flag를 보내지 않는다.

Page load 자동 submit 금지.

---

# 19. Database

WordPress prefix hardcode 금지.

`$wpdb->prefix` 사용.

Prepared query 사용.

Calculation rule source of truth는 versioned code/config다.

---

# 20. Cache

우선:

```text
Solar Terms
Calendar Reference
Ephemeris
Location
Public Content
```

User-derived calculation and AI caches are prohibited, including short-lived caches. Request-local reuse is permitted; shared caches must be non-personal references.

Birth Data Hash를 anonymous로 간주하지 않는다.

---

# 21. API

Use the six Core POST routes and separate interpretation route. Person requires birthDate/birthLocationId, birthTime optionalnull, no gender/calendar/timeKnown/raw coordinate/label fields. targetTimezone required; Asia/Tokyo UI-only default; locales ko-KR/ja-JP/en-US. Exact transport/rate/errors follow v2. Normative contract: [Final Decision v2 SC-08](contracts/runtime-contract-v2.md#sc-08-public-api-wire). Normative contract: [Final Decision v2 SC-10](contracts/runtime-contract-v2.md#sc-10-http-security-and-retention).

---

# 22. Controller

책임:

```text
Parse
Validate
Authorize
Rate Limit
DTO
Service Call
Response
```

Controller에서 Saju/Astrology/Score/SQL/AI prompt를 직접 구현하지 않는다.

---

# 23. Error

Internal exception/stack trace/SQLSTATE를 사용자에게 반환하지 않는다.

명시적 application error code 사용.

AI failure와 core failure를 분리한다.

---

# 24. AI

Structured Features precede Score and AI evidence selection. Core never depends on AI. LFIC signed context binds purpose INTERPRETATION, locale and300-second TTL; keys in environment, no token DB. Scoreless output/evidenceRefs, bounded provider budget and deterministic fallback follow Normative contract: [Final Decision v2 SC-09](contracts/runtime-contract-v2.md#sc-09-signed-context-and-ai-execution).

---

# 25. AI Validation / Fallback

Validate schema, evidenceRefs, category/direction/period, length/HTML and safety. AI disabled/timeout/outage/schema/evidence/safety failures use deterministic template fallback. Core results remain valid. No numeric score fields or invented scores/probabilities/percentages/ranks. Prohibit raw prompt/output/repair/provider-error logging and user-derived AI caches. Disallow provider training on user input; review retention/abuse/cache/region/subprocessors/deletion. Apply all-ages language and celebrity rules against claims about hidden feelings/private personality/attraction/intent/meetings/private relationships.

---

# 26. Frontend

Frontend는 score formula를 복제하지 않는다.

책임:

```text
Input
UX validation
Browser storage
API
Display
Localization
Accessibility
```

---

# 27. Admin

Admin은 CRM이 아니다.

만들지 않는다:

```text
Birth search
Relationship search
Compatibility History
Daily User History
AI User History
```

---

# 28. Logging / Retention

Normative contract: [Final Decision v2 SC-10](contracts/runtime-contract-v2.md#sc-10-http-security-and-retention). Use exact operational and Admin mutation allowlists. Retention/application backup30 days; audit/security backup90 days; Birth0; aggregate<=13 months; HMAC rate keys<=3600 seconds. No body/context/prompt/raw output capture, tracking/fingerprinting or personal caches.

---

# 29. External Privacy

검토:

```text
Web Server
Reverse Proxy
WAF
CDN
APM
Analytics
AI Provider
```

Application DB만 확인하고 privacy 완료라고 판단하지 않는다.

---

# 30. Analytics

Birth Data를 event parameter로 보내지 않는다.

Fingerprint 생성 금지.

---

# 31. Testing

모든 계산 코드 변경에는 tests가 필요하다.

필수:

```text
normal
boundary
invalid
missing data
```

Golden expected를 새 코드에 맞춰 무조건 바꾸지 않는다.

---

# 32. 필수 Boundary

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

# 33. AI Separation Test

AI Provider OFF 상태에서도 core calculation이 동작해야 한다.

---

# 34. Privacy Test

주요 E2E 후:

```text
DB
Logs
URL
Analytics
```

에서 Birth Data가 남지 않는지 검증.

---

# 35. Dependency

새 package 추가 전:

```text
Need
License
Maintenance
Security
Size
Alternative
```

확인.

Ephemeris/location/timezone/AI SDK license와 data policy를 검토한다.

---

# 36. Security

- secret hardcode 금지
- environment/secret manager 사용
- input validation
- output escape
- prepared SQL
- CSP
- XSS protection
- third-party script 최소화

AI output도 trusted HTML로 취급하지 않는다.

---

# 37. Performance

정확성을 희생하지 않는다.

순서:

```text
Profile
Measure
Optimize common calculation
Cache non-personal data
Re-measure
```

Request-local memory reuse는 가능.

---

# 38. SPEC 변경

사양 부족:

```text
SPEC_UPDATE_REQUIRED
```

코드가 SPEC과 다름:

```text
SPEC_CODE_MISMATCH
```

사용자 승인 없이 SPEC을 코드에 맞춰 바꾸지 않는다.

---

# 39. Destructive Change

Task에 명시되지 않은:

```text
DB Drop
Column Drop
Data Delete
API Breaking Change
Config Removal
Cache Purge
```

실행 금지.

---

# 40. Code Style

기존 project standard 우선.

없으면:

- PSR 관례
- WordPress Coding Standards
- strict types where compatible
- typed properties
- enums/value objects
- small functions
- explicit dependencies

Magic score value를 코드 곳곳에 흩뿌리지 않는다.

---

# 41. Completion

코드 작성만으로 완료가 아니다.

필수:

```text
Code
Tests
Static Analysis
SPEC Compliance
Privacy Check
No Unrelated Changes
```

---

# 42. 완료 보고

```text
TASK COMPLETE

Task:
LF-XXXX-###

Implemented:
- ...

Files Changed:
- ...

Tests Added:
- ...

Tests Run:
- ...

Result:
PASS / FAIL

Privacy Impact:
NONE / ...

Version Impact:
NONE / ...

Known Issues:
- ...

SPEC Deviations:
NONE
```

---

# 43. 실패/Block

Test failure:

```text
TASK NOT COMPLETE
```

Dependency/decision 부족:

```text
BLOCKED
```

가짜 구현이나 TODO로 완료 처리하지 않는다.

---

# 44. 최상위 금지사항

명시적 사양 변경 승인 없이 금지:

```text
1. 일반 사용자 Birth Profile DB
2. Relationship DB
3. 개인 Fortune History
4. Raw Birth Request Logging
5. Birth Data Analytics
6. Birth Data URL
7. AI LOVE SCORE 계산
8. AI Saju/Astrology 계산
9. ASC/House Base Score 반영
10. MC 추가
11. Unknown Birth Time 임의 보완
12. 검증되지 않은 Day Pillar Epoch
13. Saju Time Rule 임의 변경
14. Score Weight 임의 변경
15. Payment
16. Point
17. Attendance
18. 일반 사용자 회원가입 강제
19. Production Secret hardcode
20. 실패 Test 무시
```

---

# 45. Final Agent Flow

```text
READ SPEC
↓
IDENTIFY TASK
↓
CHECK DEPENDENCIES
↓
IMPLEMENT MINIMAL CHANGE
↓
ADD TESTS
↓
RUN TESTS
↓
CHECK PRIVACY
↓
CHECK SPEC
↓
REPORT
```

최종 판단 기준:

> 이 구현이 LOVE FORTUNE의 공식 SPEC과
> Privacy-first Stateless Architecture를 정확하게 지키고 있는가?

END OF DOCUMENT


Current Daily authority: [Daily Catalog v1 C21-R](contracts/daily-catalog-v1.md). Daily134/285 APPLIED/READY; S2 thresholds +/-2 / +/-5; M1-ELIGIBILITY-AWARE signal aggregation. SC-07 applies to Lifetime only. Score Engine catalog readiness READY; catalog blockers NONE; Production BLOCKED_EXTERNAL (SAJU_DAY_PILLAR_EPOCH, EPHEMERIS_PROVIDER). Public/signed wire and privacy remain unchanged.
