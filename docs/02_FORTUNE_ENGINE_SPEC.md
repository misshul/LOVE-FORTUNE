# LOVE FORTUNE
# 02_FORTUNE_ENGINE_SPEC.md

Version: 3.4.0
Status: CONTRACT FROZEN / IMPLEMENTATION READINESS SEPARATE
Document Type: Fortune Engine Specification

Astrology Authority: [Applied Astrology Catalog v1](contracts/astrology-catalog-v1.md) supersedes older Astrology placeholders.
Decision Authority: [Final Decision v3](contracts/final-decision-v3.md), then [v2](contracts/final-decision-v2.md) and [approved clarifications](contracts/clarification-v2.md)
Freeze Gate: [Freeze validation and readiness](contracts/README.md)

---

# 1. 목적

본 문서는 LOVE FORTUNE의 Saju 및 Western Astrology 계산 규칙을 정의한다.

AI는 본 계산에 참여하지 않는다.

---

# 2. Engine Pipeline

Normalized input -> Saju/Astrology -> Structured Features -> Score/Period -> deterministic result. Structured Features feed both Score Engine and AI evidence selection. AI runs through a separate API and is not a Core dependency.

---

# 3. 공통 원칙

- 동일 입력 + 동일 Version = 동일 계산 결과
- 계산 로직은 WordPress UI와 분리
- 계산 데이터와 localization 분리
- Provider/Rule/Score Version 추적
- Birth Time Unknown 지원
- AI 계산 금지

---

# 4. Saju 공식 범위

지원:

- Four Pillars
- Heavenly Stems
- Earthly Branches
- Yin / Yang
- Five Elements
- Day Master
- Ten Gods
- Hidden Stems
- 합
- 충
- 형
- 해
- 파
- Element support/control
- Seasonal weighting
- Relationship feature extraction

고급 학파 의존성이 큰 종격/조후/억부 등은
명시적 규칙이 정의되기 전에는 사용하지 않는다.

---

# 5. Year / Month Pillar

연주/월주는 절기 기준으로 계산한다.

월주 경계의 12 Jie:

```text
입춘
경칩
청명
입하
망종
소서
입추
백로
한로
입동
대설
소한
```

단순 월 번호나 음력 월만으로 월주를 결정하지 않는다.

---

# 6. Day Pillar

Julian Day 기반 sexagenary-cycle 계산을 사용한다.

필수:

- authoritative verified epoch/reference
- boundary test
- historical test
- golden dataset

검증되지 않은 Epoch를 임의 사용하지 않는다.

---

# 7. Korean Time Convention

공식 Production Mode:

```text
KOREAN_LONGITUDE_2330
```

공식 보정:

```text
adjustment_minutes =
    (540 - historical_utc_offset_minutes)
    +
    round((longitude - 127.5) * 4)
```

```text
saju_adjusted_datetime =
    local_civil_datetime
    +
    adjustment_minutes
```

Day Boundary:

```text
23:30
```

Equation of Time:

```text
사용하지 않음
```

Timezone correction과 longitude correction을 중복 적용하지 않는다.

---

# 8. Birth Time Unknown - Saju

Hour Pillar is UNKNOWN; never invent a birth time. Preserve boundary candidates. Group by ruleId+subject+category+period; signedValue and numeric rawValue use candidate means. featureConfidence uses agreement and availability ratio. Normative contract: [Final Decision v2 SC-01](contracts/runtime-contract-v2.md#sc-01-confidence-and-coverage). Normative contract: [Final Decision v2 SC-02](contracts/runtime-contract-v2.md#sc-02-rule-catalog-and-candidates).

---

# 9. Five Elements

두 종류를 분리한다.

```text
raw_count
weighted_strength
```

계절/월지 영향 등을 반영한 weighted strength를 사용한다.

---

# 10. Saju Feature Direction

Hap/Samhap are primary supportive relations; Chung/Hyeong/Hae require contextual interpretation. Do not automatically map every Hap to positive or every Chung to negative. The versioned rule catalog must define category-specific direction, signed value and weight factors. Unregistered rules cannot contribute to production scores (SC-02).

---

# 11. Astrology 기본

- Tropical Zodiac
- Geocentric ecliptic longitude
- Planet Synastry = core

---

# 12. Planet Scope

Core:

```text
Sun
Moon
Mercury
Venus
Mars
```

Secondary:

```text
Jupiter
Saturn
```

Supplementary:

```text
Uranus
Neptune
Pluto
```

Excluded:

```text
MC
Nodes
Chiron
```

---

# 13. ASC / House

ASC와 House는 optional context다.

LOVE SCORE 영향:

```text
0
```

House system:

```text
Placidus
```

고위도/비수렴 시:

```text
Porphyry fallback
```

Warning을 반환한다.

---

# 14. MC

MC는 다음 모든 영역에서 제외한다.

- Compatibility
- Score
- Daily
- Weekly
- Monthly
- Yearly
- AI relationship interpretation

---

# 15. Aspects

지원:

```text
Conjunction
Opposition
Trine
Square
Sextile
```

Base Orbs:

```text
Conjunction 8°
Opposition  8°
Trine       7°
Square      7°
Sextile     5°
```

Planet adjustments:

```text
Sun / Moon            +1°
Mercury / Venus / Mars 0°
Jupiter / Saturn      -1°
Uranus / Neptune / Pluto -2°
```

Approved Astrology v1: effectiveOrb=min(10,baseAspectOrb+max(adjustmentA,adjustmentB)); PLUS. Normalize longitude to[0,360), use circular angularDistance=min(abs(a-b),360-abs(a-b)); aspectOrb=abs(angularDistance-exactAngle), active<=effectiveOrb. Exact angles: conjunction0,sextile60,square90,trine120,opposition180.

---

# 16. Aspect Strength

Active only, orbCloseness=1-aspectOrb/effectiveOrb. It is not a score or confidence multiplier. Exact1,boundary0; inactive creates no closeness/Feature. Use optional metadata.orbCloseness for request-local priority only; no raw evidence logging.

```text
strength =
1 - abs(actual_angle - exact_angle) / effective_orb
```

Clamp:

```text
0.0 ~ 1.0
```

---

# 17. Birth Time Unknown - Astrology

ASC/House are UNKNOWN with zero score weight. Evaluate possible Moon positions across the unknown interval without inventing a representative time. Candidate grouping/agreement/availability follows v2. Normative contract: [Final Decision v2 SC-01](contracts/runtime-contract-v2.md#sc-01-confidence-and-coverage). Normative contract: [Final Decision v2 SC-02](contracts/runtime-contract-v2.md#sc-02-rule-catalog-and-candidates).

---

# 18. Moon Uncertainty

임의 noon Moon sign을 사용하지 않는다.

해당 날짜의 Moon 위치 범위를 평가한다.

```text
CERTAIN
UNCERTAIN
```

UNCERTAIN:

```json
{
  "moonCertainty": "UNCERTAIN",
  "possibleSigns": ["TAURUS", "GEMINI"]
}
```

---

# 19. Ephemeris Provider

Domain은 특정 Provider에 고정하지 않는다.

Interface:

```text
EphemerisProviderInterface
```

Provider metadata:

```text
ephemerisProviderVersion
ephemerisDataVersion
```

후보:

- JPL + Skyfield
- 라이선스 조건을 충족한 Swiss Ephemeris
- 기타 검증된 local ephemeris implementation

최종 Provider는 배포/라이선스/정밀도 검증 후 결정한다.

---

# 20. Daily Astrology / DST

Four nominal samples:06:00,12:00,18:00,23:00 in targetTimezone. Exclude missing samples; source signal=clamp(0.75*mean+0.25*signedPeak,-1,1). Peak ties select earlier local sample. Nonexistent local time moves to first valid instant afterward; ambiguous local time selects earlier UTC instant. Record pinned timezoneDataVersion. Outer daily direct influence remains0. Normative contract: [Final Decision v2 SC-05](contracts/runtime-contract-v2.md#sc-05-daily-sampling-and-dst).

---

# 21. Period Rules

Dependency: Lifetime -> Daily -> Monthly -> Yearly; Weekly also derives from Daily. Monthly is the mean of valid daily scores; Yearly is the mean of valid monthly scores. Remove standalone major_period_signal, saju_annual and major_transit formulas. Period rules contribute versioned structured features through the common pipeline. Saju period features require both people. See 03 for aggregation.

---

# 22. Structured Feature Contract

Fields: featureId,ruleId,source,subject,category,direction,signedValue,rawValue,baseWeight,confidence,period,metadata. subject is PERSON_A/PERSON_B/PAIR. rawValue is scalar or null. Period uses LIFETIME/DAY/WEEK/MONTH/YEAR objects. Metadata is an explicit allowlist. featureId is ft_ plus48 lowercase hex SHA-256 characters over canonical identity fields; signedValue/confidence are excluded. No personal identifiers. Normative contract: [Final Decision v2 SC-04](contracts/runtime-contract-v2.md#sc-04-feature-and-canonical-identity).

---

# 23. Confidence

Normative contract: [Final Decision v2 SC-01](contracts/runtime-contract-v2.md#sc-01-confidence-and-coverage). feature confidence wire field is confidence. coverage is available/eligible pre-confidence weight, not feature count. Category resultConfidence is coverage times weighted feature confidence; overall uses the v2 pre-coverage categoryResultConfidence formula. Missing-input supported rules remain eligible; unsupported/disabled rules do not.

---

# 24. Engine Output

Engine은 다음을 출력할 수 있다.

```text
score_features
context_features
warnings
metadata
versions
```

ASC/House는 context_features,
MC는 포함하지 않는다.

---

# 25. 완료 원칙

Engine 구현은 다음을 만족해야 한다.

- deterministic
- unit tested
- golden tested
- versioned
- AI independent
- timezone aware
- birth-time-unknown aware
- provider abstracted


## Final v3 contract alignment

[Runtime contract](contracts/runtime-contract-v2.md) applies the approved v3 decisions: structural coverage is retained with zero usable confidence; Daily source confidence includes all four samples with missing=0; Weekly is the valid-Daily arithmetic mean; period fallback days are excluded; periodDelta and trend magnitude/direction are separate; Actions use weighted Feature.confidence and cannot alter scores. UI displays deterministic fields; AI cannot alter Action confidence. No storage or new engine rules are introduced. Contract Freeze is independent of BLOCKED_CATALOG (SCORING_RULE_CATALOG_APPROVAL) and BLOCKED_EXTERNAL (SAJU_DAY_PILLAR_EPOCH, EPHEMERIS_PROVIDER).

See [Astrology Catalog v1](contracts/astrology-catalog-v1.md) for canonical planet order, deferred outer/Jupiter/Saturn scope, category Feature projection, orbCloseness allowlist and all-ages PASSION meaning. Existing outer caps, confidence/coverage/period/guardrail/privacy remain unchanged.

END OF DOCUMENT


## Approved Saju v1 / SC-07 v2 application

Current authority: [Saju v1](contracts/saju-catalog-v1.md), [Guardrail v2](contracts/guardrail-v2.md), [ContextEvidence](contracts/context-evidence.md). These supersede historical Saju scoring placeholders, unit-sign restrictions and guardrail search. Saju14 families:9 scoring/5 context, APPLIED/READY. Daily C21-R is APPLIED/READY; catalog blockers NONE. Other privacy/API/time contracts are unchanged.


Current Daily authority: [Daily Catalog v1 C21-R](contracts/daily-catalog-v1.md). Daily134/285 APPLIED/READY; S2 thresholds +/-2 / +/-5; M1-ELIGIBILITY-AWARE signal aggregation. SC-07 applies to Lifetime only. Score Engine catalog readiness READY; catalog blockers NONE; Production BLOCKED_EXTERNAL (SAJU_DAY_PILLAR_EPOCH, EPHEMERIS_PROVIDER). Public/signed wire and privacy remain unchanged.
