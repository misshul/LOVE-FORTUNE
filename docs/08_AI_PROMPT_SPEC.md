# LOVE FORTUNE
# 08_AI_PROMPT_SPEC.md

Version: 1.4.0
Status: CONTRACT FROZEN / IMPLEMENTATION READINESS SEPARATE
Document Type: AI Prompt / Interpretation Specification

Decision Authority: [Final Decision v3](contracts/final-decision-v3.md), then [v2](contracts/final-decision-v2.md) and [approved clarifications](contracts/clarification-v2.md)
Freeze Gate: [Freeze validation and readiness](contracts/README.md)

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

# 3. AI Input

Only verified LFIC signed context from a separate Interpretation request. No raw Birth/labels/identifiers. Header/payload/signing bytes, key rotation and TTL follow Normative contract: [Final Decision v2 SC-09](contracts/runtime-contract-v2.md#sc-09-signed-context-and-ai-execution).

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

Interpretation request carries signedContext and optional locale default ko-KR. Verify signed purpose INTERPRETATION and bound locale. Payload result/evidence are authoritative; never trust unsigned score/features. Person roles PERSON_A/PERSON_B/PAIR. Normative contract: [Final Decision v2 SC-09](contracts/runtime-contract-v2.md#sc-09-signed-context-and-ai-execution).

---

# 6. Interpretation Output

summary,strengths,challenges,advice; strength/challenge {text,evidenceRefs}. Reference approved featureId values and preserve evidence category/period/direction. No numeric score fields, invented scores/probabilities/percentages/ranks. Metadata interpretationMode=AI|FALLBACK plus aiPromptVersion/provider/model. Normative contract: [Final Decision v2 SC-09](contracts/runtime-contract-v2.md#sc-09-signed-context-and-ai-execution).

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

Normative contract: [Final Decision v2 SC-01](contracts/runtime-contract-v2.md#sc-01-confidence-and-coverage). Feature confidence is featureConfidence; coverage excludes confidence factors. Do not invent confidence or turn low confidence into a direct score penalty. Period confidence and ties follow v2.

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

Display Core first. Signed payload result contains approved deterministic period data and evidence. purpose=INTERPRETATION for all scopes; result identifies the calculation shape. Raw Birth remains excluded. Normative contract: [Final Decision v2 SC-09](contracts/runtime-contract-v2.md#sc-09-signed-context-and-ai-execution).

---

# 16. Daily Output

Use the common scoreless interpretation response. No legacy bestActions/cautionActions response override. Actions remain Core-derived recommendations; AI explains only approved evidence. Normative contract: [Final Decision v2 SC-09](contracts/runtime-contract-v2.md#sc-09-signed-context-and-ai-execution).

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

# 21. Celebrity / Age Safety

Celebrity readings describe an entertainment model based on approved public reference data. Do not assert a real person's hidden feelings, private personality, attraction toward the user, intentions, meetings or private relationships. User-entered birth dates are not age verification. v1 uses all-ages relationship language and excludes sexual/adult/exploitative content. Apply these rules to prompts, output validation and deterministic fallback.

---

# 22. Structured Output

Validate JSON Schema, required fields, lengths, escaping/HTML, numeric-score prohibition, approved evidenceRefs, evidence category/direction/period, canonical status consistency and safety. Schema validation alone cannot validate factual evidence alignment or safe natural language. No unknown output fields.

---

# 23. Retry / Fallback

Total provider budget8 seconds; each call timeout6 seconds or remaining budget. Retry network/5xx at most once. Schema failure allows at most one repair inside the same8-second budget. AI OFF/timeout/outage/schema/evidence/safety failures return200 when deterministic fallback is available; only inability to return fallback gives503 INTERPRETATION_UNAVAILABLE. meta.interpretationMode=AI|FALLBACK. Core results remain valid. Normative contract: [Final Decision v2 SC-09](contracts/runtime-contract-v2.md#sc-09-signed-context-and-ai-execution).

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

Names and nicknames must never be sent to the server or AI. This section does not authorize sending labels or free text in prompts.

nickname/free text를 instruction으로 처리하지 않는다.

현재 기본 서비스에서는 사용자 자유형 문장을
AI prompt에 직접 넣지 않는다.

---

# 28. Locale

Supported ko-KR,ja-JP,en-US; default ko-KR. AI request locale must match signed context; mismatch422 INTERPRETATION_LOCALE_MISMATCH. Korean natural polite language; Japanese polite language; English warm concise non-fatalistic language.

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

# 30. AI Logging / Provider

Use non-personal metadata allowlists only. No raw prompts, outputs, repair payloads, provider raw errors, request/response bodies or signed contexts in logs. RequestId is per request, not a tracking ID. Retention follows 04 section 29. Before provider enablement, prohibit training on user input and review retention, abuse monitoring, caching, region, subprocessors and deletion settings. Provider evaluation is not permission to log personal content.

---

# 31. AI Result Persistence

No user-derived AI server cache or persistent result/history. Signed contexts and interpretation responses are no-store with no shared CDN cache. Do not auto-send browser-stored Birth Data in background operations.

---

# 32. Version / Projection

Core version metadata is independent of AI; interpretation adds aiPromptVersion/provider/model. Signed payload separates engineVersions,scoreVersion,configVersion. Exact Core comparison excludes requestId,generatedAt,issuedAt,expiresAt,signedInterpretationContext. Never overwrite released version definitions. Normative contract: [Final Decision v2 SC-09](contracts/runtime-contract-v2.md#sc-09-signed-context-and-ai-execution).

---

# 33. Golden AI Test

검증:

- JSON Schema
- Core Score unchanged
- No numeric score output
- Evidence references valid
- All-ages/celebrity safety
- Fallback conditions
- No hallucinated rules
- No deterministic prediction
- Correct locale
- Correct category focus
- Unknown birth time handling
- Relationship type tone

문장 완전 일치는 요구하지 않는다.

---

# 34. Final Principle

Core calculates; AI interprets approved evidence. Frontend displays deterministic numeric scores. AI failure uses deterministic fallback without invalidating calculations. Maintain client-only labels, stateless privacy and all-ages/celebrity safety throughout.

---


## Final v3 contract alignment

[Runtime contract](contracts/runtime-contract-v2.md) applies the approved v3 decisions: structural coverage is retained with zero usable confidence; Daily source confidence includes all four samples with missing=0; Weekly is the valid-Daily arithmetic mean; period fallback days are excluded; periodDelta and trend magnitude/direction are separate; Actions use weighted Feature.confidence and cannot alter scores. UI displays deterministic fields; AI cannot alter Action confidence. No storage or new engine rules are introduced. Contract Freeze is independent of BLOCKED_CATALOG (SCORING_RULE_CATALOG_APPROVAL) and BLOCKED_EXTERNAL (SAJU_DAY_PILLAR_EPOCH, EPHEMERIS_PROVIDER).

END OF DOCUMENT


## Approved Saju v1 / SC-07 v2 application

Current authority: [Saju v1](contracts/saju-catalog-v1.md), [Guardrail v2](contracts/guardrail-v2.md), [ContextEvidence](contracts/context-evidence.md). These supersede historical Saju scoring placeholders, unit-sign restrictions and guardrail search. Saju14 families:9 scoring/5 context, APPLIED/READY. Daily C21-R is APPLIED/READY; catalog blockers NONE. Other privacy/API/time contracts are unchanged.

Internal context explanations use separate contextEvidenceRefs; no ce_ IDs in scoring evidenceRefs. The current public LFIC/API fields remain unchanged. No unsigned context extension, numeric score output or context scoring. See contracts/context-evidence.md.


Current Daily authority: [Daily Catalog v1 C21-R](contracts/daily-catalog-v1.md). Daily134/285 APPLIED/READY; S2 thresholds +/-2 / +/-5; M1-ELIGIBILITY-AWARE signal aggregation. SC-07 applies to Lifetime only. Score Engine catalog readiness READY; catalog blockers NONE; Production BLOCKED_EXTERNAL (SAJU_DAY_PILLAR_EPOCH, EPHEMERIS_PROVIDER). Public/signed wire and privacy remain unchanged.
