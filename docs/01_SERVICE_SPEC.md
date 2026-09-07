# LOVE FORTUNE
# 01_SERVICE_SPEC.md

Version: 2.0.0
Status: FINAL
Document Type: Service Specification

---

# 1. 서비스 개요

LOVE FORTUNE은 두 사람의 생년월일시를 기반으로
한국식 궁합(사주)과 서양 점성술을 결합하여
평생 궁합과 매일 변하는 사랑운을 제공하는 서비스다.

핵심 질문:

> 오늘 우리 두 사람의 사랑운은 어떨까?

서비스의 재방문 동기는 포인트, 출석, 보상이 아니라
매일 달라지는 관계 운세 자체에서 만들어야 한다.

---

# 2. 핵심 기능

- 두 사람의 생년월일/출생시간/출생지 입력
- 양력/음력 및 윤달 지원
- 출생시간 모름 지원
- 한국식 사주 궁합
- Western Astrology Synastry
- LOVE SCORE
- 8개 궁합 카테고리
- 평생 궁합
- 오늘의 사랑운
- 어제 대비 변화
- 최근 7일 흐름
- 오늘 추천 행동
- 주간/월간/연간 사랑운
- 연예인 궁합
- AI 관계 해석
- ko / ja / en
- 결과 공유
- 브라우저 선택 저장
- 운영자 Admin

---

# 3. 제외 기능

다음 기능은 서비스에 포함하지 않는다.

- 결제
- 포인트
- 출석 체크
- 출석 보상
- 로그인 보너스
- 일반 사용자 회원가입 강제
- 서버 Birth Profile 저장
- 서버 Relationship History 저장
- 서버 Fortune History 저장

MVP라는 이유로 기능을 제외하지 않는다.
전체 사양이 구현 범위이며 개발 순서만 단계적으로 관리한다.

---

# 4. Privacy-first Stateless Architecture

일반 사용자 출생정보는 기본적으로 서버에 영구 저장하지 않는다.

기본 흐름:

```text
Input
↓
Calculation Request
↓
Saju / Astrology / Score
↓
Result
↓
Request 종료
```

사용자 입력은 `Temporary Calculation Input`으로 취급한다.

---

# 5. 브라우저 선택 저장

입력 화면에서 다음 옵션을 독립적으로 제공한다.

```text
☐ 이 브라우저에 내 정보를 저장
☐ 이 브라우저에 상대 정보를 저장
```

두 옵션의 기본값은 OFF다.

- 둘 다 OFF: 브라우저/서버 장기 저장 없음
- 내 정보만 ON: 내 정보만 현재 브라우저 저장
- 상대 정보만 ON: 상대 정보만 현재 브라우저 저장
- 둘 다 ON: 두 사람 정보가 현재 브라우저에 저장

표현은 반드시 “이 브라우저에 저장”으로 한다.

---

# 6. 재방문 UX

두 사람 정보가 브라우저에 저장되어 있으면
메인 화면에서 저장된 관계를 보여줄 수 있다.

예:

```text
나 × 상대방
[오늘의 사랑운 보기]
```

페이지 로드만으로 저장 정보를 서버에 자동 전송하지 않는다.
사용자가 버튼을 누른 뒤 계산 요청을 전송한다.

---

# 7. 저장 정보 관리

사용자는 언제든지 다음 기능을 사용할 수 있어야 한다.

- 내 저장 정보 삭제
- 상대 저장 정보 삭제
- 전체 저장 정보 삭제

삭제는 현재 브라우저에만 적용된다.

---

# 8. 입력 정보

Person A / Person B:

- nickname 또는 display label
- birthDate
- birthTime
- birthTimeKnown
- calendarType: SOLAR / LUNAR
- isLeapMonth
- optional gender
- birth location
- timezone
- latitude / longitude

이름/닉네임은 점수 계산에 영향을 주지 않는다.

---

# 9. 출생시간 모름

출생시간이 없는 경우에도 계산을 제공한다.

- Saju hour pillar: unavailable
- ASC: unavailable
- House: unavailable
- Moon: 날짜 중 sign 이동 여부를 판단해 certainty 제공
- Base LOVE SCORE에 출생시간 미입력 패널티를 직접 부여하지 않는다.

UI는 “결과가 부정확하다”보다
“출생시간이 필요한 일부 세부 분석이 제외된다”고 안내한다.

---

# 10. 궁합 카테고리

공식 8개 카테고리:

```text
ATTRACTION      끌림
EMOTION         감정
COMMUNICATION   대화
PASSION         열정
STABILITY       안정감
HARMONY         조화
SUPPORT         서포트
LONG_TERM       장기 궁합
```

---

# 11. LOVE SCORE

LOVE SCORE는 Saju와 Astrology Feature를
카테고리별로 결합해 산출한다.

AI가 점수를 만들거나 수정하지 않는다.

---

# 12. 오늘의 사랑운

오늘 화면은 서비스 핵심 재방문 화면이다.

우선순위:

```text
오늘 LOVE SCORE
↓
어제와 비교
↓
오늘 관계 분위기
↓
추천 행동
↓
주의 행동
↓
최근 7일 흐름
```

---

# 13. 최근 7일

최근 7일은 서버 History를 읽지 않는다.

동일한 두 사람 입력으로 D~D-6을 재계산한다.

따라서 “저장된 기록”이 아니라
“최근 7일 흐름”으로 표현한다.

---

# 14. 기간 운세

지원:

- Weekly: Monday–Sunday
- Monthly: calendar month
- Yearly: Gregorian year presentation

Saju 연간 에너지 경계는 입춘을 사용한다.

---

# 15. 오늘의 행동

공식 Action:

```text
CONTACT
CONVERSATION
DATE
CONFESSION
AFFECTION
GIFT
IMPORTANT_DISCUSSION
RECONCILIATION
```

Relationship Type은 행동 표현과 우선순위에 활용할 수 있으나
Base LOVE SCORE를 변경하지 않는다.

---

# 16. Relationship Type

```text
COUPLE
MARRIED
DATING
CRUSH
FRIEND
UNKNOWN
```

관계 유형은 AI wording/UX/action context용이다.

---

# 17. Celebrity Compatibility

연예인과 사용자의 궁합을 계산할 수 있다.

Celebrity 데이터는 서버의 콘텐츠/reference 데이터로 관리하며
출처, 확인 여부, 출생시간 confidence를 관리한다.

확인되지 않은 출생시간은 추정하지 않는다.

---

# 18. AI

AI는 계산 엔진이 아니라 해석 계층이다.

AI 입력 중심:

- LOVE SCORE
- Category Score
- Structured Feature
- Trend
- Action
- Relationship Type
- Confidence
- Coverage

AI가 하지 않는 일:

- 사주 계산
- 점성술 계산
- 행성 위치 계산
- LOVE SCORE 생성/수정
- 미래 사건 확정 예측

---

# 19. 사용자 화면

주요 화면:

```text
S01 Main
S02 My Information
S03 Partner Information
S04 Loading
S05 Compatibility Result
S06 Category Detail
S07 Today Fortune
S08 Recent 7-Day Trend
S09 Weekly
S10 Monthly
S11 Yearly
S12 Celebrity List
S13 Celebrity Detail
S14 Celebrity Result
S15 Stored Information Management
S16 Privacy
S17 Error / Retry
```

---

# 20. 기술 구성

- PHP
- MySQL
- WordPress
- Custom Plugin: `love-fortune-core`
- WordPress REST API
- Browser Storage
- Deterministic calculation engine
- Optional AI interpretation

---

# 21. 서비스 구조

```text
Browser
↓
WordPress REST API
↓
Application Service
↓
Saju Engine
+
Astrology Engine
↓
Compatibility Feature Engine
↓
Score Engine
↓
Daily / Period Engine
↓
Structured Result
↓
AI Interpretation
↓
Browser
```

---

# 22. 개인정보 보호

- Birth Data URL 포함 금지
- Raw Request Body 로그 금지
- AI에 Birth Data 전달 최소화
- 개인 계산 Response `Cache-Control: no-store`
- CDN/WAF/APM/Analytics의 민감 데이터 수집 검토
- Birth Data Hash를 자동으로 anonymous로 간주하지 않음
- User fingerprint 생성 금지

---

# 23. 결과 표현

점술 결과는 오락/관계 해석 맥락으로 제공한다.

다음 표현을 피한다.

- 운명의 상대
- 반드시 결혼
- 반드시 이별
- 외도 확정
- 임신 예측
- 질병/사망/범죄/투자 결과 예측

경향과 흐름, 조언 중심으로 표현한다.

---

# 24. 최종 서비스 원칙

LOVE FORTUNE의 핵심은 다음이다.

```text
Privacy-first
+
Stateless
+
Deterministic Calculation
+
Daily Relationship Fortune
+
Explainable Score
+
Optional AI Interpretation
```

END OF DOCUMENT
