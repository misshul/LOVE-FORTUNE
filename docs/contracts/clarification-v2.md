<!-- Historical approval: Daily status/signal availability and readiness statements are superseded by daily-catalog-v1.md (C21-R). Lifetime, security and other unaffected approvals remain authoritative. -->
> Historical guardrail approval superseded by [SC-07 v2](guardrail-v2.md). The candidates clarification and unchanged SC-03 outer invariant remain active.

# Final Decision v2 — 승인된 보완

Date: 2026-09-08
Authority: 사용자가 이 대화에서 명시적으로 선택한 답변.

## SC-02 / SC-04 candidates

사용자 답변: **“candidates를 allowlist에 추가”**.

`metadata.candidates`를 허용한다. 각 원소는 number/string/boolean/null인 scalar 배열이다. 비수치 candidate의 canonical rawValue는 null이며 candidate별 값을 여기에 보존한다. candidates는 featureId identity-relevant metadata에 포함하지 않는다. Raw Birth Data나 개인 display label을 넣을 수 있다는 뜻은 아니다.

## SC-07 guardrail

사용자 답변: **“제안한 경계·실패 처리 승인”**.

승인된 질문의 내용: featureId 오름차순으로 전체 집합을 반복 검증하고, weight=0으로 제거된 집합은 Raw Score 50으로 처리하며, 32회 후에도 전체 조건을 만족하지 못하면 계산 오류로 반환한다.

실행 절차를 다음과 같이 기록한다.

1. individual cap → confidence → outer cap 적용 후 featureId 오름차순으로 정렬한다.
2. 해당 feature를 포함한 Raw Score와 제외한 Raw Score 차이를 검사한다. 빈 집합/가중치 합 0의 비교용 Raw Score는 50이다.
3. 차이가 20을 초과하면 `[0, 현재 weight]`에서 최대 32회 binary search한다. 각 midpoint에서 차이가 20 이하인 경우 lower bound를 올리고, 초과하면 upper bound를 내린다. 최종 lower bound를 사용한다.
4. 한 pass가 끝나면 최종 집합의 모든 feature를 다시 검사한다. 모두 조건을 만족할 때만 성공한다.
5. 최대 32 pass 후에도 조건이 충족되지 않으면 `500 SCORE_GUARDRAIL_UNSATISFIED`를 반환한다. 부분 결과를 성공으로 반환하지 않는다.

이 절차는 Saju/Astrology rule을 새로 정의하지 않는다. 개별 조정이 끝났다고 전체 조건이 만족된 것으로 간주하지 않는다. 테스트는 단독 양성 feature, 정상적인 영향 범위, 서로 반대 방향인 feature의 비수렴 사례를 포함한다.

추가 invariant 검증: guardrail이 non-outer weight를 줄이면 이미 적용한 outer15% 비율이 최종 집합에서 깨질 수 있다. 최종 성공 전에 outer 비율도 다시 검사하고, 위반하면 `500 SCORE_CAP_INVARIANT_FAILED`를 반환한다. 이는 승인된15% 상한을 보장하기 위한 실패 처리이며, 새로운 재가중 공식이나 사용자 승인을 받지 않은 재시도 순서를 도입하지 않는다.


## Subsequent v3 authority

[Final Decision v3](final-decision-v3.md) resolves SC-01, SC-06, Action confidence and the empty-catalog Freeze separation. The candidates approval remains active; guardrail search is superseded by SC-07 v2. All SC-01..SC-10 are RESOLVED; readiness remains independently blocked as recorded in README.md.
