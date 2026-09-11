<!-- Historical approval: Daily status/signal availability and readiness statements are superseded by daily-catalog-v1.md (C21-R). Lifetime, security and other unaffected approvals remain authoritative. -->
# LOVE FORTUNE
# ASTROLOGY SCORING RULE CATALOG v1
# REPOSITORY APPLICATION

ASTROLOGY CATALOG V1 FINAL VALIDATION REPORT가 PASS 되었습니다.

승인 상태:

Astrology Catalog v1 Design:
APPROVED

Approved scoring rules:
109

Approved category mappings:
228

Positive mappings:
170

Negative mappings:
58

Mixed rules:
26


이번 작업의 목적은
승인된 Astrology Catalog v1을
실제 docs/contracts에 반영하고
Schema / Examples / Validation을 동기화하는 것입니다.


==================================================
IMPORTANT SCOPE
==================================================

수정 가능:

docs/02_FORTUNE_ENGINE_SPEC.md
docs/03_SCORE_SPEC.md
docs/contracts/**
docs/contracts/rules/**
docs/contracts/schemas/**
docs/contracts/examples/**
docs/contracts/validation-report.md

필요하다면 Astrology 전용 계약 문서를
docs/contracts 아래에 추가할 수 있습니다.


수정 금지:

PHP
WordPress plugin source
Frontend
Database implementation
Docker/runtime config

즉 production code는 수정하지 않습니다.

commit/push도 하지 않습니다.


==================================================
1. SOURCE OF TRUTH
==================================================

다음 승인 내용을 Source of Truth로 사용합니다.

ASTROLOGY CATALOG V1 FINAL VALIDATION REPORT의
승인 결과와 직전의

ASTROLOGY SCORING RULE CATALOG v1
FINAL PAIR-ASPECT MAPPING APPROVAL

전체 내용을 사용합니다.

임의로:

- rule 추가
- rule 삭제
- signedValue 변경
- category 변경
- weight 변경
- orb 변경
- deferred rule 활성화

하지 마세요.


==================================================
2. ASTROLOGY RULE CATALOG
==================================================

현재:

docs/contracts/rules/astrology-rules.json

을 승인된 v1 구조로 동기화합니다.


최종 scoring rules:

Personal:
75

Jupiter:
15

Saturn:
19

Total:
109


모든 109개:

enabled = true


Generic:

CONJUNCTION
OPPOSITION
TRINE
SQUARE
SEXTILE

5개 항목은 더 이상 독립 scoring rule로 취급하지 않습니다.

이들은:

Aspect Reference Definition

으로 유지합니다.


즉:

generic aspect definition
≠ scoring rule

입니다.


==================================================
3. RULE ID
==================================================

Canonical planet order:

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


ruleId:

ASTRO_{PLANET_A}_{PLANET_B}_{ASPECT}


예:

ASTRO_SUN_MOON_TRINE
ASTRO_VENUS_MARS_SQUARE
ASTRO_VENUS_SATURN_OPPOSITION


ASCII alphabetical order는 사용하지 않습니다.


==================================================
4. ASTROLOGY RULE STRUCTURE
==================================================

Astrology scoring rule에는 최소 다음 의미가
Schema상 명확히 존재해야 합니다.

ruleId
source
enabled

planetA
planetB
aspect

categoryMappings

baseWeight
ruleWeight

pairWeight
pairWeightSource

requiresBirthTime
contextOnly

version


가능하면 Astrology rule 전용 Schema를 만들어:

astrology-rule.schema.json

형태로 generic rule Schema와 분리하는 것을 우선 검토하세요.


중요:

additionalProperties = false

원칙은 유지합니다.


==================================================
5. PAIR WEIGHT STORAGE DECISION
==================================================

각 Rule에는 계산에 실제 사용되는:

pairWeight

숫자 값을 저장합니다.


동시에 감사/audit 가능하도록:

pairWeightSource

를 저장합니다.


pairWeightSource는 다음 의미를 표현해야 합니다.

OVERRIDE
GENERIC


OVERRIDE인 경우:

기존 pair override table에서 가져온 값.


GENERIC인 경우:

sqrt(
  planetWeightA × planetWeightB
)

로 계산된 값.


중요:

Runtime에서 planetWeight를 다시 추가 곱하지 않습니다.

pairWeight는 이미 최종 pair factor입니다.


==================================================
6. WEIGHT PIPELINE
==================================================

공식:

preConfidenceWeight =

baseWeight
×
ruleWeight
×
pairWeight


ruleWeight = 1.0


effectiveWeight =

preConfidenceWeight
×
featureConfidence


금지:

× planetWeight 추가 곱
× orbCloseness
× aspectStrength


==================================================
7. CATEGORY MAPPING SCHEMA
==================================================

각 categoryMapping:

category
direction
signedValue


category enum:

ATTRACTION
EMOTION
COMMUNICATION
PASSION
STABILITY
HARMONY
SUPPORT
LONG_TERM


signedValue:

-1.0 <= value <= 1.0


direction:

POSITIVE:
0 < signedValue <= 1

NEGATIVE:
-1 <= signedValue < 0

NEUTRAL:
signedValue = 0


direction/sign consistency를
Schema 또는 validation에서 반드시 강제합니다.


109개 enabled Rule 중:

빈 categoryMappings:

0


==================================================
8. RULE-LEVEL MIXED
==================================================

Mixed는 categoryMapping의 direction enum이 아닙니다.

한 Rule 안에:

positive mapping
+
negative mapping

이 동시에 존재하면
해당 Rule을 분석상 Mixed Rule로 분류합니다.


예:

ASTRO_VENUS_MARS_SQUARE

ATTRACTION +0.90
PASSION +1.00
HARMONY -0.80


각 mapping direction:

POSITIVE
POSITIVE
NEGATIVE


별도:

direction=MIXED

mapping을 만들지 않습니다.


==================================================
9. FEATURE GENERATION CONTRACT
==================================================

하나의 Astrology aspect Rule이
여러 categoryMappings를 가지면

category별 Structured Feature를 생성합니다.


예:

ASTRO_VENUS_MARS_SQUARE

→ ATTRACTION Feature
→ PASSION Feature
→ HARMONY Feature


각 Feature는 기존 featureId 계약에 따라
서로 다른 featureId를 가집니다.


동일 천문 aspect가 여러 번 계산된다는 뜻은 아닙니다.

하나의 aspect detection result가
여러 category contribution으로 projection되는 구조입니다.


==================================================
10. ASPECT REFERENCE CONTRACT
==================================================

Exact angles:

CONJUNCTION = 0
SEXTILE = 60
SQUARE = 90
TRINE = 120
OPPOSITION = 180


Base weights:

CONJUNCTION = 1.00
OPPOSITION = 0.90
TRINE = 0.90
SQUARE = 0.85
SEXTILE = 0.70


기존 base orb 표와
planet orb adjustment 표를 유지합니다.


==================================================
11. ANGULAR DISTANCE
==================================================

longitude normalize:

0 <= longitude < 360


rawDifference =

abs(longitudeA - longitudeB)


angularDistance =

min(
  rawDifference,
  360 - rawDifference
)


range:

0 <= angularDistance <= 180


aspectOrb =

abs(
  angularDistance - exactAspectAngle
)


==================================================
12. EFFECTIVE ORB
==================================================

반드시 PLUS 공식입니다.

effectiveOrb =

baseAspectOrb
+
max(
  planetOrbAdjustmentA,
  planetOrbAdjustmentB
)


그 후:

effectiveOrb = min(10, effectiveOrb)


예:

Sun-Moon Conjunction = 9 degrees
Sun-Venus Trine = 8 degrees
Mercury-Venus Sextile = 5 degrees


active:

aspectOrb <= effectiveOrb


inclusive boundary를 유지합니다.


==================================================
13. ORB CLOSENESS
==================================================

active aspect에서만:

orbCloseness =

1 -
(
 aspectOrb / effectiveOrb
)


0 <= orbCloseness <= 1


Exact:
1

Inclusive boundary:
0


Inactive:

orbCloseness 생성하지 않음
scoring Feature 생성하지 않음


v1에서 orbCloseness는:

Score multiplier 아님
featureConfidence multiplier 아님


허용 용도:

- request-local evidence ordering
- deterministic interpretation priority
- Feature metadata


Feature metadata에 노출하므로
metadata allowlist에:

orbCloseness

추가.


Schema:

number
minimum 0
maximum 1


운영 log에 raw evidence를 저장한다는 의미가 아니다.


==================================================
14. BIRTH TIME
==================================================

109 scoring rules:

requiresBirthTime = false


Moon 등의 위치가 출생시간 미상으로 불확실하면
기존 candidate aggregation 계약 적용.


requiresBirthTime=false는:

featureConfidence=1

을 보장하지 않는다.


ASC / House:

requiresBirthTime=true
contextOnly=true
scoreWeight=0


MC:

완전 제외.


==================================================
15. DEFERRED RULES
==================================================

아래는 enabled=false 또는 scoring catalog 밖의
deferred 상태를 명확히 기록합니다.


Personal-Jupiter:

Square
Opposition


Mercury-Saturn:

Conjunction


Mars-Saturn:

모든 aspect


Jupiter-Jupiter:

모든 aspect


Saturn-Saturn:

모든 aspect


Jupiter-Saturn:

모든 aspect


Uranus
Neptune
Pluto:

v1.0 scoring enabled rule = 0


ASC / House:

context only


MC:

excluded


==================================================
16. OUTER PLANET POLICY
==================================================

기존:

individual outer cap 0.50
aggregate outer cap 15%

계약은 삭제하지 않습니다.


하지만 Astrology Catalog v1.0에서는

Uranus
Neptune
Pluto

scoring rule enabled = 0

이므로 실제 v1.0 결과에서
outer contribution은 발생하지 않습니다.


향후 v1.1 확장용 계약입니다.


==================================================
17. PASSION
==================================================

PASSION category 정의:

chemistry
energy
activation
mutual drive


all-ages-safe relationship concept입니다.

성적 행위 또는 성인 콘텐츠를 뜻하지 않습니다.


관련 docs 및 AI interpretation 계약에서도
이 정의가 일치하는지 확인합니다.


==================================================
18. SCHEMA UPDATE
==================================================

실제 Schema를 새 Catalog와 동기화합니다.


최소 검증:

planet enum

aspect enum

category enum

signedValue decimal range

direction/sign consistency

non-empty categoryMappings for enabled rule

requiresBirthTime boolean

contextOnly boolean

pairWeight numeric

pairWeightSource

additionalProperties=false


기존 generic rule.schema.json을 변경할 경우
Saju rule 계약을 손상시키지 않는지 반드시 확인합니다.


가능하면:

common rule schema

+

astrology-specific rule schema

구조를 사용하여
Saju와 Astrology의 identity field를 분리합니다.


==================================================
19. EXAMPLES
==================================================

valid examples를 최소 다음 케이스로 추가/동기화:

ASTRO_SUN_MOON_TRINE

ASTRO_VENUS_MARS_SQUARE

ASTRO_MOON_SATURN_OPPOSITION

ASTRO_VENUS_JUPITER_SEXTILE


Mixed example:

ASTRO_VENUS_MARS_SQUARE


unknown-time confidence example 포함.


Orb examples:

exact
inclusive boundary
inactive


invalid examples:

positive direction + negative signedValue

negative direction + positive signedValue

signedValue > 1

signedValue < -1

enabled + empty categoryMappings

invalid planet

invalid aspect

non-canonical pair order

requiresBirthTime = null

MC scoring rule

Outer enabled v1 rule


==================================================
20. VALIDATION
==================================================

적용 후 실제 파일을 대상으로 검증합니다.


Expected invariants:

Scoring Rule Count:
109

Personal:
75

Jupiter:
15

Saturn:
19

Category Mappings:
228

Positive:
170

Negative:
58

Mixed Rules:
26

Neutral:
0


PairWeight:

override = 76 rules
generic = 33 rules
unresolved = 0


Validation:

duplicate ruleId = 0

canonical pair violation = 0

empty mapping enabled rule = 0

duplicate category in same rule = 0

signedValue violation = 0

direction/sign mismatch = 0

unknown category = 0

requiresBirthTime null = 0

MC scoring = 0

Outer enabled = 0

Jupiter hard aspect enabled = 0

Mercury-Saturn conjunction enabled = 0

Mars-Saturn enabled = 0

Jupiter-Saturn enabled = 0

pairWeight unresolved = 0

planetWeight double multiplication = 0

orbCloseness score multiplication = 0


PLUS orb formula:
PASS

circular angular distance:
PASS

inclusive orb boundary:
PASS

inactive Feature handling:
PASS


==================================================
21. EXISTING CONTRACT REGRESSION
==================================================

Astrology Catalog 적용으로 기존:

SC-01 ~ SC-10

Contract Freeze 계약이 깨지지 않아야 합니다.


검증:

SPEC_CONFLICT = 0

Cross-Spec Contradiction = 0


특히:

confidence
coverage
period
guardrail
privacy
signed interpretation context

계약을 변경하지 않습니다.


==================================================
22. VALIDATION REPORT
==================================================

docs/contracts/validation-report.md를
새로운 실제 validation 결과와 동기화합니다.


기존 숫자를 수동 추측해서 입력하지 말고
실제 validation 결과를 사용합니다.


Astrology 상태를 별도로 기록:

Astrology Catalog v1:
APPROVED / APPLIED

Astrology scoring rules:
109


그러나 전체 Score Engine 상태는 아직:

BLOCKED_CATALOG

유지합니다.


이유:

SAJU_RULE_CATALOG_APPROVAL
DAILY_RULE_CATALOG_APPROVAL

이 아직 남아 있기 때문입니다.


기존 외부 blocker:

SAJU_DAY_PILLAR_EPOCH
EPHEMERIS_PROVIDER

도 유지합니다.


==================================================
23. READINESS STATUS
==================================================

이번 작업 성공 시 기대 상태:

Contract Freeze:
PASS


Astrology Catalog v1:
APPLIED


Astrology Catalog Readiness:
READY


Score Engine Readiness:
BLOCKED_CATALOG


Catalog blockers:

SAJU_RULE_CATALOG_APPROVAL
DAILY_RULE_CATALOG_APPROVAL


Production Engine Readiness:
BLOCKED_EXTERNAL


External blockers:

SAJU_DAY_PILLAR_EPOCH
EPHEMERIS_PROVIDER


==================================================
24. FINAL REPORT
==================================================

작업 후 다음 형식으로 보고해주세요.


ASTROLOGY CATALOG V1 APPLICATION REPORT

A. Files Changed

B. Astrology Scoring Rules
Expected: 109

C. Category Mappings
Expected: 228

D. Positive / Negative / Mixed
Expected:
170 / 58 / 26

E. Pair Weight Resolution
Expected:
Override 76
Generic 33
Unresolved 0

F. Schema Validation

G. Examples Validation

H. Orb Validation

I. Unknown-Time Validation

J. Feature Metadata Validation

K. Existing Contract Regression

L. SPEC_CONFLICT Remaining

M. Cross-Spec Contradictions Remaining

N. Code Files Changed

O. git diff --check

P. Astrology Catalog v1
APPLIED / FAIL

Q. Astrology Catalog Readiness
READY / BLOCKED_CATALOG

R. Overall Score Engine Readiness
READY / BLOCKED_CATALOG

S. Production Engine Readiness
READY / BLOCKED_EXTERNAL

T. Remaining Catalog Blockers

U. Remaining External Blockers


중요:

PHP / WordPress / Frontend / DB 코드는
수정하지 마세요.

commit/push도 하지 마세요.