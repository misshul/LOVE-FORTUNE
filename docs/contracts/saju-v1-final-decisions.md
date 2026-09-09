# LOVE FORTUNE
# SAJU SCORING RULE CATALOG v1
# FINAL CONTRACT DECISIONS
# READ-ONLY VALIDATION

SAJU CATALOG V1 PROPOSAL REPORT를 검토했고
S-01 ~ S-08에 대한 제품 계약 결정을 아래와 같이 확정한다.

IMPORTANT:

이번 단계는 아직 READ-ONLY다.

파일 수정 금지.
docs/contracts 수정 금지.
PHP/WordPress 수정 금지.
commit/push 금지.

아래 결정을 메모리에서 적용하여
계약 충돌과 synthetic simulation 가능 여부만 검증한다.


==================================================
0. DESIGN PRINCIPLE
==================================================

v1 목표:

- 동일 전통 관계의 중복 scoring 방지
- Day Pillar 중심의 설명 가능한 궁합
- Whole-chart cross-product 방지
- 불확실한 전통 요소를 억지 score로 변환하지 않음
- Astrology와 Saju가 8개 category를 동일 비율로 채울 필요 없음

전통 명리학 규칙 판정과
LOVE FORTUNE signedValue는 계속 구분한다.


==================================================
1. S-01 — DECIMAL SIGNED VALUE SCHEMA
==================================================

RESOLVED.

Saju categoryMapping도
Astrology v1과 동일한 decimal sign 계약을 사용한다.

POSITIVE:
0 < signedValue <= 1

NEGATIVE:
-1 <= signedValue < 0

NEUTRAL:
signedValue = 0

direction과 signedValue 부호 불일치는 INVALID.

기존 Saju unit-sign only Schema는
향후 Repository Application 단계에서 동기화한다.

현재 파일은 수정하지 않는다.


==================================================
2. SCORING OWNER DECISION
==================================================

가장 중요한 결정:

동일 Day Stem pair의
오행 관계와 Ten Gods를 동시에 scoring하지 않는다.


v1 Score Owner:

DAY_MASTER_RELATION
ELEMENT_SUPPORT
ELEMENT_CONTROL

TEN_GODS_RELATION은:

contextOnly = true
score contribution = 0


Ten Gods mapping proposal은 삭제하지 않는다.

향후:

v1.1 scoring 후보
AI interpretation context
explanation evidence

로 보존한다.


Ten Gods가 context-only이므로
Saju 자체에서 COMMUNICATION category scoring mapping이
0개여도 정상이다.

Astrology와 Saju가
각각 모든 8개 category를 채워야 한다는 요구는 없다.


==================================================
3. DAY MASTER MUTUAL EXCLUSION
==================================================

Day Master pair에는 다음 셋 중
정확히 하나만 선택한다.

same element
→ DAY_MASTER_RELATION

generation
→ ELEMENT_SUPPORT

control
→ ELEMENT_CONTROL


동일 pair에서 위 세 scoring family가
둘 이상 생성되면 INVALID.


Mappings 유지:


DAY_MASTER_RELATION / SAME

SUPPORT +0.40
HARMONY +0.30


ELEMENT_SUPPORT / GENERATION

SUPPORT +0.70
HARMONY +0.40
LONG_TERM +0.25


ELEMENT_CONTROL / CONTROL

PASSION +0.15
HARMONY -0.45
STABILITY -0.35


PAIR scoring 결과는 A/B 교환에 대해 대칭이다.

다만 실제 generation/control 방향은
evidence metadata/context에 보존 가능하다.

A→B, B→A라는 이유만으로
동일 scoring contribution을 두 번 생성하지 않는다.


==================================================
4. STEM COMBINATION PRECEDENCE
==================================================

Day Stem pair가 다음 天干合에 해당하면:

甲己
乙庚
丙辛
丁壬
戊癸

STEM_COMBINATION이
해당 Day Stem pair의 scoring owner가 된다.


Precedence:

1. STEM_COMBINATION
2. otherwise:
   DAY_MASTER_RELATION
   or ELEMENT_SUPPORT
   or ELEMENT_CONTROL


즉 天干合이 검출된 동일 Day Stem pair에 대해:

STEM_COMBINATION score 생성
+
coarse Day Master score 생성

을 동시에 하지 않는다.


Ten Gods는 어느 경우에도 v1 score에는 기여하지 않는다.


STEM_COMBINATION mapping:

ATTRACTION +0.70
HARMONY +0.45
LONG_TERM +0.25


합화는 별도 score 없음.

transformationStatus:

UNASSESSED


==================================================
5. S-05 — PILLAR SCOPE
==================================================

RESOLVED.

v1 pairwise relationship rules는
Day Pillar 중심으로 제한한다.


DAY_MASTER_RELATION:
Day Stem A × Day Stem B only


ELEMENT_SUPPORT:
Day Stem A × Day Stem B only


ELEMENT_CONTROL:
Day Stem A × Day Stem B only


TEN_GODS_RELATION:
Day Stem A ↔ Day Stem B only
context only


STEM_COMBINATION:
Day Stem A × Day Stem B only


DAY_BRANCH_RELATION:
Day Branch A × Day Branch B only


BRANCH_COMBINATION:
Day Branch A × Day Branch B only


BRANCH_CLASH:
Day Branch A × Day Branch B only


다음과 같은 whole-chart cross-product는
v1에서 수행하지 않는다:

all visible stems A × all visible stems B
all visible branches A × all visible branches B


Year / Month / Hour pillar pairwise synastry scoring:

v1 Deferred.


==================================================
6. BRANCH SCORING PRECEDENCE
==================================================

Day Branch pair scoring은 다음 순서다.


1. 六合 검출

→ BRANCH_COMBINATION score


2. 六合가 아니고 沖 검출

→ BRANCH_CLASH score


3. 위 두 scoring named relation이 없음

→ DAY_BRANCH_RELATION fallback


즉 v1 scoring precedence:

LIUHE
>
CLASH
>
ELEMENT FALLBACK


동일 Day Branch pair에
둘 이상의 scoring owner를 생성하지 않는다.


==================================================
7. LIUHE
==================================================

Pairs:

子丑
寅亥
卯戌
辰酉
巳申
午未


Mapping:

HARMONY +0.75
EMOTION +0.55
LONG_TERM +0.45


==================================================
8. CLASH
==================================================

Pairs:

子午
丑未
寅申
卯酉
辰戌
巳亥


Mapping:

PASSION +0.20
HARMONY -0.70
STABILITY -0.60


==================================================
9. DAY BRANCH FALLBACK
==================================================

六合 / 沖 scoring relation이 없을 때만 적용.


Same primary element:

EMOTION +0.55
STABILITY +0.35


Generating primary element relation:

EMOTION +0.45
SUPPORT +0.35
LONG_TERM +0.25


Controlling primary element relation:

EMOTION -0.35
STABILITY -0.30
HARMONY -0.20


Unknown branch/element:

정상적인 Other로 처리하지 않는다.

INVALID / UNAVAILABLE.


==================================================
10. S-06 — CONTEXT RELATION AND FALLBACK
==================================================

RESOLVED.

v1에서는 context-only relation:

刑
害
破

가 존재하더라도

DAY_BRANCH_RELATION fallback scoring을
자동 억제하지 않는다.


Fallback scoring을 억제하는 것은
v1 scoring owner인:

六合
沖

뿐이다.


이유:

刑/害/破는 v1 score owner가 아니며
reference table과 적용 조건도 아직 완전히 승인되지 않았다.


따라서:

六合 → scoring owner
沖 → scoring owner

刑/害/破 → context only
fallback과 공존 가능


향후 v1.1에서
刑/害/破가 scoring owner가 되면
precedence contract를 새 버전으로 재검토한다.


==================================================
11. TEN GODS CONTEXT CONTRACT
==================================================

TEN_GODS_RELATION:

contextOnly = true
scoreContribution = 0


Gender-specific spouse-star interpretation:

금지.


Ten Gods lookup은 전통 구조대로:

same element:
 same polarity → 比肩
 opposite polarity → 劫財


Day Master produces other:
 same polarity → 食神
 opposite polarity → 傷官


Day Master controls other:
 same polarity → 偏財
 opposite polarity → 正財


Other controls Day Master:
 same polarity → 偏官
 opposite polarity → 正官


Other produces Day Master:
 same polarity → 偏印
 opposite polarity → 正印


PERSON_A 관점과
PERSON_B 관점의 Ten God은
서로 다를 수 있다.


그러나 이는 두 개의 score contribution을 뜻하지 않는다.

Context evidence로만 보존한다.


기존 제안 mapping 값은
향후 scoring 후보 자료로 보존하되
v1 score aggregation에는 넣지 않는다.


==================================================
12. CONTEXT-ONLY FAMILY STATUS
==================================================

v1 context-only:

TEN_GODS_RELATION
HIDDEN_STEM_RELATION
PUNISHMENT
HARM
DESTRUCTION


이들은 scoring Feature가 아니다.

score aggregation 제외.
coverage denominator 제외.
usableEvidence scoring 집계 제외.
Guardrail 대상 제외.


기존 family baseWeight는
historical/reference config로 유지 가능하지만
context score weight로 사용하지 않는다.


==================================================
13. S-07 — CONTEXT EVIDENCE STRUCTURE
==================================================

RESOLVED.

Scoring Feature와 Context Evidence를 분리한다.


Scoring:

Feature


Non-scoring:

ContextEvidence


ContextEvidence는 절대로
Feature 배열에 섞어서
confidence=0 등의 방식으로 scoring 제외를 흉내 내지 않는다.


향후 Schema:

context-evidence.schema.json

을 별도로 정의하는 방향을 채택한다.


최소 개념 필드:

contextEvidenceId
ruleId
source
subject
period
confidence
metadata


category / signedValue / effectiveWeight는
context evidence에 필수값이 아니다.


ContextEvidence:

score aggregation 제외
coverage denominator 제외
guardrail 제외
Action score evidenceRefs 제외


AI interpretation에서 사용하려면
향후 별도:

contextEvidenceRefs

계약으로 전달한다.


기존 scoring Feature evidenceRefs와
혼합하지 않는다.


==================================================
14. TRANSFORMATION STATUS
==================================================

STEM_COMBINATION의:

transformationStatus = UNASSESSED

는 합 검출과 실제 합화를 구분하기 위한
정식 상태다.


v1 enum:

UNASSESSED


다른 상태:

TRANSFORMED
NOT_TRANSFORMED

를 v1에서 임의 생성하지 않는다.


Repository Application 단계에서
정식 closed metadata/schema 위치를 정의한다.


==================================================
15. CONTEXT TABLES NOT YET APPROVED
==================================================

다음은 context-only지만
reference table / execution condition이
아직 최종 승인되지 않았다.

HIDDEN_STEM_RELATION
PUNISHMENT
HARM
DESTRUCTION


따라서 현재 단계에서:

임의 pair 생성 금지.
임의 negative 의미 생성 금지.


Family contract는 존재하되
실제 context detector activation은
reference table 승인까지 Deferred 가능하다.


破의 4-pair / 6-pair 문헌 차이는
research note로 유지한다.

SPEC_CONFLICT로 만들지 않는다.


==================================================
16. S-02 — ELEMENT COMPLEMENT
==================================================

RESOLVED.

v1 ELEMENT_COMPLEMENT는
seasonal/month-strength engine과 분리된
compatibility-specific count normalization을 사용한다.


이 family에 한해서만:

Visible Heavenly Stem = 1 token

Earthly Branch primary element = 1 token


각 token의 weight는 동일:

1


Hidden Stems:
제외


Season/month strength:
ELEMENT_COMPLEMENT에서는 제외


Yongshin:
제외


즉 기존 일반 Saju engine의
seasonal strength 계약을 삭제하지 않는다.

ELEMENT_COMPLEMENT v1만
명시적 exception이다.


==================================================
17. ELEMENT COMPLEMENT INPUT
==================================================

시간 있음:

1 person = 8 visible tokens


시간 없음:

1 person = 6 visible tokens


missing Hour Pillar를
임의 생성하지 않는다.


각 사람별 known token만 사용하여
element distribution을 normalize한다.


pA[e]:

A의 known token 내 비율


pB[e]:

B의 known token 내 비율


각 p의 합:

1


balance(p):

1 - SUM(abs(p[e] - 0.2)) / 1.6


joint[e]:

(pA[e] + pB[e]) / 2


complementGain:

balance(joint)
-
(
 balance(A) + balance(B)
) / 2


수학적 expected range:

0 <= complementGain <= 0.50


==================================================
18. ELEMENT COMPLEMENT BAND
==================================================

Full precision으로 판정.


gain < 0.05:

Feature 없음


0.05 <= gain < 0.15:

SUPPORT +0.30
HARMONY +0.20


0.15 <= gain < 0.30:

SUPPORT +0.60
HARMONY +0.40


gain >= 0.30:

SUPPORT +0.90
HARMONY +0.60


API serialized rounded value가 아니라
internal full precision을 사용한다.


==================================================
19. S-08 — WHOLE-CHART UNKNOWN TIME
==================================================

RESOLVED.


ELEMENT_COMPLEMENT
YIN_YANG_BALANCE

는 known-token result를 사용한다.


expected complete tokens:

A = 8
B = 8

combined expected:

16


observedTokenRatio:

(
 knownTokenCountA
 +
 knownTokenCountB
)
/
16


Examples:

both time known:
16/16 = 1


one unknown:
14/16 = 0.875


both unknown:
12/16 = 0.75


이 값을 whole-chart aggregate Feature의:

baseConfidence

로 사용한다.


후보 agreement가 별도로 존재하지 않는 경우:

candidateAgreement = 1


따라서 기본:

featureConfidence =
observedTokenRatio


Day-boundary 등 승인된 candidate ambiguity가
동시에 존재할 경우에는
기존 candidate agreement 계약을 적용하고:

featureConfidence =

observedTokenRatio
×
candidateAgreement

로 결합한다.


기존:

coverage
eligibleEvidence
availableEvidence
usableEvidence

공식은 변경하지 않는다.


observedTokenRatio를
coverage에 다시 곱하지 않는다.

즉 double penalty 금지.


==================================================
20. DAY-PILLAR UNKNOWN TIME
==================================================

Day Stem / Day Branch rule:

requiresBirthTime = false


단:

23:30 day-boundary policy에 의해
날짜 후보가 발생하는 경우

기존 candidate aggregation 계약 적용.


임의 noon:
금지

임의 midnight:
금지

임의 hour pillar:
금지


==================================================
21. YIN-YANG BALANCE AGGREGATION
==================================================

RESOLVED.

개인별 score를 각각 만든 뒤 평균하지 않는다.


두 사람의 known visible tokens를
하나의 compatibility pool로 합산한다.


combined:

yangCount
yinCount


knownTokenCount:

yangCount + yinCount


balance:

1 -
abs(yangCount - yinCount)
/
knownTokenCount


knownTokenCount > 0 필수.


invalid:

knownTokenCount = 0
negative count
count mismatch


invalid를:

balance = 0

으로 바꿔 negative score를 만들지 않는다.


==================================================
22. YIN-YANG BANDS
==================================================

Full precision으로 판정.


balance >= 0.75:

HARMONY +0.45
STABILITY +0.25


0.50 <= balance < 0.75:

HARMONY +0.20


0.25 <= balance < 0.50:

HARMONY -0.15


balance < 0.25:

HARMONY -0.35
STABILITY -0.20


featureConfidence는
whole-chart observedTokenRatio 계약 사용.


==================================================
23. REQUIRES BIRTH TIME
==================================================

v1 scoring families:

STEM_COMBINATION
DAY_MASTER_RELATION
ELEMENT_SUPPORT
ELEMENT_CONTROL
BRANCH_COMBINATION
BRANCH_CLASH
DAY_BRANCH_RELATION
ELEMENT_COMPLEMENT
YIN_YANG_BALANCE


모두:

requiresBirthTime = false


하지만:

false ≠ confidence 1 guaranteed


unknown-time confidence 정책은
위 계약을 따른다.


==================================================
24. FINAL V1 SCORING FAMILIES
==================================================

Scoring:

1. STEM_COMBINATION
2. DAY_MASTER_RELATION
3. ELEMENT_SUPPORT
4. ELEMENT_CONTROL
5. BRANCH_COMBINATION
6. BRANCH_CLASH
7. DAY_BRANCH_RELATION
8. ELEMENT_COMPLEMENT
9. YIN_YANG_BALANCE


Total scoring families:

9


Context-only:

1. TEN_GODS_RELATION
2. HIDDEN_STEM_RELATION
3. PUNISHMENT
4. HARM
5. DESTRUCTION


Total context-only families:

5


기존 14 family 이름은 모두 유지한다.


==================================================
25. SCORING PRECEDENCE SUMMARY
==================================================

DAY STEM:


if STEM_COMBINATION:
    score STEM_COMBINATION only

else if same element:
    score DAY_MASTER_RELATION

else if generation:
    score ELEMENT_SUPPORT

else if control:
    score ELEMENT_CONTROL


TEN_GODS:
context only


--------------------------------

DAY BRANCH:


if LIUHE:
    score BRANCH_COMBINATION

else if CLASH:
    score BRANCH_CLASH

else:
    score DAY_BRANCH_RELATION fallback


刑 / 害 / 破:
context only
fallback suppression 없음


==================================================
26. CATEGORY POLICY
==================================================

Saju가 모든 8 category를
독립적으로 충족할 필요 없음.


Ten Gods context-only 적용 결과
Saju COMMUNICATION scoring = 0

이어도 정상.


전체 LOVE FORTUNE category score는
Astrology + Saju available evidence contract에 따라
계산한다.


Saju category가 없다는 이유로
가짜 COMMUNICATION rule을 만들지 않는다.


==================================================
27. GUARDRAIL
==================================================

기존 SC-07 20-point guardrail을
이번 계약 결정에서 변경하지 않는다.


그러나 Synthetic Simulation에서 반드시 측정:

- guardrail invocation count
- feature weight reduction count
- feature exclusion count
- category becoming INSUFFICIENT_DATA count
- rule별 guardrail 발생 빈도


특히:

signedValue

+0.70
+0.75
+0.90
-0.70

등을 가진 sparse category를 집중 검사.


현재 단계에서
새 threshold나 새 guardrail 수식을
임의로 만들지 않는다.


Simulation 결과가 구조적 문제를 보이면
그때 SC-07 재검토 여부를 별도 결정한다.


==================================================
28. EXTERNAL BLOCKER
==================================================

SAJU_DAY_PILLAR_EPOCH:

BLOCKED_EXTERNAL 유지.


하지만 Catalog Contract 검증과
Runtime day pillar calculation readiness는
분리한다.


Epoch가 미확정이라는 이유로
이번 Contract Decision Validation을
FAIL 처리하지 않는다.


EPHEMERIS_PROVIDER 역시
Astrology production blocker로 별도 유지.


==================================================
29. REQUIRED MEMORY VALIDATION
==================================================

다음을 메모리에서 검증:


A. 14 family 이름 유지

B. Scoring family = 9

C. Context-only family = 5

D. Day Stem scoring owner가 pair당 최대 1개

E. Day Branch scoring owner가 pair당 최대 1개

F. STEM_COMBINATION precedence

G. Ten Gods scoring contribution = 0

H. Context Evidence가 coverage에 포함되지 않음

I. Context Evidence가 guardrail에 포함되지 않음

J. Visible token equal weight

K. Complement gain range 0..0.50

L. Complement band boundaries

M. Combined Yin-Yang aggregation

N. Unknown-time ratios:
   1
   0.875
   0.75

O. Whole-chart confidence double penalty 없음

P. requiresBirthTime scoring null = 0

Q. Decimal signedValue sign consistency

R. No new unapproved Xing/Harm/Po score

S. No Yongshin/Shinsal/12-stage score

T. Existing Contract Freeze regressions


==================================================
30. NEXT SIMULATION PLAN
==================================================

이번 validation이 PASS하면
다음 단계는 Repository Application이 아니다.


먼저:

SAJU CATALOG V1 SYNTHETIC SIMULATION

을 수행한다.


개인정보 사용 금지.


필수 simulation:

- all 10 Day Stems × 10 Day Stems
- all 12 Day Branches × 12 Day Branches
- A/B swap symmetry
- 5 Heavenly Stem combinations
- all Liuhe
- all Clash
- fallback cases
- both known / one unknown / both unknown time
- Complement boundaries
- Yin-Yang boundaries
- sparse category
- featureConfidence=0
- guardrail behavior
- Astrology + Saju combined category availability


이번 단계에서는 simulation을
실제로 실행하지 않아도 된다.

실행 가능한 정확한 simulation plan과
expected invariants를 보고한다.


==================================================
31. OUTPUT
==================================================

제목:

SAJU CATALOG V1 CONTRACT DECISION VALIDATION REPORT


보고 항목:

A. Contract Decision Result
PASS / FAIL

B. Scoring Families
Expected: 9

C. Context-Only Families
Expected: 5

D. Day Stem Precedence Validation

E. Day Branch Precedence Validation

F. Ten Gods Context Validation

G. Element Complement Validation

H. Yin-Yang Validation

I. Unknown-Time Validation

J. Context Evidence Validation

K. Category Distribution After Final Precedence

L. Double Counting Remaining

M. Schema Changes Required

N. Contract Conflicts Remaining

O. Numeric Decisions Remaining

P. Guardrail Risk Review

Q. Synthetic Simulation Readiness

READY_TO_SIMULATE
NEEDS_CONTRACT_DECISION
FAIL

R. Score Engine Readiness

S. External Blockers


==================================================
32. RESTRICTIONS
==================================================

파일 수정 금지.

docs/contracts 수정 금지.

production code 수정 금지.

commit/push 금지.

새 signedValue 발명 금지.

새 전통 scoring rule 발명 금지.

승인되지 않은 context pair table 발명 금지.