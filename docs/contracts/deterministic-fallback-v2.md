# Deterministic Fallback V2

Authority: user contract freeze 64172558-4e0a-4513-befa-d4ec7a802de9 and implementation approval 2f3d21ae-975b-499e-bf51-35eecfae421a. Version: **AI_PROMPT_V2**. Supersedes the output semantics of [Interpretation V1](interpretation-route-v1.md); that document and its execution reports remain historical. Unchanged request, signing, TTL, rate, provider transport, errors, privacy and numeric/date grammar continue to apply.

## Selection and exact assembly

Compatibility only. Use the already validated overall status; never recompute thresholds. Canonical rank order is ATTRACTION, EMOTION, PASSION, STABILITY, HARMONY, SUPPORT, LONG_TERM. COMMUNICATION is never ranked and never counts as missing. Null score or INSUFFICIENT_DATA is unavailable, never a weakness. Highest and lowest use score, with first canonical category winning ties independently of object order.

Emit comparative High and Low only with at least two valid categories and unequal minimum/maximum. Zero, one or all-tied valid categories use no High and generic advice. Missing any of the seven rankable categories selects the missing limitation; otherwise use the generic confidence limitation. Confidence/coverage do not introduce thresholds, affect selection or imply success probability. Relationship personalization remains deferred.

Summary is existing locale disclaimer, status sentence, optional High, limitation, separated by single LF, with no padding, empty lines or final newline. Advice is Low when comparison is valid, otherwise existing generic advice. Both arrays are always empty, for **AI as well as fallback**, for all five result types. No new evidenceRefs, fields or Feature conversions. Daily/Weekly/Monthly/Yearly preserve their prior fallback content, apart from shared V2 metadata.

## Approved locale copy

| Status | ko-KR | ja-JP | en-US |
|---|---|---|---|
| CAUTION | 차이와 기대를 차분히 확인해 볼 구간입니다. | 違いや期待を落ち着いて確かめたい範囲です。 | This range invites a careful check of differences and expectations. |
| BALANCED | 균형을 살피며 서로에게 편안한 방식을 찾아볼 구간입니다. | バランスを見ながら、互いに無理のない関わり方を探す範囲です。 | This range invites attention to balance and a comfortable way of relating. |
| GOOD | 관계의 조화를 활용해 볼 구간입니다. | 関係の調和を生かすことを考えられる範囲です。 | This range suggests opportunities to draw on harmony in the relationship. |
| VERY_GOOD | 조화로운 경향을 일상의 협력으로 이어가 볼 구간입니다. | 調和の傾向を日々の協力につなげることを考えられる範囲です。 | This range suggests exploring how harmonious tendencies can support everyday cooperation. |
| EXCELLENT | 높게 나타난 조화의 경향을 참고하되 실제 경험도 함께 살펴보세요. | 高く表れた調和の傾向を参考にしつつ、実際の経験も大切にしてください。 | Consider the strongly indicated harmony alongside your actual experiences. |

No new INSUFFICIENT_DATA status sentence is invented; existing generic summary remains neutral wording. Unexpected template selection failure uses the minimal safe fallback below.

| Category | ko-KR | ja-JP | en-US |
|---|---|---|---|
| ATTRACTION | 관심과 접근 방식 | 関心と近づき方 | interest and approach |
| EMOTION | 감정 교류 | 感情の交流 | emotional exchange |
| PASSION | 활동 에너지 | 活動のエネルギー | activity and energy |
| STABILITY | 일상의 일관성 | 日常の一貫性 | everyday consistency |
| HARMONY | 선호의 조율 | 好みのすり合わせ | coordination of preferences |
| SUPPORT | 서로 돕는 방식 | 支え合い方 | mutual support |
| LONG_TERM | 장기적인 방향 | 長期的な方向性 | long-term direction |

| Template | ko-KR | ja-JP | en-US |
|---|---|---|---|
| High | 확인 가능한 항목 중 {label} 항목이 상대적으로 높게 나타납니다. | 評価できる項目の中では、{label}の項目が相対的に高く表れています。 | Among the available results, the area of {label} is relatively higher. |
| Low advice | {label} 항목에서는 서로의 기대와 편안한 방식을 확인해 보세요. | {label}の項目では、互いの期待や無理のない関わり方を確かめてみてください。 | For {label}, consider checking each other's expectations and what feels comfortable. |
| No missing | 계산 신뢰 지표는 실제 관계의 결과를 보장하지 않습니다. | 計算上の信頼指標は、実際の関係の結果を保証するものではありません。 | Calculation confidence indicators do not guarantee real-world relationship outcomes. |
| Missing | 평가되지 않은 항목이 있어 확인 가능한 결과를 중심으로 참고해 주세요. | 評価されていない項目があるため、確認できる結果を中心に参考にしてください。 | Some areas were not evaluated, so use the available results as a limited reference. |

Existing disclaimer, generic summary and generic advice are unchanged `InterpretationText::copy` entries 0, 2 and 1. Their exact text is independently preserved in the [A–D fixture](../../wp-content/plugins/love-fortune-core/tests/fixtures/fallback-v2.json). PASSION is all-ages activity/energy, not sexual content. No claims about private feelings, fate or relationship success.

## Validation and failure behavior

Fallback content must exactly match the expected input-dependent content, including LF assembly. Provider acceptance remains a controlled grammar: existing typed scalar/date/ranking clauses plus only the locale/status/selected High/Low/missing sentences applicable to this Compatibility input. No global cross-status/category/locale allowlist. Plain text, disclaimer and Unicode limits (summary 1200, advice 1600) remain. Nonempty arrays are rejected regardless of evidence. Wrong locale, status sentence, selected category, limitation, markup or unsupported prose is rejected; provider repair/fallback continues under the original budget.

AI OFF or timeout/outage/invalid output uses V2. If template selection unexpectedly fails after verification, minimal safe fallback is disclaimer + LF + existing generic summary, generic advice, empty arrays. Ordinary missing categories do not cause 5xx. Invalid fallback or true system failure still yields 503 INTERPRETATION_UNAVAILABLE. Unsupported locale/request/signature is rejected upstream, never coerced. No random or hash-selected wording.

Provider DTO and signed payload are unchanged. aiPromptVersion is assigned after verification and is not added to signed context. TTL300, key rotation, eight-second total budget, six-second call limit, one network/5xx retry and one repair are unchanged. No new logging, persistence, cache, external call or retained birth data. Operational vendor and infrastructure privacy remain deployment checks.

## Schema, examples and evidence

Response shape is unchanged; schema change is only meta.aiPromptVersion const to AI_PROMPT_V2. Existing structural array schema remains intact, while runtime V2 enforces empty arrays. The four legacy interpretation examples have updated version metadata and retain their original structural validation purpose. In particular `interpretation-evidence-valid.json` is **schema-valid only, not an accepted V2 runtime response**. V1 historical reports are not rewritten.

Independent A–D fixtures cover three locales: A CAUTION/PASSION high/EMOTION low; B GOOD/HARMONY high/STABILITY low; C BALANCED/only EMOTION; D GOOD/no valid categories. D is a formatter-defense case, not a claim of a normal signable Core response. Expected fixture text is literal, not generated by the formatter. Selection, missing/tie/permutation, confidence invariance, locale, safe fallback and retained integration tests supplement these examples.

Implementation task is local only; no commit, push, deployment or server configuration change is authorized.
