# LOVE FORTUNE
# 01_SERVICE_SPEC.md

Version: 2.4.0
Status: CONTRACT FROZEN / IMPLEMENTATION READINESS SEPARATE
Document Type: Service Specification

Current V1 authority: [Zodiac V1](contracts/zodiac-catalog-v1.md). SAJU + ZODIAC are active; planetary Astrology and ephemeris are ADVANCED / DEFERRED. This scope supersedes prior source/weight/version gates; preserved Advanced sections do not authorize V1 execution.

Decision Authority: [Final Decision v3](contracts/final-decision-v3.md), then [v2](contracts/final-decision-v2.md) and [approved clarifications](contracts/clarification-v2.md)
Freeze Gate: [Freeze validation and readiness](contracts/README.md)

---

# 1. 서비스 개요

LOVE FORTUNE은 두 사람의 생년월일시를 기반으로
한국식 궁합(사주)과 고정 날짜 기반 12별자리 모델을 결합하여
평생 궁합과 매일 변하는 사랑운을 제공하는 서비스다.

핵심 질문:

> 오늘 우리 두 사람의 사랑운은 어떨까?

서비스의 재방문 동기는 포인트, 출석, 보상이 아니라
매일 달라지는 관계 운세 자체에서 만들어야 한다.

---

# 2. 핵심 기능

- 두 사람의 생년월일/출생시간/출생지 입력
- Gregorian birth dates1900..2099; Public lunar/leap flags are excluded in v2.
- 출생시간 모름 지원
- 한국식 사주 궁합
- Zodiac V1 fixed-date compatibility; Western Astrology Synastry is ADVANCED / DEFERRED
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
V1 구현 범위는 현재 product-scope를 따른다. Advanced Astrology는 보존된 후속 범위다.

---

# 4. Privacy-first Stateless Architecture

General-user Birth, Partner, Relationship and History data are transient calculation input, never server resources. No personal DB tables, stable anonymous IDs, fingerprinting, Birth-hash tracking, user-derived calculation cache or AI cache. Request-local memory reuse is permitted. Do not log request/response bodies, signed contexts, prompts or raw AI output. Infrastructure and retention requirements are in 04 sections 28-29.

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

# 6. Profile UX / Browser Storage

Support one My Profile and one Partner Profile. Saving is independently opt-in, default OFF. OFF uses memory; ON uses IndexedDB. Provide explicit save/edit/replace/delete and delete-all controls. Names and nicknames remain client-side display labels. No automatic transmission on page load, navigation, timer, prefetch, service worker, background sync, analytics or error reporting. Send stored birth input only after an explicit calculation action.

---

# 7. 저장 정보 관리

사용자는 언제든지 다음 기능을 사용할 수 있어야 한다.

- 내 저장 정보 삭제
- 상대 저장 정보 삭제
- 전체 저장 정보 삭제

삭제는 현재 브라우저에만 적용된다.

---

# 8. Input / Location / Target Timezone

Person requires birthDate and birthLocationId; birthTime defaults to null. Public birth input is Gregorian1900..2099. No gender, raw coordinates, birth timezone or client labels. Resolve birthLocationId from versioned server references. targetTimezone is required; Asia/Tokyo is UI/config default only, never a calculation fallback. locale defaults to ko-KR; support ko-KR/ja-JP/en-US. Normative contract: [Final Decision v2 SC-08](contracts/runtime-contract-v2.md#sc-08-public-api-wire).

---

# 9. Calculation Pipeline

Normalized input -> Saju + original-birthDate Zodiac -> source category results -> M2 source/category blend -> Score/Period -> Deterministic Result -> Frontend. The same feature set feeds score calculation and approved AI evidence selection. Do not invent evidence after scoring. Core calculation does not depend on AI. Interpretation is a separate signed-context request.

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

LOVE SCORE는 Saju와 Zodiac source category score를 4/5와 1/5로 결합한다.
COMMUNICATION은 context-only이며 overall의 score/coverage/confidence 분모에서 제외한다.

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

POST /interpretation/generate verifies an HMAC-SHA-256 context with a five-minute TTL and an environment-managed secret. Context is tamper-evident, not encrypted or guaranteed one-time. It contains no raw Birth Data or personal labels. AI outputs summary, strengths, challenges and advice; strengths/challenges include approved evidenceRefs. AI must not generate numeric scores, percentages, probabilities or ranks. Frontend displays deterministic scores. AI disabled, timeout, outage, schema/evidence/safety failures use deterministic template fallback without invalidating Core results.

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

# 21. Service Notices

Notifications mean non-personal Service Notices. Personal Push, Web Push subscriptions and birth-based background notifications are excluded from v1.

---

# 22. Implementation Gate / Versions

Zodiac V1 supersedes the prior active source model. V1_EXTERNAL_BLOCKERS=NONE; SAJU_DAY_PILLAR_EPOCH_V1 is APPLIED / READY. EPHEMERIS_PROVIDER remains an Advanced-only blocker. Full Four Pillars, score orchestration and API implementation are still incomplete. Contract Freeze does not certify production readiness. See contracts/readiness.md.

---

# 23. 결과 표현

User-entered birth dates are not verified ages. All v1 content and fallback must use all-ages safe language. Exclude sexual/adult/exploitative content. Celebrity readings must not assert hidden feelings, private personality, attraction to the user, intent, meetings or private relationships.

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


## Final v3 contract alignment

[Runtime contract](contracts/runtime-contract-v2.md) applies the approved v3 decisions: structural coverage is retained with zero usable confidence; Daily source confidence includes all four samples with missing=0; Weekly is the valid-Daily arithmetic mean; period fallback days are excluded; periodDelta and trend magnitude/direction are separate; Actions use weighted Feature.confidence and cannot alter scores. UI displays deterministic fields; AI cannot alter Action confidence. No storage or new engine rules are introduced. Contract Freeze is independent of BLOCKED_CATALOG (SCORING_RULE_CATALOG_APPROVAL) and ADVANCED-only BLOCKED_EXTERNAL (EPHEMERIS_PROVIDER); V1_EXTERNAL_BLOCKERS=NONE, epoch APPLIED.

END OF DOCUMENT
