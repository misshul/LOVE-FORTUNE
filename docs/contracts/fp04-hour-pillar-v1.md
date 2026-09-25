# FP-04 Hour Pillar v1

Authority: user FP-04 Product Decision Approval and Production Implementation request. Version: SAJU_HOUR_PILLAR_V1. Existing FP-01~03, Day Pillar epoch and time correction semantics remain unchanged.

## Input and calculation

HourPillarCalculator consumes an already-adjusted DateTimeImmutable civil value, the same-candidate existing service array {calculationDate,pillar}, and required convention/version metadata. It reads civil fields without timezone conversion. Signed64-bit PHP is required for integer microseconds.

hourTimeBasis=ADJUSTED_CIVIL_TIME; hourBranchConvention=ZI_2300_TWO_HOUR_HALF_OPEN_V1; dayStemSource=SAME_CANDIDATE_APPROVED_DAY_PILLAR; dayBoundaryConvention=ADJUSTED_CIVIL_2330; correctionVersion=KOREAN_LONGITUDE_2330 (the existing approved correction contract, not a new formula). dayPillarVersion=SAJU_DAY_PILLAR_EPOCH_V1; hourPillarVersion=SAJU_HOUR_PILLAR_V1. conventions() exposes code-owned expected metadata; callers must verify it describes their actual inputs rather than relabel raw input.

The upstream adjustment retains32400-historicalOffsetSeconds+60*HALF_UP((longitude-127.5)*4), exactly once. Hour code performs none of it. It does not take location, raw civil time or service coordinates. DateTimeImmutable is treated as an already-validated civil field container; its timezone is not reapplied. Upstream must reject invalid/normalized birth input and unresolved gaps before creating that container.

Branch order: 子丑寅卯辰巳午未申酉戌亥. Starts:23,01,03,05,07,09,11,13,15,17,19,21. Every interval is start-inclusive/end-exclusive. With u=civil microseconds since midnight, hourIndex=intdiv((u+3600000000)%86400000000,7200000000); branchIndex=hourIndex. No rounding.

Use the supplied pillar.stemIndex: ziStemIndex=2*(dayStemIndex%5); stemIndex=(ziStemIndex+hourIndex)%10. Reuse approved stem/branch constants. The23:00 transition changes亥 to子;23:30 changes the approved day stem while remaining子. This intentional product policy must not be treated as an error.

## Integrity and output

The calculator calls existing CalculationDateResolver.fromAdjusted solely to verify calculationDate, without adjustment. Supplied pillar fields are validated against DayPillarCalculator for that approved calculationDate; there is no separate civil-date/23:00 day-stem calculation. Missing/mismatched provenance, date, malformed or inconsistent pillar fields are rejected without including input values in errors.

Existing Day result DTOs contain no candidate identity. Cross-date inconsistencies are rejected locally; distinct candidates with the same date/pillar cannot be distinguished here. Binding candidate lineage is explicitly the upstream responsibility. No public ID is added and this implementation does not claim same-date lineage authentication.

Result fields: known,hourIndex,stemIndex,branchIndex,stem,branch,ganzhi,dayStemIndexUsed,calculationDateUsed,timeBasis,boundaryConvention,dayPillarVersion,hourPillarVersion. Same array-result style as Year/Month/Day. unknown() sets known=false and all calculation fields including calculationDateUsed to null, retaining convention/version metadata. Raw/local timestamps are not returned or logged.

## Resolver states and scope

UNIQUE calls calculate once. FOLD_AMBIGUOUS calls it per resolved candidate without collapsing lineage. GAP_UNRESOLVED has no admissible resolved input; no definite result. UNKNOWN_TIME uses unknown(), never a fabricated00:00/12:00. No stateful dispatch, resolver, candidate generator, correction adapter or Four Pillars orchestration is implemented in FP04.

No public schema/routes, persistence, user-derived cache, astronomy/network dependency, score/feature code or UI is added. Full Four Pillars integration remains PARTIAL even though four individual pillar calculators exist.

## Golden authority

The approved2019-01-27甲子 sequence is fixed in tests/fixtures/hour-pillar-golden.json. Its00:00/00:59/01:00 entries are on2019-01-28. Five Rat stem rows were transcribed from the already-reviewed HKO Table5: https://www.hko.gov.hk/en/gts/time/stemsandbranches.htm . Tests expand the five paired-day-stem rows into120 combinations independently of production expectations. HKO supports the hour table; the23:30 combined policy is the user's explicit product decision.

Run scripts/test.ps1 for production tests. Additional gates: solar-reference.cjs, zodiac-application.cjs and actual WordPress smoke. Synthetic historical-offset/fold probes exercise supplied inputs, not an implemented Natal resolver.
