# Astrology Scoring Rule Catalog v1

Status: APPROVED / APPLIED
Version: 1.0.0

Authority: [Final mapping approval](astrology-v1-mapping-approval.md) and [repository application approval](astrology-v1-application-approval.md). These supersede older Astrology mapping/direction/orb placeholders; Saju, confidence, coverage, periods, guardrail, privacy and signed context remain unchanged.

## Catalog and scope

[rules/astrology-rules.json](rules/astrology-rules.json) contains109 enabled scoring rules: Personal75, Jupiter15, Saturn19;228 mappings (170 positive,58 negative),26 mixed rules. Five generic aspects are reference definitions, not scoring rules. The canonical planet order is SUN,MOON,MERCURY,VENUS,MARS,JUPITER,SATURN,URANUS,NEPTUNE,PLUTO. ruleId is ASTRO_{PLANET_A}_{PLANET_B}_{ASPECT} in that order, never ASCII alphabetical order.

## Closed storage contract

[Astrology rule schema](schemas/astrology-rule.schema.json) owns planetA,planetB,aspect, pairWeightSource and decimal direction mapping. additionalProperties=false at rule and mapping levels. Saju uses saju-rule.schema.json; both catalogs use decimal sign mappings. Every enabled v1 rule has mappings, requiresBirthTime=false,contextOnly=false,ruleWeight=1,version=1.0.0. A rule's exactAngle/baseWeight/baseOrb resolves from the catalog aspectDefinitions by aspect. Planet weights, pair overrides and orb adjustments resolve from referenceTables. The validator checks these joins and the exact approved mapping matrix; no free-form metadata stores hidden rule definitions.

Category direction is POSITIVE for signedValue>0,NEGATIVE for signedValue<0,NEUTRAL for0, within[-1,1]. Mixed is an analytical rule-level classification, never a categoryMapping direction. Existing Saju direction rules remain intact.

## Weight and orb

Persist numeric pairWeight and pairWeightSource OVERRIDE or GENERIC. Canonicalize both override lookup directions. Use override if present; otherwise sqrt(planetWeightA*planetWeightB), without intermediate decimal truncation. preConfidenceWeight=baseWeight*1*pairWeight; effectiveWeight=preConfidenceWeight*featureConfidence. Never multiply planetWeight,orbCloseness or aspectStrength again.

Normalize longitudes into[0,360). rawDifference=abs(a-b); angularDistance=min(rawDifference,360-rawDifference), range[0,180]. aspectOrb=abs(angularDistance-exactAngle). effectiveOrb=min(10,baseAspectOrb+max(adjustmentA,adjustmentB)), using PLUS. Active iff aspectOrb<=effectiveOrb. Active only: orbCloseness=1-aspectOrb/effectiveOrb, range[0,1], exact1,inclusive boundary0. Inactive creates neither closeness nor scoring Feature. Reference examples: Sun-Moon conjunction9; Sun-Venus trine8; Mercury-Venus sextile5; deferred Jupiter-Saturn sextile4 degrees.

## Features and uncertainty

Detect one astronomical aspect, then project each mapping into a category Feature. Category participates in existing featureId identity, so mappings produce separate IDs. Canonicalizing rule definitions does not merge PERSON_A Sun/PERSON_B Moon with PERSON_A Moon/PERSON_B Sun evidence. Feature metadata planetA/planetB retains the contributing PERSON_A/PERSON_B planet orientation; do not sort these evidence metadata fields by rule order. This uses the existing identity fields and does not change canonical hashing. Do not introduce a tracking ID.

requiresBirthTime=false is not confidence=1. Keep candidate agreement/availability aggregation; missing-time Hour/ASC/House remain UNKNOWN. ASC/House context requires birth time and has scoreWeight0. MC is completely excluded.

metadata.orbCloseness is optional, numeric[0,1], active only and not identity-relevant. Allowed only for request-local evidence order/deterministic interpretation priority/Feature metadata; never a score or confidence multiplier. Metadata allowlist expansion grants no logging permission. Candidates allowlist is unchanged.

PASSION means all-ages-safe chemistry,energy,activation,mutual drive. AI explains approved evidence only, cannot alter numeric scores/confidence, and must not render this category as sexual/adult content. Existing AI safety/privacy/fallback contracts remain effective.

## Deferred scope and readiness

Outside v1 scoring: personal-Jupiter Square/Opposition; Mercury-Saturn Conjunction; all Mars-Saturn,Jupiter-Jupiter,Saturn-Saturn,Jupiter-Saturn aspects; all Uranus/Neptune/Pluto scoring. Deferred entries remain outside rules, recorded in catalog.deferred. ASC/House are context only; MC/Nodes/Chiron excluded. Outer individual0.50 and aggregate15% caps are retained for future approvals but no enabled outer contribution exists in v1.

Contract Freeze PASS. Astrology Catalog APPLIED and Astrology Catalog Readiness READY. Overall Score Engine catalog readiness READY; catalog blockers NONE. Production BLOCKED_EXTERNAL: EPHEMERIS_PROVIDER. SAJU_DAY_PILLAR_EPOCH_V1 is APPLIED / READY. SCORING_RULE_CATALOG_APPROVAL remains the historical umbrella; there are no remaining catalog components. Catalog readiness does not mean production engine implementation or provider validation.
