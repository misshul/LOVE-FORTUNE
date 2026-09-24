# Saju Catalog v1

Version: 1.0.0
Status: APPROVED / APPLIED / READY

Authority: [Application approval](saju-v1-application-approval.md), [final decisions](saju-v1-final-decisions.md). Mapping numbers in rules/saju-rules.json reproduce these approvals. [Guardrail v2](guardrail-v2.md) applies to Saju Lifetime before source blending. Preserved Advanced Astrology retains its original guardrail; Zodiac V1 is NO_SC07. The later [Zodiac V1](zodiac-catalog-v1.md) application changes only source blending/wire availability, not this catalog matrix.

## Catalog and precedence

14 family records:9 enabled scoring families,16 variants,37 category mappings. Five context families have enabled=false (scoring switch), contextOnly=true and empty variants. TEN_GODS_RELATION detectorStatus=DEFINED; the other four DEFERRED. Context baseWeight is historical and never a score weight. All scoring requiresBirthTime=false, not guaranteed confidence1.

Day Stem A x B: STEM_COMBINATION first; otherwise SAME -> DAY_MASTER_RELATION, GENERATION -> ELEMENT_SUPPORT, CONTROL -> ELEMENT_CONTROL. Exactly one owner. Day Branch A x B: LIUHE -> BRANCH_COMBINATION, else CLASH -> BRANCH_CLASH, else primary-element SAME/GENERATION/CONTROL -> DAY_BRANCH_RELATION. Context Xing/Harm/Po never suppresses fallback. No Year/Month/Hour cross-product. Invalid symbols are rejected/unavailable, never OTHER. PAIR score is A/B symmetric.

Basic element order WOOD,FIRE,EARTH,METAL,WATER: generation follows next element in cycle; control skips one. Either directed relation is one PAIR score. Reference tables contain approved5 stem combinations,6 Liuhe,6 Clash. Basic element lookup was checked against https://www.shidianguji.com/book/HY1439/chapter/1m70sj2411b8j ; traditional classification is separate from product signedValues.

Ten Gods columns are same/opposite polarity. Stem indices0,2,4,6,8 are yang;1,3,5,7,9 yin. Each viewpoint may differ; both are context only. No gender-specific spouse-star scoring. No Hidden Stem/Xing/Harm/Po pairs are invented/activated. Po4/6 tradition difference remains a research note, not a conflict.

## Counts and confidence

Visible stem and branch primary element each have weight1. Known time8/person, unknown6/person. No invented Hour/noon/midnight; preserve existing23:30 day-boundary candidates. ELEMENT_COMPLEMENT alone excludes seasonal/month strength, hidden stems and Yongshin; general seasonal engine weighting remains.

pA,pB normalize separately. balance(p)=1-sum(abs(p[e]-0.2))/1.6; joint=(pA+pB)/2; gain=balance(joint)-(balance(A)+balance(B))/2 in[0,.5]. gain<.05 gives no Feature. EC1 [.05,.15), EC2 [.15,.30), EC3 [.30,.50]. Catalog variants contain exact bands and mappings.

YIN_YANG pools both people: balance=1-abs(yang-yin)/(yang+yin). Positive total, nonnegative integer counts and exact sum required. YY0 [0,.25), YY1 [.25,.50), YY2 [.50,.75), YY3 [.75,1]. Invalid is not balance0.

Both families use baseConfidence=(knownA+knownB)/16 =1/.875/.75. Multiply existing candidateAgreement, including existing presence ratio where required, only once. Do not multiply coverage again. Use full precision for bands.

## Projection

Family ruleId; metadata.ruleVariant=catalog variant; subject PAIR; period LIFETIME. Existing canonical featureId algorithm unchanged. Multiple category Features are allowed, duplicate rule/category/variant not allowed. POSITIVE>0, NEGATIVE<0, NEUTRAL=0 within[-1,1]. Mixed signs use separate mappings.

STEM_COMBINATION metadata.transformationStatus=UNASSESSED only; closed non-identity field, no actual transformation score. ContextEvidence is separate: see context-evidence.md.

## Approved product behavior

Low ATTRACTION availability/strong single signal, sparse all-ages PASSION, Saju COMMUNICATION0 and high/positive SUPPORT distribution are APPROVED v1 behavior. No calibration to synthetic frequencies. v1.1 calibration needs a separate task and grants no permission to collect Birth data.

No Hidden Stem, Xing/Harm/Po,12-stage, Shinsal, Wonjin, Yongshin/Heeshin/Gishin, Geokguk or Daewoon scoring.

Freeze PASS; Saju APPLIED/READY; Astrology READY; overall Score Engine catalog readiness READY; catalog blockers NONE. Production BLOCKED_EXTERNAL: EPHEMERIS_PROVIDER. SAJU_DAY_PILLAR_EPOCH_V1 is APPLIED / READY. Catalog readiness is not implementation readiness.
