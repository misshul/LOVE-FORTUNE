# ContextEvidence v1

Status: APPROVED CONTRACT / no production implementation
Authority: saju-v1-application-approval.md sections15-16.

Closed context-evidence.schema.json requires contextEvidenceId,ruleId,source,subject,period,confidence,metadata. No category/signedValue/effectiveWeight fields are accepted. Never put context into Feature arrays, score aggregation, eligible/available coverage denominator, usable evidence, guardrail or Action evidenceRefs. Do not simulate exclusion with confidence0.

TEN_GODS_RELATION requires PERSON_A or PERSON_B, DAY pillar and a registered Ten God. Other family detectors remain DEFERRED; no calculated evidence until reference/conditions approved.

contextEvidenceId='ce_'+first48 SHA256 hex of existing canonical JSON {ruleId,source,subject,period,metadata}. Identity metadata is tenGod,pillar,referenceId when present; confidence excluded. No raw Birth, names or tracking. Feature transformationStatus is non-identity and not a Ten God claim.

## AI contextEvidenceRefs

context-interpretation.schema.json defines a separate INTERNAL context explanation envelope, not the public request/response or LFIC payload. Each contextEvidenceRefs must uniquely resolve inside its trusted contextEvidence array; duplicate IDs and dangling refs are invalid. No ce_ ref in scoring ft_ evidenceRefs or Action dependencies.

Current public API and signed-context schema/signing/TTL are unchanged. Do not send unsigned context alongside LFIC or inject this envelope into a closed wire DTO. A future public transport extension must explicitly version and bind context; this application defines the internal contract only.

Existing scoreless output, all-ages/celebrity safety, fallback, no prompt/output logging and no personal cache rules apply. Core remains AI-independent.
