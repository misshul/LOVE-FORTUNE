# FP-01 Solar-Term reference and time-scale bridge v1

Status: APPROVED / REFERENCE APPLIED. Authority: [final user approval](fp01-final-approval.md).
Artifacts: [reference directory](references/solar-terms-v1/). Build evidence: [report](fp01-build-report.md).
This contract does not approve or implement Year/Month/Hour formulas or the complete Natal resolver.

## Identity and domains

`NAOJ_SOLAR_TERMS_1899_2100_V1` contains 4,848 public calendar events. Its normalized artifact requires `SAJU_TIME_SCALE_BRIDGE_V1`. Version/checksum mismatches must fail closed. Artifact application does not implement a runtime loader.

Keep NATAL_CIVIL_LOCAL, NATAL_RESOLVED_INSTANT, SOLAR_TERM_REFERENCE_TIME and DAY_PILLAR_ADJUSTED_CIVIL separate. Year/Month comparison must never consume the Day Pillar adjusted civil value. SAJU_DAY_PILLAR_EPOCH_V1, longitude correction and the23:30 boundary are unchanged.

The comparison axis is SERVICE_PROLEPTIC_POSIX_V1: proleptic Gregorian, nominal86,400 seconds/day, epoch1970-01-01T00:00:00, signed integer microseconds, negative coordinates supported, no leap-second coordinate. It is neither actual UTC nor continuous SI time. JSON transports microsecond integers as decimal strings, avoiding floating conversion.

## Frozen source and precision

NAOJ Long Term Edition2.1i, years1899..2100, Gregorian, UT+0,24 Solar Terms, explicit deltaT=0. Returned minutes are TT-equivalent under that explicit request, not observed UT1 with physical deltaT=0. The prior raw manifest SHA256 is12af0c28a1c8f72ac1ced8deadd0fd3cff66d76eeb364664c18f314a1bae7744; all202 source bodies were individually verified and reused. Original24:00 rows remain in provenance and normalize to next-day00:00 only for calculation.

sourcePrecision=MINUTE. The frozen TT displayed minute start is a SERVICE_BOUNDARY, not an ASTRONOMICAL_EXACT_SECOND. Convert that instant through the bridge; never round the converted coordinate back to minutes or seconds. Prior TT comparison P95~28.56s/max~30.04s is not a bound on historical/future civil-time accuracy. No new ephemeris comparison is claimed by this application.

## Exact era bridge

Let t and s denote nominal seconds on the TT-labelled calendar and service calendar respectively, both relative to1970-01-01; MJD(s)=40587+s/86400. All decimal source coefficients are exact rationals for this service contract.

- Pre1961: s=t-deltaT[normalized source TT year], using frozen explicit NAOJ S2020 annual values. This is HISTORICAL_CIVIL_APPROXIMATION. All202 annual values are preserved;62 years are used by pre1961 events.
-1961..1971: for each official service interval [start,end), solve s=(t-32.184-base-(40587-referenceMJD)*dailyDrift)/(1+dailyDrift/86400). Retain a solution only when s belongs to that interval.
-1972 onward: s=t-32.184-(TAI-UTC), using the effective service interval from the frozen official table.
- Future: FROZEN_LAST_KNOWN_TAI_MINUS_UTC_V1 extends37s through the reference range.2026-09-24T00:00:00 is the fixed snapshot-day lineage cut; it does not change the37s arithmetic or claim that the whole day was independently observed. This is FROZEN_SERVICE_CONVENTION, not a prediction of future UTC.

Require exactly one applicable solution. Do not choose an interval solely from the TT calendar year around era transitions. Official service intervals have no gaps/overlaps; their images on the TT-labelled axis can have gaps or overlaps at clock steps. Reject nonexistent/nonunique conversions rather than invent a smear or transition rule. All4,848 actual events have unique conversions.

## Integer boundary

boundaryUs=ceil(exactServiceCoordinateSeconds*1000000), mathematical ceiling even for negatives. No intermediate rounding, HALF_UP or truncation toward zero. For an exact rational n/d with d>0, ceil(n/d)=-floor(-n/d). This is the smallest permitted integer microsecond at or after the exact boundary.

Comparison: natalUs<boundaryUs is BEFORE; natalUs>=boundaryUs is AFTER. Thus boundaryUs-1 is BEFORE and boundaryUs/boundaryUs+1 are AFTER. Fractions arise from conversion and do not increase the astronomical source precision. source TT12:34:00 can map to service12:32:50.816 in the37s convention; it must not become service12:33:00.

## Snapshots and reproducibility

Preserved inputs: source-manifest.json; lossless gzip JSON containers of original NAOJ HTML; original raw hash manifest; original USNO tai-utc.dat; delta-t-s2020.json; historical-drift.json; leap-seconds.json; timezone-identity.json; bridge.json. Source-row hashes use the exact HTML tr substring or official text row bytes. Annual values retain original decimal strings and response hashes. Sources are public calendar data, never user-derived inputs.

The normalized artifact is sorted-key ASCII JSON with compact separators and one LF. metadata.artifactChecksum is SHA256 of those bytes with only that field omitted; the report also records the whole-file hash. Input checksums bind the frozen manifests/metadata. Retrieval timestamps are captured inputs, never build-time clock reads. Runtime consumers must bind approved versions/checksums, not trust an arbitrary self-consistent replacement.

Separate solar reference and bridge versions. Also track deltaT/drift/leap/timezone/location/resolver and Year/Month formula versions. Same input plus all those versions must replay identically. No automatic upstream updates. Changes require new version, full regression, Golden diff and explicit activation/migration.

Timezone identity records actual PHP8.3.33 embedded DB2026.1,598-zone transition digest, PHP binary hash, and distinct OS tzdata2026b hashes. Normalizing absolute solar reference values uses neither timezone data nor a location DB. A complete location database is not created; locationReferenceVersion and FP06 natalResolutionPolicyVersion remain explicitly NOT_IMPLEMENTED/PENDING. Those must be pinned before Natal integration, not fabricated as completed by this build.

## Natal interface and runtime scope

Frozen location/timezone data resolves historical civil offsets with seconds retained. Never substitute today's offset, invent local mean time, or apply longitude correction to Solar-Term comparison. Resolver states: UNIQUE, GAP_UNRESOLVED, FOLD_AMBIGUOUS, UNKNOWN_TIME. Never silently turn unresolved input into one instant. PHP default normalization is not a policy. FP06 UX remains separate; public HH:MM input is unchanged.

Future runtime: load versioned local artifact, verify compatible identity/checksum, compare integers. No NAOJ request, Skyfield, JPL kernel, astronomy calculation or external time-scale API. Python/JS/PHP files under validation are build/review tools only; they are not runtime implementations.

## Provenance and use

NAOJ source: https://eco.mtk.nao.ac.jp/cgi-bin/koyomi/cande/phenomena_sy_en.cgi ; explicit request options and retrieval timestamps are in the manifest. Source attribution: (C) NAOJ. Site use terms: https://eco.mtk.nao.ac.jp/koyomi/site/ . S2020 model context: https://eco.mtk.nao.ac.jp/koyomi/wiki/A6A4A3D42FBFE4C4EA.html . USNO original: https://maia.usno.navy.mil/ser7/tai-utc.dat . TT relation: https://aa.usno.navy.mil/faq/TT . IANA historical coverage limits: https://data.iana.org/time-zones/theory.html . No external source program code or JPL kernel is bundled.
