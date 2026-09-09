# SC-07 Guardrail v2

Version: 2.0.0
Status: APPROVED / APPLIED

Authority: [Application approval](saju-v1-application-approval.md), [approved proposal](guardrail-v2-approval.md).

After individual caps, confidence and outer aggregate cap, freeze W0=sum(usable effectiveWeight)>0. impact_i=50*effectiveWeight_i*abs(signedValue_i)/W0. impact<=20 retains the weight. Otherwise adjustedWeight_i=min(effectiveWeight_i,0.4*W0/abs(signedValue_i)). signedValue=0 retains its weight.

finalRawScore=50+50*sum(adjustedWeight_i*signedValue_i)/W0. NEVER divide by sum(adjustedWeight). Removed mass is neutral, not reassigned. Multiple features may move a category by more than20. Unadjusted inputs exactly retain the original weighted mean. Evaluate bounded signed contributions algebraically to avoid boundary roundoff; no tolerance or intermediate score rounding is authorized.

No binary search, global passes or forced zero-weight exclusion. Evidence/coverage/confidence use their pre-guardrail definitions. Stabilization remains 50+(finalRawScore-50)*coverage. No guardrail-induced INSUFFICIENT_DATA. No usable evidence follows SC-01 without division. SCORE_GUARDRAIL_UNSATISFIED is legacy/deprecated, never reused for a new invariant error.

Unchanged SC-03 final outer-share check remains; SCORE_CAP_INVARIANT_FAILED retains its meaning. Current Astrology109 has no active Outer scoring.

Score contract: GUARDRAIL_V2. Future implementation must bump scoreVersion/configVersion; do not relabel historical results. This documentation does not change a production release version.
