# LOVE FORTUNE
# SAJU FOUR PILLARS
# FP-01 FINAL CONTRACT APPROVAL
# READ-ONLY

현재 상태:

FP-01 TIME-SCALE BRIDGE CONTRACT PROPOSAL

Result:
NEEDS_PRODUCT_DECISION


아래 Product Decisions를 승인합니다.


==================================================
1. PRODUCT DECISIONS — APPROVED
==================================================

PD-01 PRE-UTC APPROXIMATION

APPROVED.

1900~1960 historical civil time에 대해:

- versioned location reference
- frozen IANA timezone data
- historical offset
- approved ΔT snapshot

을 이용한 deterministic service convention을 허용합니다.

이 결과를 당시 civil/astronomical time의
완전한 물리적 복원이라고 표현하지 않습니다.

Contract wording:

HISTORICAL_CIVIL_APPROXIMATION


==================================================
2. FUTURE FREEZE
==================================================

PD-02 FUTURE TIME FREEZE

APPROVED.

SAJU_TIME_SCALE_BRIDGE_V1에서는
현재 승인된 leap-second/TAI-UTC snapshot을 고정합니다.

현재 후보:

TAI-UTC = 37 seconds
for the frozen future service convention.


중요:

이 값은 2099년 실제 미래 UTC를 예측하거나
보장한다는 의미가 아닙니다.

이는 오직:

FROZEN_SERVICE_CONVENTION

입니다.


향후:

UTC standard
leap-second policy
time-scale data

가 변경되어도 기존 V1 계산은 자동 변경하지 않습니다.


변경 적용 시:

new bridge version
full regression
Golden diff
explicit migration/activation

필수.


==================================================
3. PRECISION LIMIT
==================================================

PD-03 SOLAR-TERM PRECISION

APPROVED.

NAOJ source precision:

MINUTE


Frozen source minute의 시작점을
service boundary로 사용합니다.


Boundary predicate:

natal < boundary
→ BEFORE

natal >= boundary
→ AFTER


`:00`은:

SERVICE_BOUNDARY

이며:

ASTRONOMICAL_EXACT_SECOND

가 아닙니다.


기존 검증:

TT comparison
P95 abs difference ≈ 28.56 sec
max ≈ 30.04 sec


이 값은 Solar-Term source comparison 결과이며
전체 historical/future natal 판정 오차 보장이 아닙니다.


==================================================
4. CANONICAL COMPARISON AXIS
==================================================

Approve candidate:

SERVICE_PROLEPTIC_POSIX_V1


Properties:

- Proleptic Gregorian
- nominal 86,400 seconds/day
- epoch 1970-01-01T00:00:00
- signed integer microseconds
- negative coordinates allowed
- no leap-second coordinate
- must NOT be named actual UTC
- must NOT be named continuous SI time


Purpose:

deterministic service comparison coordinate only.


==================================================
5. TIME DOMAIN SEPARATION
==================================================

Keep explicit separation:

NATAL_CIVIL_LOCAL

NATAL_RESOLVED_INSTANT

SOLAR_TERM_REFERENCE_TIME

DAY_PILLAR_ADJUSTED_CIVIL


DAY_PILLAR_ADJUSTED_CIVIL must never be used
for Year/Month Solar-Term comparison.


Existing Day Pillar:

longitude correction
23:30 boundary
SAJU_DAY_PILLAR_EPOCH_V1

unchanged.


==================================================
6. PRE-1961
==================================================

Approve contract direction:

Natal:
historical timezone/location reference
→ SERVICE_PROLEPTIC_POSIX_V1


Solar-Term reference:

TT source
→ frozen ΔT model
→ modeled UT1-like civil coordinate
→ SERVICE_PROLEPTIC_POSIX_V1


ΔT candidate:

versioned NAOJ S2020 annual snapshot.


However:

the actual snapshot must still be generated,
checksummed and validated before repository application.


==================================================
7. 1961-1971
==================================================

Approve:

official historical TAI−UTC drift relationships
are applied during reference generation.


Runtime must not calculate rubber-second relations.


Reference generation must:

- use versioned official table
- validate interval
- reject unresolved conversion
- preserve provenance/checksum


==================================================
8. 1972+
==================================================

Approve:

TT = UTC + (TAI−UTC) + 32.184s


Known TAI−UTC table is used
during boundary generation.


Runtime uses already converted service coordinates.


Natal POSIX coordinate must not have leap seconds
added a second time.


==================================================
9. FUTURE
==================================================

Approve:

FROZEN_LAST_KNOWN_TAI_MINUS_UTC_V1


Current candidate snapshot:

37 seconds


Do NOT describe this as future physical UTC truth.


Metadata must explicitly contain:

futureConvention
timeScaleDataVersion
snapshotDate


==================================================
10. BOUNDARY CONVERSION
==================================================

Source:

NAOJ frozen TT displayed minute.


Interpretation:

sourcePrecision = MINUTE

sourceBoundary =
start of frozen source minute


Then apply approved bridge.


Do NOT round the converted service boundary
again to minute or second.


If integer microseconds are required:

compute exact/high-precision converted coordinate

then use the smallest integer microsecond that
preserves the approved >= boundary semantics.


Document exact rounding rule.


==================================================
11. VERSIONING
==================================================

Approve separate versions:


Solar-Term Reference Version

and

SAJU_TIME_SCALE_BRIDGE_V1


A normalized boundary artifact identity depends on:

solarTermReferenceVersion
+
timeScaleBridgeVersion


Also track:

timezoneDataVersion
locationReferenceVersion
natalResolutionPolicyVersion
preUtcDeltaTTableVersion
historicalUtcDriftTableVersion
leapSecondTableVersion


Incompatible combinations must fail closed.


==================================================
12. REPRODUCIBILITY
==================================================

Required invariant:

same birth input
+
same solar reference
+
same bridge version
+
same timezone/location reference
+
same natal resolver policy
+
same Year/Month formula version

=

identical Year/Month result


No automatic upstream data updates.


==================================================
13. FP-06 INTERFACE BOUNDARY
==================================================

Do not finalize Natal DST UX yet.


But require Natal resolver states:

UNIQUE
GAP_UNRESOLVED
FOLD_AMBIGUOUS
UNKNOWN_TIME


Never silently convert unresolved input
into one definite instant.


PHP default normalization behavior
is not the contract.


==================================================
14. FP-01 FINAL CONTRACT
==================================================

Using the approved Product Decisions above,
produce the final FP-01 contract candidate.


FP-01 should include:

referenceMode

primaryAuthority

referenceRange

sourcePrecision

sourceBoundaryConvention

comparisonAxis

preUtcConvention

1961_1971Convention

post1972Convention

futureConvention

coordinateUnit

runtimeDependency

versionDependencies

updatePolicy

reproducibilityRule


==================================================
15. APPROVAL RESULT
==================================================

Expected result if no technical contradiction remains:

FP01_APPROVED_READY_FOR_REFERENCE_BUILD


Otherwise:

FP01_APPROVAL_BLOCKED


Do not use NEEDS_PRODUCT_DECISION again
for the three decisions explicitly approved above.


==================================================
16. NEXT PHASE DEFINITION
==================================================

If approved, define the next phase precisely as:

SOLAR-TERM REFERENCE + TIME-SCALE BRIDGE BUILD


That next phase should:

1.
freeze ΔT snapshot

2.
freeze 1961-1971 drift data

3.
freeze known leap-second data

4.
freeze timezone/location reference version

5.
convert all 4,848 Solar-Term boundaries

6.
generate versioned local reference artifacts

7.
create Golden fixtures

8.
cross-check boundary transformations

9.
only after validation,
apply contracts/reference to repository


Do NOT perform this build in the current task.


==================================================
17. REQUIRED OUTPUT
==================================================

Title:

LOVE FORTUNE
FP-01 FINAL CONTRACT APPROVAL REPORT


A. Result

B. Approved Product Decisions

C. Canonical Comparison Axis

D. Historical Civil Policy

E. Pre-1961 Policy

F. 1961-1971 Policy

G. Post-1972 Policy

H. Future Freeze Policy

I. Solar-Term Boundary Semantics

J. Exact Conversion / Rounding Rule

K. Versioning

L. Reproducibility

M. Natal Resolver Interface Requirement

N. FP-01 Final Contract

O. Remaining Non-Blocking Unknowns

P. Next Phase

Q. Files Changed

Expected:
NONE

R. Commit / Push

Expected:
NONE


==================================================
18. FINAL QUESTIONS
==================================================

반드시 답변:

1.
"FP-01은 승인 완료 상태인가?"

YES / NO


2.
"다음 단계는 무엇인가?"

Expected:

SOLAR-TERM REFERENCE + TIME-SCALE BRIDGE BUILD