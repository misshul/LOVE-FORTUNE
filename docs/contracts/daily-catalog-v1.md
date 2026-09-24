# Daily Catalog v1 — C21-R

Version: 1.0.0
Status: APPROVED / APPLIED; SAJU active V1, ASTROLOGY Advanced deferred

Current scope: [Zodiac V1](zodiac-catalog-v1.md) supersedes this document's dual-source combination for V1. The Saju9/19 catalog, person/sample pipeline and source-internal M1 denominator remain unchanged. Planetary sections and .50/.50 examples below are preserved Advanced contract history, not active V1 requirements.
Authority: [Application approval](daily-v1-application-approval.md). This decision supersedes earlier Daily status and signal missing-data semantics, including archived v1/v2/v3 decisions. Lifetime formulas are unchanged.

## Catalog and numeric authority

[Actual catalog](rules/daily-rules.json) contains 125 Astrology rules / 266 mappings and 9 Saju rules / 19 mappings, all enabled. [Approved matrix](daily-v1-approved-matrix.json) preserves the exact proposal cells approved by C21-R; columns are Conjunction, Sextile, Square, Trine, Opposition. Codes A/E/C/P/S/H/U/L map to the eight official categories. Positive/negative/neutral direction follows signedValue, including mixed signs across a rule's categories. No calibration or new scoring rule is authorized.

Transit and natal roles are directional and MUST NOT be sorted as a Lifetime symmetric planet pair. Transit Moon/Mercury/Venus/Sun/Mars and natal Sun/Moon/Mercury/Venus/Mars give 5×5×5 rules. Person A/B projection, not transit/natal exchange, is symmetric.

Daily orb is exactly 1 degree, inclusive. Circular distance=min(abs(normalizedLongitudeA-normalizedLongitudeB),360-abs(...)); orb=abs(distance-exactAngle). Angles: 0/60/90/120/180. No planet adjustment. orbCloseness=1-orb for active evidence only; metadata/priority only, never a score or confidence multiplier.

Astrology baseWeight is the existing aspect weight (1/.70/.85/.90/.90 in matrix order); ruleWeight is the existing Daily TRANSIT planet weight; pairWeight=1. preConfidenceWeight=baseWeight*ruleWeight; effectiveWeight=preConfidenceWeight*featureConfidence exactly once. No natal weight, Lifetime pair override or extra result-confidence factor. requiresBirthTime=false; uncertain natal positions use present-candidate mean, candidateAgreement and availabilityRatio, not an invented natal time.

Jupiter/Saturn are DEFERRED_V1, including natal targets. Outer direct score=0; MC excluded. Daily Ten Gods/ASC/House/ContextEvidence export are DEFERRED_NON_BLOCKING, scoring contribution=0. Passion means all-ages chemistry/energy/activation, not sexual content.

## Saju ownership and reference

Daily stem vs natal DAY stem: COMBINATION > SAME > GENERATION/CONTROL. Daily branch vs natal DAY branch: LIUHE > CLASH > element fallback (SAME/GENERATION/CONTROL). Tables are the unchanged Saju catalog referenceTables. SAME means same element in these coarse families. At most one stem and one branch owner per person/sample/candidate. Preserve DAILY_TO_NATAL or NATAL_TO_DAILY lineage for generation/control; both use the approved coarse family in v1. This is product normalization, not a claim of traditional equivalence. No 刑/害/破/Hidden Stem scoring.

| Daily family | baseWeight | Existing weight source |
|---|---:|---|
| STEM_COMBINATION | .90 | STEM_COMBINATION |
| STEM_SAME | 1.20 | DAY_MASTER_RELATION |
| STEM_GENERATION | .85 | ELEMENT_SUPPORT |
| STEM_CONTROL | .80 | ELEMENT_CONTROL |
| BRANCH_LIUHE | 1.00 | BRANCH_COMBINATION |
| BRANCH_CLASH | 1.00 | BRANCH_CLASH |
| BRANCH_SAME | 1.20 | DAY_BRANCH_RELATION |
| BRANCH_GENERATION | .85 | ELEMENT_SUPPORT |
| BRANCH_CONTROL | .80 | ELEMENT_CONTROL |

Saju ruleWeight=pairWeight=1; requiresBirthTime=false. Candidate uncertainty still applies.

DAILY_SAJU_REFERENCE_V1 is Asia/Seoul, longitude 127.5E, not the user's current location. UI date stays in targetTimezone. Resolve nominal targetTimezone sample through DST to an actual instant; convert to reference local civil datetime; apply (540-historical_utc_offset_minutes)+round((longitude-127.5)*4) minutes once; then evaluate the 23:30 day boundary. Use the reference zone's historical offset at that instant. Day-pillar epoch is resolved by the approved saju-day-pillar-epoch-v1.md contract and production calculator. Same pillar/evaluation inputs may reuse request-local results; four logical sample slots remain distinct, without fourfold weight/confidence accumulation.

## Person and sample pipeline

For each source/person/category/sample, aggregate usable active Features: signal=sum(effectiveWeight*signedValue)/sum(effectiveWeight). Confidence is the preConfidenceWeight-weighted Feature confidence. No usable evidence from zero-confidence active events gives unavailable. If all relevant evaluations complete but no event is active: COMPUTED_NO_EVENT, signal=0, confidence=1, no fake Feature.

Pair projection follows person aggregation: pairSignal=(signalA+signalB)/2; pairConfidence=(confidenceA+confidenceB)/2. Both persons must be computable (active or no-event), otherwise pair category/sample is unavailable. Different ruleIds can contribute on each side. No shared-direction bonus, conflict penalty, gender or relationship multiplier.

## M1-ELIGIBILITY-AWARE (source-internal retained; dual-source examples ADVANCED)

Structural sets are derived from enabled mappings, not detected events:

| Source | Eligible categories |
|---|---|
| ASTROLOGY | ATTRACTION, EMOTION, COMMUNICATION, PASSION, HARMONY, SUPPORT |
| SAJU | ATTRACTION, EMOTION, COMMUNICATION, PASSION, STABILITY, HARMONY, SUPPORT |
| LONG_TERM | No eligible source |

STRUCTURALLY_INELIGIBLE is excluded from the approved denominator. ELIGIBLE_UNAVAILABLE retains its approved denominator weight but provides no numerator term. ELIGIBLE_AVAILABLE supplies its computed value. COMPUTED_NO_EVENT supplies a computed 0 and participates in the denominator; do not invent neutral evidence. With no computable eligible entries, result is unavailable, not a computed zero.

For source sample signal, denominator=sum(categoryWeight over source-eligible categories), regardless of runtime availability. Numerator uses computable pair category signals. Existing category weights .12/.17/.14/.10/.15/.12/.08/.12 are unchanged; denominators are Astrology .73, Saju .88. Source sample confidence retains the approved weighted mean over computable categories; confidence is separate from signal attenuation.

Overall source combination keeps .50 Astrology/.50 Saju with fixed denominator1. One unavailable source does not cause signal reweighting: Saju .4 and Astrology unavailable gives .2. With neither source available, preserve lifetime/delta0/INSUFFICIENT_PERIOD_DATA fallback; exclude it from period aggregates.

Category source denominator contains only structurally eligible sources, each with approved weight .50. Runtime unavailable sources remain in that denominator. Thus EMOTION with Saju .4 and Astrology unavailable gives .2, whereas STABILITY with Saju .4 and Astrology structurally ineligible gives .4. LONG_TERM is structurally unavailable; do not synthesize a normal neutral category. No category evidence retains the internal insufficient-period fallback, not a computed normal state.

## Four logical samples

Samples are 06:00/12:00/18:00/23:00 in targetTimezone. Gap: first valid instant after nominal local time. Fold: earlier UTC instant. Pin timezoneDataVersion. Logical sample identity uses nominal date/time/timezone even if DST resolves slots to the same instant.

Mean and signed peak use computable samples only. Peak has greatest absolute signal; ties use earlier nominal local sample. sourceDailySignal=clamp(.75*mean+.25*signedPeak,-1,1). Zero computable samples means unavailable. Source confidence=sum(confidence of four logical slots, unavailable=0)/4. Both available sources combine confidence .50/.50; one uses its confidence; neither gives0. This confidence rule is unchanged by M1 signal attenuation.

Apply temporal aggregation separately to each category's source samples. Overall: category-weighted source sample -> temporal mean/peak -> source combination. Category: category source sample -> temporal mean/peak -> category source combination. Signed peak is nonlinear: do not copy or reverse-derive one pipeline from the other.

Computable-only sample mean can increase magnitude when lower samples are missing. This is V1 KNOWN CHARACTERISTIC ACCEPTED; no new fixed-four-slot signal denominator. Directional bias is V1 SYNTHETIC CHARACTERISTIC ACCEPTED / NOT CALIBRATED, not a real-population claim.

## Score and S2

dailyDelta=18*dailySignal; dailyScore=clamp(lifetimeScore+dailyDelta,0,100). categoryDailyDelta=18*categoryDailySignal; categoryDailyScore=clamp(lifetimeCategoryScore+categoryDailyDelta,0,100). Preserve ±18. Null lifetime propagates null/INSUFFICIENT_DATA. Existing canonical four-decimal HALF_UP wire/status precision contract remains; internal period aggregation retains full precision.

| S2 status | Daily delta |
|---|---|
| VERY_LOW | delta <= -5 |
| LOW | -5 < delta <= -2 |
| STABLE | -2 < delta < 2 |
| GOOD | 2 <= delta < 5 |
| VERY_GOOD | delta >= 5 |

Missing period/lifetime overrides still apply. SC-07 v2 is Lifetime-only: NOT_APPLIED_TO_DAILY_V1. No 50-point raw scale or 20-point single-feature guardrail in Daily.

## Identity, wire and privacy

[daily-evidence.schema.json](schemas/daily-evidence.schema.json) is INTERNAL. The Zodiac V1 application explicitly updates public Feature/LFIC metadata and static zodiacContext. Optional final categoryScores is closed and COMMUNICATION=null; internal categoryDailySignal and sample lineage export remain deferred. Internal metadata must not be silently injected into public wire.

Daily identity extends the canonical Feature identity with sampleRef={date,time,timezone} and source-specific lineage. Astrology: transitPlanet,natalPlanet,aspect. Saju: dailyStem/natalDayStem or dailyBranch/natalDayBranch, relation, relationDirection, referenceId. Group candidates by ruleId+subject+category+period+sampleRef; cross-sample merge is forbidden. For ambiguous Saju lineage, preserve sorted unique lineage tuples separately and use that stable set in identity; do not fabricate a single natal pillar. Same sample/semantic evidence yields the same ft_ digest; different nominal sample yields a different digest. Exclude values/confidence/orb/candidates from identity. No tracking use.

Birth logs, URL Birth data, derived user caches, Daily history DB, stable anonymous fingerprinting and background Birth transmission remain prohibited. Cache-Control: no-store. Only request-local reuse and non-personal reference caches are permitted. AI remains scoreless and independent; numeric Daily results are deterministic.

## Historical C21-R gates (current V1 state: readiness.md)

Daily APPLIED/READY is a catalog contract, not an implemented engine. [Readiness](readiness.md) distinguishes Score Engine catalog readiness from Production BLOCKED_EXTERNAL (EPHEMERIS_PROVIDER; epoch resolved by SAJU_DAY_PILLAR_EPOCH_V1). Validation commands and reproducibility limits are in [validation report](validation-report.md).
