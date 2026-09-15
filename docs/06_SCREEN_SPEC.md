# LOVE FORTUNE
# 06_SCREEN_SPEC.md

Version: 1.3.0
Status: CONTRACT FROZEN / IMPLEMENTATION READINESS SEPARATE
Document Type: User Screen / UX Specification

Decision Authority: [Final Decision v3](contracts/final-decision-v3.md), then [v2](contracts/final-decision-v2.md) and [approved clarifications](contracts/clarification-v2.md)
Freeze Gate: [Freeze validation and readiness](contracts/README.md)

---

# 1. 화면 목록

```text
S01 Main / Landing
S02 My Information
S03 Partner Information
S04 Calculation Loading
S05 Compatibility Result
S06 Category Detail
S07 Today Fortune
S08 Recent 7-Day Trend
S09 Weekly
S10 Monthly
S11 Yearly
S12 Celebrity List
S13 Celebrity Detail / Input
S14 Celebrity Compatibility Result
S15 Stored Information Management
S16 Privacy / Data Usage
S17 Error / Retry
```

---

# 2. First Visit Flow

```text
Main
↓
My Information
↓
My Browser Save Choice
↓
Partner Information
↓
Partner Browser Save Choice
↓
LOVE SCORE
↓
Today
↓
Recent 7-Day Trend
```

---

# 3. Returning Flow

두 정보 저장:

```text
Main
↓
Saved Pair Card
↓
[오늘의 사랑운 보기]
```

페이지 로드만으로 API 호출하지 않는다.

내 정보만 저장:

```text
Main
↓
My Info Autofill
↓
Partner Input
```

---

# 4. Privacy Copy

공식 문구:

> 입력하신 출생정보는 기본적으로 서버에 저장되지 않습니다.  
> 원하시는 경우에만 이 브라우저에 정보를 저장할 수 있습니다.

---

# 5. S01 Main

Hero:

```text
우리 둘의 LOVE SCORE는?
```

설명:

```text
사주와 서양 점성술을 결합해
두 사람의 궁합과 오늘의 사랑운을 확인해보세요.
```

CTA:

```text
[궁합 보기]
```

저장된 pair가 있으면:

```text
[오늘의 사랑운 보기]
```

---

# 6. Main Privacy

메인 화면에 full birth data를 표시하지 않는다.

저장 상태는 nickname 또는 generic label 중심.

---

# 7. S02 My Information

필드:

- nickname
- birth date
- Gregorian calendar (1900..2099)
- leap month
- birth time
- birth time unknown
- birth location
- gender is not collected by the current calculation flow
- browser save toggle

---

# 8. S03 Partner Information

동일 구조.

Relationship Type 선택 포함 가능.

---

# 9. Browser Save Toggle

One My Profile and one Partner Profile; independent opt-in, default OFF. OFF uses memory, ON uses IndexedDB. Provide explicit save/edit/replace/delete/delete-all. Names and nicknames are client-side display labels only. Never automatically transmit stored Birth Data on load, navigation, timers, prefetch, service workers, background sync, analytics or error reporting.

---

# 10. 저장 시점

입력 Flow에서는 validation 성공 후
사용자가 계산 CTA를 누를 때 저장하는 것을 기본으로 한다.

Storage Management 화면의 직접 편집은
명시적 저장 action 후 저장한다.

---

# 11. Birth Time Unknown

Explain which evidence is unavailable. Hour Pillar, ASC and House are UNKNOWN. Do not invent noon/midnight or a representative time. Show candidate uncertainty and coverage/resultConfidence without treating missing evidence as a negative score. If all categories are insufficient, display overallScore=null and INSUFFICIENT_DATA clearly.

---

# 12. Location / Target Timezone

Choose server reference location; send birthLocationId only. Core targetTimezone is REQUIRED. Browser timezone may prefill the UI; service default Asia/Tokyo is UI-only. Optional locale defaults ko-KR, supports ko-KR/ja-JP/en-US. Do not send gender or labels. Normative contract: [Final Decision v2 SC-08](contracts/runtime-contract-v2.md#sc-08-public-api-wire).

---

# 13. S04 Loading

가짜 percentage는 사용하지 않는다.

예:

```text
두 사람의 기본 궁합을 계산하고 있어요
오늘의 관계 흐름을 확인하고 있어요
결과를 정리하고 있어요
```

실제 backend 단계인 것처럼 허위 realtime 상태를 표현하지 않는다.

---

# 14. S05 Compatibility Result

우선순위:

```text
LOVE SCORE
Deterministic Summary / optional later AI summary
Today CTA
8 Categories
Highlights
Details
Saju / Astrology
Disclaimer
```

---

# 15. Lifetime Labels

CAUTION:

```text
서로 이해가 필요한 궁합
```

EXCELLENT:

```text
강한 조화를 가진 궁합
```

결정론적 표현 금지.

---

# 16. Category Labels

```text
끌림
감정
대화
열정
안정감
조화
서포트
장기 궁합
```

---

# 17. Highlights

사용자 친화적인 표현 사용.

Raw rule code를 직접 노출하지 않는다.

---

# 18. Optional Astrology

ASC/House는 상세 Context로만 표현.

Birth Time Unknown이면 관련 영역은 unavailable.

MC는 표시하지 않는다.

---

# 19. S06 Category Detail

각 카테고리:

- score
- meaning
- strengths
- challenges
- related features
- advice

---

# 20. S07 Today Fortune

핵심 화면.

우선순위:

```text
오늘 LOVE SCORE
↓
어제와 비교
↓
오늘 요약
↓
추천 Action
↓
주의 Action
↓
오늘 Category
↓
최근 7일
```

---

# 21. Daily Action Labels

Daily flow labels map delta statuses VERY_LOW/LOW/STABLE/GOOD/VERY_GOOD. Missing period evidence is INSUFFICIENT_PERIOD_DATA; null lifetime is INSUFFICIENT_DATA. Actions are separate evidence-backed recommendations, never score modifiers. Normative contract: [Final Decision v2 SC-06](contracts/runtime-contract-v2.md#sc-06-dates-periods-and-statuses). Normative contract: [Final Decision v2 SC-07](contracts/runtime-contract-v2.md#sc-07-daily-category-actions-and-guardrail).

---

# 22. S08 Recent 7-Day Trend

API:

```text
POST /fortune/daily-range
```

표현:

```text
최근 7일 흐름
```

“저장된 기록”이라고 하지 않는다.

---

# 23. S09 Weekly

표시:

- weekly score
- trend
- daily graph
- best day
- caution day
- AI summary

---

# 24. S10 Monthly

표시:

- monthly score
- calendar
- best dates
- caution dates
- volatility
- AI summary

---

# 25. S11 Yearly

표시:

- yearly score
- monthly graph
- best months
- caution months
- AI summary

---

# 26. Celebrity Screens

S12:

- list
- search
- categories
- featured

S13:

- celebrity info
- source/confidence indication where relevant
- user info autofill if browser-saved
- explicit calculate CTA

S14:

- LOVE SCORE
- categories
- AI interpretation
- share

---

# 27. S15 Stored Information Management

기능:

```text
내 정보 보기/수정
상대 정보 보기/수정
내 정보 삭제
상대 정보 삭제
전체 삭제
```

설명:

```text
이 정보는 현재 브라우저에만 저장됩니다.
다른 기기와 자동 동기화되지 않습니다.
```

민감정보는 collapsed/masked display를 고려할 수 있다.

---

# 28. S16 Privacy

설명:

- 서버 비저장 기본
- 브라우저 opt-in
- calculation request 순간 서버 처리
- external AI/provider가 있는 경우 과장된 “외부 전송 없음” 문구 금지
- browser delete 설명

---

# 29. S17 Error / Retry

상태:

```text
INVALID_INPUT
LOCATION_ERROR
NETWORK_ERROR
CALCULATION_ERROR
EPHEMERIS_ERROR
AI_ONLY_ERROR
RATE_LIMITED
```

AI only error에서는 core result를 유지한다.

---

# 30. UI State

```text
EMPTY
INPUTTING
VALIDATING
CALCULATING
RESULT_READY
PARTIAL_RESULT
ERROR
```

---

# 31. Browser Persistence

공식 정책:

```text
My Profile: 1 + Partner Profile: 1
Toggle OFF = JavaScript memory only
Toggle ON  = IndexedDB
```

sessionStorage는 기본 정책에 포함하지 않는다.

---

# 32. Reload

저장하지 않은 계산 결과는 refresh 시 사라질 수 있다.

Privacy-first 원칙과 일치한다.

---

# 33. Share

기본:

```text
Client-side share image/text
```

Birth Data 포함 금지.

---

# 34. Analytics

Frontend event에 birth data를 포함하지 않는다.

예:

```text
reading_started
result_viewed
daily_viewed
share_clicked
```

---

# 35. SEO

Indexable:

- public content
- celebrity public pages

Noindex 권장:

- input
- personalized results
- stored information management

---

# 36. Responsive

Mobile-first.

Desktop/Tablet은 확장 layout.

---

# 37. Accessibility

필수:

- keyboard
- focus
- labels
- error association
- contrast
- screen reader
- chart text alternative

Score를 색상만으로 전달하지 않는다.

---

# 38. Localization

Locales ko-KR,ja-JP,en-US; default ko-KR. Do not send prior ko/ja/en aliases. Translation and formatting do not recalculate canonical scores/status. Signed AI context locale must match the interpretation request.

---

# 39. Final UX Principle

출생정보는 기본적으로 temporary calculation input이다.

Browser storage는 선택적 convenience 기능이다.

서비스가 사용자를 다시 부르는 핵심은:

```text
오늘 우리 두 사람의 사랑운은?
```

이다.

---

# 40. v1 Safety / AI Display

Display canonical scores/statuses from Core. AI does not supply numeric scores, probabilities, percentages or ranks. Render strengths/challenges backed by approved evidenceRefs using localized explanations. Birth dates are not verified ages. All AI and fallback language must be safe for all ages; exclude sexual/adult/exploitative relationship content. Celebrity readings describe an entertainment model, never hidden feelings, private personality, attraction to the user, intent, meetings or private relationships. Notifications are Service Notices only; no personal Push/Web Push or birth-based background transmission.

---


## Final v3 contract alignment

[Runtime contract](contracts/runtime-contract-v2.md) applies the approved v3 decisions: structural coverage is retained with zero usable confidence; Daily source confidence includes all four samples with missing=0; Weekly is the valid-Daily arithmetic mean; period fallback days are excluded; periodDelta and trend magnitude/direction are separate; Actions use weighted Feature.confidence and cannot alter scores. UI displays deterministic fields; AI cannot alter Action confidence. No storage or new engine rules are introduced. Contract Freeze is independent of BLOCKED_CATALOG (SCORING_RULE_CATALOG_APPROVAL) and BLOCKED_EXTERNAL (EPHEMERIS_PROVIDER; epoch resolved by SAJU_DAY_PILLAR_EPOCH_V1).

END OF DOCUMENT
