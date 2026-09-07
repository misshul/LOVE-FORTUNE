# LOVE FORTUNE
# 02_FORTUNE_ENGINE_SPEC.md

Version: 2.0.0
Status: FINAL
Document Type: Fortune Engine Specification

---

# 1. 목적

본 문서는 LOVE FORTUNE의 Saju 및 Western Astrology 계산 규칙을 정의한다.

AI는 본 계산에 참여하지 않는다.

---

# 2. 전체 Engine 구조

```text
Validated Birth Input
↓
Calendar / Time Normalization
├─ Saju Engine
└─ Astrology Engine
↓
Structured Relationship Features
↓
Score Engine
```

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

# 8. Birth Time Unknown — Saju

출생시간을 모르는 경우:

- year/month/day pillar 계산 가능
- hour pillar = null
- hour 기반 feature 제외
- limited precision warning 반환
- 임의 hour 생성 금지

---

# 9. Five Elements

두 종류를 분리한다.

```text
raw_count
weighted_strength
```

계절/월지 영향 등을 반영한 weighted strength를 사용한다.

---

# 10. Saju 관계 Feature

예:

```json
{
  "source": "SAJU",
  "rule": "DAY_BRANCH_COMBINATION",
  "categories": ["HARMONY", "LONG_TERM"],
  "direction": "POSITIVE",
  "strength": 0.85,
  "confidence": 1.0
}
```

합/충은 primary,
형/해/파는 secondary relation으로 취급한다.

규칙은 카테고리별 의미를 가진다.
단순히 합=좋음, 충=나쁨으로 처리하지 않는다.

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

두 행성 adjustment 결합은 현재 versioned product rule을 사용한다.

---

# 16. Aspect Strength

```text
strength =
1 - abs(actual_angle - exact_angle) / effective_orb
```

Clamp:

```text
0.0 ~ 1.0
```

---

# 17. Birth Time Unknown — Astrology

출생시간을 모르면:

- ASC unavailable
- House unavailable
- base LOVE SCORE penalty 없음
- Moon uncertainty 별도 처리

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
providerName
providerSoftwareVersion
ephemerisDataVersion
```

후보:

- JPL + Skyfield
- 라이선스 조건을 충족한 Swiss Ephemeris
- 기타 검증된 local ephemeris implementation

최종 Provider는 배포/라이선스/정밀도 검증 후 결정한다.

---

# 20. Daily Astrology

Daily target timezone 기준:

```text
00:00
06:00
12:00
18:00
```

각 시점을 UTC로 변환해 계산한다.

Outer planets는 direct daily influence 0을 기본으로 한다.

---

# 21. Period Rules

Hierarchy:

```text
Lifetime
↓
Yearly
↓
Monthly
↓
Weekly
↓
Daily
```

Daily Saju는 target timezone date 기준.

Weekly:

```text
Monday–Sunday
```

Monthly:

```text
Calendar Month
```

Yearly:

```text
Gregorian Year Presentation
```

Saju annual boundary:

```text
Lichun
```

---

# 22. Structured Feature 공통 모델

```json
{
  "source": "ASTROLOGY",
  "rule": "VENUS_MARS_TRINE",
  "categories": ["ATTRACTION", "PASSION"],
  "direction": "POSITIVE",
  "strength": 0.92,
  "confidence": 1.0
}
```

Direction:

```text
POSITIVE
NEGATIVE
NEUTRAL
MIXED
```

---

# 23. Confidence

Confidence는 score penalty 자체가 아니라
해당 feature/evidence의 신뢰도를 나타낸다.

Missing data는 coverage/confidence로 표현하고
무조건 낮은 점수로 처리하지 않는다.

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

END OF DOCUMENT
