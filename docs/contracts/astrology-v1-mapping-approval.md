# LOVE FORTUNE
# ASTROLOGY SCORING RULE CATALOG v1
# FINAL PAIR-ASPECT MAPPING APPROVAL

본 결정은
ASTROLOGY CATALOG V1 PROPOSAL REPORT에서 요청된

pair-aspect별
category / signedValue 승인표와
관련 미확정 계약을 최종 승인하기 위한 것이다.

이번 단계에서도 아직 파일을 수정하지 않는다.
먼저 아래 계약으로 Catalog를 메모리에서 재생성하고
검증 보고만 수행한다.


==================================================
1. CANONICAL PLANET ORDER
==================================================

ASCII 정렬은 사용하지 않는다.

LOVE FORTUNE Astrology의 canonical planet order를
다음으로 고정한다.

SUN
MOON
MERCURY
VENUS
MARS
JUPITER
SATURN
URANUS
NEPTUNE
PLUTO

pair ruleId는 위 순서에서 먼저 나오는 행성을 앞에 둔다.

예:

ASTRO_SUN_MOON_TRINE
ASTRO_VENUS_MARS_SQUARE
ASTRO_SUN_SATURN_OPPOSITION

다음과 같이 만들지 않는다:

ASTRO_MOON_SUN_TRINE

단,

PERSON_A Sun × PERSON_B Moon

과

PERSON_A Moon × PERSON_B Sun

은 별도 evidence로 계산 가능하다.

ruleId의 pair canonicalization은
rule definition 중복 제거를 위한 것이며,
실제 양방향 evidence를 삭제하거나 합친다는 의미가 아니다.


==================================================
2. ASPECT CONTRACT
==================================================

Exact angles:

CONJUNCTION = 0
SEXTILE = 60
SQUARE = 90
TRINE = 120
OPPOSITION = 180

기존 baseWeight 유지:

CONJUNCTION = 1.00
OPPOSITION = 0.90
TRINE = 0.90
SQUARE = 0.85
SEXTILE = 0.70


==================================================
3. ORB CONTRACT CORRECTION
==================================================

중요:

이전 proposal에서 Codex가
"- max(...)"로 해석한 것은 잘못이다.

공식은 반드시 PLUS다.

effectiveOrb =

baseAspectOrb
+
max(
  planetOrbAdjustment(A),
  planetOrbAdjustment(B)
)

그 후:

effectiveOrb =
min(10, effectiveOrb)

이다.

예:

Sun–Moon Conjunction

8 + max(1,1)
= 9 degrees


Sun–Venus Trine

7 + max(1,0)
= 8 degrees


Mercury–Venus Sextile

5 + max(0,0)
= 5 degrees


Jupiter–Saturn Sextile

5 + max(-1,-1)
= 4 degrees


planet adjustment의 목적은
Sun/Moon처럼 허용 범위를 넓히고,
Jupiter/Saturn 등은 기존 계약에 따라
좁히는 것이다.


==================================================
4. ANGULAR DISTANCE
==================================================

longitude는 [0,360) degree로 normalize한다.

rawDifference =
abs(longitudeA - longitudeB)

angularDistance =
min(
  rawDifference,
  360 - rawDifference
)

범위:

0 <= angularDistance <= 180


aspectOrb =

abs(
  angularDistance - exactAspectAngle
)


active:

aspectOrb <= effectiveOrb


==================================================
5. ORB CLOSENESS
==================================================

active aspect에 대해서만:

orbCloseness =
1 - (aspectOrb / effectiveOrb)

범위:

0 <= orbCloseness <= 1

경계:

aspectOrb = effectiveOrb
→ active = true
→ orbCloseness = 0

exact:
aspectOrb = 0
→ orbCloseness = 1


inactive aspect에는:

orbCloseness를 생성하지 않는다.

null이나 음수 값으로 scoring Feature를 생성하지 않는다.


orbCloseness는 v1에서:

- score multiplier 사용 금지
- featureConfidence multiplier 사용 금지

사용 가능:

- request-local evidence ordering
- deterministic interpretation priority
- optional Feature metadata

Feature metadata로 노출하려면
metadata allowlist에:

orbCloseness

를 추가한다.

로그 저장 허용을 의미하지 않는다.


==================================================
6. MAPPING MODEL
==================================================

한 pair-aspect rule은
0개 이상의 categoryMappings를 가진다.

v1에서 enabled=true인 scoring rule은
최소 1개의 categoryMapping이 반드시 있어야 한다.

각 mapping:

category
direction
signedValue

signedValue 범위:

-1.0 <= signedValue <= 1.0


direction은 signedValue에서 결정한다.

signedValue > 0:
POSITIVE

signedValue < 0:
NEGATIVE

signedValue = 0:
NEUTRAL


rule 하나에 positive와 negative category mapping이
동시에 존재할 수 있다.

이 경우 rule-level interpretation은 MIXED이다.


==================================================
7. SCHEMA DECISION
==================================================

기존 Schema의:

POSITIVE = +1 only
NEGATIVE = -1 only

제한은 폐기한다.

새 계약:

POSITIVE:
0 < signedValue <= 1

NEGATIVE:
-1 <= signedValue < 0

NEUTRAL:
signedValue = 0


direction은 signedValue와 반드시 일치해야 한다.

예:

POSITIVE +0.7 → VALID

POSITIVE -0.7 → INVALID

NEGATIVE -0.4 → VALID

NEGATIVE +0.4 → INVALID


이 Schema 변경은
Catalog 실제 적용 단계에서
rule.schema.json 및 관련 examples에
동기화해야 한다.


==================================================
8. PERSONAL PLANET MATRIX
==================================================

대상:

SUN
MOON
MERCURY
VENUS
MARS


표기:

C = CONJUNCTION
T = TRINE
S = SEXTILE
Q = SQUARE
O = OPPOSITION


각 셀:

CATEGORY:signedValue

여러 개면 "|"로 구분한다.


--------------------------------------------------
SUN × SUN
--------------------------------------------------

C:
HARMONY:+0.70

T:
HARMONY:+0.90

S:
HARMONY:+0.70

Q:
HARMONY:-0.70

O:
HARMONY:-0.60


--------------------------------------------------
SUN × MOON
--------------------------------------------------

C:
HARMONY:+0.90
SUPPORT:+0.80
LONG_TERM:+0.65

T:
HARMONY:+1.00
SUPPORT:+0.90
LONG_TERM:+0.75

S:
HARMONY:+0.80
SUPPORT:+0.70
LONG_TERM:+0.55

Q:
HARMONY:-0.75
SUPPORT:-0.55
LONG_TERM:-0.45

O:
HARMONY:-0.65
SUPPORT:-0.50
LONG_TERM:-0.45


--------------------------------------------------
SUN × MERCURY
--------------------------------------------------

C:
COMMUNICATION:+0.75

T:
COMMUNICATION:+0.90

S:
COMMUNICATION:+0.70

Q:
COMMUNICATION:-0.65

O:
COMMUNICATION:-0.60


--------------------------------------------------
SUN × VENUS
--------------------------------------------------

C:
ATTRACTION:+0.90
HARMONY:+0.80

T:
ATTRACTION:+0.90
HARMONY:+0.85

S:
ATTRACTION:+0.70
HARMONY:+0.70

Q:
ATTRACTION:+0.20
HARMONY:-0.55

O:
ATTRACTION:+0.25
HARMONY:-0.50


--------------------------------------------------
SUN × MARS
--------------------------------------------------

C:
ATTRACTION:+0.80
PASSION:+0.90
HARMONY:-0.15

T:
ATTRACTION:+0.80
PASSION:+0.80
HARMONY:+0.45

S:
ATTRACTION:+0.65
PASSION:+0.65
HARMONY:+0.30

Q:
ATTRACTION:+0.50
PASSION:+0.70
HARMONY:-0.70

O:
ATTRACTION:+0.55
PASSION:+0.70
HARMONY:-0.60


--------------------------------------------------
MOON × MOON
--------------------------------------------------

C:
EMOTION:+0.90
HARMONY:+0.80

T:
EMOTION:+1.00
HARMONY:+0.90

S:
EMOTION:+0.80
HARMONY:+0.70

Q:
EMOTION:-0.85
HARMONY:-0.70

O:
EMOTION:-0.75
HARMONY:-0.65


--------------------------------------------------
MOON × MERCURY
--------------------------------------------------

C:
COMMUNICATION:+0.85
EMOTION:+0.75

T:
COMMUNICATION:+0.90
EMOTION:+0.80

S:
COMMUNICATION:+0.80
EMOTION:+0.70

Q:
COMMUNICATION:-0.70
EMOTION:-0.65

O:
COMMUNICATION:-0.65
EMOTION:-0.60


--------------------------------------------------
MOON × VENUS
--------------------------------------------------

C:
EMOTION:+0.90
HARMONY:+0.90

T:
EMOTION:+1.00
HARMONY:+0.95

S:
EMOTION:+0.85
HARMONY:+0.80

Q:
EMOTION:-0.55
HARMONY:-0.50

O:
EMOTION:-0.45
HARMONY:-0.40


--------------------------------------------------
MOON × MARS
--------------------------------------------------

C:
PASSION:+0.70
EMOTION:-0.10

T:
PASSION:+0.75
EMOTION:+0.60

S:
PASSION:+0.60
EMOTION:+0.45

Q:
PASSION:+0.55
EMOTION:-0.75

O:
PASSION:+0.55
EMOTION:-0.70


--------------------------------------------------
MERCURY × MERCURY
--------------------------------------------------

C:
COMMUNICATION:+0.90

T:
COMMUNICATION:+1.00

S:
COMMUNICATION:+0.80

Q:
COMMUNICATION:-0.90

O:
COMMUNICATION:-0.80


--------------------------------------------------
MERCURY × VENUS
--------------------------------------------------

C:
COMMUNICATION:+0.85
HARMONY:+0.70

T:
COMMUNICATION:+0.90
HARMONY:+0.80

S:
COMMUNICATION:+0.75
HARMONY:+0.65

Q:
COMMUNICATION:-0.45
HARMONY:-0.35

O:
COMMUNICATION:-0.40
HARMONY:-0.30


--------------------------------------------------
MERCURY × MARS
--------------------------------------------------

C:
COMMUNICATION:+0.30
PASSION:+0.40

T:
COMMUNICATION:+0.75
PASSION:+0.50

S:
COMMUNICATION:+0.60
PASSION:+0.40

Q:
COMMUNICATION:-0.85
PASSION:+0.30

O:
COMMUNICATION:-0.75
PASSION:+0.35


--------------------------------------------------
VENUS × VENUS
--------------------------------------------------

C:
ATTRACTION:+0.90
HARMONY:+0.85

T:
ATTRACTION:+0.95
HARMONY:+0.95

S:
ATTRACTION:+0.80
HARMONY:+0.80

Q:
ATTRACTION:-0.45
HARMONY:-0.55

O:
ATTRACTION:-0.35
HARMONY:-0.45


--------------------------------------------------
VENUS × MARS
--------------------------------------------------

C:
ATTRACTION:+1.00
PASSION:+1.00
HARMONY:+0.20

T:
ATTRACTION:+0.90
PASSION:+0.90
HARMONY:+0.65

S:
ATTRACTION:+0.80
PASSION:+0.80
HARMONY:+0.55

Q:
ATTRACTION:+0.90
PASSION:+1.00
HARMONY:-0.80

O:
ATTRACTION:+0.90
PASSION:+0.95
HARMONY:-0.65


--------------------------------------------------
MARS × MARS
--------------------------------------------------

C:
PASSION:+0.80
HARMONY:-0.10

T:
PASSION:+0.85
HARMONY:+0.55

S:
PASSION:+0.65
HARMONY:+0.40

Q:
PASSION:+0.55
HARMONY:-0.80

O:
PASSION:+0.60
HARMONY:-0.70


==================================================
9. PERSONAL RULE COUNT
==================================================

15 canonical personal-planet pairs

×

5 aspects

=

75 enabled candidate rules.

위 75개는 모두 v1 승인 대상이다.


==================================================
10. JUPITER RULES
==================================================

Jupiter는 v1에서 secondary다.

Personal planet × Jupiter만 사용한다.

Jupiter-Jupiter:
v1 비활성

Jupiter-Saturn:
v1 비활성


허용 aspects:

CONJUNCTION
TRINE
SEXTILE

Square/Opposition:
v1 비활성


--------------------------------------------------
SUN × JUPITER
--------------------------------------------------

C:
SUPPORT:+0.80

T:
SUPPORT:+0.90

S:
SUPPORT:+0.70


--------------------------------------------------
MOON × JUPITER
--------------------------------------------------

C:
SUPPORT:+0.85
EMOTION:+0.60

T:
SUPPORT:+0.90
EMOTION:+0.70

S:
SUPPORT:+0.70
EMOTION:+0.55


--------------------------------------------------
MERCURY × JUPITER
--------------------------------------------------

C:
COMMUNICATION:+0.70

T:
COMMUNICATION:+0.80

S:
COMMUNICATION:+0.65


--------------------------------------------------
VENUS × JUPITER
--------------------------------------------------

C:
HARMONY:+0.85
SUPPORT:+0.75

T:
HARMONY:+0.90
SUPPORT:+0.85

S:
HARMONY:+0.70
SUPPORT:+0.65


--------------------------------------------------
MARS × JUPITER
--------------------------------------------------

C:
SUPPORT:+0.55

T:
SUPPORT:+0.65

S:
SUPPORT:+0.50


==================================================
11. JUPITER RULE COUNT
==================================================

5 personal pairs
×
3 aspects

=

15 enabled candidate rules.


==================================================
12. SATURN RULES
==================================================

Saturn은 v1에서 secondary다.

Saturn-Saturn:
비활성

Jupiter-Saturn:
비활성

Mars-Saturn:
v1 비활성

v1에서는:

Sun-Saturn
Moon-Saturn
Mercury-Saturn
Venus-Saturn

만 사용한다.


--------------------------------------------------
SUN × SATURN
--------------------------------------------------

C:
STABILITY:+0.75
LONG_TERM:+0.75
HARMONY:-0.25

T:
STABILITY:+0.90
LONG_TERM:+0.85
SUPPORT:+0.60

S:
STABILITY:+0.75
LONG_TERM:+0.70
SUPPORT:+0.50

Q:
STABILITY:+0.25
LONG_TERM:+0.20
HARMONY:-0.75

O:
STABILITY:+0.30
LONG_TERM:+0.25
HARMONY:-0.70


--------------------------------------------------
MOON × SATURN
--------------------------------------------------

C:
STABILITY:+0.60
LONG_TERM:+0.65
EMOTION:-0.45

T:
STABILITY:+0.80
LONG_TERM:+0.75
EMOTION:+0.45

S:
STABILITY:+0.70
LONG_TERM:+0.65
EMOTION:+0.35

Q:
STABILITY:+0.20
LONG_TERM:+0.20
EMOTION:-0.85

O:
STABILITY:+0.25
LONG_TERM:+0.25
EMOTION:-0.80


--------------------------------------------------
MERCURY × SATURN
--------------------------------------------------

CONJUNCTION:
v1 비활성

T:
STABILITY:+0.55
LONG_TERM:+0.45
COMMUNICATION:+0.65

S:
STABILITY:+0.45
LONG_TERM:+0.35
COMMUNICATION:+0.50

Q:
STABILITY:+0.15
LONG_TERM:+0.10
COMMUNICATION:-0.80

O:
STABILITY:+0.20
LONG_TERM:+0.15
COMMUNICATION:-0.70


--------------------------------------------------
VENUS × SATURN
--------------------------------------------------

C:
STABILITY:+0.75
LONG_TERM:+0.85
HARMONY:-0.20

T:
STABILITY:+0.90
LONG_TERM:+0.90
HARMONY:+0.55

S:
STABILITY:+0.75
LONG_TERM:+0.75
HARMONY:+0.45

Q:
STABILITY:+0.20
LONG_TERM:+0.30
HARMONY:-0.80

O:
STABILITY:+0.25
LONG_TERM:+0.35
HARMONY:-0.75


==================================================
13. SATURN RULE COUNT
==================================================

Sun-Saturn:
5

Moon-Saturn:
5

Mercury-Saturn:
4

Venus-Saturn:
5

총:

19 enabled candidate rules.


==================================================
14. TOTAL V1 ASTROLOGY SCORING RULE COUNT
==================================================

Personal planet:
75

Jupiter:
15

Saturn:
19

TOTAL:

109 scoring rules


Generic aspect 5개는
scoring rule이 아니라 reference definition으로 유지한다.

따라서 generic 5개를
109개와 중복 카운트하지 않는다.


==================================================
15. DEFERRED / DISABLED
==================================================

v1 scoring 비활성:

- Sun/Jupiter Square
- Sun/Jupiter Opposition
- Moon/Jupiter Square
- Moon/Jupiter Opposition
- Mercury/Jupiter Square
- Mercury/Jupiter Opposition
- Venus/Jupiter Square
- Venus/Jupiter Opposition
- Mars/Jupiter Square
- Mars/Jupiter Opposition

- Mercury/Saturn Conjunction
- Mars/Saturn all aspects
- Jupiter/Jupiter all aspects
- Saturn/Saturn all aspects
- Jupiter/Saturn all aspects

- Uranus scoring
- Neptune scoring
- Pluto scoring

ASC / House:
context only

MC:
excluded


==================================================
16. OUTER PLANETS
==================================================

Uranus
Neptune
Pluto

는 v1.0 score Catalog에서:

enabled=false

로 유지한다.

기존:

individual outer cap 0.50
aggregate outer cap 15%

계약은 향후 v1.1을 위해 유지하지만,
v1.0에서는 실제 enabled outer feature가 없으므로
적용 결과는 발생하지 않는다.


==================================================
17. WEIGHT RESOLUTION
==================================================

각 rule:

preConfidenceWeight =

aspectBaseWeight
×
ruleWeight
×
pairWeight


ruleWeight:

1.0


pairWeight:

기존 pair override가 존재하면 override 사용.

없으면:

sqrt(
  planetWeightA × planetWeightB
)


Planet weight를
pairWeight 외에 한 번 더 곱하지 않는다.


orbCloseness도 곱하지 않는다.


그 후:

effectiveWeight =

preConfidenceWeight
×
featureConfidence


==================================================
18. CATEGORY MAPPING SCORE BEHAVIOR
==================================================

동일 aspect Feature가
여러 category에 mapping될 경우

하나의 천문 aspect evidence에서
여러 category contribution을 생성할 수 있다.

예:

ASTRO_VENUS_MARS_SQUARE

ATTRACTION +0.90
PASSION +1.00
HARMONY -0.80

이는 3개의 서로 다른 천문 aspect를 의미하지 않는다.

하나의 source evidence가
3개 category score aggregation에 기여하는 것이다.


feature identity에서는
category가 포함되므로
category별 Feature가 각각 생성되는 현재 계약을 유지한다.

즉 실제 Structured Feature 예:

ASTRO_VENUS_MARS_SQUARE / ATTRACTION
ASTRO_VENUS_MARS_SQUARE / PASSION
ASTRO_VENUS_MARS_SQUARE / HARMONY

각각 별도 featureId를 가진다.


==================================================
19. BIRTH TIME
==================================================

109 scoring rule:

requiresBirthTime=false


그러나 Moon 등 위치 불확실성이 발생하면
기존 unknown-time candidate aggregation 계약을 적용한다.

즉:

requiresBirthTime=false

는

confidence=1 guaranteed

를 의미하지 않는다.


ASC / House:

requiresBirthTime=true
contextOnly=true


==================================================
20. PASSION SAFETY DEFINITION
==================================================

PASSION은:

chemistry
energy
activation
mutual drive

를 의미한다.

성적 행위나 성인 콘텐츠를 의미하지 않는다.

AI interpretation도
all-ages-safe 표현을 사용한다.


==================================================
21. CATALOG STORAGE STRUCTURE
==================================================

proposal 분석용 필드를
현재 rule object top-level에 무조건 추가하지 않는다.

실제 Catalog 저장 방식은
현재 Schema와 호환되도록 다음 중
하나의 정식 구조로 동기화한다.

권장:

ruleId
source
enabled
categoryMappings
baseWeight
ruleWeight
pairWeight 또는 pairWeightRef
requiresBirthTime
contextOnly
version
metadata


Astrology-specific identity:

planetA
planetB
aspect
exactAngle

등은 astrology rule schema 확장으로
정식 정의해야 한다.

additionalProperties=false를 유지한다.

즉 임의 metadata free-form으로 숨기지 않는다.


==================================================
22. REQUIRED SCHEMA UPDATE WHEN APPLYING
==================================================

실제 Catalog 적용 단계에는 최소:

- astrology rule structure
- decimal signedValue
- direction/sign consistency
- planet enums
- aspect enums
- category mapping array
- requiresBirthTime boolean
- pairWeight resolution
- orb definition/reference
- additionalProperties=false

를 Schema에 반영한다.


==================================================
23. VALIDATION REQUIREMENTS
==================================================

메모리에서 proposal을 재생성한 뒤 확인:

1. enabled scoring rule count = 109

2. Personal = 75

3. Jupiter = 15

4. Saturn = 19

5. Duplicate ruleId = 0

6. Canonical pair order 위반 = 0

7. enabled rule의 빈 categoryMappings = 0

8. signedValue 범위 위반 = 0

9. direction/sign mismatch = 0

10. unknown category = 0

11. requiresBirthTime null = 0

12. MC rule = 0

13. Outer enabled rule = 0

14. Jupiter Square/Opposition enabled = 0

15. Mercury-Saturn conjunction enabled = 0

16. Mars-Saturn enabled = 0

17. Jupiter-Saturn enabled = 0

18. pairWeight source unresolved = 0

19. planetWeight double multiplication = 0

20. orbCloseness score multiplier = 0

21. effectiveOrb가 PLUS contract와 일치

22. circular angular-distance test PASS

23. inclusive orb boundary PASS

24. inactive aspect에 orbCloseness Feature가 생성되지 않음


==================================================
24. TASK
==================================================

위 결정을 기준으로

ASTROLOGY CATALOG V1 FINAL PROPOSAL

을 메모리에서 생성하고 검증해주세요.

아직:

docs
contracts
schema
PHP
WordPress
Frontend
DB

파일은 수정하지 마세요.

commit/push도 하지 마세요.


최종 보고:

ASTROLOGY CATALOG V1 FINAL VALIDATION REPORT

A. Total Rule Count
B. Personal Rule Count
C. Jupiter Rule Count
D. Saturn Rule Count
E. Category Mapping Count
F. Positive Mapping Count
G. Negative Mapping Count
H. Mixed Rule Count
I. Pair Weight Resolution
J. Orb Validation
K. Unknown-Time Validation
L. Schema Changes Required
M. Duplicate / Canonicalization Validation
N. Deferred Rules
O. Contract Conflicts Remaining
P. Recommended enabled Rule Count
Q. Astrology Catalog Approval
PASS / FAIL
R. Score Engine Readiness