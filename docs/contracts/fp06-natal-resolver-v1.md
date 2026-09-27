# FP-06 Natal resolver and candidate generation

Version: SAJU_NATAL_RESOLVER_V1. Authority: approved FP06 algorithm/behavior decisions and user's FP06 Production Implementation request (attachment94802ead-ae07-4437-9eaf-e7ba53772b2a). This document records the approved behavior without changing FP01-04, epoch, correction or frozen location/timezone data.

## Production entry and internal result

`NatalResolutionService::resolve(string $birthDate, string $birthLocationId, ?string $birthTime = null): array` is an internal application entry, registered as `saju.natal_resolver`. No HTTP route/controller/public candidate DTO is introduced. Array results follow existing Year/Month/Day/Hour conventions; they are not public wire objects. Missing or null birthTime means unknown. Empty time, seconds,24:00 and normalized invalid dates are rejected. Gregorian birthDate is1900-01-01..2099-12-31 inclusive.

Result fields: status,timeKnown,locationReferenceVersion,timezoneReferenceVersion,natalResolverVersion,referenceProvenance,candidates. Each joint candidate owns kind,candidateId,serviceCoordinateUs or half-open serviceRange,historicalOffsetSeconds,adjustmentSeconds,adjustedCivil or half-open adjustedCivilRange,yearPillar,monthPillar,dayPillar,hourPillar and non-public provenance. Day retains the existing {calculationDate,pillar} array shape. INTERVAL has serviceCoordinateUs=null and adjustedCivil=null; the internal lower-endpoint invariant probe is not returned as a birth time. Sensitive fields must never be directly serialized to a public API or logged.

## Frozen sources

LocationReferenceRepository reads only approved129 selectable records from the byte-pinned plugin copy of SAJU_LOCATION_REFERENCE_V1.005/006 and Golden-only locations remain unavailable. TimezoneReferenceRepository uses only the byte-pinned SAJU_TIMEZONE_REFERENCE_V1 transition data:23 canonical zones, IANA2026b, integer second offsets, half-open1899..2101 service envelope. Each public reference is lazily loaded once per repository instance; no user input or derived calculation is cached. A wrong version, modified bytes, missing reference or remote stream path fails closed. The configurable filesystem path is for deployment/testing, never a request parameter or identity override.

UTC DateTime objects in NatalCivilTime are neutral Gregorian arithmetic carriers, not an OS tzdb birth-time resolver. Timezone lookup never calls DateTimeZone/getTransitions. Birth mapping has no TT/DeltaT/TAI/longitude operation on service coordinates.

## Known point and unknown date domain

HH:MM means exactly HH:MM:00.000000. For every frozen source interval with offset o, compute S=nominalCivilUs-o*1000000 and accept only active-interval membership plus exact round-trip. One point is UNIQUE; more than one is FOLD_AMBIGUOUS, with no two-candidate assumption or identical-pillar collapse. Zero is GAP_UNRESOLVED with candidates[], never a normalized neighboring time.

Unknown input maps the entire nominal local date [D00:00,D+1 00:00) into all valid service intervals. This is a domain, not an invented time. Preserve gap/fold structure and original offset/provenance interval identity. A skipped date has GAP_UNRESOLVED/timeKnown=false/candidates[]. Other nonempty unknown domains have UNKNOWN_TIME. Do not assume24 elapsed hours or assign duration weights.

Partition only at timezone provenance transitions, Lichun/twelve Jie boundaries and adjusted23:30 Day boundaries. Existing SolarTermReference.interval supplies next Jie boundaries; no solar recomputation, Hour cuts or Zhongqi cuts. All endpoints are exact integer microseconds, start inclusive/end exclusive. Merge adjacent pieces only with equal provenance/offset/Year/Month/Day and no crossed gap, fold lineage or original transition. Order by start,end,offset,transition identity (points by service coordinate), then assign C0,C1,... per result/person/request. No birth-derived hashes, persistence, global counters, independent pillar arrays or Cartesian products.

## Atomic calculation

AdjustmentSeconds=32400-historicalOffsetSeconds+60*HALF_UP(4*(longitudeMicrodegrees-127500000)/1000000), ties away from zero. The context factory applies this once to neutral local civil values. Year and Month consume exactly the same unadjusted service coordinate. Day uses CalculationDateResolver.fromAdjusted then the unchanged DayPillarCalculator. Known Hour consumes that same adjusted civil value and that same candidate Day result, with the existing Hour conventions. Unknown Hour always uses HourPillarCalculator.unknown().23:00 Zi start and23:30 Day/Hour-stem switch are unchanged.

## Failure and privacy

Internal static error messages: INVALID_DATE,UNSUPPORTED_DATE,INVALID_BIRTH_TIME,LOCATION_NOT_FOUND,LOCATION_VERSION_MISMATCH,TIMEZONE_NOT_FOUND,TIMEZONE_VERSION_MISMATCH,REFERENCE_INCOMPATIBLE,CANDIDATE_RESOLUTION_FAILED. Downstream calculation failures produce no partial candidate result. Gap is a normal status. No raw input/path/downstream exception text is put in these messages; an eventual API error adapter must not expose traces or internal results.

No raw birth/location/instant/adjusted time/longitude/candidate logging, analytics, shared user cache, network lookup or DB writes. LocationReferenceProvider is an internal dependency seam: production composition always binds the pinned repository; test-only providers supply Lord Howe/Apia without creating public IDs.

## Verification and scope

NatalResolverTest exercises actual reference lookup, known point/fold/gap, Golden-only30-minute transitions/date skip, synthetic three-way mapping/order, unknown23/25-hour domains, Lichun/Jie/Day partitions, absent Hour/Zhongqi splits, interval endpoint invariance, same-candidate pairing, maximal normalization, exact correction/historical seconds, all129 locations at both public date edges, source corruption, ambient timezone independence and no writes. Existing Goldens are unchanged. [Implementation report](fp06-implementation-report.md) records actual regression results.

This completes birth-input-to-atomic-Natal-candidates integration only. Feature extraction, confidence/coverage scoring, Lifetime/Daily orchestration, public REST/signing, frontend and AI remain separate tasks.
