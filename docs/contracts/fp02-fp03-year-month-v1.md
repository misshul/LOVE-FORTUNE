# FP-02 / FP-03 Year and Month Pillar v1

Authority: approved FP-02/FP-03 final contract review and subsequent Year/Month production implementation request, 2026-09-25. This records those decisions; it does not revise FP-01.

## Input and identity

One resolvedServiceCoordinateUs: canonical signed decimal integer microseconds on SERVICE_PROLEPTIC_POSIX_V1. No floats, civil-time strings, inferred time, longitude correction or Day Pillar adjusted civil values. FP06 location/DST/candidate generation is upstream.

Consume NAOJ_SOLAR_TERMS_1899_2100_V1 with SAJU_TIME_SCALE_BRIDGE_V1 unchanged. The plugin packages a byte-identical copy of the approved docs artifact; it is not a second editable source. Frozen payload checksum: `6fc6165b76951a0267d46fe2a7e7d4da190c7e2119dc8d1ffdef12fed399848c`. Whole-file SHA256: `948f243c049ede8519b8e998d191d6e2a9a49c837120836d101e38b67f332943`. Changes require explicit version approval, never a silent rehash. Runtime validates pinned bytes, metadata, 4,848 events and complete chronological term/year identities. Source attribution: (C) NAOJ; full provenance and use terms remain in [FP-01](fp01-solar-reference-v1.md).

Runtime performs no bridge calculation, astronomy, network, database or shared user cache. A reader lazily loads once per instance and retains public reference rows only. Signed 64-bit PHP is required. The deployment includes config/references/solar-terms-v1.json; runtime does not depend on a docs mount. The scoped .gitattributes preserves its exact bytes.

## FP-02

Year interval: [Lichun(Y), Lichun(Y+1)). The latest Lichun at or before the coordinate supplies sajuYear. Gregorian birth year is not the year selector.

cycleIndex = floorMod(sajuYear - 1984, 60); stemIndex = cycleIndex % 10; branchIndex = cycleIndex % 12. floorMod(a,n)=((a%n)+n)%n. Anchor 1984=甲子/index0. Stem order 甲乙丙丁戊己庚辛壬癸; branch order 子丑寅卯辰巳午未申酉戌亥. Formula version SAJU_YEAR_PILLAR_V1.

Internal array result: sajuYear, cycleIndex, stemIndex, branchIndex, stem, branch, ganzhi, lichunEventId, nextLichunEventId, solarTermReferenceVersion, timeScaleBridgeVersion, artifactChecksum, formulaVersion. Existing array-result conventions are retained; no REST schema is introduced.

## FP-03

Month interval: [start Jie, next Jie). Ordered monthIndex 0..11: LICHUN, JINGZHE, QINGMING, LIXIA, MANGZHONG, XIAOSHU, LIQIU, BAILU, HANLU, LIDONG, DAXUE, XIAOHAN. branchIndex=(monthIndex+2)%12. The other twelve Zhongqi never switch month. Gregorian/lunar/leap months are not used.

Obtain yearStemIndex from FP-02 at the identical coordinate and reference instance. tigerStemIndex=(2*(yearStemIndex%5)+2)%10; stemIndex=(tigerStemIndex+monthIndex)%10. Five Tiger first-month stems for yearStemIndex 0..9: [2,4,6,8,0,2,4,6,8,0]. Formula version SAJU_MONTH_PILLAR_V1.

Internal array result: sajuYear, yearStemIndex, monthIndex, stemIndex, branchIndex, stem, branch, ganzhi, startJieEventId, endJieEventId, solarTermReferenceVersion, timeScaleBridgeVersion, artifactChecksum, formulaVersion. Year and Month transition together at Lichun.

## Boundaries, failures and candidates

Exact boundary belongs to the new interval. Lookup uses integer binary search. The joint supported calculation envelope is [Lichun1899, Lichun2100); both brackets must exist. This includes the entire public birthDate1900..2099 range with buffer events and does not broaden the public API range. Civil date range and timezone resolution remain upstream responsibilities.

Missing/unreadable/nonlocal artifact, mismatched version or checksum, missing/duplicate/reordered events fail closed. Corrupted artifacts are rejected by pinned file identity before their contents can be trusted. Invalid coordinate syntax/overflow and coordinates outside the bracketed envelope explicitly throw; no fallback pillar or request value logging.

Pure calculators accept one coordinate and can be independently called for each supplied candidate. They do not collapse resolver states UNIQUE/FOLD_AMBIGUOUS/UNKNOWN_TIME/GAP_UNRESOLVED, assign confidence or fabricate candidates. Higher layers must preserve joint Year/Month candidate lineage and multiplicity.

## Golden authority and limits

[Fixed PHP fixture](../../wp-content/plugins/love-fortune-core/tests/fixtures/year-month-golden.json) transcribes the approved seven Lichun years, twelve 2000 Jie boundaries and Five Tiger table. Expectations are not generated from production outputs. Exhaustive boundary properties complement these fixed expected values.

Prior approval evidence: [HKO 1984 anchor](https://www.hko.gov.hk/en/gts/time/calendar/pdf/files/1984e.pdf), [HKO stems and branches tables](https://www.hko.gov.hk/en/gts/time/stemsandbranches.htm). Their calendar-year labels support cycle/formula evidence; the Saju Lichun boundary and exact service coordinates come from the approved contract, not from HKO lunar New Year dates.

Hour Pillar, full Natal resolver, unknown-time generation, feature extraction, Saju scoring, Lifetime/Daily orchestration and REST remain outside this implementation. Full FOUR_PILLARS remains PARTIAL.
