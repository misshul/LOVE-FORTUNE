# LOVE FORTUNE
# 03_SCORE_SPEC.md

Version: 3.4.0
Status: CONTRACT FROZEN / IMPLEMENTATION READINESS SEPARATE
Document Type: LOVE SCORE Specification

Astrology Authority: [Applied Astrology Catalog v1](contracts/astrology-catalog-v1.md) supersedes older Astrology placeholders.
Decision Authority: [Final Decision v3](contracts/final-decision-v3.md), then [v2](contracts/final-decision-v2.md) and [approved clarifications](contracts/clarification-v2.md)
Freeze Gate: [Freeze validation and readiness](contracts/README.md)

---

# 1. 목적

본 문서는 LOVE FORTUNE의 LOVE SCORE 및
Daily / Weekly / Monthly / Yearly Score 규칙을 정의한다.

---

# 2. Score Range / Canonical Precision

Category and overall scores range from 0 to 100; neutral is 50. Keep full precision in intermediate calculations. Canonical API scores use four decimal places, HALF_UP (74.123456 -> 74.1235). Determine status from the canonical score before UI rounding. No display clamp may change canonical score/status. Missing evidence follows sections 11 and 19.

---

# 3. Category

```text
ATTRACTION
EMOTION
COMMUNICATION
PASSION
STABILITY
HARMONY
SUPPORT
LONG_TERM
```

---

# 4. Lifetime Category Weight

```text
ATTRACTION      0.12
EMOTION         0.17
COMMUNICATION   0.14
PASSION         0.10
STABILITY       0.15
HARMONY         0.12
SUPPORT         0.08
LONG_TERM       0.12
```

Total = 1.00

---

# 5. Source Weight Application

Use the unified category feature aggregation in decision v1 sections 11-14. The previous lifetime Saju/Astrology percentage blend is superseded; do not add a separate source/category multiplier to effectiveWeight. Daily source weights remain 0.50/0.50 under section 23.

---

# 6. Feature Value

For approved Astrology v1 mappings: POSITIVE means0<signedValue<=1, NEGATIVE means-1<=signedValue<0, NEUTRAL means0. Mixed is rule-level positive/negative coexistence, not a mapping direction. Legacy/Saju inventory retains +1/-1/0 and explicitly defined MIXED values until its own approval. Unregistered rules cannot be scored. The former direction_sign times strength formula is superseded. rawValue is not an additional implicit multiplier.

---

# 7. Effective Weight

preConfidenceWeight=baseWeight*ruleWeight*pairWeight. For outer evidence cap that PRODUCT to0.50, then effectiveWeight=preConfidenceWeight*featureConfidence. categoryWeight applies only to overall. A single existing weight becomes baseWeight with the other factors1. Catalogs may only normalize documented values. Normative contract: [Final Decision v2 SC-02](contracts/runtime-contract-v2.md#sc-02-rule-catalog-and-candidates). Normative contract: [Final Decision v2 SC-03](contracts/runtime-contract-v2.md#sc-03-outer-cap).

---

# 8. Category Weighted Signal

Freeze W0=sum(pre-guardrail usable effectiveWeight) after caps/confidence. R=50+50*sum(adjustedWeight*signedValue)/W0. Never renormalize by adjustedWeight sum. See contracts/guardrail-v2.md; coverage stabilization is unchanged.

---

# 9. Category Raw Score

R = 50 + 50 * weightedSignal, in [0,100]. Apply evidence stabilization exactly once after this step.

---

# 10. Evidence Stabilization / Coverage

categoryScore=50+(R-50)*coverage. coverage=clamp(sum(available preConfidenceWeight)/sum(eligible preConfidenceWeight),0,1), with zero denominator giving0. Do not multiply confidence into coverage. Unsupported rules are ineligible; missing-time supported rules remain eligible. Normative contract: [Final Decision v2 SC-01](contracts/runtime-contract-v2.md#sc-01-confidence-and-coverage).

---

# 11. Missing Data / Confidence

No fixed missing-time penalty. Distinguish eligible, available and usable evidence. Only available Features with confidence>0 and preConfidenceWeight>0 are usable for scoring. No usable evidence gives category50/resultConfidence0/INSUFFICIENT_DATA while retaining structural coverage; exclude that category overall. All insufficient gives null overall. categoryResultConfidence is the preConfidenceWeight-weighted confidence over available evidence; category wire resultConfidence=coverage*categoryResultConfidence. Overall retains the v2 pre-coverage confidence formula. Normative contract: [SC-01 with v3 decisions](contracts/runtime-contract-v2.md#sc-01-confidence-and-coverage).

---

# 12. Astrology Aspect Base Weight

```text
Conjunction  1.00
Opposition   0.90
Trine        0.90
Square       0.85
Sextile      0.70
```

---

# 13. Planet Weight

```text
Sun      1.00
Moon     1.10
Mercury  0.90
Venus    1.10
Mars     1.00
Jupiter  0.75
Saturn   0.90
Uranus   0.45
Neptune  0.45
Pluto    0.50
```

---

# 14. Planet Pair Override

```text
Sun-Moon        1.20
Moon-Moon       1.25
Moon-Venus      1.20
Venus-Venus     1.15
Venus-Mars      1.25
Mercury-Mercury 1.15
Mercury-Moon    1.15
Sun-Sun         1.00
Sun-Venus       1.05
Sun-Mars        1.00
Moon-Mars       1.05
Mars-Mars       0.95
Saturn-Moon     1.10
Saturn-Venus    1.10
Jupiter-Sun     0.85
Jupiter-Moon    0.85
```

Generic:

```text
sqrt(weightA × weightB)
```

---

# 15. Astrology Weight Catalog

Sections12-14 reference tables are retained. Approved Astrology v1 contains109 enabled pair-aspect rules (75 Personal,15 Jupiter,19 Saturn) with228 mappings. Generic aspects are reference definitions only. Actual approved mappings and closed schema are in contracts/astrology-catalog-v1.md. See contracts/rules/astrology-rules.json and Normative contract: [Final Decision v2 SC-02](contracts/runtime-contract-v2.md#sc-02-rule-catalog-and-candidates).

---

# 16. Outer Planet Cap

Uranus/Neptune/Pluto only; Jupiter/Saturn are excluded. preConfidenceWeight=min(0.50,baseWeight*ruleWeight*pairWeight) for outer evidence. Apply featureConfidence, then alpha=min(1,(0.15/0.85)*N/O) to all outer effective weights when O>0. N/O sum non-outer/outer Astrology weights. N=0 means outer context only; O=0 means no scaling. Apply the all-feature guardrail afterward. Normative contract: [Final Decision v2 SC-03](contracts/runtime-contract-v2.md#sc-03-outer-cap).

---

# 17. Supplementary Zodiac

```text
Element max 0.30
Modality max 0.20
```

---

# 18. Saju Base Weight

```text
DAY_MASTER_RELATION    1.20
DAY_BRANCH_RELATION    1.20
STEM_COMBINATION       0.90
BRANCH_COMBINATION     1.00
BRANCH_CLASH           1.00
ELEMENT_COMPLEMENT     1.00
ELEMENT_SUPPORT        0.85
ELEMENT_CONTROL        0.80
TEN_GODS_RELATION      0.75
YIN_YANG_BALANCE       0.45
HIDDEN_STEM_RELATION   0.45
PUNISHMENT             0.60
HARM                   0.50
DESTRUCTION            0.45
```

---

# 19. Evidence Minimum / Overall

Use section 11 for usableEvidence and confidence. The previous optional E<0.50 insufficient-data threshold is superseded.

overallScore = sum(available categoryScore * categoryWeight) / sum(available categoryWeight).

With no available categories return null and INSUFFICIENT_DATA. Preserve section 4 category weights, whose full sum is 1.00. No additional resultConfidence penalty is permitted.

---

# 20. Display

Preserve canonical scores in [0,100]. Remove earlier category/lifetime display clamps. UI may format canonical values but must not recompute status. JSON numeric values need not preserve trailing zeros; the draft schema uses numbers.

---

# 21. Lifetime Status

Determine status from the canonical score:

- [0,45): CAUTION
- [45,60): BALANCED
- [60,75): GOOD
- [75,85): VERY_GOOD
- [85,100]: EXCELLENT

A category with no usableEvidence uses INSUFFICIENT_DATA even though its neutral score is 50.

---

# 22. Explainability

Top Positive Features:

```text
max 5
```

Top Negative Features:

```text
max 5
```

---

# 23. Daily Score

dailySignal=0.50*sajuDailySignal+0.50*astrologyDailySignal; delta=18*signal; score=clamp(lifetimeScore+delta,0,100). One runtime available source retains its approved .50 signal weight; neither gives lifetime/delta0/INSUFFICIENT_PERIOD_DATA. Lifetime null gives null dailyScore and INSUFFICIENT_DATA. Normative contract: [Final Decision v2 SC-05](contracts/runtime-contract-v2.md#sc-05-daily-sampling-and-dst). Normative contract: [Final Decision v2 SC-06](contracts/runtime-contract-v2.md#sc-06-dates-periods-and-statuses).

---

# 24. Daily Planet Weight

```text
Moon      1.00
Venus     0.90
Mercury   0.75
Mars      0.80
Sun       0.60
Jupiter   0.30
Saturn    0.35
Outer     0 direct
```

---

# 25. Daily Transit Aggregation / DST

Versioned samples06:00/12:00/18:00/23:00. For signal, each source excludes unavailable samples; zero samples means unavailable. For confidence, sourceDailyConfidence=sum(four sample confidences)/4, counting unavailable samples as0. Both sources use0.50/0.50; one uses its confidence; none gives0. sourceDailySignal=clamp(0.75*mean+0.25*peak,-1,1), peak by absolute magnitude and earlier nominal local-time tie. Ambiguous time chooses earlier UTC instant; nonexistent time advances to first valid instant. Normative contract: [Final Decision v2 SC-05](contracts/runtime-contract-v2.md#sc-05-daily-sampling-and-dst).

---

# 26. Daily Category

categoryDailyDelta=18*categoryDailySignal from category-specific evidence; categoryDailyScore=clamp(lifetimeCategoryScore+categoryDailyDelta,0,100). No computable category period data gives delta0/periodStatus INSUFFICIENT_PERIOD_DATA. Full precision internally; serialize scores with4-decimal HALF_UP. Normative contract: [Final Decision v2 SC-07](contracts/runtime-contract-v2.md#sc-07-daily-category-actions-and-guardrail).

---

# 27. Daily Status

Use dailyDelta: <=-5 VERY_LOW; (-5,-2] LOW; (-2,2) STABLE; [2,5) GOOD; >=5 VERY_GOOD. No available period sources gives INSUFFICIENT_PERIOD_DATA; null lifetime gives INSUFFICIENT_DATA. No absolute daily-score status bands. Normative contract: [Final Decision v2 SC-06](contracts/runtime-contract-v2.md#sc-06-dates-periods-and-statuses).

---

# 28. Action Category Weights

```text
CONTACT
COMMUNICATION .45
EMOTION       .30
HARMONY       .25

CONVERSATION
COMMUNICATION .55
EMOTION       .25
HARMONY       .20

DATE
ATTRACTION    .25
EMOTION       .20
PASSION       .20
HARMONY       .20
COMMUNICATION .15

CONFESSION
ATTRACTION    .20
EMOTION       .30
COMMUNICATION .25
PASSION       .15
HARMONY       .10

AFFECTION
EMOTION       .35
ATTRACTION    .25
PASSION       .20
HARMONY       .20

GIFT
EMOTION       .30
SUPPORT       .30
HARMONY       .20
COMMUNICATION .20

IMPORTANT_DISCUSSION
COMMUNICATION .40
STABILITY     .25
HARMONY       .25
EMOTION       .10

RECONCILIATION
HARMONY       .35
COMMUNICATION .30
EMOTION       .25
STABILITY     .10
```

Action adjustment = 0; this table is recommendation reference only.

---

# 29. Action Recommendation

Action adjustment=0. Actions are a separate deterministic recommendation layer and never change LOVE SCORE. No evidence means no Action. Require evidenceRefs to actual featureIds. Exclude refs with preConfidenceWeight<=0; actionConfidence=sum(preConfidenceWeight*Feature.confidence)/sum(preConfidenceWeight) over remaining refs. No valid refs means no Action. AI cannot change this confidence. Existing Action weight tables are reference only; no score mutation. Normative contract: [Final Decision v2 SC-07](contracts/runtime-contract-v2.md#sc-07-daily-category-actions-and-guardrail).

---

# 30. Weekly

Valid Daily requires a non-null score and lifetime, no Core error, supported date and dailyStatus not INSUFFICIENT_PERIOD_DATA. Exclude numeric lifetime fallback from aggregates. Weekly/Monthly/Yearly status uses lifetime bands; periodDelta/trendStatus/trendDirection follow section34. ISO Monday-Sunday in targetTimezone. weeklyScore=arithmetic mean of valid full-precision Daily scores. Empty results or null lifetime give null score/INSUFFICIENT_DATA/confidence0. OLS slope uses calendar-day index and points/day; fewer than2 valid days gives null. Confidence averages valid Daily confidence. bestDays/cautionDays max2 each. Normative contract: [Final Decision v2 SC-06](contracts/runtime-contract-v2.md#sc-06-dates-periods-and-statuses). Normative contract: [Final Decision v2 SC-07](contracts/runtime-contract-v2.md#sc-07-daily-category-actions-and-guardrail).

---

# 31. Monthly

monthlyScore=mean(valid full-precision dailyScore), volatility=population standard deviation of those values. Empty or lifetime-null gives null/INSUFFICIENT_DATA/confidence0. resultConfidence=mean(valid daily resultConfidence). best/caution max5 each. Status uses lifetime score bands; trendStatus uses change significance. Normative contract: [Final Decision v2 SC-06](contracts/runtime-contract-v2.md#sc-06-dates-periods-and-statuses).

---

# 32. Yearly

yearlyScore=mean(valid full-precision monthlyScore); yearlyVolatility=population standard deviation. resultConfidence=mean(valid Monthly resultConfidence). Empty or lifetime-null gives null/INSUFFICIENT_DATA/confidence0. bestMonths/cautionMonths max3 each. Status uses lifetime bands; trendStatus uses change significance. No standalone saju_annual/major_transit score. Normative contract: [Final Decision v2 SC-06](contracts/runtime-contract-v2.md#sc-06-dates-periods-and-statuses).

---

# 33. Period Tie / Rounding

Best: internal full-precision score DESC, then resultConfidence DESC, then chronological ASC. Caution: internal score ASC, confidence DESC, chronological ASC. Monthly averages unrounded Daily values; Yearly averages unrounded Monthly values. Round API scores only to4-decimal HALF_UP. Normative contract: [Final Decision v2 SC-06](contracts/runtime-contract-v2.md#sc-06-dates-periods-and-statuses).

---

# 34. Change Significance

periodDelta=periodScore-lifetimeScore using internal full precision. Null operand gives null delta, INSUFFICIENT_DATA trendStatus and UNKNOWN trendDirection. Otherwise direction is STABLE for abs(delta)<3, UP for delta>=3, DOWN for delta<=-3. OLS slope is secondary and does not set trendStatus. For absolute periodDelta d: d<3 is STABLE; 3<=d<8 is NOTICEABLE; d>=8 is SIGNIFICANT. Do not change these thresholds arbitrarily.

---

# 35. Complexity

Not a numeric Score modifier in v1. Optional UI/context information only. No invented intensity formula. Normative contract: [Final Decision v2 SC-07](contracts/runtime-contract-v2.md#sc-07-daily-category-actions-and-guardrail).

---

# 36. Single-feature Guardrail

SC-07 v2 freezes W0=sum(pre-guardrail usable effectiveWeight) after individual caps/confidence/outer cap. impact=50*effectiveWeight*abs(signedValue)/W0; above20 use adjustedWeight=min(effectiveWeight,0.4*W0/abs(signedValue)); zero signal retains weight. Raw=50+50*sum(adjustedWeight*signedValue)/W0, never adjustedWeight sum. No search, passes, exclusion or confidence/coverage change. Legacy SCORE_GUARDRAIL_UNSATISFIED is deprecated. See contracts/guardrail-v2.md.

---

# 37. Symmetry

Lifetime base compatibility:

```text
score(A,B) = score(B,A)
```

Directional Feature는 별도 context로 관리한다.

---

# 38. Bias Rule

- relationshipType: base score 변경 금지
- gender: weight 변경 금지
- heterosexual Venus/Mars assumption 금지

---

# 39. AI Separation

AI는:

- score 생성 금지
- score 변경 금지
- strength 변경 금지
- confidence 변경 금지
- status 변경 금지

---

# 40. Response

Core returns overall/category canonical scores and statuses, coverage, resultConfidence, approved features and calculation versions. It has no interpretation field. Feature confidence retains wire name confidence and means featureConfidence. See 05 and draft schemas for the response envelope.

---

# 41. Version

This document update does not overwrite a released scoreVersion. Approved v3/v2 decisions and normalized catalogs are versioned separately. Astrology109 rules are APPLIED/READY. Overall Score Engine catalog readiness is READY; catalog blockers NONE. Freeze status and the remaining external ephemeris gate are in contracts/README.md.

---

# 42. Config

권장:

```text
/config/score/
```

---

# 43. Test

필수:

- Unit
- Golden Dataset
- A/B Symmetry
- Missing Data
- Low Evidence
- Outer Planet Cap
- Threshold
- Daily/Period Regression

Same provider/software/data/config/version and input require exact canonical output. A named floating regression tolerance is permitted only with an explicit justification; no global +/-0.01 allowance. Golden data must be synthetic, licensed or approved public reference, never real-user input.

See [Astrology Catalog v1](contracts/astrology-catalog-v1.md) for canonical planet order, deferred outer/Jupiter/Saturn scope, category Feature projection, orbCloseness allowlist and all-ages PASSION meaning. Existing outer caps, confidence/coverage/period/privacy remain unchanged; guardrail is superseded by SC-07 v2.

END OF DOCUMENT


## Approved Saju v1 / SC-07 v2 application

Current authority: [Saju v1](contracts/saju-catalog-v1.md), [Guardrail v2](contracts/guardrail-v2.md), [ContextEvidence](contracts/context-evidence.md). These supersede historical Saju scoring placeholders, unit-sign restrictions and guardrail search. Saju14 families:9 scoring/5 context, APPLIED/READY. Daily C21-R is APPLIED/READY; catalog blockers NONE. Other privacy/API/time contracts are unchanged.


Current Daily authority: [Daily Catalog v1 C21-R](contracts/daily-catalog-v1.md). Daily134/285 APPLIED/READY; S2 thresholds +/-2 / +/-5; M1-ELIGIBILITY-AWARE signal aggregation. SC-07 applies to Lifetime only. Score Engine catalog readiness READY; catalog blockers NONE; Production BLOCKED_EXTERNAL (EPHEMERIS_PROVIDER; epoch resolved by SAJU_DAY_PILLAR_EPOCH_V1). Public/signed wire and privacy remain unchanged.
