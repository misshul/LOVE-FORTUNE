> Epoch status update: historical unresolved epoch statements are superseded by [SAJU_DAY_PILLAR_EPOCH_V1](saju-day-pillar-epoch-v1.md). Current readiness is in [readiness.md](readiness.md).

<!-- Historical approval: Daily status/signal availability and readiness statements are superseded by daily-catalog-v1.md (C21-R). Lifetime, security and other unaffected approvals remain authoritative. -->
> Historical decision record. Current Saju catalog and SC-07 guardrail are superseded by [Saju v1](saju-catalog-v1.md) and [Guardrail v2](guardrail-v2.md). Unrelated decisions remain active.

# LOVE FORTUNE — SPECIFICATION FREEZE DECISION v1

Status: APPROVED FOR SPEC UPDATE
Purpose: LOVE FORTUNE 구현을 위한 Contract Freeze 기준
Scope: docs/01 ~ docs/10 및 신규 docs/contracts/
Important:
이 결정문은 기존 문서의 미확정 사항을 해소하기 위한 상위 결정입니다.
문서 수정 단계에서는 PHP/WordPress/Frontend 코드를 수정하지 않습니다.

==================================================
1. CORE ARCHITECTURE
==================================================

LOVE FORTUNE의 계산과 AI 해석을 명확히 분리한다.

공식 처리 흐름:

User Input
→ Input Normalization
→ Deterministic Engine
→ Structured Features
→ LOVE SCORE
→ Deterministic Result
→ Frontend 즉시 표시

필요한 경우:

Deterministic Result
→ Signed Interpretation Context
→ POST /interpretation/generate
→ AI Interpretation
→ Frontend에 추가 표시

원칙:

- AI는 계산 엔진의 일부가 아니다.
- AI는 Saju/Astrology/Score를 계산하지 않는다.
- AI 실패는 Core Calculation 실패가 아니다.
- Core 결과는 AI 없이 완결되어야 한다.
- AI OFF/timeout/outage/validation failure에서도
  사용자는 Core 결과를 정상적으로 볼 수 있어야 한다.


==================================================
2. OFFICIAL API
==================================================

Base:

/wp-json/love-fortune/v1

공식 Core API:

POST /compatibility/calculate
POST /fortune/daily
POST /fortune/daily-range
POST /fortune/weekly
POST /fortune/monthly
POST /fortune/yearly

AI:

POST /interpretation/generate

기존 Core API 내부에서 AI를 실행하지 않는다.

includeInterpretation은 제거한다.

Core response의 interpretation 필드도 제거한다.

Core와 AI는 독립적인 API lifecycle을 가진다.


==================================================
3. DETERMINISTIC PIPELINE
==================================================

공식 데이터 흐름:

Normalized Input
→ Saju Engine
→ Astrology Engine
→ Structured Feature Set
→ Score Engine
→ Period Engine
→ Result DTO

AI용 feature를 Score 계산 후 새로 생성하는 구조로 만들지 않는다.

Structured Feature Set이 먼저 생성되고,

Structured Feature Set
→ Score Engine
→ AI Evidence Selection

두 경로에서 사용한다.

즉:

Saju/Astrology
        ↓
Structured Features
       ↙ ↘
    Score  AI Evidence


==================================================
4. FEATURE CONTRACT
==================================================

모든 계산 Feature는 최소 다음 필드를 가진다.

featureId
rule
source
subject
category
direction
rawValue
baseWeight
confidence
period
metadata

featureId는 계산 결과 내의 stable evidence identifier다.

원칙:

- 동일 canonical input
- 동일 engine/config/reference version
- 동일 feature

이면 동일 featureId를 생성해야 한다.

단:

featureId는 사용자 식별자나 추적 ID로 사용해서는 안 된다.

featureId 생성에:

- 이름
- nickname
- IP
- browser ID
- device ID
- random persistent ID

를 사용하지 않는다.

rule과 featureId는 서로 다른 개념이다.

rule:
계산 규칙의 ID

featureId:
해당 계산에서 생성된 feature instance의 ID


==================================================
5. DIRECTION
==================================================

Feature direction은 다음 enum으로 제한한다.

POSITIVE
NEGATIVE
NEUTRAL
MIXED

MIXED는 Score Engine이 임의로 부호를 결정하지 않는다.

각 rule catalog가 category별 direction을 명시해야 한다.

예:

rule:
ASTRO_VENUS_MARS_TRINE

category:
ATTRACTION

direction:
POSITIVE

미등록 rule은 점수 계산에서 사용하지 않는다.


==================================================
6. SCORE CATEGORIES
==================================================

공식 LOVE SCORE category:

ATTRACTION
EMOTION
COMMUNICATION
PASSION
STABILITY
HARMONY
SUPPORT
LONG_TERM

각 Category Score:

0.0000 ~ 100.0000

Overall Score:

0.0000 ~ 100.0000


==================================================
7. CONFIDENCE CONTRACT
==================================================

confidence를 하나의 의미로 혼용하지 않는다.

다음 세 개를 분리한다.

1. featureConfidence
2. coverage
3. resultConfidence


featureConfidence:

0.0 ~ 1.0

해당 feature 계산 자체의 신뢰도.

예:

정확한 출생시간 기반:
1.0

출생시간 불명으로 범위 계산:
0.0 ~ 1.0


coverage:

0.0 ~ 1.0

해당 category에서 사용 가능한 evidence의 충족 정도.


resultConfidence:

0.0 ~ 1.0

최종 결과의 evidence 충분성을 나타낸다.

resultConfidence는 LOVE SCORE에 직접 감점으로 사용하지 않는다.

Score와 Confidence는 별도의 값이다.


==================================================
8. MISSING DATA POLICY
==================================================

출생시간 미입력 자체에 고정 점수 패널티를 주지 않는다.

금지:

unknown birth time
→ -10점

같은 방식.

대신 사용할 수 없는 feature를 제외하고
남은 evidence를 reweight한다.

단 evidence가 적을 경우 결과가 극단값으로 치우치는 것을 막기 위해
Neutral Stabilization을 적용한다.


==================================================
9. EVIDENCE STABILIZATION
==================================================

Category의 evidence raw score를 R이라고 한다.

R 범위:

0 ~ 100

coverage를 C라고 한다.

C 범위:

0 ~ 1

최종 Category Score:

Score = 50 + (R - 50) × C

예:

R = 90
C = 1.0

Score = 90

R = 90
C = 0.5

Score = 70

R = 20
C = 0.5

Score = 35

즉 evidence가 부족할수록
좋다/나쁘다 어느 방향이든 50점으로 수축한다.

이것은 Missing Data Penalty가 아니다.

정보 부족에 대한 불확실성 표현이다.


==================================================
10. E = 0
==================================================

사용 가능한 evidence가 0인 category:

score = 50.0000
coverage = 0
resultConfidence = 0
status = INSUFFICIENT_DATA

Overall 계산에서 해당 category를
일반적인 50점 category로 취급하지 않는다.

Overall Score 계산 시
사용 가능한 category만 reweight한다.

모든 category가 INSUFFICIENT_DATA이면:

overallScore = null
status = INSUFFICIENT_DATA


==================================================
11. FEATURE WEIGHT PIPELINE
==================================================

Weight는 각 단계에서 정확히 한 번만 적용한다.

공식 순서:

baseWeight
× ruleWeight
× pairWeight
× featureConfidence
= effectiveWeight

categoryWeight는 Feature 단계에서 곱하지 않는다.

먼저 category 내부 Score를 계산한다.

그 후 Overall Score 계산 시 categoryWeight를 적용한다.

즉:

Feature Weight
→ Category Score
→ Category Weight
→ Overall Score

동일 weight를 두 번 적용하지 않는다.


==================================================
12. CATEGORY RAW SCORE
==================================================

각 feature의 signedValue:

POSITIVE = +1
NEGATIVE = -1
NEUTRAL = 0

MIXED는 rule catalog에 정의된
category-specific signed value를 사용한다.

weightedSignal:

Σ(signedValue × effectiveWeight)
──────────────────────────────
Σ(effectiveWeight)

범위:

-1 ~ +1

Category Raw Score:

R = 50 + 50 × weightedSignal

그 후 Evidence Stabilization을 적용한다.


==================================================
13. OUTER PLANET POLICY
==================================================

Uranus
Neptune
Pluto

는 supplementary evidence다.

Outer Planet 전체 effectiveWeight는
Astrology evidence weight의 최대 15%로 제한한다.

N:
non-outer effective weight 합

O:
outer effective weight 합

O > 0인 경우:

alpha = min(
    1,
    (0.15 / 0.85) × N / O
)

각 outer effectiveWeight:

outerWeight' = alpha × outerWeight

이후 Score 계산에는 조정된 weight를 사용한다.

개별 outer feature의 base/rule weight 상한은 0.50으로 한다.

적용 순서:

individual rule cap
→ confidence
→ effective weight
→ outer 15% cap
→ category aggregation

N = 0이고 O > 0이면
Outer Planet만으로 Category Score를 만들지 않는다.

해당 outer evidence는 contextual evidence로만 유지하고
score evidence에서는 제외한다.


==================================================
14. OVERALL SCORE
==================================================

Overall Score는 사용 가능한 category score의
weighted average다.

Overall =
Σ(categoryScore × categoryWeight)
─────────────────────────────────
Σ(available categoryWeight)

INSUFFICIENT_DATA category는 분모와 분자에서 제외한다.

Category Weight는
versioned Score Config에 정의한다.

구현 코드에 magic number로 넣지 않는다.


==================================================
15. ROUNDING
==================================================

내부 계산에서는 가능한 한 full precision을 유지한다.

중간 단계에서 임의 반올림하지 않는다.

Canonical API Score:

decimal 4자리 HALF_UP

예:

74.123456
→ 74.1235

Status 판정은 UI 정수값이 아니라
Canonical Score를 사용한다.

UI에서는 필요에 따라 정수로 표시할 수 있다.


==================================================
16. LIFETIME STATUS
==================================================

Canonical Score 기준:

0 <= score < 45
CAUTION

45 <= score < 60
BALANCED

60 <= score < 75
GOOD

75 <= score < 85
VERY_GOOD

85 <= score <= 100
EXCELLENT

score = null:

INSUFFICIENT_DATA


==================================================
17. DAILY SCORE
==================================================

Daily Score는 Lifetime Score를 기준점으로 사용한다.

Daily Score:

dailyScore =
clamp(
    lifetimeScore + dailyDelta,
    0,
    100
)

dailyDelta 범위:

-18 ~ +18

dailyDelta는:

Saju Daily Signal
Astrology Transit Signal

두 source의 weighted combination으로 계산한다.

초기 공식:

dailySignal =
0.50 × sajuDailySignal
+
0.50 × astrologyDailySignal

각 signal 범위:

-1.0 ~ +1.0

dailyDelta:

dailyDelta = 18 × dailySignal

한 source가 계산 불가능한 경우
사용 가능한 source를 100%로 reweight한다.

둘 다 계산 불가능한 경우:

dailyScore = lifetimeScore
dailyDelta = 0
dailyStatus = INSUFFICIENT_PERIOD_DATA

이 경우 lifetime result 자체가
INSUFFICIENT_DATA라는 뜻은 아니다.


==================================================
18. DAILY TRANSIT AGGREGATION
==================================================

Astrology Daily Transit은
공식 transit rule catalog를 사용한다.

각 transit feature:

signedValue × effectiveWeight

를 집계하여 -1 ~ +1로 정규화한다.

Saju Daily Signal도 동일하게
versioned daily rule catalog를 통해
-1 ~ +1 범위로 정규화한다.

구현자가 임의로 새로운 rule이나 weight를 만들 수 없다.

Daily Rule Catalog는
Score Config의 versioned data로 관리한다.


==================================================
19. DAILY SAMPLE / DST
==================================================

Daily 계산의 기준 timezone은 targetTimezone이다.

하루의 sample 시간은
문서에서 versioned config로 관리한다.

DST 지역에서 local time이 존재하지 않는 경우:
해당 timezone의 다음 유효 instant로 이동한다.

local time이 두 번 존재하는 경우:
earlier offset을 canonical instant로 사용한다.

모든 DST 처리 결과는 deterministic 해야 한다.


==================================================
20. MONTHLY
==================================================

Monthly는 별도의 임의 점수를 새로 생성하지 않는다.

해당 월의 Daily deterministic 결과를 집계한다.

monthlyScore:

해당 월의 유효 dailyScore 평균

bestDays:
dailyScore 상위 날짜

cautionDays:
dailyScore 하위 날짜

volatility:

해당 월 dailyScore의 population standard deviation

major_period_signal은
현재 별도 독립 점수 항으로 사용하지 않는다.

필요한 장기 Saju/Astrology 신호는
Daily/Period Feature로 명시적으로 표현한다.

따라서 정의되지 않은
major_period_signal 공식은 제거한다.


==================================================
21. YEARLY
==================================================

Yearly도 월별/일별 deterministic 결과에서 파생한다.

yearlyScore:

해당 연도의 유효 monthlyScore 평균

bestMonths:
monthlyScore 상위 월

cautionMonths:
monthlyScore 하위 월

yearlyVolatility:

monthlyScore의 population standard deviation

saju_annual 또는 major_transit이라는
정의되지 않은 독립 숫자를 임의로 추가하지 않는다.

연간 Saju/Astrology Feature가 필요하면
versioned rule catalog의 Feature로 생성하고
Period Engine에서 명시적으로 반영한다.


==================================================
22. PERIOD TIE BREAK
==================================================

동일 Score인 best/caution 후보의 정렬:

1. score
2. confidence
3. chronological order

Best:
score DESC
confidence DESC
date ASC

Caution:
score ASC
confidence DESC
date ASC


==================================================
23. CHANGE SIGNIFICANCE
==================================================

소수점 구간에 빈틈이 없도록 정의한다.

abs(delta) < 3
STABLE

3 <= abs(delta) < 8
NOTICEABLE

abs(delta) >= 8
SIGNIFICANT


==================================================
24. DETERMINISM
==================================================

동일:

canonical input
engine version
score version
config version
reference data version
timezone/tzdb version
ephemeris provider/data version

에서는 동일 canonical output을 생성해야 한다.

동일 환경의 Golden Test는 exact match를 기본으로 한다.

± tolerance는
서로 다른 floating runtime/provider 환경을 비교하는
명시적인 regression test에서만 허용한다.

Tolerance는 테스트별로 선언해야 한다.

전역 ±0.01 규칙을 사용하지 않는다.


==================================================
25. SAJU DAY PILLAR
==================================================

Day Pillar epoch는 구현자가 임의 선택하지 않는다.

authoritative reference와 대조한
verified epoch가 확보될 때까지
Day Pillar production implementation을 freeze한다.

Golden Dataset으로 검증 후
saju_engine_version에 epoch identifier를 포함한다.

공식 mode:

KOREAN_LONGITUDE_2330

공식 day boundary:

23:30

공식 adjustment:

adjustment_minutes =
(540 - historical_utc_offset_minutes)
+
round((longitude - 127.5) × 4)

saju_adjusted_datetime =
local_civil_datetime
+
adjustment_minutes

Equation of Time은 사용하지 않는다.


==================================================
26. UNKNOWN BIRTH TIME
==================================================

출생시간이 없으면 임의 시간을 생성하지 않는다.

금지:

12:00
00:00
평균 시간

등을 실제 출생시간처럼 사용하는 것.

Hour Pillar:
UNKNOWN

ASC/House:
UNKNOWN

시간 불명으로 인해 경계 결과가 여러 개 가능하면
candidate result를 계산하고
확정되지 않은 feature의 confidence/coverage에 반영한다.

하나의 임의 결과를 확정값으로 선택하지 않는다.


==================================================
27. ASTROLOGY
==================================================

Core:

Sun
Moon
Mercury
Venus
Mars

Secondary:

Jupiter
Saturn

Supplementary:

Uranus
Neptune
Pluto

ASC/House:
Context only
Score weight = 0

MC:
Excluded

System:

Tropical
Geocentric

Aspects:

Conjunction
Opposition
Trine
Square
Sextile

Orb 조합은
versioned Astrology Rule Catalog에서 명시한다.

두 행성의 서로 다른 orb allowance가 있을 경우
구현자가 임의로 결합하지 않는다.

공식 조합 규칙이 Rule Catalog에 없으면
해당 rule은 production score에서 사용하지 않는다.


==================================================
28. EPHEMERIS PROVIDER
==================================================

EphemerisProviderInterface를 유지한다.

특정 provider/kernel/software에
domain layer를 직접 결합하지 않는다.

Provider 채택 전:

- license
- date coverage
- precision
- reproducibility
- timezone independence
- deployment compatibility

를 검증한다.

Provider/Data version은
결과 metadata에 포함한다.


==================================================
29. NAME / NICKNAME
==================================================

사용자의 이름과 nickname은
client-only display data다.

서버 계산 요청에 보내지 않는다.

AI에도 보내지 않는다.

서버 내부 subject:

PERSON_A
PERSON_B

만 사용한다.

사용자가 저장한 nickname은
브라우저 UI에서 결과 표시 시 치환한다.


==================================================
30. BROWSER STORAGE
==================================================

지원:

My Profile: 최대 1개
Partner Profile: 최대 1개

각각 독립 opt-in.

기본:

OFF

OFF:
memory only

ON:
IndexedDB

사용자가 직접:

저장
수정
교체
삭제

할 수 있어야 한다.

서버에는 profile을 저장하지 않는다.


==================================================
31. AUTOMATIC TRANSMISSION
==================================================

저장된 Birth Data를 다음 상황에서
자동으로 서버에 보내지 않는다.

- page load
- route change
- timer
- prefetch
- service worker
- background sync
- analytics
- error reporting

사용자가 명시적으로:

오늘의 사랑운 보기
궁합 보기

등의 계산 Action을 실행한 경우에만 전송한다.


==================================================
32. LOCATION
==================================================

일반 사용자 계산 요청은
locationId를 사용한다.

Server는 versioned Location Reference에서:

latitude
longitude
timezone

을 resolve한다.

Client가 임의 좌표를 authoritative 값으로 보내지 않는다.

Location Reference는 DB 또는
versioned dataset으로 구현 가능하지만
Domain Contract는 동일하게 유지한다.

Reference version을 결과 metadata에 포함한다.


==================================================
33. TARGET TIMEZONE
==================================================

계산 timezone precedence:

1. explicit targetTimezone
2. service-config defaultTimezone

Browser timezone은
UI prefill에만 사용할 수 있다.

계산 요청에는 사용자가 최종 선택한
targetTimezone을 명시적으로 전송한다.

잘못된 IANA timezone은 validation error다.

암묵적으로 browser timezone으로 fallback하지 않는다.


==================================================
34. PRIVACY
==================================================

일반 사용자에 대해 금지:

- server-side Birth Profile storage
- Partner Profile storage
- Relationship History storage
- stable anonymous tracking ID
- fingerprinting
- Birth Data hash를 tracking ID로 사용
- user-derived calculation server cache
- user-derived AI server cache
- request/response body logging
- signed interpretation context logging
- raw prompt logging
- raw AI output logging

허용:

- request-scoped memory
- shared non-personal reference cache
- ephemeris/reference/calendar cache
- aggregate operational metrics

단 aggregate metrics에
Birth Data를 포함하지 않는다.


==================================================
35. LOGGING
==================================================

Production log allowlist 방식으로 운영한다.

허용 후보:

requestId
endpoint
HTTP status
duration
errorCode
engineVersion
scoreVersion
configVersion

금지:

birthDate
birthTime
location coordinates
nickname
signed context
AI prompt
AI raw output
full request body
full response body

requestId는 요청 단위 random ID이며
사용자 추적 ID가 아니다.


==================================================
36. RETENTION
==================================================

Raw Birth Data retention:

0

서버에 저장하지 않는다.

Application operational log:

30 days

Security log:

90 days

Aggregate non-personal service metrics:

최대 13 months

Backup에도 금지 데이터가 들어가지 않도록 한다.

CDN/WAF/APM/error tracking에서
request/response body capture를 OFF 또는 redact한다.


==================================================
37. CACHE
==================================================

개인 계산 API:

Cache-Control: no-store

AI Interpretation:

Cache-Control: no-store

Signed Context:

Cache-Control: no-store

CDN shared cache 금지.

공용:

location
timezone reference
calendar
ephemeris
rule/config

등 비개인 데이터만 cache 가능.


==================================================
38. SIGNED INTERPRETATION CONTEXT
==================================================

Core는 AI 해석이 가능한 결과에 대해
short-lived signed context를 발급할 수 있다.

Context에 포함 가능:

contextVersion
purpose
issuedAt
expiresAt
engine versions
score version
config version
canonical score/status
approved evidence subset

포함 금지:

raw Birth Data
name
nickname
IP
device identifier

TTL:

5 minutes

Token은 URL query에 넣지 않는다.

POST body에서만 전달한다.

서명 알고리즘:

HMAC-SHA-256

Key는 환경 secret으로 관리한다.

wp_options plaintext에 저장하지 않는다.

Context는 암호화가 아니라
tamper verification 용도임을 문서에 명시한다.

현재 v1에서는 server-side replay store를 만들지 않는다.

따라서 strict one-time token이라고 표현하지 않는다.


==================================================
39. AI EVIDENCE
==================================================

AI가 사용하는 evidence는
signed context 안의 approved evidence로 제한한다.

AI output:

summary
strengths
challenges
advice

strengths/challenges item:

{
  "text": "...",
  "evidenceRefs": ["feature-id"]
}

존재하지 않는 featureId 참조는 validation failure다.

Category/period/direction과 맞지 않는
evidence 참조도 validation failure다.


==================================================
40. AI SCORE POLICY
==================================================

AI output schema에는 numeric score field를 두지 않는다.

AI는 새로운 숫자 궁합점수,
확률,
백분율,
순위를 생성하지 않는다.

Authoritative Score는
Deterministic Core만 생성한다.

Frontend가 Core Score를 표시한다.


==================================================
41. AI FALLBACK
==================================================

다음은 정상적으로 예상되는 fallback condition이다.

AI disabled
timeout
provider outage
invalid schema
invalid evidenceRefs
safety validation failure

이 경우 Core Result는 유지한다.

Fallback은 deterministic template을 사용한다.

AI가 없다는 이유로
계산 API를 실패 처리하지 않는다.


==================================================
42. AI LOGGING / PROVIDER
==================================================

금지:

raw prompt log
raw AI output log
repair payload log
provider raw error body log

Provider 선정 시 확인:

retention
training use
abuse monitoring
prompt cache
data region
subprocessors
deletion policy

사용자 입력을 모델 학습에 사용하는 설정은
허용하지 않는다.


==================================================
43. CELEBRITY SAFETY
==================================================

Celebrity 기능은 공개적으로 알려진
생년 정보에 기반한 entertainment interpretation만 제공한다.

AI는 실제 인물에 대해 다음을 사실처럼 단정하지 않는다.

- 숨은 감정
- 성격의 비공개 사실
- 특정 사용자에 대한 호감
- 실제 연애 의도
- 실제 만남 가능성
- 사적인 관계 상태 추측

결과는 계산 모델 기반의
entertainment interpretation임을 표시한다.


==================================================
44. AGE / MINOR SAFETY
==================================================

입력된 생년월일은
실제 사용자의 연령 인증으로 취급하지 않는다.

LOVE FORTUNE v1의 기본 콘텐츠는
all-ages safe relationship language를 사용한다.

미성년 또는 연령 불명 가능성이 있는 경우에도:

- 성적 콘텐츠
- 성적 궁합
- 성행위 조언
- 착취적 관계 조언

을 제공하지 않는다.

관계 존중,
대화,
감정 이해,
갈등 관리 중심으로 표현한다.

v1에서는 별도의 성인 전용 콘텐츠를 제공하지 않는다.


==================================================
45. NOTIFICATIONS
==================================================

현재 범위:

Service Notices

만 지원한다.

개인화 Push/Web Push,
개인 Birth Data 기반 자동 알림은
현재 Scope에서 제외한다.


==================================================
46. API CONTENT CONTRACT
==================================================

Content-Type:

application/json

JSON UTF-8

지원하지 않는 Content-Type:

415 Unsupported Media Type

Payload 최대:

Core API: 32 KB
Interpretation API: 64 KB

초과:

413 Payload Too Large

Unknown top-level fields:

Reject

Validation은 allowlist 기반으로 한다.


==================================================
47. RATE LIMIT
==================================================

v1 기본 정책:

Core Calculation:

60 requests / 10 minutes / source

AI Interpretation:

10 requests / 10 minutes / source

초과:

429 Too Many Requests

Retry-After header를 반환한다.

여기서 source는
영구 사용자 식별자가 아니다.

인프라 수준의 단기 abuse prevention key를 의미한다.

Rate-limit 식별 데이터는
장기 사용자 tracking에 사용하지 않는다.

Rate-limit raw key retention 최대:

1 hour

실제 production traffic 분석 후
별도 spec version에서 조정 가능하다.


==================================================
48. ERROR CONTRACT
==================================================

공식 error envelope:

{
  "error": {
    "code": "STRING_CODE",
    "messageKey": "i18n.key",
    "field": "optionalField",
    "details": {}
  }
}

HTTP:

400 malformed request
404 unknown reference
413 payload too large
415 unsupported media type
422 semantic validation
429 rate limited
500 internal error
503 temporary provider unavailable

사용자에게:

stack trace
SQL
filesystem path
provider raw response
secret

을 노출하지 않는다.


==================================================
49. OPENAPI / JSON SCHEMA
==================================================

docs/contracts/를 생성한다.

최소:

openapi.yaml

schemas/
common.schema.json
person-input.schema.json
compatibility-request.schema.json
compatibility-response.schema.json
daily-request.schema.json
daily-response.schema.json
period-request.schema.json
period-response.schema.json
interpretation-request.schema.json
interpretation-response.schema.json
error.schema.json

examples/
valid/
invalid/

API 구현 전에 schema가 승인되어야 한다.


==================================================
50. ADMIN SECURITY
==================================================

Admin 변경 작업:

WordPress capability check
+
WordPress nonce

필수.

CORS를 CSRF 방어로 취급하지 않는다.

Destructive operation은 GET으로 실행하지 않는다.

POST/DELETE 등 명시적 mutation method 사용.

금지:

arbitrary SQL execution
arbitrary PHP/code execution
secret plaintext wp_options storage

Admin MFA는 production 운영 계정에 권장한다.


==================================================
51. GOLDEN DATA
==================================================

Golden/Unit/Integration/AI fixture에:

실사용자 Birth Data를 사용하지 않는다.

허용:

synthetic
licensed
explicitly approved public/reference data

Golden fixture에는
출처와 사용 가능 근거를 기록한다.


==================================================
52. VERSIONING
==================================================

결과 재현을 위해 최소 다음 버전을 관리한다.

serviceVersion
apiVersion
sajuEngineVersion
astrologyEngineVersion
scoreVersion
configVersion
locationReferenceVersion
timezoneDataVersion
ephemerisProviderVersion
ephemerisDataVersion
aiPromptVersion

계산 결과 metadata에는
AI 관련 버전을 제외한 계산 관련 버전을 포함한다.

AI response에는 추가로:

aiPromptVersion
provider/model identifier

를 포함한다.


==================================================
53. VERSION FREEZE RULE
==================================================

공식 계산 규칙이 변경되면
기존 version을 조용히 덮어쓰지 않는다.

결과에 영향을 주는 변경은
해당 engine/score/config/reference version을 증가시킨다.

동일 version은 동일 Contract를 의미한다.


==================================================
54. CURRENT SCOPE DECISION
==================================================

v1에서 지원:

- Lifetime Compatibility
- Daily Love Fortune
- Daily Range
- Weekly
- Monthly
- Yearly
- AI Interpretation
- Celebrity entertainment compatibility
- Browser-local My/Partner profile
- Service Notices
- Admin configuration/reference management

v1에서 제외:

- User account relationship storage
- Relationship history
- Personal push
- Multiple partner management
- Social graph
- Server-side personal profile
- User-derived persistent cache
- Adult/sexual compatibility
- AI-generated numeric score


==================================================
55. IMPLEMENTATION GATE
==================================================

이 결정문을 기준으로 docs/01~10을 수정한다.

단, 이번 단계에서는 PHP/WordPress/Frontend 코드를
수정하지 않는다.

문서 수정 후 반드시 다시
Cross-Spec Reconciliation을 수행한다.

다음 조건을 모두 만족해야
Contract Freeze PASS로 판정한다.

1. docs/01~10 사이 contradiction 0
2. Score 공식의 구현자 선택지 0
3. 공식 API endpoint 일치
4. Core/AI dependency 방향 일치
5. Privacy storage/log/cache 규칙 일치
6. Location/Timezone contract 일치
7. Feature/evidence contract 일치
8. Rounding/status 경계 완전 정의
9. Unknown birth time 처리 일치
10. Version metadata 일치
11. OpenAPI/JSON Schema와 문서 일치
12. SPEC_CONFLICT 0

단 다음 항목은 외부 검증이 완료될 때까지
명시적인 BLOCKED_EXTERNAL 상태를 허용한다.

- verified Saju Day Pillar epoch
- Ephemeris provider/license/data selection

BLOCKED_EXTERNAL은 구현자가 임의 결정해서는 안 된다.

해당 의존성이 필요한 production task만 차단한다.


==================================================
56. DOCUMENT UPDATE TASK
==================================================

이제 위 결정을 기준으로
docs/01~10을 실제로 수정해주세요.

추가로:

docs/contracts/
OpenAPI / JSON Schema

초안을 생성해주세요.

중요:

이번 작업에서는
PHP/WordPress/Frontend/DB migration 코드는
수정하지 마세요.

문서와 contract 파일만 수정합니다.

기존 문서의 의미 없는 전체 재작성보다는
필요한 부분을 정확하게 동기화해주세요.

변경 후:

1. docs/01~10 전체 재검토
2. OpenAPI/Schema와 문서 비교
3. contradiction 검사
4. SPEC_CONFLICT 검사
5. git diff --check
6. 코드 파일 변경 여부 검사

를 수행해주세요.

완료 보고 형식:

SPEC FREEZE UPDATE REPORT

A. Documents Changed
B. Contracts Added
C. Decisions Applied
D. Contradictions Remaining
E. SPEC_CONFLICT Remaining
F. BLOCKED_EXTERNAL
G. API Contract Validation
H. Privacy Contract Validation
I. Score Contract Validation
J. Code Files Changed
K. Can Contract Freeze?
PASS / FAIL

PASS인 경우에도 Git commit/push는 하지 말고
결과를 먼저 보고해주세요.