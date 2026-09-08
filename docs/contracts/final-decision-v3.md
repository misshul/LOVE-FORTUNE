# LOVE FORTUNE
# FINAL REMAINING CONTRACT DECISIONS v3

현재 FINAL FREEZE VERIFICATION 결과에서 남은
SC-01, SC-06, SC-07 및 validation-report 불일치를
아래 기준으로 최종 확정합니다.

중요:
- PHP / WordPress / Frontend / DB 코드는 수정하지 않는다.
- docs/01~10 및 docs/contracts/**만 수정한다.
- 아래 결정 외의 새로운 계산 규칙은 추가하지 않는다.


==================================================
SC-01
COVERAGE / ZERO-CONFIDENCE / DAILY SOURCE CONFIDENCE
==================================================

기존의 "E=0" 표현을 더 이상 모호하게 사용하지 않는다.

다음을 구분한다.

1. eligibleEvidence
2. availableEvidence
3. usableEvidence


--------------------------------------------------
eligibleEvidence
--------------------------------------------------

현재 rule/catalog 및 입력 조건상
이론적으로 평가 대상이 되는 evidence.


--------------------------------------------------
availableEvidence
--------------------------------------------------

실제 계산 결과 객체가 생성된 eligible evidence.

featureConfidence = 0인 Feature도
availableEvidence일 수 있다.


--------------------------------------------------
usableEvidence
--------------------------------------------------

다음을 모두 만족하는 evidence:

- availableEvidence
- featureConfidence > 0
- preConfidenceWeight > 0


Score aggregation에는 usableEvidence만 사용한다.


--------------------------------------------------
coverage
--------------------------------------------------

coverage는 "구조적으로 얼마나 계산되었는가"를 나타낸다.

따라서 featureConfidence와 독립적이다.

coverage =

Σ available preConfidenceWeight
────────────────────────────────
Σ eligible preConfidenceWeight

범위 0 ~ 1.

eligible weight 합이 0이면:

coverage = 0


중요:

available evidence가 존재하지만
모든 featureConfidence가 0인 경우에도
coverage는 0보다 클 수 있다.

이는 정상이다.

예:

coverage = 0.80
resultConfidence = 0.00

가능하다.


--------------------------------------------------
Zero-confidence 처리
--------------------------------------------------

usableEvidence가 0이면:

categoryScore = 50.0000
resultConfidence = 0
status = INSUFFICIENT_DATA

단 coverage는 위 공식으로 계산된 값을 유지한다.

기존 문서의:

"evidence=0이면 coverage=0"

규칙은 삭제한다.

정확히는:

eligible evidence가 0인 경우에만
coverage=0이다.


--------------------------------------------------
resultConfidence
--------------------------------------------------

usableEvidence가 1개 이상이면:

categoryResultConfidence =

Σ(preConfidenceWeight × featureConfidence)
──────────────────────────────────────────
Σ(preConfidenceWeight)

여기서 합산 대상은 availableEvidence다.

그 후:

resultConfidence =
coverage × categoryResultConfidence


availableEvidence가 0:

resultConfidence = 0


--------------------------------------------------
Daily sample confidence
--------------------------------------------------

각 Daily source는 공식 sample 4개:

06:00
12:00
18:00
23:00

를 기준으로 한다.

각 sample confidence:

0 ~ 1

계산 불가능한 sample은:

confidence = 0

으로 집계한다.


sourceDailyConfidence =

Σ(4개 sampleConfidence)
───────────────────────
4


즉 누락 sample은 confidence를 자연스럽게 낮춘다.


--------------------------------------------------
Daily overall resultConfidence
--------------------------------------------------

Saju와 Astrology source가 모두 available:

dailyResultConfidence =

0.50 × sajuDailyConfidence
+
0.50 × astrologyDailyConfidence


한 source만 available:

dailyResultConfidence =
해당 sourceDailyConfidence


둘 다 unavailable:

dailyResultConfidence = 0


Score 계산에서 source weight를 reweight하는 것과
confidence 집계 규칙은 동일한 availability 판단을 사용한다.


==================================================
SC-06
VALID DAY / WEEKLY STATUS / PERIOD TREND
==================================================

--------------------------------------------------
Valid Daily for Period Aggregation
--------------------------------------------------

Weekly / Monthly / Yearly 집계에 포함 가능한 Daily는
다음을 모두 만족해야 한다.

1. dailyScore != null
2. Core calculation error가 없음
3. lifetimeScore != null
4. dailyPeriodStatus != INSUFFICIENT_PERIOD_DATA
5. 날짜가 supported calculation range 안에 있음


따라서:

period evidence가 하나도 없어
dailyScore = lifetimeScore,
dailyDelta = 0

으로 fallback된 Daily는
화면에는 표시할 수 있지만
Weekly/Monthly/Yearly 통계 평균에는 포함하지 않는다.

이유:
기간 신호가 없는 lifetime fallback이
월/연간 평균을 왜곡하는 것을 방지한다.


--------------------------------------------------
Weekly Score
--------------------------------------------------

weeklyScore:

해당 ISO week의 valid Daily Score arithmetic mean.

valid Daily = 0:

weeklyScore = null
status = INSUFFICIENT_DATA
resultConfidence = 0


--------------------------------------------------
Weekly / Monthly / Yearly Score Status
--------------------------------------------------

모든 Period Score의 score status는
Lifetime과 동일한 band를 사용한다.

0 <= score < 45:
CAUTION

45 <= score < 60:
BALANCED

60 <= score < 75:
GOOD

75 <= score < 85:
VERY_GOOD

85 <= score <= 100:
EXCELLENT

score = null:

INSUFFICIENT_DATA


--------------------------------------------------
Period Delta
--------------------------------------------------

Period의 trend 판단 기준:

periodDelta =
periodScore - lifetimeScore


periodScore 또는 lifetimeScore가 null이면:

periodDelta = null
trendStatus = INSUFFICIENT_DATA


--------------------------------------------------
Trend Status
--------------------------------------------------

abs(periodDelta) < 3:

STABLE


3 <= abs(periodDelta) < 8:

NOTICEABLE


abs(periodDelta) >= 8:

SIGNIFICANT


Weekly / Monthly / Yearly 모두 동일한 기준을 사용한다.


--------------------------------------------------
Trend Direction
--------------------------------------------------

별도 필드로 정의한다.

abs(periodDelta) < 3:

STABLE


periodDelta >= 3:

UP


periodDelta <= -3:

DOWN


periodDelta = null:

UNKNOWN


즉:

trendStatus는 변화 크기,
trendDirection은 변화 방향이다.


--------------------------------------------------
Weekly slope
--------------------------------------------------

SC-07에서 정의된 OLS slope:

score points / day

를 유지한다.

단 slope는 trendStatus 판정에 사용하지 않는다.

slope는 설명용 secondary metric이다.


--------------------------------------------------
Period confidence
--------------------------------------------------

Weekly:

valid Daily resultConfidence의 arithmetic mean


Monthly:

valid Daily resultConfidence의 arithmetic mean


Yearly:

valid Monthly resultConfidence의 arithmetic mean


valid input이 0:

0


--------------------------------------------------
Aggregation source values
--------------------------------------------------

모든 period aggregation은
API용 4자리 반올림값이 아니라
internal full-precision score를 사용한다.

최종 API serialization 단계에서만
HALF_UP 4자리 반올림한다.


==================================================
SC-07
ACTION CONFIDENCE
==================================================

기존 문서의:

"Feature.resultConfidence 평균"

표현은 잘못된 참조이므로 제거한다.

Feature에는:

featureConfidence

가 존재한다.


--------------------------------------------------
Action Evidence
--------------------------------------------------

Action Recommendation은
1개 이상의 evidenceRefs를 가져야 한다.

evidenceRefs는 실제 Structured Feature의
featureId만 참조할 수 있다.


--------------------------------------------------
Action Confidence
--------------------------------------------------

Action confidence는
참조된 Feature들의 weighted feature confidence다.

공식:

actionConfidence =

Σ(preConfidenceWeight × featureConfidence)
──────────────────────────────────────────
Σ(preConfidenceWeight)


참조된 evidence 중:

preConfidenceWeight <= 0

인 Feature는 제외한다.


유효 evidence가 0이면:

Action Recommendation을 생성하지 않는다.


--------------------------------------------------
Action Score 영향
--------------------------------------------------

Action은 LOVE SCORE를 변경하지 않는다.

Action adjustment = 0

유지.


--------------------------------------------------
AI와 Action
--------------------------------------------------

AI는 Action confidence를 변경하지 않는다.

AI는 deterministic Action Recommendation을
문장으로 설명할 수만 있다.


==================================================
SC-02 REGISTER STATUS
==================================================

빈 활성 Rule Catalog와 Contract Freeze를 분리한다는
결정은 최종 승인된 것으로 문서화한다.

따라서 SC-02 status:

RESOLVED

로 변경한다.


Catalog 상태:

Contract:
FROZEN

Active scoring rules:
0

Score Engine Readiness:
BLOCKED_CATALOG

Blocker:

SCORING_RULE_CATALOG_APPROVAL


빈 Catalog 자체는
SPEC_CONFLICT가 아니다.


==================================================
VALIDATION REPORT SYNCHRONIZATION
==================================================

기존:

docs/contracts/validation-report.md

가 이전 계약 상태를 기록하고 있으므로
현재 산출물과 동기화한다.

과거 숫자를 유지해서
현재 상태와 충돌하게 두지 않는다.

validation-report에는 최소:

- validation timestamp
- OpenAPI operation count
- JSON Schema count
- valid example count
- invalid example count
- Score arithmetic validation result
- canonicalization validation result
- signed context validation result
- period/DST validation result
- SC-01 ~ SC-10 status
- remaining SPEC_CONFLICT
- Contract Freeze
- Score Engine Readiness
- Production Engine Readiness
- BLOCKED items

를 현재 실제 검증 결과로 기록한다.

중요:

검증 숫자는 문서에 하드코딩하기 전에
실제 validation script 결과에서 가져온다.

임의 숫자를 작성하지 않는다.


==================================================
EXPECTED FINAL STATUS
==================================================

SC-01:
RESOLVED

SC-02:
RESOLVED

SC-03:
RESOLVED

SC-04:
RESOLVED

SC-05:
RESOLVED

SC-06:
RESOLVED

SC-07:
RESOLVED

SC-08:
RESOLVED

SC-09:
RESOLVED

SC-10:
RESOLVED


SPEC_CONFLICT Remaining:

0


Cross-Spec Contradictions Remaining:

0


다만 아래 readiness blocker는 유지한다.

Score Engine Readiness:

BLOCKED_CATALOG

Reason:

SCORING_RULE_CATALOG_APPROVAL


Production Engine Readiness:

BLOCKED_EXTERNAL

Reasons:

SAJU_DAY_PILLAR_EPOCH
EPHEMERIS_PROVIDER


이는 Contract Freeze 실패 사유가 아니다.


==================================================
TASK
==================================================

위 결정을:

docs/01~10
docs/contracts/**

에 동기화해주세요.

PHP / WordPress / Frontend / DB 파일은
수정하지 마세요.

수정 완료 후 실제 validation을 다시 실행해주세요.

다음 항목을 확인해주세요.

1. SC-01 ~ SC-10 모두 RESOLVED
2. SPEC_CONFLICT = 0
3. Cross-Spec contradiction = 0
4. OpenAPI validation PASS
5. JSON Schema validation PASS
6. valid examples PASS
7. invalid examples expected rejection PASS
8. Score arithmetic PASS
9. canonicalization PASS
10. signed context PASS
11. period/DST PASS
12. Privacy/API validation PASS
13. validation-report가 현재 결과와 일치
14. PHP/WordPress/Frontend/DB code changes NONE
15. git diff --check PASS


최종 보고:

FINAL CONTRACT FREEZE REPORT

A. SC-01 ~ SC-10 Status
B. SPEC_CONFLICT Remaining
C. Cross-Spec Contradictions Remaining
D. OpenAPI Validation
E. JSON Schema Validation
F. Examples Validation
G. Score Arithmetic
H. Canonicalization
I. Signed Context
J. Period/DST Validation
K. Privacy/API Validation
L. Validation Report Synchronization
M. Code Files Changed
N. git diff --check

O. Contract Freeze
PASS / FAIL

P. Score Engine Readiness
READY / BLOCKED_CATALOG

Q. Production Engine Readiness
READY / BLOCKED_EXTERNAL

R. Remaining Blockers

commit/push는 하지 말고
결과만 보고해주세요.