# LOVE FORTUNE
# CONTRACT FREEZE FINAL DECISION v2

Status: APPROVED
Purpose:
남아 있는 SPEC_CONFLICT SC-01 ~ SC-10을 해소하고
구현 가능한 Contract를 최종 동결한다.

중요:

- 이번 작업은 docs 및 docs/contracts만 수정한다.
- PHP / WordPress / DB migration / Frontend 코드는 수정하지 않는다.
- 기존 사양에 없는 Saju/Astrology 해석 규칙을 새로 창작하지 않는다.
- 미등록 rule은 production calculation에서 사용하지 않는다.
- 기존 문서에 존재하는 rule만 공식 Catalog로 정규화한다.
- 아래 결정 이후 구현자가 선택해야 하는 계산/프로토콜 옵션이 남아서는 안 된다.

==================================================
SC-01
CONFIDENCE / COVERAGE
==================================================

다음 세 개를 완전히 분리한다.

1. featureConfidence
2. coverage
3. resultConfidence


--------------------------------------------------
1. featureConfidence
--------------------------------------------------

범위:

0.0 <= featureConfidence <= 1.0

Feature 자체의 계산 신뢰도다.

정확히 계산 가능한 feature:

1.0

Unknown birth time 등으로 candidate가 복수 존재하는 feature:

candidateAgreement를 사용한다.


동일 의미의 candidate가 N개 존재하고,
각 candidate의 해당 feature signedValue가 vi일 때:

candidateAgreement =
1 - (
    max(vi) - min(vi)
) / 2

signedValue 범위가 -1 ~ +1이므로
candidateAgreement도 0 ~ 1 범위다.

최종:

featureConfidence =
baseConfidence × candidateAgreement

baseConfidence 기본값:

1.0

candidate가 하나면:

candidateAgreement = 1.0


--------------------------------------------------
2. eligible evidence
--------------------------------------------------

eligibleEvidence란:

현재 category/rule catalog상
해당 입력 조건에서 이론적으로 평가 가능한 Feature다.

예:

출생시간이 있을 때만 계산 가능한 Hour Pillar feature는
시간 미입력에서도 eligible에는 포함된다.

단:

현재 service/config에서 아예 지원하지 않는 rule은
eligible에 포함하지 않는다.


--------------------------------------------------
3. available evidence
--------------------------------------------------

availableEvidence란:

실제 입력으로 계산 결과가 생성된 eligible Feature다.


--------------------------------------------------
4. coverage
--------------------------------------------------

단순 feature 개수가 아니라
weight 기준으로 계산한다.

coverage =

Σ availableBaseWeight
────────────────────
Σ eligibleBaseWeight

0 ~ 1 clamp.

여기서 weight는:

baseWeight × ruleWeight × pairWeight

까지 사용한다.

featureConfidence는 coverage 분자에 넣지 않는다.

즉:

coverage는
"얼마나 많은 evidence 구조를 확보했는가"

featureConfidence는
"확보된 evidence 자체가 얼마나 확실한가"

를 분리한다.


eligible weight 합 = 0인 경우:

coverage = 0


--------------------------------------------------
5. resultConfidence
--------------------------------------------------

Category resultConfidence:

Σ(effectiveWeight × featureConfidence)
──────────────────────────────────────
Σ(effectiveWeight)

단 effectiveWeight 자체에
featureConfidence를 이미 곱하지 않는다.

Confidence 집계용 weight:

confidenceWeight =
baseWeight
× ruleWeight
× pairWeight

따라서:

categoryResultConfidence =

Σ(confidenceWeight × featureConfidence)
───────────────────────────────────────
Σ(confidenceWeight)

그 후 coverage를 반영:

resultConfidence =
coverage × categoryResultConfidence


available evidence = 0이면:

resultConfidence = 0


--------------------------------------------------
6. Overall resultConfidence
--------------------------------------------------

available category만 사용한다.

overallResultConfidence =

Σ(
  categoryResultConfidence
  × categoryWeight
)
──────────────────────────
Σ(available categoryWeight)


INSUFFICIENT_DATA category는 제외한다.


--------------------------------------------------
7. Period resultConfidence
--------------------------------------------------

Daily:

해당 daily calculation의
source confidence weighted average.

Monthly:

유효 Daily resultConfidence 평균.

Yearly:

유효 Monthly resultConfidence 평균.

Weekly:

유효 Daily resultConfidence 평균.


--------------------------------------------------
8. Tie-break confidence
--------------------------------------------------

best/caution tie에서 사용하는 confidence는:

해당 date/period의 resultConfidence

로 고정한다.


==================================================
SC-02
RULE CATALOG
==================================================

새로운 Saju/Astrology rule을 임의로 작성하지 않는다.

공식 Rule Catalog는:

현재 docs/02 및 docs/03에 이미 명시된
계산 규칙만 정규화하여 작성한다.

신규 파일:

docs/contracts/rules/
saju-rules.json
astrology-rules.json
daily-rules.json
category-weights.json


각 rule 필수 필드:

ruleId
source
enabled
categoryMappings
baseWeight
ruleWeight
pairWeight
requiresBirthTime
contextOnly
version


categoryMappings:

[
  {
    "category": "EMOTION",
    "direction": "POSITIVE",
    "signedValue": 1.0
  }
]


direction별 기본 signedValue:

POSITIVE = 1.0
NEGATIVE = -1.0
NEUTRAL = 0.0

MIXED:

반드시 catalog에서
category별 signedValue를 직접 명시한다.

범위:

-1.0 ~ +1.0


MIXED에 default value는 없다.

MIXED인데 signedValue가 없으면:

contract validation failure.


--------------------------------------------------
Weight mapping
--------------------------------------------------

기존 spec의:

planet weight
aspect weight
pair weight
rule weight

등을 다음처럼 정규화한다.

baseWeight:
Feature 자체의 기본 중요도

ruleWeight:
해당 rule/aspect의 중요도

pairWeight:
행성 pair 또는 Saju relation pair 보정


공식:

preConfidenceWeight =
baseWeight
× ruleWeight
× pairWeight

score용 effectiveWeight:

effectiveWeight =
preConfidenceWeight
× featureConfidence


기존 문서에 대응값이 하나만 존재하면:

baseWeight = 기존 값
ruleWeight = 1.0
pairWeight = 1.0

두 개가 존재하면
의미에 따라 각각 명시적으로 배치한다.

문서에 없는 weight를 새로 창작하지 않는다.


--------------------------------------------------
Unknown-time candidate aggregation
--------------------------------------------------

candidate가 복수일 경우:

동일 ruleId + subject + category + period

기준으로 묶는다.

signedValue는:

candidate signedValue의 arithmetic mean

rawValue가 numeric이면:

candidate rawValue의 arithmetic mean

rawValue가 비수치라면:

candidate별 값을 metadata.candidates에 보존하고
canonical rawValue = null

featureConfidence는
SC-01의 candidateAgreement를 사용한다.

candidate 간 feature 존재 여부가 다른 경우:

존재한 candidate 수 / 전체 candidate 수

를 availabilityRatio라고 한다.

최종:

featureConfidence =
candidateAgreement
× availabilityRatio


==================================================
SC-03
OUTER PLANET CAP
==================================================

Outer Planet:

Uranus
Neptune
Pluto

만 해당한다.

Jupiter/Saturn은 outer cap 대상이 아니다.


개별 0.50 제한은:

preConfidenceWeight

즉:

baseWeight × ruleWeight × pairWeight

에 적용한다.

공식:

preConfidenceWeight =
min(
    0.50,
    baseWeight × ruleWeight × pairWeight
)

그 후:

effectiveWeight =
preConfidenceWeight × featureConfidence


그리고 Category aggregation 직전에
전체 Outer 15% cap을 적용한다.

순서:

base/rule/pair
→ individual 0.50 cap
→ featureConfidence
→ outer 15% aggregate cap
→ category aggregation


==================================================
SC-04
FEATURE ID / FEATURE WIRE FORMAT
==================================================

Feature canonical representation을 확정한다.


--------------------------------------------------
subject
--------------------------------------------------

subject enum:

PERSON_A
PERSON_B
PAIR

궁합 Feature 기본 subject:

PAIR

개인 natal/transit context:

PERSON_A 또는 PERSON_B


--------------------------------------------------
period
--------------------------------------------------

period는 object로 고정한다.

Lifetime:

{
  "type": "LIFETIME"
}

Daily:

{
  "type": "DAY",
  "date": "YYYY-MM-DD",
  "timezone": "Asia/Tokyo"
}

Weekly:

{
  "type": "WEEK",
  "startDate": "YYYY-MM-DD",
  "endDate": "YYYY-MM-DD",
  "timezone": "Asia/Tokyo"
}

Monthly:

{
  "type": "MONTH",
  "year": 2026,
  "month": 9,
  "timezone": "Asia/Tokyo"
}

Yearly:

{
  "type": "YEAR",
  "year": 2026,
  "timezone": "Asia/Tokyo"
}


--------------------------------------------------
rawValue
--------------------------------------------------

rawValue 타입:

number | string | boolean | null

array/object는 rawValue에서 금지한다.

복합 데이터는 metadata에 넣는다.


--------------------------------------------------
metadata
--------------------------------------------------

metadata는 allowlist 방식이다.

허용 key:

planetA
planetB
aspect
orb
pillar
element
relation
candidateCount
availabilityRatio
sampleCount
referenceId
ruleVariant

그 외 key:

schema validation failure

필요한 metadata 추가는
contract version 변경으로 처리한다.


--------------------------------------------------
featureId
--------------------------------------------------

featureId 입력 canonical fields:

ruleId
subject
category
period
source
metadata의 identity-relevant fields

signedValue나 confidence는
featureId 생성에 포함하지 않는다.


identity-relevant metadata:

planetA
planetB
aspect
pillar
relation
referenceId
ruleVariant


canonical JSON:

- UTF-8
- key lexicographic sort
- whitespace 없음
- Unicode normalization NFC
- numeric은 JSON canonical decimal
- null field는 제거하지 않고 schema대로 포함


featureId:

"ft_" +
lowercase hex SHA-256(canonical JSON)의 앞 24 bytes

즉 hex 48 characters.

사용자 추적 용도로 사용하지 않는다.


==================================================
SC-05
DAILY SAMPLE CONTRACT
==================================================

Daily sample timezone:

targetTimezone


공식 sample local times:

06:00
12:00
18:00
23:00

총 4개.


각 sample에서
Saju Daily/Astrology Transit signal을 계산한다.


--------------------------------------------------
Missing sample
--------------------------------------------------

특정 sample 계산 불가:

해당 sample만 제외한다.

유효 sample >= 1:

남은 sample reweight.

0:

source unavailable.


--------------------------------------------------
Daily source signal
--------------------------------------------------

각 source별:

meanSignal =
유효 sample signed signal arithmetic mean

peakSignal =
절댓값이 가장 큰 sample signal


signedPeak 정의:

abs(signal)가 최대인 값.

동률이면:

1. 더 이른 local sample time
2. 그래도 동일하면 numeric value 그대로

즉 deterministic.


초기 v1 source signal:

sourceDailySignal =
0.75 × meanSignal
+
0.25 × peakSignal

clamp -1 ~ +1


--------------------------------------------------
DST nonexistent local time
--------------------------------------------------

해당 local sample 시간이 존재하지 않으면:

그 timezone에서
해당 nominal local time 이후 최초 유효 instant 사용.


--------------------------------------------------
DST ambiguous local time
--------------------------------------------------

같은 local time이 두 instant에 대응하면:

UTC instant가 더 이른 쪽을 선택한다.

즉:

earlier instant

로 정의한다.

"earlier offset"이라는 모호한 표현은 제거한다.


==================================================
SC-06
PERIOD / STATUS CONTRACT
==================================================

--------------------------------------------------
Supported calendar
--------------------------------------------------

Gregorian Calendar.

지원 계산 날짜:

1900-01-01
~
2099-12-31

단 Ephemeris/Saju external provider의
실제 verified coverage가 더 좁으면
그 범위 밖은 UNSUPPORTED_DATE.

이를 silent fallback하지 않는다.


--------------------------------------------------
daily-range
--------------------------------------------------

startDate/endDate inclusive.

최대:

31 calendar days.

startDate > endDate:

422 INVALID_DATE_RANGE

31일 초과:

422 DATE_RANGE_TOO_LARGE


--------------------------------------------------
Weekly
--------------------------------------------------

ISO week:

Monday 00:00
~
Sunday 23:59:59...

targetTimezone 기준.


--------------------------------------------------
Empty period
--------------------------------------------------

유효 Daily 결과가 0:

score = null
status = INSUFFICIENT_DATA
confidence = 0


--------------------------------------------------
Lifetime null propagation
--------------------------------------------------

Daily는 Lifetime Score가 null이면:

dailyScore = null

Daily/Weekly/Monthly/Yearly 모두:

status = INSUFFICIENT_DATA

Lifetime Score 없는 상태에서
기간 점수를 독립적으로 생성하지 않는다.


--------------------------------------------------
Daily Status
--------------------------------------------------

dailyDelta 기준:

delta <= -12:
VERY_LOW

-12 < delta <= -6:
LOW

-6 < delta < 6:
STABLE

6 <= delta < 12:
GOOD

delta >= 12:
VERY_GOOD

period data 자체가 부족하면:

INSUFFICIENT_PERIOD_DATA


--------------------------------------------------
Monthly/Yearly status
--------------------------------------------------

최종 score는 Lifetime status와 동일한
score band를 사용한다.

Period-specific 상태는 별도 필드:

trendStatus

STABLE
NOTICEABLE
SIGNIFICANT


--------------------------------------------------
Best/Caution count
--------------------------------------------------

Weekly:

bestDays max 2
cautionDays max 2

Monthly:

bestDays max 5
cautionDays max 5

Yearly:

bestMonths max 3
cautionMonths max 3


--------------------------------------------------
Tie
--------------------------------------------------

순서:

Score canonical unrounded internal value
→ resultConfidence
→ chronological order


API에는 canonical 4-decimal score를 반환하지만
정렬은 rounding 전 internal value를 사용한다.


--------------------------------------------------
Aggregation rounding
--------------------------------------------------

Daily internal score:
full precision

Monthly:
Daily full precision 값으로 평균

Yearly:
Monthly full precision 값으로 평균

API 직렬화 시에만 4자리 HALF_UP.


==================================================
SC-07
DAILY CATEGORY / ACTION / GUARDRAIL
==================================================

--------------------------------------------------
Category daily delta
--------------------------------------------------

각 category도 Overall과 동일한 dailySignal을
category-specific evidence로 계산한다.

categoryDailyDelta:

18 × categoryDailySignal

categoryDailyScore:

clamp(
  lifetimeCategoryScore
  + categoryDailyDelta,
  0,
  100
)


category evidence가 없으면:

categoryDailyDelta = 0
periodStatus = INSUFFICIENT_PERIOD_DATA


--------------------------------------------------
Action
--------------------------------------------------

Action은 점수를 수정하지 않는다.

Action Recommendation은
Core deterministic result에서 파생되는
별도 recommendation layer다.

즉:

Action adjustment = 0

Action이 LOVE SCORE를 변경하는 구조를 제거한다.


Action confidence:

추천에 사용된 Feature들의
resultConfidence 평균.

evidence가 없으면
Action을 생성하지 않는다.


--------------------------------------------------
Weekly slope
--------------------------------------------------

유효 Daily Score를 y,
날짜 index를 x = 0,1,2...

Ordinary Least Squares linear regression의
slope를 사용한다.

단위:

score points / day

유효 날짜 < 2:

slope = null


--------------------------------------------------
Complexity intensity
--------------------------------------------------

v1에서는 Score 연산에 사용하지 않는다.

필요 시 UI/context metadata로만 제공한다.

공식 numeric score modifier에서 제거한다.


--------------------------------------------------
Single-feature 20 point guardrail
--------------------------------------------------

목적:

하나의 Feature가 Category Score를
과도하게 변경하지 못하게 한다.


각 Feature f에 대해:

1. 모든 Feature 포함 Category Raw Score 계산
2. f 하나만 제외한 Category Raw Score 계산
3. 두 Score 차이 계산

abs(scoreWith - scoreWithout) <= 20

이어야 한다.


20을 초과하면
해당 Feature effectiveWeight를
binary search 방식으로 감소시켜

difference <= 20.0000

이 되도록 한다.

최대 32 iterations.

적용 순서:

individual caps
→ confidence
→ outer cap
→ single-feature guardrail
→ category aggregation


guardrail은
모든 Feature에 적용한다.

외행성만의 규칙이 아니다.


==================================================
SC-08
API REQUIRED / DEFAULT / ENUM / WIRE
==================================================

--------------------------------------------------
Person input
--------------------------------------------------

필수:

birthDate
birthLocationId

선택:

birthTime

birthTime 미입력:

null

gender:

v1 calculation에 사용하지 않는다.

따라서 Public Calculation API에서
gender 필드를 제거한다.

UI가 향후 표시 목적으로 받아도
Core API에는 보내지 않는다.


--------------------------------------------------
targetTimezone
--------------------------------------------------

Calculation API:

required

Client가 반드시 보낸다.

service defaultTimezone은
UI/default config용으로만 사용한다.

공식 service defaultTimezone:

Asia/Tokyo


--------------------------------------------------
Locale
--------------------------------------------------

선택.

default:

ko-KR

지원:

ko-KR
ja-JP
en-US

지원하지 않는 locale:

422 UNSUPPORTED_LOCALE


--------------------------------------------------
Celebrity
--------------------------------------------------

Celebrity API에는
raw birth profile을 반환하지 않는다.

Public Celebrity DTO:

celebrityId
displayName
displayNameLocalized
publicBirthPrecision
availableFeatures
imageReference
sourceSummary

정확한 출생시각/위치 정보는
공개 데이터 정책에 따라 내부 reference로 사용 가능하나
API에서 불필요하게 노출하지 않는다.


--------------------------------------------------
Reference endpoints
--------------------------------------------------

GET /locations
GET /locations/{locationId}
GET /service-config
GET /celebrities
GET /celebrities/{celebrityId}

Public read-only.


--------------------------------------------------
Admin
--------------------------------------------------

Admin REST namespace:

/wp-json/love-fortune/v1/admin

Mutation은:

POST
PUT
DELETE

GET mutation 금지.

모든 Admin endpoint:

authenticated WordPress admin
capability
nonce

필수.


--------------------------------------------------
Unknown fields
--------------------------------------------------

Public calculation request:

additionalProperties = false


--------------------------------------------------
Birth Date
--------------------------------------------------

YYYY-MM-DD

1900-01-01 ~ 2099-12-31


--------------------------------------------------
Birth Time
--------------------------------------------------

HH:MM

24-hour

seconds 미지원.


==================================================
SC-09
SIGNED TOKEN / AI EXECUTION
==================================================

--------------------------------------------------
Token structure
--------------------------------------------------

형식:

base64url(header)
.
base64url(payload)
.
base64url(signature)

JWT library dependency를 의미하지 않는다.

자체 명시된 LF signed-context format이다.


Header:

{
  "alg": "HS256",
  "typ": "LFIC",
  "kid": "key-id",
  "v": 1
}


서명 대상 bytes:

ASCII(
  base64url(headerCanonicalJson)
  + "."
  + base64url(payloadCanonicalJson)
)

Signature:

HMAC-SHA256(signingInput, secret)


base64url:

RFC 4648 URL-safe
padding "=" 제거.


JSON canonicalization:

SC-04와 동일.


--------------------------------------------------
Key rotation
--------------------------------------------------

kid 필수.

현재 key + 직전 key를 verify 가능.

새 token 발급은 current key만 사용.

Secret은 environment/config secret store에서 관리.

wp_options plaintext 금지.


--------------------------------------------------
Payload binding
--------------------------------------------------

필수:

contextVersion
purpose
locale
issuedAt
expiresAt
engineVersions
scoreVersion
configVersion
result
evidence


purpose:

INTERPRETATION


locale:

AI 요청 locale과 반드시 일치해야 한다.


TTL:

300 seconds.


--------------------------------------------------
Expiry
--------------------------------------------------

now >= expiresAt:

422 INTERPRETATION_CONTEXT_EXPIRED

서명 실패:

422 INVALID_INTERPRETATION_CONTEXT

purpose mismatch:

422 INVALID_INTERPRETATION_PURPOSE

locale mismatch:

422 INTERPRETATION_LOCALE_MISMATCH


--------------------------------------------------
Replay
--------------------------------------------------

v1에서는 strict one-time replay prevention을 제공하지 않는다.

대신:

TTL 5분
AI rate limit
payload size limit

으로 abuse를 제한한다.

Token 자체를 DB에 저장하지 않는다.


--------------------------------------------------
AI timeout
--------------------------------------------------

총 provider budget:

8 seconds


provider 호출 timeout:

6 seconds


retry:

network/5xx에 한해 최대 1회

단 전체 8초 budget을 초과하지 않는다.


--------------------------------------------------
Repair
--------------------------------------------------

Schema validation 실패 시:

최대 1회 repair request.

전체 timeout budget 내에서만 실행.

두 번째 실패:

deterministic fallback.


--------------------------------------------------
Fallback
--------------------------------------------------

다음은 HTTP 200:

AI OFF
AI timeout
provider outage
schema validation failure
evidence validation failure

Core result 기반 deterministic fallback을
정상 반환할 수 있는 경우.


Response metadata:

interpretationMode:

AI
FALLBACK


fallback조차 생성할 수 없는
서버 내부 장애만:

503 INTERPRETATION_UNAVAILABLE


--------------------------------------------------
Deterministic comparison projection
--------------------------------------------------

계산 결과 deterministic test에서 제외:

requestId
generatedAt
issuedAt
expiresAt
signedInterpretationContext

나머지 canonical calculation payload는
exact compare.


==================================================
SC-10
HTTP / SECURITY / LOGGING
==================================================

--------------------------------------------------
KB
--------------------------------------------------

1 KB = 1024 bytes


--------------------------------------------------
Payload enforcement
--------------------------------------------------

limit은 decompressed request body 기준.

Core:

32768 bytes

Interpretation:

65536 bytes


압축 body:

v1 Public API에서 지원하지 않는다.

Content-Encoding이 존재하면:

415 UNSUPPORTED_CONTENT_ENCODING


--------------------------------------------------
Content-Type
--------------------------------------------------

허용:

application/json
application/json; charset=utf-8

charset은 대소문자 비구분.

다른 parameter:

415 UNSUPPORTED_MEDIA_TYPE


--------------------------------------------------
Rate limit source
--------------------------------------------------

우선:

trusted reverse proxy가 전달한
검증된 client IP

그 외:

direct remote address


X-Forwarded-For를
무조건 신뢰하지 않는다.

Trusted proxy allowlist 설정이 있는 경우에만 사용한다.


--------------------------------------------------
Window
--------------------------------------------------

Fixed window:

600 seconds

Core:

60 / 600 sec

AI:

10 / 600 sec


--------------------------------------------------
Retry-After
--------------------------------------------------

integer seconds.

현재 window 종료까지 남은 초.


--------------------------------------------------
Rate key
--------------------------------------------------

HMAC(
  short-lived infrastructure secret,
  normalized source IP
)

raw IP를 rate-limit store key로 저장하지 않는다.

Rate-limit key TTL:

최대 3600 sec


--------------------------------------------------
Admin auth errors
--------------------------------------------------

미인증:

401 ADMIN_AUTH_REQUIRED

권한 없음:

403 ADMIN_CAPABILITY_REQUIRED

nonce 없음/실패:

403 ADMIN_NONCE_INVALID


--------------------------------------------------
Operational log
--------------------------------------------------

allowlist:

timestamp
requestId
endpoint
method
httpStatus
durationMs
errorCode
engineVersion
scoreVersion
configVersion


금지 field가 발견되면
logging sanitizer가 제거해야 한다.


--------------------------------------------------
Security audit log
--------------------------------------------------

Admin mutation만 기록.

허용:

timestamp
adminUserId
action
resourceType
resourceId
result
requestId

payload 원문 저장 금지.


보존:

Security/Admin Audit = 90 days


--------------------------------------------------
Application log
--------------------------------------------------

30 days


--------------------------------------------------
Backups
--------------------------------------------------

Application log backup:

최대 30 days

Security audit backup:

최대 90 days

만료 후 삭제.

Raw Birth Data,
request body,
AI prompt/output,
signed context는
backup에도 존재해서는 안 된다.


==================================================
EXTERNAL BLOCKERS
==================================================

다음 두 항목만 BLOCKED_EXTERNAL로 유지한다.

1. Verified Saju Day Pillar Epoch
2. Ephemeris Provider / License / Data Selection

이는 SPEC_CONFLICT가 아니다.

Contract는:

"검증 완료 전 production engine을 활성화하지 않는다"

로 확정한다.

구현자가 임의 값을 사용해서는 안 된다.


==================================================
FINAL FREEZE CONDITIONS
==================================================

위 SC-01 ~ SC-10 결정을
docs/01~10 및 docs/contracts에 적용한다.

이후 다시 전체 검증한다.

PASS 조건:

1. SC-01 ~ SC-10 모두 RESOLVED
2. SPEC_CONFLICT = 0
3. Cross-spec contradiction = 0
4. Schema validation PASS
5. OpenAPI validation PASS
6. 모든 valid example PASS
7. 모든 invalid example expected failure PASS
8. Score arithmetic tests PASS
9. Feature canonicalization examples PASS
10. Token signing/verification contract example PASS
11. Period boundary examples PASS
12. DST examples PASS
13. Privacy contract 일치
14. API contract 일치
15. Code changes NONE


BLOCKED_EXTERNAL은 다음 두 개만 허용:

SAJU_DAY_PILLAR_EPOCH
EPHEMERIS_PROVIDER


이 두 외부 의존성 때문에
전체 Specification Freeze를 FAIL로 처리하지 않는다.

대신:

Contract Freeze = PASS
Production Engine Readiness = BLOCKED_EXTERNAL

로 분리한다.


==================================================
TASK
==================================================

이번 작업에서:

docs/01~10
docs/contracts/**

만 수정해주세요.

PHP
WordPress plugin
Frontend
DB migration

은 수정하지 마세요.

변경 후 다음 형식으로 보고해주세요.


FINAL CONTRACT FREEZE REPORT

A. SC-01 Status
B. SC-02 Status
C. SC-03 Status
D. SC-04 Status
E. SC-05 Status
F. SC-06 Status
G. SC-07 Status
H. SC-08 Status
I. SC-09 Status
J. SC-10 Status

K. SPEC_CONFLICT Remaining
L. Cross-Spec Contradictions Remaining
M. OpenAPI Validation
N. JSON Schema Validation
O. Valid/Invalid Examples
P. Score Arithmetic Validation
Q. Canonicalization Validation
R. Signed Context Validation
S. Privacy Validation
T. Code Files Changed

U. BLOCKED_EXTERNAL
- SAJU_DAY_PILLAR_EPOCH
- EPHEMERIS_PROVIDER

V. Contract Freeze
PASS / FAIL

W. Production Engine Readiness
READY / BLOCKED_EXTERNAL

commit/push는 하지 말고
결과를 먼저 보고해주세요.