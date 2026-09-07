# LOVE FORTUNE
# 03_SCORE_SPEC.md

Version: 1.0.0
Status: FINAL
Document Type: LOVE SCORE Specification

---

# 1. 목적

본 문서는 LOVE FORTUNE의 LOVE SCORE 및
Daily / Weekly / Monthly / Yearly Score 규칙을 정의한다.

---

# 2. 점수 기본

- 범위: 0–100
- Neutral: 50
- Internal/response precision: 4 decimals
- UI: rounded display
- Status는 표시 반올림 이전 값 기준

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

# 5. Saju / Astrology Source Weight

```text
Category        Saju    Astrology

ATTRACTION      .30     .70
EMOTION         .40     .60
COMMUNICATION   .40     .60
PASSION         .30     .70
STABILITY       .70     .30
HARMONY         .60     .40
SUPPORT         .55     .45
LONG_TERM       .70     .30
```

---

# 6. Feature Value

```text
signed_value =
direction_sign × strength
```

Direction sign:

```text
POSITIVE  +1
NEGATIVE  -1
NEUTRAL    0
MIXED      category-specific
```

---

# 7. Effective Weight

```text
effective_weight =
rule_weight
× category_rule_weight
× confidence
```

---

# 8. Source Category Raw

```text
raw =
Σ(signed_value × effective_weight)
/
Σ(effective_weight)
```

Range:

```text
-1.0 ~ 1.0
```

---

# 9. Raw → Score

```text
score =
50 + raw × 50
```

---

# 10. Evidence Stabilization

```text
E = Σ effective_weight
```

```text
S = min(1, E / TARGET_EVIDENCE)
```

```text
TARGET_EVIDENCE = 3.0
```

```text
source_final =
50 + (raw × 50) × S
```

Neutral feature는 numerator/denominator에서 제외 가능.

---

# 11. Missing Data

Missing source/feature는 penalty가 아니라
available evidence 기준 reweight한다.

Coverage와 Confidence는 별도로 반환한다.

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

# 15. Astrology Feature Weight

```text
aspect_base_weight
×
planet_pair_weight
×
category_mapping_weight
```

---

# 16. Outer Planet Cap

각 Outer Planet rule weight 최대 0.50.

Outer Planet Feature 전체 기여는
해당 Category Astrology Weight의 최대 15%.

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

# 19. Evidence Minimum

Source/category evidence가 0.50 미만이면
insufficient로 처리 가능.

---

# 20. Display Clamp

Recommended:

```text
Category: 10–95
Lifetime: 15–95
```

Internal raw와 display를 분리한다.

---

# 21. Lifetime Status

```text
0–44     CAUTION
45–59    BALANCED
60–74    GOOD
75–84    VERY_GOOD
85–100   EXCELLENT
```

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

```text
daily_score =
lifetime_score
+
daily_delta
```

```text
daily_delta range = -18 ~ +18
```

Source:

```text
Saju       0.45
Astrology  0.55
```

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

# 25. Daily Transit Aggregation

4 samples:

```text
00:00
06:00
12:00
18:00
```

```text
transit_signal =
0.75 × mean
+
0.25 × signed_peak
```

---

# 26. Daily Display

Recommended:

```text
15–95
```

Category daily delta:

```text
max ±20
```

---

# 27. Daily Status

```text
85–95   VERY_GOOD
70–84   GOOD
50–69   NORMAL
15–49   CAUTION
```

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

Daily action adjustment: max ±10.

---

# 29. Action Status

```text
80+     VERY_GOOD
65–79   GOOD
45–64   NORMAL
<45     CAUTION
```

---

# 30. Weekly

```text
weekly =
mean × .80
+
peak × .10
+
low × .10
```

Least-squares slope:

```text
>= +1 RISING
<= -1 FALLING
otherwise STABLE
```

---

# 31. Monthly

```text
monthly =
daily_mean × .65
+
top5mean × .15
+
bottom5mean × .10
+
major_period_signal × .10
```

Volatility candidate:

```text
stddev >= 9
```

---

# 32. Yearly

Period:

```text
monthly_average × .60
+
saju_annual × .20
+
major_transit × .20
```

User Year:

```text
lifetime × .45
+
period × .55
```

Saju annual energy는 입춘 기준.

---

# 33. Trend

Return:

```text
average
min
max
slope
volatility
positive_days
caution_days
```

---

# 34. Change Significance

Absolute difference:

```text
< 3     STABLE
3–7     NOTICEABLE
>= 8    SIGNIFICANT
```

---

# 35. Relationship Complexity

```text
complexity =
min(positive_intensity, negative_intensity)
```

AI context only.

Score를 직접 변경하지 않는다.

---

# 36. Single Feature Guardrail

하나의 minor rule/aspect/transit/outer feature가
Category를 20점 이상 변경하지 않도록 제한한다.

Critical rule 개념은 사용하지 않는다.

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

Score response는 최소 다음을 포함할 수 있다.

```text
raw
display
status
confidence
coverage
categories
```

---

# 41. Version

```text
score_version = 1.0.0
```

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

동일 Version 결과는 ±0.01 이상 변하지 않아야 한다.

END OF DOCUMENT
