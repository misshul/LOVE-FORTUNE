# LOVE FORTUNE
# DAILY RULE CATALOG v1
# REPOSITORY APPLICATION
# APPLY APPROVED C21-R CONTRACT

현재 상태:

Contract Freeze:
PASS

Astrology Catalog v1:
READY

Saju Catalog v1:
READY

SC-07 Guardrail v2:
APPLIED

Daily Catalog v1:
C21-R FINAL VALIDATION PASS

Daily Catalog Repository Application Readiness:
READY_FOR_REPOSITORY_APPLICATION

Score Engine Readiness:
BLOCKED_CATALOG

Remaining Catalog Blocker:
DAILY_RULE_CATALOG_APPROVAL

External Blockers:
SAJU_DAY_PILLAR_EPOCH
EPHEMERIS_PROVIDER


이번 작업의 목적은:

승인된 Daily Catalog v1 C21-R 계약을
repository 문서·catalog·schema·examples·validation에
정식 반영하고

DAILY_RULE_CATALOG_APPROVAL blocker를 닫은 뒤

Score Engine Readiness를
READY

로 전환 가능한 상태인지 검증하는 것이다.


==================================================
IMPORTANT
==================================================

이번 단계는 Repository Application이다.

허용 범위 안의 문서/catalog/schema/example/validator 수정 가능.

단:

PHP 수정 금지.
WordPress runtime code 수정 금지.
Frontend 수정 금지.
DB 수정 금지.
Docker 수정 금지.

SAJU_DAY_PILLAR_EPOCH와
EPHEMERIS_PROVIDER는 Production blocker로 유지한다.

Daily Catalog 적용 성공이
Production Engine READY를 의미하지 않는다.


==================================================
1. SOURCE OF TRUTH
==================================================

다음 승인 결과를 Source of Truth로 사용한다:

DAILY RULE CATALOG V1 RESEARCH PROPOSAL REPORT

DAILY CATALOG V1 FINAL CONTRACT DECISION VALIDATION REPORT

DAILY CATALOG V1 SYNTHETIC SIMULATION REPORT

DAILY CATALOG V1 PRODUCT DECISION COMPARISON REPORT

DAILY CATALOG V1 C21-R FINAL VALIDATION REPORT


최종 승인안:

C21-R


==================================================
2. FINAL DAILY V1 PRODUCT CONTRACT
==================================================

Status:

VERY_LOW:
delta <= -5

LOW:
-5 < delta <= -2

STABLE:
-2 < delta < 2

GOOD:
2 <= delta < 5

VERY_GOOD:
delta >= 5


Missing aggregation:

M1-ELIGIBILITY-AWARE


Sample missing:

computable-only signal mean 유지

V1 KNOWN CHARACTERISTIC ACCEPTED


Category directional bias:

V1 SYNTHETIC CHARACTERISTIC ACCEPTED
NOT CALIBRATED


==================================================
3. DAILY RULE SET
==================================================

Core Astrology:

125 rules
266 mappings


Saju:

9 rules
19 mappings


Total:

134 rules
285 mappings


Jupiter:

DEFERRED_V1


Saturn:

DEFERRED_V1


Outer:

Daily direct score 0


MC:

excluded


Daily context:

deferred, scoring 0


==================================================
4. DAILY ASTROLOGY CONTRACT
==================================================

Transit:

MOON
MERCURY
VENUS
SUN
MARS


Natal target:

SUN
MOON
MERCURY
VENUS
MARS


Aspect:

CONJUNCTION
SEXTILE
SQUARE
TRINE
OPPOSITION


Rule ID:

DAILY_ASTRO_{TRANSIT_PLANET}_{NATAL_PLANET}_{ASPECT}


Daily orb:

1 degree


Planet orb adjustment:

NONE


requiresBirthTime:

false


preConfidenceWeight =

aspectWeight
×
Daily transit planetWeight


effectiveWeight =

preConfidenceWeight
×
featureConfidence


Natal planet weight 추가 곱셈 없음.

Lifetime pair override 사용 금지.

orbCloseness score/confidence multiplier 금지.


==================================================
5. DAILY SAJU CONTRACT
==================================================

9 rules:

DAILY_SAJU_STEM_COMBINATION
DAILY_SAJU_STEM_SAME
DAILY_SAJU_STEM_GENERATION
DAILY_SAJU_STEM_CONTROL

DAILY_SAJU_BRANCH_LIUHE
DAILY_SAJU_BRANCH_CLASH
DAILY_SAJU_BRANCH_SAME
DAILY_SAJU_BRANCH_GENERATION
DAILY_SAJU_BRANCH_CONTROL


Stem precedence:

COMBINATION
>
SAME
>
GENERATION / CONTROL


Branch precedence:

LIUHE
>
CLASH
>
ELEMENT FALLBACK


requiresBirthTime:

false


Generation/control direction:

lineage metadata에는 보존

v1 scoring:

approved coarse family 사용


刑 / 害 / 破 / Hidden Stem:

Daily scoring 제외


==================================================
6. DAILY SAJU REFERENCE
==================================================

referenceId:

DAILY_SAJU_REFERENCE_V1


timezone:

Asia/Seoul


longitude:

127.5E


UI calendar date:

targetTimezone


Sample instant:

targetTimezone nominal sample
→ DST resolve
→ actual instant
→ Asia/Seoul civil datetime
→ historical offset adjustment
→ 23:30 Saju day boundary


보정 중복 금지.


==================================================
7. FOUR SAMPLE CONTRACT
==================================================

Sample:

06:00
12:00
18:00
23:00


Signal aggregation:

computable sample만 mean/peak에 사용


meanWeight:

0.75


signedPeakWeight:

0.25


peak tie:

earlier nominal local sample


Confidence:

항상 4 logical sample slot denominator


같은 Saju result는 request-local reuse 가능.

단 logical sample identity는 유지.


==================================================
8. PERSON A/B PROJECTION
==================================================

각 source/category/sample에서:

person/category aggregation 먼저 수행


그 후:

pairCategorySignal =

(signalA + signalB) / 2


pairCategoryConfidence =

(confidenceA + confidenceB) / 2


A/B symmetry 필수.


Shared-direction bonus:

없음


Conflict penalty:

없음


Gender/relationship multiplier:

없음


==================================================
9. COMPUTED NO-EVENT
==================================================

COMPUTED_NO_EVENT:

정상 계산 완료
active event 없음

signal = 0

confidence = 1

fake neutral Feature 생성 금지


UNAVAILABLE:

필요한 input/provider/candidate 평가 불가

signal 계산 불가


두 상태를 반드시 분리한다.


==================================================
10. STRUCTURAL ELIGIBILITY
==================================================

Daily v1 category source eligibility:


ASTROLOGY:

ATTRACTION
EMOTION
COMMUNICATION
PASSION
HARMONY
SUPPORT


SAJU:

ATTRACTION
EMOTION
COMMUNICATION
PASSION
STABILITY
HARMONY
SUPPORT


LONG_TERM:

no eligible source


Astrology STABILITY:

STRUCTURALLY_INELIGIBLE


==================================================
11. M1-ELIGIBILITY-AWARE AGGREGATION
==================================================

각 category/source에서 상태를 구분:

STRUCTURALLY_INELIGIBLE

ELIGIBLE_AVAILABLE

ELIGIBLE_UNAVAILABLE

COMPUTED_NO_EVENT


STRUCTURALLY_INELIGIBLE:

approved denominator에서 제외


ELIGIBLE_UNAVAILABLE:

approved denominator weight 유지


COMPUTED_NO_EVENT:

signal 0으로 denominator 포함


이 원칙은:

category source aggregation

및

source 내부 category aggregation

양쪽에 적용한다.


==================================================
12. CATEGORY WEIGHTS
==================================================

기존 weights 재사용:

ATTRACTION    .12
EMOTION       .17
COMMUNICATION .14
PASSION       .10
STABILITY     .15
HARMONY       .12
SUPPORT       .08
LONG_TERM     .12


새 Daily 전용 category weight 생성 금지.


Source 내부 denominator는
source structurally eligible category의
weight만 합산.


Runtime unavailable category의 weight는
고정 denominator에 유지.


==================================================
13. SOURCE COMBINATION
==================================================

Overall source weights:

ASTROLOGY .50
SAJU .50


두 source structurally eligible.


Runtime에서 한 source unavailable이어도
다른 source를 100%로 재정규화하지 않는다.


예:

Astrology unavailable
Saju signal .4

combined = .2


이는 penalty가 아니라
Daily modifier attenuation이다.


==================================================
14. CATEGORY SOURCE COMBINATION
==================================================

둘 다 structurally eligible category:

Astro .50
Saju .50


한 source structurally ineligible category:

eligible source만 denominator 구성


예:

STABILITY:

Astrology structurally ineligible
Saju eligible


Saju signal .4:

categoryDailySignal = .4


.2로 감쇠 금지.


==================================================
15. DAILY SCORE
==================================================

overall:

dailySignal

dailyDelta =
18 × dailySignal


dailyScore =

clamp(
 lifetimeScore + dailyDelta,
 0,
 100
)


category:

categoryDailyDelta =
18 × categoryDailySignal


categoryDailyScore =

clamp(
 lifetimeCategoryScore + categoryDailyDelta,
 0,
 100
)


Daily ±18 maximum 변경 금지.


==================================================
16. SC-07
==================================================

Lifetime:

SC-07 v2 유지


Daily v1:

NOT_APPLIED_TO_DAILY_V1


50-point raw scale
20-point single feature threshold

Daily에 적용 금지.


==================================================
17. FEATURE IDENTITY
==================================================

Daily identity는
sampleRef를 포함해야 한다.


최소:

ruleId
subject
category
sampleRef


Astrology lineage:

transitPlanet
natalPlanet


Saju lineage:

dailyStem / dailyBranch
natalDayStem / natalDayBranch
DAILY_SAJU_REFERENCE_V1


Cross-sample candidate merge 금지.


같은 semantic evidence + same sample:

same deterministic ID


different sample:

different ID


==================================================
18. SCHEMA
==================================================

필요한 schema 변경을 최소 범위로 적용.


Daily rule schema에 필요한:

transitPlanet
natalPlanet
aspect
orbPolicy
source
sample identity
Saju relation owner
structural eligibility

등을 명시적으로 정의.


기존 Lifetime rule schema 의미를
조용히 덮어쓰지 않는다.


Daily용 별도 schema 또는
명확한 discriminated structure를 우선 검토.


==================================================
19. DAILY RULES JSON
==================================================

현재:

rules: []


를 승인된 134 rules / 285 mappings으로 반영.


status:

기존 incomplete 상태 제거.


Daily Catalog v1이 적용된 상태를
명확히 표시.


각 rule:

enabled=true

로 적용 가능한지
승인 contract와 schema 기준으로 검증.


Jupiter/Saturn은
scoring rules에 넣지 않거나
명확히 deferred/inactive 처리.


Core count가 125에서 변하면 FAIL.


Saju count가 9에서 변하면 FAIL.


==================================================
20. STATUS CONTRACT UPDATE
==================================================

기존 Daily status threshold:

±6 / ±12


를 최종 승인 S2:

±2 / ±5

로 문서/schema/example에 일관 반영.


불일치가 남으면 FAIL.


==================================================
21. MISSING CONTRACT UPDATE
==================================================

기존 available-only re-normalization 문구가 있으면

M1-ELIGIBILITY-AWARE로 갱신.


반드시 명시:

structural ineligible
!=
runtime unavailable
!=
computed no-event


전체 Daily와
category Daily 양쪽에 반영.


==================================================
22. DOCUMENTS TO UPDATE
==================================================

실제 영향이 있는 범위만 수정.


최소 검토:

docs/02_FORTUNE_ENGINE_SPEC.md
docs/03_SCORE_SPEC.md
docs/05_API_SPEC.md
docs/08_AI_PROMPT_SPEC.md
docs/09_TASK_LIST.md
docs/10_AGENTS.md

docs/contracts/runtime-contract-v2.md
docs/contracts/guardrail-v2.md
docs/contracts/daily-catalog-v1.md
docs/contracts/readiness.md
docs/contracts/validation-report.md

docs/contracts/rules/daily-rules.json

Daily 관련 schema/examples/validation scripts


필요 없는 파일은 수정하지 않는다.


==================================================
23. READINESS METADATA
==================================================

Repository Application 성공 시:

Daily Catalog v1:

APPLIED
READY


Score Engine Readiness:

READY


Remaining Catalog Blockers:

NONE


다만:

Production Engine Readiness:

BLOCKED_EXTERNAL


External blockers:

SAJU_DAY_PILLAR_EPOCH
EPHEMERIS_PROVIDER


==================================================
24. FIX CD-01
==================================================

astrology-rules.json 등에 남아 있는 stale:

SAJU_RULE_CATALOG_APPROVAL

readiness metadata를 정리.


현재 실제 Source of Truth와 동기화.


Application 성공 후
catalog blocker는 NONE이어야 한다.


==================================================
25. PUBLIC API
==================================================

이번 적용에서
public API shape를 불필요하게 확장하지 않는다.


categoryDailyScore/categoryDailySignal의
public 노출은 별도 deferred contract라면
그 상태 유지.


Internal calculation과
public wire를 구분.


기존 signed context wire 변경 금지.


==================================================
26. CONTEXT
==================================================

Daily Ten Gods
ASC
House
Daily ContextEvidence export

DEFERRED_NON_BLOCKING 유지.


Daily scoring에는 영향 0.


Lifetime ContextEvidence 계약 변경 금지.


==================================================
27. PRIVACY
==================================================

기존 privacy contract 유지.


raw birth log 금지
URL birth data 금지
server derived user cache 금지
Daily history DB 생성 금지
stable anonymous fingerprint 금지

Cache-Control:

no-store


==================================================
28. VALIDATION
==================================================

Repository 반영 후 반드시 실행.


A.

Daily rule count:

134


B.

Astrology:

125 rules
266 mappings


C.

Saju:

9 rules
19 mappings


D.

Total mappings:

285


E.

Duplicate Daily ruleId:

0


F.

signedValue range:

all -1..1


G.

direction/sign mismatch:

0


H.

Orb:

0
.9999
1.0 active

1.0001 inactive


I.

A/B symmetry:

difference 0


J.

No-event:

fake neutral Feature 0


K.

Structural eligibility:

STABILITY full-data attenuation 0


L.

Runtime missing attenuation:

PASS


M.

Cross-sample merge:

0


N.

Daily SC-07 application:

0


O.

Jupiter/Saturn active scoring:

0


P.

Outer scoring:

0


Q.

MC:

0


R.

Daily status threshold:

S2 only


S.

Old ±6/±12 active contract references:

0


T.

Old available-only reweight semantics:

0


U.

Stale catalog blocker metadata:

0


==================================================
29. REGRESSION
==================================================

기존 전체 regression 유지.


Astrology Lifetime:

109 rules
228 mappings


Saju Lifetime:

14 families
9 scoring
5 context


SC-07 v2:

unchanged


Contract Freeze:

PASS


Schemas:

all PASS


Examples:

valid expected pass
invalid expected fail


OpenAPI:

PASS


Canonicalization:

PASS


Signed context:

PASS


DST:

PASS


Privacy:

PASS


==================================================
30. DAILY C21-R REGRESSION
==================================================

가능하면 기존 256,000 fixture 전체를
Repository 적용된 actual catalog로 다시 실행.


최소 기대:


Hard Fail:

0


Full-data M0-equivalent overall diff:

0


Full-data category diff:

0


STABILITY attenuation:

0


Status:

S2 적용


Runtime missing:

eligibility-aware attenuation


Sample-missing 6-case characteristic:

기존 accepted behavior와 일치


단 fixture 결과를 맞추기 위해
catalog 숫자를 수정하지 않는다.


==================================================
31. SCORE ENGINE READY GATE
==================================================

다음이 모두 PASS일 때만:

Score Engine Readiness = READY


- Astrology Catalog READY
- Saju Catalog READY
- Daily Catalog READY
- Contract Freeze PASS
- SPEC_CONFLICT 0
- Catalog blocker 0
- Regression PASS


External runtime dependency는
Score Engine Catalog readiness와 분리.


==================================================
32. HARD FAIL
==================================================

다음이면 Application FAIL:


Daily rule count != 134

Astrology Daily count != 125

Saju Daily count != 9

Mapping count != 285

signedValue 변경

proposal matrix와 불일치

S2 status 불일치

M1 eligibility-aware semantics 불일치

Full-data STABILITY attenuation 재발

runtime unavailable 100% 재정규화

fake neutral Feature 생성

Cross-sample merge

Daily SC-07 적용

Jupiter/Saturn 활성화

Outer scoring 활성화

MC 포함

Lifetime Astrology/Saju regression

Contract Freeze failure

SPEC_CONFLICT 발생

production PHP 변경


==================================================
33. REQUIRED OUTPUT
==================================================

제목:

DAILY CATALOG V1 REPOSITORY APPLICATION REPORT


A. Files Changed

B. Daily Catalog Application

C. Rule / Mapping Counts

D. Astrology Daily Validation

E. Saju Daily Validation

F. Status S2 Application

G. M1 Eligibility-Aware Application

H. Structural Eligibility Validation

I. No-Event / Unavailable Validation

J. Four-Sample Validation

K. Feature Identity Validation

L. Schema Validation

M. Examples Validation

N. C21-R Regression

O. Lifetime Astrology Regression

P. Lifetime Saju Regression

Q. SC-07 Regression

R. Contract Freeze

S. SPEC_CONFLICT Remaining

T. Readiness Metadata Sync

U. Daily Catalog Status

Expected:

APPLIED
READY


V. Score Engine Readiness

Expected:

READY


W. Remaining Catalog Blockers

Expected:

NONE


X. Production Engine Readiness

Expected:

BLOCKED_EXTERNAL


Y. External Blockers

SAJU_DAY_PILLAR_EPOCH
EPHEMERIS_PROVIDER


Z. Production Code Files Changed

Expected:

NONE


AA. git diff --check

Expected:

PASS


==================================================
34. GIT
==================================================

이번 단계에서는:

파일 수정은 수행하되

commit/push는 하지 않는다.


마지막 보고서에:

Files Changed count
Git status summary

를 포함한다.


사용자가 검토 후
별도 commit/push 진행.


==================================================
35. RESTRICTIONS
==================================================

Daily rule 숫자 자동 조정 금지.

signedValue 변경 금지.

orb 변경 금지.

source weight 변경 금지.

sample time 변경 금지.

.75/.25 변경 금지.

±18 변경 금지.

Lifetime Catalog 변경 금지.

SC-07 변경 금지.

Production PHP/WordPress runtime 수정 금지.

Frontend/DB/Docker 수정 금지.

commit/push 금지.