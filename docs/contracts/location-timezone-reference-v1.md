# Location and timezone reference V1

Status: APPROVED / BUILT / APPLIED. Application authority: user's 2026-09-25 Location + Timezone Reference Product Decision Approval and V1 Curated Location Scope Finalization + Full Reference Build. No commit/push authorized. This approval does not implement FP-06.

## Scope and identity

`SAJU_LOCATION_REFERENCE_V1`: **129 user-selectable records**: KR17, JP47, US36, CA12, GB7, AU8, NZ2. P3 is DEFERRED. This is curated coverage, not complete national city coverage. The selected object is a specific city/location, never an entire province silently mapped to its capital.

The explicit [manifest](references/location-timezone-v1/location-manifest.json) is authoritative, not a runtime population query. Korean source IDs are the approved17 without replacements. Muan/Hongseong retain the original populated administrative-center point, not province geometry. Japan uses47 distinct GeoNames admin1 codes with individually reviewed city identities (PPLA46 plus approved Tokyo PPLC). Tokyo is the approved Tokyo reference point; it is not replaced with a fabricated Tokyo municipality or Shinjuku record. Admin1 labels come from the separately hashed GeoNames admin1 snapshot; they are display context, not coordinates.

P2 selection: frozen GeoNames population >=500,000 OR PPLC national capital, limited to PPLC/PPLA/PPLA2/PPL.69 candidates were reviewed; four New York City borough records were excluded:5110266 Bronx,5110302 Brooklyn,5125771 Manhattan,5133273 Queens. Retained primary:LOC000003/5128581. [NYC official borough evidence](https://portal.311.nyc.gov/article/?kanumber=KA-02877). Independent cities such as Toronto/Mississauga/Brampton/Surrey and Dallas/Fort Worth are retained; proximity is not a merge rule. Population is the frozen source field, not a claim of current or comparable metro population. All accepted city/administrative-center source identities, feature codes, population, admin1, coordinates and selection reasons are explicit in the manifest; its excludedCandidates contains every exclusion.

The [registry](references/location-timezone-v1/location-registry.json) is append-only. Never renumber, reuse or derive identity from sorted position. LOC000001 Tokyo,002 Seoul,003 New York,004 London are preserved.005/006 are reserved non-public prior candidate IDs; Lord Howe/Apia use fixture IDs only. New active IDs start007; V1 active IDs end131. Replay reads the registry; it never reallocates IDs.

Lord Howe Island2159558 and Apia4035413 are [validation-only](references/location-timezone-v1/validation-only-locations.json), not accepted public birthLocationIds. Public lookup must use only locations.json records. Unknown IDs fail closed: no runtime geocoding, IP/browser fallback, fuzzy substitution or arbitrary free-text birth location.

## Records, numbers, privacy

Each internal record carries locationId,sourceGeoNamesId,displayName,countryCode,admin1,timezoneId,longitudeDecimal,longitudeMicrodegrees,latitudeDecimal,locationReferenceVersion,referenceSource,referenceLicense,referenceChecksum,supportedFrom,supportedTo. Longitude/timezone are calculation dependencies; latitude is retained for the existing public DTO. Internal metadata is not a public DTO extension. A future adapter projects exactly locationId/displayName/timezoneId/latitude/longitude; it may convert decimals to public JSON numbers, but calculations consume exact internal integers. No public schema changed.

Source longitude is preserved as a decimal string, <=6 fractional digits, range[-180,180]. Reject excess precision, invalid syntax and out-of-range values; never silently round. Parse signed integer microdegrees. Exact longitude correction minutes is HALF_UP(4*(microdegrees-127500000)/1000000), ties away from zero. Do not apply the historical offset or longitude correction twice. This reference does not change FP01-04 or the approved FP06 behavior.

Only static/public reference artifacts are shared. A user's selected birthLocationId is user-derived: no raw logs/analytics, fingerprinting or shared user-derived cache. Reference generation contains no real user birth inputs.

## Timezone reference

`SAJU_TIMEZONE_REFERENCE_V1` uses IANA2026b, archive SHA256 `114543d9f19a6bfeb5bca43686aea173d38755a3db1f2eec112647ae92c6f544`. PHP timezone2026.1 is a separate identity. No OS/latest-data fallback.

[Timezones](references/location-timezone-v1/timezones.json):23 zones (21 production plus Australia/Lord_Howe and Pacific/Apia),5,041 transition records including23 effective-start sentinels. Coverage is half-open service coordinates [1899-01-01,2101-01-01); public birth dates remain1900-01-01..2099-12-31 inclusive. The sentinel describes the state at the coverage start, not a real transition. Each record applies until the next start, last until the exclusive end. serviceStartUs is an exact signed decimal integer string; offsetSeconds integer seconds; isDst boolean. Abbreviation is provenance, not calculation logic.

Compile standard regional sources + etcetera + backward using pinned zic. No optional backzone and no leap/right zones. Expand future rule tails through2100. Build-time alias normalization follows2026b Link chains, cycle-checked; canonical IDs alone go into location records. Alias map only includes links targeting the supported zones. This Natal artifact does not authorize narrowing the public targetTimezone API contract to23 zones.

1900-2099 support means deterministic computation using the frozen IANA reference. It does NOT guarantee perfect physical reconstruction of every pre-1970 historical civil clock. Future transitions are the release's predictions, not a promise about future legislation. Preserve historical second offsets and source LMT; do not round to minutes, substitute current offsets or synthesize LMT from longitude.

Runtime design: local versioned artifacts, verify expected version/checksum before use, reject missing zone/out-of-coverage/corrupt reference. No GeoNames/IANA/network timezone API at runtime. The runtime reader and Natal resolver are NOT implemented by this application. Existing PHP/OS tzdb behavior is not automatically certified by the new artifact.

## Checksums, updates and reproducibility

Canonical JSON: UTF8, lexically sorted object keys, compact separators, LF. artifactChecksum is SHA256 over the complete object excluding its own top-level artifactChecksum. referenceChecksum is SHA256 over the record excluding referenceChecksum. [Replay evidence](references/location-timezone-v1/replay-validation.json) also records complete file-byte hashes, builder image and immutable input hashes. Retrieval metadata remains in source-manifest.json, outside artifact payload hashes. These hashes are intentionally different domains.

Source archives and notices are packaged for offline replay. The source URLs are mutable; never replace frozen bytes with a fresh download under the same version. Source/admin labels retain original data spelling. No claim of localized UI implementation is made.

Any scope/source/coordinate/timezone change requires a new explicit manifest/reference version, full reference and FP06 regressions, Golden diff and explicit activation. No silent updates.

Validation and build instructions: [reference README](references/location-timezone-v1/README.md). Application evidence: [build report](location-timezone-build-report.md).
