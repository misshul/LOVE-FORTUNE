# LOVE FORTUNE
# SC-07 GUARDRAIL v2
# FIXED-DENOMINATOR MARGINAL CONTRIBUTION
# SYNTHETIC RE-VALIDATION

SAJU CATALOG V1 SYNTHETIC SIMULATION REPORT가
기존 SC-07 Guardrail의 구조적 문제로 FAIL했습니다.

실패 원인은 Saju signedValue 자체가 아니라
normalized weighted mean에서 Feature weight를 줄이면
분자와 분모가 동시에 감소하여
single-feature impact를 제한할 수 없는 구조입니다.

이번 작업에서는 아래 SC-07 v2 proposal을
메모리에서만 적용하여
동일 simulation을 다시 검증합니다.


IMPORTANT:

파일 수정 금지.
docs/contracts 수정 금지.
production code 수정 금지.
commit/push 금지.

signedValue 변경 금지.
Saju mapping 변경 금지.
Astrology mapping 변경 금지.

이번 단계는 Guardrail Contract Validation only입니다.


==================================================
1. SC-07 v2 OBJECTIVE
==================================================

하나의 scoring Feature가
Category Score에 미치는 최대 절대 영향:

20 score points


즉:

singleFeatureImpactPoints <= 20


이 계약은 유지한다.

변경하는 것은
그 조건을 만족시키는 알고리즘이다.


==================================================
2. PRE-GUARDRAIL EFFECTIVE WEIGHT
==================================================

기존 pipeline 유지:

baseWeight
× ruleWeight
× pairWeight
× featureConfidence

및 기존:

individual cap
outer aggregate cap

등을 모두 적용한 뒤의 weight를:

effectiveWeight_i

라고 한다.


Guardrail 입력 시점은 기존 순서를 유지한다.


==================================================
3. FIXED BASELINE DENOMINATOR
==================================================

Category의 Guardrail 적용 전
usable scoring Feature들의 weight 합:

W0 = SUM(effectiveWeight_i)


W0 > 0 이어야 한다.


W0는 Guardrail 처리 전체 동안
절대로 변경하지 않는다.


중요:

adjustedWeight 합을
새 denominator로 사용하지 않는다.


==================================================
4. ORIGINAL CATEGORY SCORE
==================================================

Guardrail이 하나도 작동하지 않을 경우
기존 Category Score와 완전히 동일해야 한다.


originalSignal =

SUM(
 effectiveWeight_i × signedValue_i
)
/
W0


originalRawScore =

50
+
50 × originalSignal


==================================================
5. FEATURE IMPACT DEFINITION
==================================================

Feature i의 절대 최대 score contribution:

featureImpactPoints_i =

50
×
(
 effectiveWeight_i
 ×
 abs(signedValue_i)
 /
 W0
)


Guardrail threshold:

20


따라서:

featureImpactPoints_i <= 20

이면 weight 변경 없음.


==================================================
6. DIRECT WEIGHT CAP
==================================================

featureImpactPoints_i > 20인 경우:


maxAllowedWeight_i =

0.4
×
W0
/
abs(signedValue_i)


adjustedWeight_i =

min(
 effectiveWeight_i,
 maxAllowedWeight_i
)


signedValue_i = 0이면:

Guardrail 불필요
adjustedWeight_i = effectiveWeight_i


binary search 사용 금지.


==================================================
7. FINAL CATEGORY SCORE
==================================================

adjustedSignal =

SUM(
 adjustedWeight_i × signedValue_i
)
/
W0


중요:

denominator는:

SUM(adjustedWeight)

가 아니다.


반드시:

original W0


이다.


finalRawScore =

50
+
50 × adjustedSignal


필요한 기존 0..100 clamp는
기존 계약대로 이후 적용한다.


==================================================
8. NEUTRAL MASS SEMANTICS
==================================================

Guardrail로 감소한 weight는
다른 Feature의 상대 가중치를
자동으로 증가시키지 않는다.


즉 감소된 부분은
neutral contribution으로 취급한다.


이것이 fixed denominator를 사용하는 이유다.


Guardrail이 강한 Feature를 약화시켰다고 해서
남아 있는 Feature의 영향력이
자동 확대되면 안 된다.


==================================================
9. CONFIDENCE / COVERAGE
==================================================

Guardrail은 score moderation이다.

Evidence availability/confidence를
재정의하지 않는다.


따라서 기존:

eligibleEvidence
availableEvidence
usableEvidence
coverage
categoryResultConfidence
resultConfidence

공식은 변경하지 않는다.


Guardrail adjustedWeight를:

coverage
resultConfidence

계산에 사용하지 않는다.


featureConfidence도 변경하지 않는다.


==================================================
10. STATUS
==================================================

Guardrail이 weight를 감소시켰다는 이유만으로:

INSUFFICIENT_DATA

로 바꾸지 않는다.


INSUFFICIENT_DATA는 기존:

eligible
available
usable

계약으로만 판단한다.


Guardrail은
evidence 존재 자체를 삭제하는 기능이 아니다.


==================================================
11. FEATURE EXCLUSION
==================================================

SC-07 v2 정상 처리에서는
Guardrail 때문에 Feature를
weight=0으로 강제 제외하지 않는다.


정상적인:

W0 > 0
abs(signedValue) <= 1

조건에서 maxAllowedWeight는
항상 계산 가능하다.


따라서 기존 Guardrail의:

weight reaches 0 → feature excluded

처리는 폐기한다.


==================================================
12. BINARY SEARCH
==================================================

기존:

binary search max 32 iterations
global passes max 32

Guardrail 알고리즘은 폐기한다.


SC-07 v2는 closed-form direct calculation이다.


따라서 정상 입력에서:

SCORE_GUARDRAIL_UNSATISFIED

가 발생해서는 안 된다.


해당 error code는:

legacy/deprecated

로 표시하거나

Guardrail calculation의
비정상 invariant failure 전용으로
재정의하는 방안을 보고한다.


아직 실제 Schema/docs는 수정하지 않는다.


==================================================
13. SINGLE FEATURE EXPECTATIONS
==================================================

effectiveWeight = 1
W0 = 1
confidence = 1

일 때 검사:


signedValue +0.20
→ Score 60


+0.40
→ Score 70


+0.45
→ adjusted
→ Score 70


+0.70
→ adjusted
→ Score 70


+0.90
→ adjusted
→ Score 70


-0.20
→ Score 40


-0.40
→ Score 30


-0.45
→ adjusted
→ Score 30


-0.70
→ adjusted
→ Score 30


-0.90
→ adjusted
→ Score 30


즉 single Feature의 score impact는
항상 최대 ±20.


==================================================
14. MULTI FEATURE PROPERTY
==================================================

각 Feature i에 대해:

50 ×
(
 adjustedWeight_i
 × abs(signedValue_i)
 / W0
)
<= 20


를 반드시 만족한다.


여러 Feature가 동일 방향이면
Category 전체 score는
20점 이상 이동할 수 있다.


이것은 허용한다.


Guardrail 목적은:

Category 전체 ±20 cap

이 아니라

Single Feature 최대 영향 ±20

이기 때문이다.


==================================================
15. NO-REGRESSION PROPERTY
==================================================

모든 Feature가 이미:

featureImpactPoints <= 20

이면:

adjustedWeight_i = effectiveWeight_i


따라서:

new categoryScore
=
old categoryScore


가 정확히 성립해야 한다.


==================================================
16. RE-RUN SAJU SIMULATION
==================================================

직전:

SAJU CATALOG V1 SYNTHETIC SIMULATION

과 동일한 합성 입력을 사용한다.


반드시 다시 실행:

Day Stem 10×10
Day Branch 12×12
Element Complement
Yin-Yang
unknown time
context invariance
single feature
multi feature
57,600 combination simulation
Astrology + Saju combined


입력 fixture를
Guardrail 결과에 맞춰 변경하지 않는다.


==================================================
17. REQUIRED GUARDRAIL METRICS
==================================================

보고:

Guardrail evaluated category count

Guardrail activated category count

adjusted Feature count

Feature exclusion count

SCORE_GUARDRAIL_UNSATISFIED count

INSUFFICIENT_DATA transition count


Expected:

Feature exclusion due Guardrail = 0

SCORE_GUARDRAIL_UNSATISFIED = 0

INSUFFICIENT_DATA transition due Guardrail = 0


==================================================
18. MARGINAL CONTRIBUTION VALIDATION
==================================================

모든 adjusted Feature에 대해:

individual impact <= 20


위반:

0 expected


boundary:

exactly 20

허용.


==================================================
19. SCORE DISTRIBUTION
==================================================

직전 simulation과 동일하게 보고:

Overall:

mean
median
p5
p25
p75
p95
min
max


각 Category:

mean
median
p5
p25
p75
p95
min
max


추가:

<20
>80
<10
>90
0
100

비율.


직전 결과와 비교하여
Guardrail v2 때문에 어떤 변화가 발생했는지
Delta table을 출력한다.


==================================================
20. SAJU CATEGORY RISKS
==================================================

특히 다시 확인:

ATTRACTION

이 더 이상 전부 50으로
neutralized되지 않는지.


EMOTION의 positive signal이
일괄 삭제되지 않는지.


PASSION sparse 상태.


SUPPORT high-score concentration.


COMMUNICATION=0은
기존 승인 상태이므로
FAIL 이유가 아님.


==================================================
21. ASTROLOGY REGRESSION
==================================================

중요:

SC-07은 공통 Score Contract이므로
Astrology에도 영향을 줄 수 있다.


승인된 Astrology 109 rules에 대해
synthetic category fixture를 다시 실행.


확인:

- schema/catalog 변경 없음
- no-guardrail category 결과 동일
- guardrail 대상 Feature만 moderation
- Feature exclusion 0
- unresolved error 0
- category status 의미 유지


Astrology Catalog 자체의 signedValue를
변경하지 않는다.


==================================================
22. CONFIDENCE REGRESSION
==================================================

Guardrail 전후:

coverage 동일
resultConfidence 동일

이어야 한다.


Guardrail은 score만 moderation한다.


Context 추가/삭제도 기존과 같이:

score 제외
coverage 제외
confidence 제외

유지.


==================================================
23. HARD FAIL
==================================================

다음 중 하나라도 발생하면 FAIL:


individual Feature impact > 20

Guardrail Feature exclusion > 0

SCORE_GUARDRAIL_UNSATISFIED > 0

NaN / division by zero

score outside allowed range before defined clamp handling

coverage changed by Guardrail

resultConfidence changed by Guardrail

context changes scoring result

pair symmetry violation

duplicate scoring owner


==================================================
24. REVIEW CONDITIONS
==================================================

자동 FAIL은 아니지만 보고:


Overall score distribution severe change

Category starvation

Category excessive concentration

Guardrail activation rate

Adjusted Feature rate

Support/Attraction/Passion distribution


임의 PASS threshold를
새로 만들지 않는다.


필요하면:

NEEDS_PRODUCT_DECISION

으로 판정.


==================================================
25. OUTPUT
==================================================

제목:

SC-07 GUARDRAIL V2 SYNTHETIC VALIDATION REPORT


A. Guardrail Contract Result

PASS
NEEDS_PRODUCT_DECISION
FAIL


B. Mathematical Property Validation

C. Single Feature Result

D. Multi Feature Result

E. 57,600 Saju Simulation Result

F. Guardrail Metrics

G. Marginal Contribution Violations

H. Feature Exclusions

I. Unresolved Errors

J. Confidence/Coverage Regression

K. Saju Category Distribution

L. Saju Score Distribution

M. Astrology Regression

N. Context Invariance

O. Symmetry / Double Counting

P. Previous vs V2 Delta

Q. Product Risks Remaining

R. Numeric Changes Required

Expected:
NONE

S. SC-07 v2 Approval Recommendation

READY_TO_APPROVE
NEEDS_PRODUCT_DECISION
FAIL


T. Saju Catalog Approval Recommendation

READY_TO_APPROVE
NEEDS_PRODUCT_DECISION
FAIL


U. Score Engine Readiness

V. External Blockers


==================================================
26. RESTRICTIONS
==================================================

파일 수정 금지.

docs/contracts 수정 금지.

production code 수정 금지.

commit/push 금지.

Saju signedValue 변경 금지.

Astrology signedValue 변경 금지.

weight table 변경 금지.

threshold 20 변경 금지.

새 scoring rule 추가 금지.

simulation 결과를 맞추기 위해
fixture를 변경하지 않는다.