# Saju Feature Extraction V1

Status: SAJU_FEATURE_EXTRACTION_CONTRACT_APPROVED / APPLIED.
Production identifier: `SAJU_FEATURE_EXTRACTOR_V1`.
Authority: approved FE-01 through FE-06 and FE-COV-01 decisions, applied by the user's production implementation request `e6cc6023-deea-47d6-a1be-ec40fece75dd`. This addition supersedes object-presence-only coverage and the candidate denominator wording for **Lifetime Saju extraction only**. Numeric catalogs, Daily, SC-07 and public wire schemas are unchanged.

## Inputs and atomicity

`SajuFeatureExtractionService::extract(array $personA, array $personB): array` consumes the internal production `NatalResolutionService::resolve` results. It does not accept birth input directly, recalculate time/location/solar terms, or resolve user-supplied arbitrary pillar arrays. Each candidate owns Year/Month/Day/Hour jointly. The service validates dependency versions, static reference checksums, request-local IDs, candidate status, symbols/indices and cross-pillar lineage invariants before processing any pair. An invalid candidate fails the entire call. Structural validation cannot authenticate fabricated but internally consistent arrays; the integration boundary is trusted in-process Natal output, not a public endpoint.

FE-01: evaluate the full A candidates x B candidates set. Each complete pair is one equal discrete unit. Preserve multiplicity even when pillars/results are identical. No duration, probability, interval-length weights or pillar-level Cartesian product. Candidate IDs are request-local C0/C1/...; side-qualified `A:C0|B:C1` references never enter semantic hashes. Canonical ordering makes caller array iteration order irrelevant.

## Raw data and detectors

Visible stems and branch primary elements have weight 1. Known Hour gives 8 tokens/person; unknown Hour gives 6 and no fabricated Hour. Use the catalog's stem/branch element tables and approved relations only. No hidden-stem, season or transformation calculation.

FE-04: `SAJU_BRANCH_POLARITY_V1`: 子丑寅卯辰巳午未申酉戌亥 indices 0..11, even YANG / odd YIN. Stem indices 0..9 use the same parity rule. Index/symbol mismatch fails closed.

Day stem owner: STEM_COMBINATION first, otherwise element SAME / GENERATION / CONTROL, owned respectively by DAY_MASTER_RELATION / ELEMENT_SUPPORT / ELEMENT_CONTROL. Day branch owner: LIUHE, then CLASH, otherwise BSAME / BGENERATION / BCONTROL in DAY_BRANCH_RELATION. These relations use Day pairs only. ELEMENT_COMPLEMENT and YIN_YANG_BALANCE independently use visible tokens. HIDDEN_STEM_RELATION, PUNISHMENT, HARM and DESTRUCTION stay DEFERRED, without scoring detectors.

The authoritative [catalog](rules/saju-rules.json) remains 14 families / 9 scoring / 16 variants / 37 mappings / 5 context. The runtime deployment copy is byte-identical, SHA-256 `3d580c733e91c9aff32373c684deda4063895d7671cc54b1f6cb7ed0b6bd2c3f`. A mismatch fails closed. Do not edit only the runtime copy.

For each person, p(element)=count/knownTokenCount. Balance(p)=1-sum(abs(p-1/5))/(8/5). Joint distribution=(pA+pB)/2; complementGain=Balance(joint)-(Balance(pA)+Balance(pB))/2. Gain below .05 is NOT_MATCHED. Approved EC bands are [.05,.15), [.15,.30), [.30,.50]. Yin/Yang balance=1-abs(yang-yin)/(yang+yin); approved YY bands [0,.25), [.25,.50), [.50,.75), [.75,1]. Values and weights are read from the catalog, never recalibrated. All arithmetic/comparisons remain exact rational.

## Evidence ledger and aggregation

FE-06 states: MATCHED, NOT_MATCHED, UNAVAILABLE, DEFERRED, ERROR. Only MATCHED carries a variant that resolves to an approved category signedValue. NOT_MATCHED is computable without a scoring feature; UNAVAILABLE is normal missing required input without fake 0/neutral/50. ERROR fails closed, not a skipped unit. DEFERRED contributes no denominator.

FE-02 group: ruleId + subject(PAIR) + category + period(LIFETIME). Categories are the union of that enabled rule's approved mappings. A matched variant without a mapping for a particular group is NOT_MATCHED in that group; it does not create a new category. Let M/N/U be the three eligible states:

- eligibleCount E=M+N+U
- computableCount C=M+N
- totalCount=C; presentCount=M
- aggregateSignedValue=arithmetic mean of M approved signedValues, retaining multiplicity
- featureConfidence=baseConfidence * (1-(maxPresent-minPresent)/2) * M/C, only if M>0 and C>0

Otherwise no average/min/max or scoring Feature is created. Whole-chart baseConfidence=(knownTokensA+knownTokensB)/16: 1, 7/8, or 3/4. Apply once to those two families only. All candidates of a person share known/unknown-time status, so that base is invariant across pairs. Day relations default to base 1. Unknown Hour alone never makes these supported rules UNAVAILABLE.

## FE-COV-01

For each group with E>0, candidateAvailabilityRatio=C/E. Static w=baseWeight*ruleWeight*pairWeight, once per group, independent of variants/pair count. coverageEligibleWeight=w; coverageAvailableWeight=w*C/E. For E=0 the ratio is null, both weights are 0 and no Feature is created. Category coverage remains SC-01 sum(available weights)/sum(eligible weights), or 0 for a zero denominator.

M2/N0/U0 gives availability 1, presence 1; M1/N1/U0 gives 1 and 1/2; M1/N0/U1 gives 1/2 and 1; M2/N1/U1 gives 3/4 and 2/3. All NOT_MATCHED yields full coverage with no Feature. All UNAVAILABLE yields zero coverage with no Feature. UNAVAILABLE affects coverage only; NOT_MATCHED affects presence; missing Hour completeness affects whole-chart baseConfidence only. No duplicate penalty. This module does not turn absent features into category scores; subsequent scoring must retain coverage when there is no usable evidence.

## FE-03 identity

One unique present variant uses unchanged SC-04 semantic identity: ruleId, source, subject, category, period and metadata.ruleVariant. Existing transformationStatus=UNASSESSED is preserved in feature metadata but excluded from identity. The 16 frozen legacy IDs must match exactly.

Two or more unique present variants use an **internal** identity with the same common rule/source/subject/category/period fields plus identityVersion=`SAJU_MULTI_VARIANT_IDENTITY_V1`, aggregateVariantType=MULTI and ASCII-sorted unique ruleVariants. No representative/null ruleVariant. No count, multiplicity, pair refs, candidate IDs, signedValue or confidence in identity. Recursively lexical-key-sorted compact UTF-8 JSON; its identity vocabulary is ASCII and therefore NFC. ID=`ft_`+first 48 lowercase hex SHA-256 characters. Variant array ordering is semantic. Contribution records separately retain approved value, count and request-local pair refs.

Internal aggregates, including single-variant records with additional contribution fields, are **not public Feature DTOs**. No schema extension or public MULTI/context projection is approved here. Existing schemas and signed context are unchanged.

## FE-05 context

TEN_GODS_RELATION is evaluated independently from each person's Day-stem perspective using the existing relation/polarity table. It has no scoring signedValue, category weight, SC-01 coverage, SC-07 or scoring evidence refs. Aggregate each perspective with eligibleCount, computableCount, valueCounts and request-local rows. Priority: C=0 UNAVAILABLE; 0<C<E PARTIAL; all computed with one distinct value AGREED; otherwise VARIANT. contextConfidence=C/E, no agreement boost/range penalty. E=0 gives null confidence without a fabricated context evidence object. A side-labelled internal summary may report this unavailable state.

## Internal output and errors

The result is an array model, matching the existing Natal array convention: version/catalogVersion/branchPolarityVersion, rawCandidates(PERSON_A/PERSON_B), candidatePairCount, pairs, ledger groups, features, category coverage, tenGods(PERSON_A/PERSON_B), deferred families. Arithmetic fields are immutable Rational objects, not rounded wire decimals. Input arrays are not mutated. Results and lineage live only in the request.

Errors include UNSUPPORTED_PILLAR_VALUE, FEATURE_CATALOG_MISMATCH, REFERENCE_VERSION_MISMATCH, INVALID_CANDIDATE_LINEAGE and FEATURE_EVIDENCE_ERROR. No neutral fallback, logging, DB, analytics, shared user cache, persistent fingerprint, external runtime service or network. Static catalog file reads are the only added runtime IO.

This implements Natal-to-pre-SC07 evidence, not Lifetime category scoring, SC-07 implementation, blending/orchestration, Daily, REST, frontend or AI. Verification entry: `node docs/contracts/validation/saju-feature-extraction.cjs`.
