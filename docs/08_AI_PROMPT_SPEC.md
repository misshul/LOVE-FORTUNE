# LOVE FORTUNE
# 08_AI_PROMPT_SPEC.md

Version: 1.0.0
Status: FINAL
Document Type: AI Prompt / Interpretation Specification

---

# 1. 목적

AI는 LOVE FORTUNE의 계산 엔진이 아니라
Structured Result를 사용자 친화적 언어로 설명하는 Interpretation Layer다.

---

# 2. AI가 계산하지 않는 항목

```text
사주
만세력
절기
천간/지지
행성 위치
Zodiac Sign
Aspect
LOVE SCORE
Category Score
Daily Delta
Action Score
Trend
```

---

# 3. AI 입력

가능하면 다음만 전달한다.

```text
LOVE SCORE
Category Score
Structured Features
Trend
Actions
Relationship Type
Confidence
Coverage
Warnings
```

기본적으로 전달하지 않는다.

```text
birthDate
birthTime
birthCity
latitude
longitude
IP
email
device ID
```

---

# 4. Prompt Types

```text
COMPATIBILITY_SUMMARY
CATEGORY_DETAIL
DAILY_FORTUNE
WEEKLY_FORTUNE
MONTHLY_FORTUNE
YEARLY_FORTUNE
ACTION_ADVICE
CELEBRITY_COMPATIBILITY
```

---

# 5. Compatibility Input

```json
{
  "type": "COMPATIBILITY",
  "relationshipType": "DATING",
  "loveScore": {
    "display": 78,
    "status": "VERY_GOOD",
    "confidence": 0.91,
    "coverage": 0.87
  },
  "categories": {
    "ATTRACTION": 84,
    "EMOTION": 80,
    "COMMUNICATION": 68,
    "PASSION": 87,
    "STABILITY": 73,
    "HARMONY": 71,
    "SUPPORT": 77,
    "LONG_TERM": 76
  },
  "positiveFeatures": [],
  "negativeFeatures": [],
  "warnings": []
}
```

---

# 6. Compatibility Output

```json
{
  "headline": "...",
  "summary": "...",
  "strengths": ["..."],
  "challenges": ["..."],
  "advice": "...",
  "categorySummaries": {
    "ATTRACTION": "...",
    "EMOTION": "...",
    "COMMUNICATION": "...",
    "PASSION": "...",
    "STABILITY": "...",
    "HARMONY": "...",
    "SUPPORT": "...",
    "LONG_TERM": "..."
  }
}
```

---

# 7. 핵심 Safety

금지:

```text
운명의 상대
반드시 결혼
반드시 이별
절대 만나면 안 됨
임신 예측
질병 예측
사망 예측
사고 예측
범죄 예측
투자 결과
상대 행동 확정
```

권장:

```text
~하기 좋은 흐름
~일 수 있습니다
~에 신경 쓰면 도움이 됩니다
~하는 경향이 있습니다
```

---

# 8. Score Authority

Input Score는 authoritative다.

AI가:

- 점수 수정
- 점수 재계산
- “실제로는 더 높다/낮다” 표현

을 해서는 안 된다.

---

# 9. Feature Authority

Input에 없는 feature/aspect/saju rule을 만들지 않는다.

Direction을 뒤집지 않는다.

MIXED는 장점과 긴장을 함께 설명한다.

---

# 10. Confidence / Coverage

낮은 confidence는 확정적 표현을 완화한다.

Coverage가 낮다고 score 자체를 낮게 설명하지 않는다.

Birth Time Unknown:

```text
출생시간이 필요한 일부 세부 요소는 분석에서 제외되었습니다.
```

---

# 11. Moon Uncertainty

UNCERTAIN이면 possible signs 범위에 근거한
제한적 설명만 한다.

임의 sign 확정 금지.

---

# 12. ASC / House

Optional Context.

Score 근거로 표현하지 않는다.

MC는 input/output 모두에서 사용하지 않는다.

---

# 13. Saju Interpretation

기본 화면에서는 전문용어를 과도하게 노출하지 않는다.

Advanced detail에서 필요한 경우 제한적으로 사용.

엔진에 없는 재물/직업/건강/자녀운 등을 추가하지 않는다.

---

# 14. Astrology Interpretation

Rule Dictionary를 기반으로 설명한다.

예:

```text
VENUS_MARS_TRINE
→ 자연스러운 끌림과 애정 표현의 흐름
```

Input에 없는 aspect 생성 금지.

---

# 15. Daily Input

```json
{
  "type": "DAILY",
  "date": "2026-09-07",
  "lifetimeScore": 78,
  "dailyScore": {
    "display": 84,
    "status": "GOOD",
    "deltaFromLifetime": 6
  },
  "yesterday": {
    "score": 79,
    "difference": 5
  },
  "categories": {},
  "actions": {}
}
```

---

# 16. Daily Output

```json
{
  "headline": "...",
  "summary": "...",
  "yesterdayComparison": "...",
  "bestActions": [],
  "cautionActions": [],
  "advice": "..."
}
```

---

# 17. Action 표현

높은 score:

```text
오늘 추천
```

낮은 score:

```text
조금 신중하게
```

절대 금지 형태:

```text
고백하지 마세요.
```

대신:

```text
고백을 고민하고 있다면 분위기를 조금 더 살펴보는 편이 좋습니다.
```

---

# 18. Relationship Type

```text
COUPLE
MARRIED
DATING
CRUSH
FRIEND
UNKNOWN
```

Score 변경에는 사용하지 않는다.

문체/행동 context에만 반영한다.

---

# 19. Weekly / Monthly / Yearly

AI는 Engine이 제공한:

```text
score
trend
best day/date/month
caution day/date/month
volatility
```

만 설명한다.

새 날짜를 선택하지 않는다.

---

# 20. 미래 사건

Yearly 결과에서도 다음을 확정하지 않는다.

```text
결혼
이별
임신
질병
사고
사망
범죄
```

---

# 21. Celebrity

Entertainment context로 표현.

실제로 만나거나 연애/결혼할 가능성을 예측하지 않는다.

---

# 22. Structured Output

Production AI는 JSON Schema 기반 Structured Output을 우선한다.

Validation:

- required fields
- score consistency
- category
- action
- date
- unsupported rule
- HTML
- length
- safety

---

# 23. Retry / Fallback

권장:

```text
MAX_AI_RETRY = 1
```

Flow:

```text
Validation Fail
↓
Repair/Retry
↓
Deterministic Fallback
```

AI 실패가 core calculation 실패가 되어서는 안 된다.

---

# 24. Prompt Structure

```text
SYSTEM RULES
ROLE
INPUT CONTRACT
INTERPRETATION RULES
SAFETY RULES
STYLE RULES
OUTPUT SCHEMA
STRUCTURED INPUT
```

---

# 25. 공통 System Prompt 개념

```text
You are the interpretation layer for LOVE FORTUNE.

Do not calculate astrology, saju, scores, planetary positions,
or future events.

All numeric values and features in the input are authoritative.

Never change scores.
Never invent missing features.
Never infer birth data.
Never state deterministic future events.

Return only the requested JSON schema.
```

---

# 26. Rule Dictionary

권장:

```text
/config/ai/rules/
  saju_rules.json
  astrology_rules.json
  action_rules.json
  category_rules.json
```

Unknown rule은 AI input에서 제외하거나
정의된 generic fallback만 사용한다.

---

# 27. Prompt Injection

nickname/free text를 instruction으로 처리하지 않는다.

현재 기본 서비스에서는 사용자 자유형 문장을
AI prompt에 직접 넣지 않는다.

---

# 28. Locale

```text
ko
ja
en
```

ko:
- 자연스러운 존댓말
- 점술가 과장 말투 금지

ja:
- 자연스러운 丁寧語
- 단정적 占い 표현 금지

en:
- warm
- concise
- non-deterministic

---

# 29. Gender / Bias

금지:

```text
남자는 Mars
여자는 Venus
```

성적 지향/국적/출생도시를 추정하거나
좋고 나쁨의 이유로 사용하지 않는다.

---

# 30. AI Logging

허용:

```text
request_id
prompt_type
prompt_version
model
status
duration_ms
token_count
error_code
```

금지:

```text
raw prompt
raw birth payload
birthDate
birthTime
```

---

# 31. AI Result Persistence

기본:

```text
OFF
```

---

# 32. Version

관리:

```text
promptVersion
interpretationRuleVersion
provider
model
```

Prompt 변경은 새 Version으로 관리한다.

---

# 33. Golden AI Test

검증:

- JSON Schema
- Score unchanged
- No hallucinated rules
- No deterministic prediction
- Correct locale
- Correct category focus
- Unknown birth time handling
- Relationship type tone

문장 완전 일치는 요구하지 않는다.

---

# 34. Final Principle

```text
Calculation
↓
Score
↓
Feature
↓
AI Interpretation
```

절대로:

```text
AI
↓
Calculation
```

구조가 되어서는 안 된다.

END OF DOCUMENT
