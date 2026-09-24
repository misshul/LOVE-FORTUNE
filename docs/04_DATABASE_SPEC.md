# LOVE FORTUNE
# 04_DATABASE_SPEC.md

Version: 2.4.0
Status: CONTRACT FROZEN / IMPLEMENTATION READINESS SEPARATE
Document Type: Database Specification

Current V1 authority: [Zodiac V1](contracts/zodiac-catalog-v1.md). SAJU + ZODIAC are active; planetary Astrology and ephemeris are ADVANCED / DEFERRED. This scope supersedes prior source/weight/version gates; preserved Advanced sections do not authorize V1 execution.

Decision Authority: [Final Decision v3](contracts/final-decision-v3.md), then [v2](contracts/final-decision-v2.md) and [approved clarifications](contracts/clarification-v2.md)
Freeze Gate: [Freeze validation and readiness](contracts/README.md)

---

# 1. 목적

본 문서는 LOVE FORTUNE의 MySQL / WordPress 데이터 구조를 정의한다.

핵심 원칙은 Privacy-first Stateless Architecture다.

일반 사용자 Birth/Partner/Relationship/Calculation History를
서버 DB에 저장하지 않는다.

---

# 2. Server Persistence 원칙

저장 대상:

- Admin/User for WordPress operations
- Engine Version Registry
- Celebrity Reference Data
- WordPress Content
- Location Reference Data
- Safe Shared Cache
- Admin Audit
- Aggregate Metrics

저장하지 않는 대상:

- User Birth Profile
- Partner Birth Profile
- Relationship
- Compatibility History
- Daily/Weekly/Monthly/Yearly user history
- Raw AI user history

---

# 3. WordPress User

일반 사용자는 WordPress account를 요구하지 않는다.

`wp_users`는 Admin/Operator/Content Manager 등 운영 계정용이다.

---

# 4. Table Prefix

실제 구현에서는 WordPress prefix를 사용한다.

```php
$wpdb->prefix
```

문서의 `wp_lf_*`는 예시다.

---

# 5. 권장 Tables

Required:

```text
{prefix}lf_engine_versions
{prefix}lf_celebrities
```

WordPress:

```text
{prefix}posts
{prefix}postmeta
```

Optional:

```text
{prefix}lf_locations
{prefix}lf_calculation_cache
{prefix}lf_audit_logs
```

`lf_calculation_cache` is restricted to non-personal shared reference data.
Do not create `lf_ai_cache` or user-derived calculation/AI caches.

---

# 6. 생성 금지 Tables

```text
{prefix}lf_birth_profiles
{prefix}lf_relationships
{prefix}lf_saju_profiles
{prefix}lf_astrology_profiles
{prefix}lf_compatibilities
{prefix}lf_compatibility_scores
{prefix}lf_compatibility_features
{prefix}lf_daily_fortunes
{prefix}lf_period_fortunes
```

동일 목적의 다른 이름 테이블도 만들지 않는다.

---

# 7. Engine Versions

예시 schema:

```sql
CREATE TABLE {prefix}lf_engine_versions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  version_type VARCHAR(64) NOT NULL,
  version VARCHAR(64) NOT NULL,
  status VARCHAR(32) NOT NULL,
  description TEXT NULL,
  activated_at DATETIME NULL,
  deprecated_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_type_version (version_type, version)
);
```

DB는 Registry/Audit 역할이며
계산 공식의 source of truth는 versioned code/config다.

---

# 8. Version Types

예:

```text
FORTUNE_ENGINE
SAJU_RULE
ZODIAC_RULE
ZODIAC_DATE_RANGE
SCORE
PERIOD_RULE
AI_PROMPT
AI_INTERPRETATION_RULE
```

---

# 9. Celebrities

권장 필드:

```text
id
slug
display_name
category
country_code
birth_date
birth_time
birth_time_known
birth_location_id
timezone_id
latitude
longitude
source_note
source_url
source_verified_at
birth_data_confidence
profile_version
status
sort_order
created_at
updated_at
```

이미지는 WordPress Media attachment ID 사용을 권장한다.

---

# 10. Celebrity Privacy / Provenance

공개 인물 데이터도 출처와 provenance를 관리한다.

“공개 데이터”라는 이유만으로
검증/개인정보 고려가 불필요하다고 간주하지 않는다.

확인되지 않은 birth time은 null.

---

# 11. Content

일반 콘텐츠는 WordPress CPT/Posts/Pages를 우선 사용한다.

예:

```text
FAQ
Guide
Notice
Privacy
Terms
Disclaimer
Love Content
```

별도 custom table은 필요성이 명확한 경우만 사용한다.

---

# 12. Location Reference

Server versioned location reference resolves public birthLocationId to lat/lon/timezone. Public reference routes use locationId; calculation Person uses birthLocationId. Both reference DB and packaged dataset remain supported. targetTimezone is required; service-config default Asia/Tokyo is UI-only. Normative contract: [Final Decision v2 SC-08](contracts/runtime-contract-v2.md#sc-08-public-api-wire).

---

# 13. Calculation Cache

User-derived calculation caches are prohibited, including caches keyed by Birth Data hashes. Request-local memory reuse is allowed. Cache only non-personal reference/config/rule/calendar/ephemeris data. No opt-in personal cache is introduced by this specification.

---

# 14. Cache Privacy

금지:

- raw birth payload in key
- user-derived natal JSON
- relationship payload
- raw request body

Birth Data Hash를 자동으로 anonymous로 간주하지 않는다.

---

# 15. AI Cache / Signed Context

Do not create lf_ai_cache or another user-derived AI cache. Signed contexts are transient, client-held and sent in POST bodies with no-store responses. HMAC-SHA-256 verifies integrity; TTL is five minutes. Secrets belong in environment/secret management, never plaintext wp_options. No server replay store or strict one-time claim. Token serialization follows Final Decision v2 SC-09 and runtime-contract-v2.md.

---

# 16. Browser Storage

My Profile: maximum one. Partner Profile: maximum one. Each has independent opt-in, default OFF. OFF uses memory; ON uses IndexedDB. Support explicit save/edit/replace/delete/delete-all. Names/nicknames remain client-side. Never auto-send stored Birth Data on page load, navigation, timers, prefetch, service worker, background sync, analytics or error reporting.

---

# 17. Browser Storage Minimum

Local-only display label plus birthDate,birthLocationId and optional birthTime. My/Partner opt-ins remain independent and default OFF. The calculation DTO excludes labels and requires explicit targetTimezone; no gender, birthTimeKnown or lunar/calendar flags are sent.

---

# 18. Browser Delete

지원:

- delete my info
- delete partner info
- delete all

저장 Toggle을 OFF로 변경할 때
기존 저장 데이터 삭제를 기본 UX로 한다.

---

# 19. Security / Admin Audit

Record Admin mutations only. Allowlist: timestamp,adminUserId,action,resourceType,resourceId,result,requestId. No raw payload or personal input. Retention and audit backup maximum90 days then deletion. Normative contract: [Final Decision v2 SC-10](contracts/runtime-contract-v2.md#sc-10-http-security-and-retention).

---

# 20. Analytics

가능하면 aggregate 중심:

```text
date
metric
dimension
count
sum
min
max
```

Birth demographics나 개인 profile segment는 만들지 않는다.

---

# 21. IP

Domain DB에 일반 사용자 IP를 저장하지 않는다.

Infrastructure rate limit에서 IP가 필요할 수 있으나
retention/purpose를 최소화하고 별도 운영 정책을 적용한다.

---

# 22. Request DTO

Request DTO는 temporary object다.

DB/File/Log에 자동 저장하지 않는다.

---

# 23. Session / Transient / Redis

Raw Birth Data를 기본적으로 다음에 저장하지 않는다.

```text
PHP Session
WordPress Transient
Redis
Job Queue
```

비동기 작업이 필요할 경우 별도 privacy review가 필요하다.

---

# 24. Response Cache

Core calculation, AI interpretation and signed-context responses require Cache-Control: no-store. No CDN/shared cache. Only non-personal reference responses may be cached.

---

# 25. Database Settings

권장:

```text
InnoDB
utf8mb4
UTC timestamps
```

MySQL version은 hosting 호환성을 확인해 확정한다.

---

# 26. Migration

모든 schema change는 migration으로 관리한다.

Raw SQL을 Admin UI에서 직접 실행하지 않는다.

---

# 27. Repository

권장:

```text
EngineVersionRepository
CelebrityRepository
LocationRepository
AuditRepository
SharedCacheRepository
```

다음 Repository는 만들지 않는다.

```text
BirthProfileRepository
RelationshipRepository
CompatibilityHistoryRepository
```

---

# 28. Logs / Infrastructure

Operational allowlist: timestamp,requestId,endpoint,method,httpStatus,durationMs,errorCode,engineVersion,scoreVersion,configVersion. Remove all other fields before persistence. Disable body capture in CDN/WAF/APM/proxy/error tracking; backups must exclude Birth Data, request-response bodies, signed context, prompts and AI output. No stable IDs/fingerprinting/user-derived cache. Normative contract: [Final Decision v2 SC-10](contracts/runtime-contract-v2.md#sc-10-http-security-and-retention).

---

# 29. Retention

Raw Birth0; operational/application logs and backups<=30 days; Security/Admin audit logs and backups<=90 days; non-personal aggregate<=13 months; HMAC rate keys<=3600 seconds. Delete expired backups. No forbidden data under any retention category. Normative contract: [Final Decision v2 SC-10](contracts/runtime-contract-v2.md#sc-10-http-security-and-retention).

---

# 30. Backups

Application log backup maximum30 days; Security/Admin audit backup maximum90 days, delete after expiry. Raw Birth Data, request bodies, signed contexts, raw prompts/AI outputs are forbidden in every backup. Normative contract: [Final Decision v2 SC-10](contracts/runtime-contract-v2.md#sc-10-http-security-and-retention).

---

# 31. Stateless Acceptance

일반 사용자 두 사람 정보를 계산한 뒤:

```text
DB
Backup
Application Log
```

에서 개인 Birth/Relationship/History row가 생성되지 않아야 한다.


## Final v3 contract alignment

[Runtime contract](contracts/runtime-contract-v2.md) applies the approved v3 decisions: structural coverage is retained with zero usable confidence; Daily source confidence includes all four samples with missing=0; Weekly is the valid-Daily arithmetic mean; period fallback days are excluded; periodDelta and trend magnitude/direction are separate; Actions use weighted Feature.confidence and cannot alter scores. UI displays deterministic fields; AI cannot alter Action confidence. No storage or new engine rules are introduced. Contract Freeze is independent of BLOCKED_CATALOG (SCORING_RULE_CATALOG_APPROVAL) and ADVANCED-only BLOCKED_EXTERNAL (EPHEMERIS_PROVIDER); V1_EXTERNAL_BLOCKERS=NONE, epoch APPLIED.

END OF DOCUMENT

V1 version administration admits Zodiac rule/date versions; Astrology and ephemeris types remain Advanced-only and cannot activate through the V1 schema. No DB migration is introduced by this contract change.
