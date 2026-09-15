# SAJU Day Pillar Epoch v1

Status: APPROVED; application gate and actual results: [readiness](readiness.md), [validation](validation-report.md).
Authority: [repository application approval](saju-day-pillar-epoch-v1-application-approval.md). Supersedes only the unresolved epoch/evidence gate in older decisions. Score catalogs, public wire, privacy and timezone policies are unchanged.

## Normative metadata

```yaml
identifier: SAJU_DAY_PILLAR_EPOCH_V1
calendarConvention: PROLEPTIC_GREGORIAN_CALCULATION_DATE
dayNumberConvention: INTEGER_GREGORIAN_JDN
cycleConvention: ZERO_BASED_JIA_ZI_0_GUI_HAI_59
formula: floorMod(JDN + 49, 60)
formulaVersion: NAOJ_JDN_GANZHI_V1
goldenAnchorDate: "2019-01-27"
goldenAnchorJDN: 2458511
goldenAnchorIndex: 0
goldenAnchorGanzhi: 甲子
evidenceVersion: PHASE3_2026-09-15_APPROVED
```

The formula is normative; the anchor is a GOLDEN_REFERENCE_ANCHOR, not the production calculation origin. The request-local version registry key `saju.day_pillar` contains the identifier. No new public field or invented whole-engine version is introduced.

## Integer arithmetic

Input is a strict Gregorian `YYYY-MM-DD` CALCULATION_DATE only. Reject malformed/impossible dates with INVALID_DATE and out-of-range dates with UNSUPPORTED_DATE. Error messages never include input. Public accepted dates remain 1900-01-01..2099-12-31; the calculator supports 1899-12-30..2100-01-02 inclusively. Internal support does not expand the API. Earlier calendar reform conventions are out of scope.

```text
K = floor((14-month)/12)
JDN = floor((year+4800-K)*1461/4)
    + floor((12*K+month-2)*367/12)
    - floor(floor((year+4900-K)/100)*3/4)
    + day - 32075
index = ((JDN+49)%60+60)%60
stemIndex = index%10; branchIndex = index%12
STEMS = 甲乙丙丁戊己庚辛壬癸
BRANCHES = 子丑寅卯辰巳午未申酉戌亥
```

All division operands in this supported range are positive: PHP intdiv equals floor at each specified division. Intermediate products are below 11 million, safe even for 32-bit integer JDN arithmetic. Timezone integration requires 64-bit timestamps for the full supported interval. Never compute JDN from timestamps divided by seconds per day. JDN+49 and JDN-11 differ by60, proving equivalence for every integer JDN. Immutable calculation results include cycleIndex, stemIndex, branchIndex, stem, branch, ganzhi.

## Integration boundary

`CalculationDateResolver` applies `(540-historical_utc_offset_minutes)+round((longitude-127.5)*4)` to resolved local civil fields exactly once, then the23:30 boundary. Historical offsets retain seconds; multiplying the equation by60 gives `32400-offsetSeconds+60*round(...)`. Longitude rounding uses PHP HALF_UP. Adjusted wall arithmetic uses neutral civil fields so DST is not applied a second time. The date-only calculator never performs these corrections.

- Natal: location reference and gap/fold resolution remain upstream. `natalResolved` consumes their immutable local datetime and resolved longitude; it does not convert natal input to Seoul. No birthLocationId lookup, location database or new natal ambiguity policy is implemented here.
- Daily: `daily` keeps nominal06/12/18/23 slots in targetTimezone, resolving folds to earlier UTC and gaps to the first valid instant, including skipped dates and non-hour gaps. `dailyResolvedInstant` converts that instant to Asia/Seoul and uses127.5E, retaining DAILY_SAJU_REFERENCE_V1. Original slot keys remain distinct. The timezone resolver uses seconds only for transitions, never JDN.
- Unknown natal time: `candidateDates` consumes nonempty upstream calculation-date candidates. It preserves order and multiplicity and never invents00:00/12:00. No confidence calculation or premature candidate collapse is added.
- `fromAdjusted` is an explicit already-adjusted boundary entry point. Do not feed its output through adjustment again.

Local integration uses installed PHP/IANA timezone data; record its runtime version during validation. Deployment must pin timezone data under the existing version contract. Location reference data, full natal candidate generation, four pillars, score engines and API controllers remain later tasks. These implementation tasks are not new external epoch blockers.

## Evidence hierarchy (accessed 2026-09-15)

1. PRIMARY_DAY_GANZHI_AUTHORITY: [NAOJ, ユリウス日について, 暦象年表2023](https://eco.mtk.nao.ac.jp/koyomi/topics/html/topics2023_1.html). Direct Gregorian->JDN and `(JDN+49) mod60`, zero-based甲子. [PDF p2](https://eco.mtk.nao.ac.jp/koyomi/topics/tex/topics2023_1.pdf) confirms癸亥 where HTML extraction garbles the character.
2. INDEPENDENT_FORMULA_CROSSCHECK: [Y.T. Liu, Sexagenary Cycle](https://ytliu0.github.io/ChineseCalendar/sexagenary.html), one-based `1+mod(JD_noon-11,60)`, explicit2019-01-27甲子/JDN2458511.
3. JDN_ALGORITHM_CROSSCHECK: [USNO Gregorian/JD conversion](https://aa.usno.navy.mil/faq/JD_formula), Fliegel-van Flandern integer algorithm and1970-01-01/JDN2440588.
4. OFFICIAL_CYCLE_ORDER_REFERENCE: [HKO stems and branches](https://www.hko.gov.hk/sc/gts/time/stemsandbranches.htm); cycle order, not Gregorian anchor evidence.
5. DATE_VALUE_CORROBORATION: 計算サイト, Gozaaru, 日历网 and auxiliary watashino.net. Per-date URLs are retained in the [20-date fixture](../../wp-content/plugins/love-fortune-core/tests/fixtures/day-pillar-golden.json). Calendar upstream lineage is unverified and is not counted as independent normative evidence. Commercial fortune material is auxiliary only.

Revised EP-07 requires direct official authority, separate formula agreement, multiple calendar corroboration and zero observed value disagreement. It does not require commercial implementations to disclose upstream lineage. EP-01 calculation date; EP-02 zero-based cycle; EP-03 integer Gregorian JDN; EP-04 floorMod; EP-05 ambiguity outside epoch; EP-06 separate ranges; EP-07 revised evidence: all APPROVED.

## Regression and privacy

The approved20 dates and JDN values are fixed public calendar fixtures, not user profiles. PHPUnit tests production against these values and an independent cumulative ordinal, the full inclusive range, adjacent/+60 cycles, negative modulo, leap rules, invalid input,23:30, historical offset, natal reference preservation, Daily gap/fold, candidate multiplicity and public/internal ranges. Existing Daily synthetic scoring populations remain synthetic; no epoch is silently substituted into them.

No DB, cache, network, logging or WordPress dependency in these classes. No raw dates/locations in error messages. Formula approval does not certify production infrastructure capture settings. Existing privacy requirements remain binding.
