# LOVE FORTUNE
# SC-07 GUARDRAIL v2 + SAJU CATALOG v1
# REPOSITORY APPLICATION

최종 승인:

SC-07 Guardrail v2:
APPROVED

Saju Catalog v1:
APPROVED

Saju category 분포 특성은
v1 의도된 product behavior로 승인한다.

이번 작업의 목적은:

1. SC-07 Guardrail v2 계약
2. Saju Scoring Rule Catalog v1
3. ContextEvidence 계약
4. 관련 Schema / Examples / Validation

을 실제 docs/contracts 저장소에 반영하는 것이다.


==================================================
IMPORTANT
==================================================

수정 가능:

docs/02_FORTUNE_ENGINE_SPEC.md
docs/03_SCORE_SPEC.md
docs/08_AI_PROMPT_SPEC.md
docs/contracts/**
docs/contracts/rules/**
docs/contracts/schemas/**
docs/contracts/examples/**
docs/contracts/validation-report.md

필요한 validation script / contract document 추가 허용.


수정 금지:

PHP
WordPress source
Frontend
Database implementation
Docker/runtime config

production code 변경 금지.

commit/push 금지.


==================================================
1. SC-07 V2 SOURCE OF TRUTH
==================================================

SC-07 GUARDRAIL V2 SYNTHETIC VALIDATION REPORT
및 승인된 직전 SC-07 v2 계약을 Source of Truth로 사용한다.


Guardrail 목적:

single Feature maximum absolute category-score impact
=
20 points


==================================================
2. SC-07 V2 FORMULA
==================================================

Guardrail 직전 usable scoring Feature의
effectiveWeight 합:

W0 = SUM(effectiveWeight_i)


W0 > 0


각 Feature:

featureImpactPoints_i =

50 *
(
 effectiveWeight_i
 * abs(signedValue_i)
 / W0
)


impact <= 20:

adjustedWeight_i = effectiveWeight_i


impact > 20:

maxAllowedWeight_i =

0.4
*
W0
/
abs(signedValue_i)


adjustedWeight_i =

min(
 effectiveWeight_i,
 maxAllowedWeight_i
)


signedValue=0:

adjustedWeight = effectiveWeight


==================================================
3. FIXED DENOMINATOR
==================================================

final signal:

SUM(
 adjustedWeight_i * signedValue_i
)
/
W0


중요:

SUM(adjustedWeight_i)

를 denominator로 사용하지 않는다.


감소된 weight는
neutral contribution으로 취급한다.


==================================================
4. REMOVE LEGACY GUARDRAIL SEARCH
==================================================

기존:

binary search max 32
global passes max 32
weight=0 exclusion

정상 Guardrail path에서 제거.


정상 입력에서:

Feature exclusion due Guardrail = 0


SCORE_GUARDRAIL_UNSATISFIED:

legacy/deprecated 상태로 문서화한다.

새 invariant error 의미로
같은 code를 재사용하지 않는다.


==================================================
5. CONFIDENCE / COVERAGE
==================================================

Guardrail은 score moderation only.


다음 계산에는
pre-guardrail evidence 계약 사용:

eligibleEvidence
availableEvidence
usableEvidence
coverage
categoryResultConfidence
resultConfidence


adjustedWeight는
coverage/confidence에 사용하지 않는다.


Guardrail로:

INSUFFICIENT_DATA 전환 금지.


==================================================
6. SAJU FAMILY CLASSIFICATION
==================================================

기존 14 family 이름 유지.


Scoring = 9:

STEM_COMBINATION
DAY_MASTER_RELATION
ELEMENT_SUPPORT
ELEMENT_CONTROL
BRANCH_COMBINATION
BRANCH_CLASH
DAY_BRANCH_RELATION
ELEMENT_COMPLEMENT
YIN_YANG_BALANCE


Context-only = 5:

TEN_GODS_RELATION
HIDDEN_STEM_RELATION
PUNISHMENT
HARM
DESTRUCTION


==================================================
7. DAY STEM PRECEDENCE
==================================================

Day Stem A × Day Stem B only.


if STEM_COMBINATION:
    score STEM_COMBINATION only

else if same element:
    score DAY_MASTER_RELATION

else if generation:
    score ELEMENT_SUPPORT

else if control:
    score ELEMENT_CONTROL


Ten Gods:
context only


Year/Month/Hour cross-product:
v1 금지.


==================================================
8. DAY MASTER MAPPINGS
==================================================

DAY_MASTER_RELATION / SAME:

SUPPORT +0.40
HARMONY +0.30


ELEMENT_SUPPORT / GENERATION:

SUPPORT +0.70
HARMONY +0.40
LONG_TERM +0.25


ELEMENT_CONTROL / CONTROL:

PASSION +0.15
HARMONY -0.45
STABILITY -0.35


==================================================
9. STEM COMBINATION
==================================================

Pairs:

甲己
乙庚
丙辛
丁壬
戊癸


Mappings:

ATTRACTION +0.70
HARMONY +0.45
LONG_TERM +0.25


transformationStatus:

UNASSESSED


actual transformation scoring:
없음


==================================================
10. DAY BRANCH PRECEDENCE
==================================================

Day Branch A × Day Branch B only.


if LIUHE:
    BRANCH_COMBINATION

else if CLASH:
    BRANCH_CLASH

else:
    DAY_BRANCH_RELATION fallback


刑 / 害 / 破:

context only

fallback suppression 하지 않음.


==================================================
11. LIUHE
==================================================

Pairs:

子丑
寅亥
卯戌
辰酉
巳申
午未


Mappings:

HARMONY +0.75
EMOTION +0.55
LONG_TERM +0.45


==================================================
12. CLASH
==================================================

Pairs:

子午
丑未
寅申
卯酉
辰戌
巳亥


Mappings:

PASSION +0.20
HARMONY -0.70
STABILITY -0.60


==================================================
13. DAY BRANCH FALLBACK
==================================================

Same primary element:

EMOTION +0.55
STABILITY +0.35


Generating:

EMOTION +0.45
SUPPORT +0.35
LONG_TERM +0.25


Controlling:

EMOTION -0.35
STABILITY -0.30
HARMONY -0.20


Unknown:

INVALID / UNAVAILABLE


==================================================
14. TEN GODS
==================================================

TEN_GODS_RELATION:

contextOnly=true
scoreContribution=0


lookup:

same element
 same polarity → 比肩
 opposite → 劫財

Day Master produces other
 same → 食神
 opposite → 傷官

Day Master controls other
 same → 偏財
 opposite → 正財

Other controls Day Master
 same → 偏官
 opposite → 正官

Other produces Day Master
 same → 偏印
 opposite → 正印


gender-specific spouse-star scoring:
금지


==================================================
15. CONTEXT EVIDENCE
==================================================

Scoring Feature와 분리하여:

ContextEvidence

별도 closed schema 생성.


권장 파일:

docs/contracts/schemas/context-evidence.schema.json


최소:

contextEvidenceId
ruleId
source
subject
period
confidence
metadata


ContextEvidence에는:

category
signedValue
effectiveWeight

필수 아님.


Score aggregation 제외
Coverage 제외
Guardrail 제외
Action scoring evidenceRefs 제외


AI용 별도:

contextEvidenceRefs

계약 정의.


==================================================
16. CONTEXT DETECTOR STATUS
==================================================

TEN_GODS_RELATION:
defined context detector


아래는 family 존재하나 detector deferred:

HIDDEN_STEM_RELATION
PUNISHMENT
HARM
DESTRUCTION


미승인 pair/table 발명 금지.


破 4/6 pair 차이:
research note 유지.


==================================================
17. ELEMENT COMPLEMENT
==================================================

Visible Heavenly Stem:
1 token


Earthly Branch primary element:
1 token


모든 token 동일 weight.


Hidden Stems:
제외

Seasonal/month strength:
ELEMENT_COMPLEMENT에서 제외

Yongshin:
제외


시간 있음:
8 tokens/person


시간 없음:
6 tokens/person


pA[e], pB[e] 각각 known-token 기준 normalize.


balance(p) =

1 - SUM(abs(p[e]-0.2))/1.6


joint[e] =

(pA[e]+pB[e])/2


complementGain =

balance(joint)
-
(balance(A)+balance(B))/2


range:

0..0.50


==================================================
18. COMPLEMENT BANDS
==================================================

full precision 판정.


<0.05:
Feature 없음


[0.05,0.15):

SUPPORT +0.30
HARMONY +0.20


[0.15,0.30):

SUPPORT +0.60
HARMONY +0.40


>=0.30:

SUPPORT +0.90
HARMONY +0.60


==================================================
19. YIN-YANG
==================================================

두 사람 known visible tokens 합산.


balance =

1 -
abs(yangCount-yinCount)
/
knownTokenCount


knownTokenCount > 0


Bands:


>=0.75:

HARMONY +0.45
STABILITY +0.25


[0.50,0.75):

HARMONY +0.20


[0.25,0.50):

HARMONY -0.15


<0.25:

HARMONY -0.35
STABILITY -0.20


==================================================
20. UNKNOWN TIME
==================================================

Whole-chart observedTokenRatio:

both known:
1


one unknown:
0.875


both unknown:
0.75


candidateAgreement 없으면:

featureConfidence =
observedTokenRatio


candidate ambiguity 있으면:

featureConfidence =
observedTokenRatio
*
candidateAgreement


coverage에 observedTokenRatio를
다시 곱하지 않는다.


==================================================
21. REQUIRES BIRTH TIME
==================================================

9 scoring family:

requiresBirthTime=false


false != confidence 1 guaranteed


Day boundary:

기존 23:30 candidate contract 유지.


임의 noon/midnight/hour pillar 생성 금지.


==================================================
22. DECIMAL SIGN SCHEMA
==================================================

Saju도 Astrology와 동일.


POSITIVE:
0 < signedValue <= 1


NEGATIVE:
-1 <= signedValue < 0


NEUTRAL:
signedValue = 0


direction/sign mismatch:
INVALID


==================================================
23. PRODUCT DISTRIBUTION DECISION
==================================================

다음은 v1에서 의도된 동작으로 승인한다.


ATTRACTION:

Saju availability가 낮고
활성될 경우 강한 단일 signal이 될 수 있음.


PASSION:

sparse 허용.


COMMUNICATION:

Saju scoring mapping 0 허용.

Astrology Communication evidence 사용.


SUPPORT:

상대적으로 positive/high 분포 허용.


Synthetic distribution에 맞추기 위해
signedValue/weight를 조정하지 않는다.


실제 서비스 데이터 기반 calibration은
v1.1 이후 별도 task.


==================================================
24. SCOPE EXCLUSIONS
==================================================

v1 scoring에 추가 금지:

Hidden Stem scoring
刑 scoring
害 scoring
破 scoring

12운성
신살
원진살
용신
희신
기신
격국
대운


==================================================
25. SCHEMA
==================================================

필요한 Saju-specific schema를
generic/astrology와 충돌하지 않도록 구성.


additionalProperties=false 유지.


최소:

family
variant
categoryMappings
decimal signedValue
requiresBirthTime
contextOnly
precedence/reference
closed metadata


transformationStatus:

UNASSESSED only


==================================================
26. VALID EXAMPLES
==================================================

최소:

STEM_COMBINATION
DAY_MASTER SAME
ELEMENT_SUPPORT
ELEMENT_CONTROL
LIUHE
CLASH
DAY_BRANCH fallback
ELEMENT_COMPLEMENT low/medium/high
YIN_YANG 각 band

unknown time confidence

Ten Gods ContextEvidence


==================================================
27. INVALID EXAMPLES
==================================================

최소:

direction/sign mismatch
signedValue outside range
enabled scoring mapping empty
invalid Stem
invalid Branch
invalid element
invalid Ten God
requiresBirthTime null
duplicate scoring owner
whole-chart cross-product
context inside Feature array
context category scoring
coverage double penalty
invalid token count


==================================================
28. VALIDATION
==================================================

반드시 실제 파일 대상으로 검증.


Expected:

Saju families = 14
Scoring = 9
Context = 5


Day Stem 10x10:
PASS


Day Branch 12x12:
PASS


duplicate scoring owner:
0


Ten Gods score contribution:
0


Context score contribution:
0


Complement range:
0..0.50


Unknown time:
1 / .875 / .75


SC-07 v2:

Feature impact >20:
0

Feature exclusion:
0

Guardrail unresolved:
0

Guardrail-induced insufficient:
0


==================================================
29. ASTROLOGY REGRESSION
==================================================

Astrology:

109 rules
228 mappings

유지.


SC-07 v2 적용으로
Catalog 숫자 변경 없음.


Astrology schema/examples:
PASS


Guardrail v2 synthetic regression:
PASS


==================================================
30. EXISTING CONTRACT REGRESSION
==================================================

기존:

SC-01~SC-10

중 SC-07만 v2로 supersede.


다른 contract:

confidence
coverage
period
privacy
API
signed context
canonicalization

변경 없음.


Expected:

SPEC_CONFLICT = 0

Cross-Spec Contradictions = 0


==================================================
31. VALIDATION REPORT
==================================================

docs/contracts/validation-report.md
실제 실행 결과로 동기화.


상태:

Contract Freeze:
PASS


SC-07 Guardrail:
V2 APPLIED


Astrology Catalog:
READY


Saju Catalog:
APPLIED


Saju Catalog Readiness:
READY


Score Engine Readiness:
BLOCKED_CATALOG


remaining Catalog blocker:

DAILY_RULE_CATALOG_APPROVAL


Production Engine Readiness:

BLOCKED_EXTERNAL


external blockers:

SAJU_DAY_PILLAR_EPOCH
EPHEMERIS_PROVIDER


==================================================
32. CODE CHANGE CHECK
==================================================

production code changed:

NONE


허용 scope 밖 파일 hash 비교.


git diff --check:

PASS


==================================================
33. FINAL REPORT
==================================================

제목:

SC-07 V2 + SAJU CATALOG V1 APPLICATION REPORT


A. Files Changed

B. SC-07 v2 Application

C. Guardrail Formula Validation

D. Saju Families
Expected:
14 / scoring 9 / context 5

E. Saju Mapping Validation

F. Day Stem Exhaustive Validation

G. Day Branch Exhaustive Validation

H. Element Complement Validation

I. Yin-Yang Validation

J. Unknown-Time Validation

K. ContextEvidence Validation

L. Schema Validation

M. Examples Validation

N. Astrology Regression

O. Existing Contract Regression

P. SPEC_CONFLICT Remaining

Q. Cross-Spec Contradictions

R. Production Code Files Changed

S. git diff --check

T. SC-07 v2
APPLIED / FAIL

U. Saju Catalog v1
APPLIED / FAIL

V. Saju Catalog Readiness
READY / BLOCKED_CATALOG

W. Score Engine Readiness
READY / BLOCKED_CATALOG

X. Remaining Catalog Blockers

Y. Production Engine Readiness

Z. External Blockers


==================================================
34. RESTRICTIONS
==================================================

PHP 수정 금지.
WordPress 수정 금지.
Frontend 수정 금지.
DB 수정 금지.

commit/push 금지.

signedValue 임의 변경 금지.
weight 임의 변경 금지.
threshold 20 변경 금지.
새 scoring rule 생성 금지.