# LOVE FORTUNE
# 04_DATABASE_SPEC.md

Version: 2.0.0
Status: FINAL
Document Type: Database Specification

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

Conditional:

```text
{prefix}lf_ai_cache
```

AI cache는 기본 OFF.

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
ASTROLOGY_RULE
SCORE
PERIOD_RULE
EPHEMERIS_PROVIDER
EPHEMERIS_DATA
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
calendar_type
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

Optional schema concept:

```text
location_id
country_code
region
city
display_name
timezone_id
latitude
longitude
data_version
status
```

다국어/검색 성능을 위해 alias/normalized name 구조를 추가할 수 있다.

---

# 13. Calculation Cache

기본 허용 대상:

- solar terms
- ephemeris positions
- calendar reference
- location reference
- public celebrity calculation components

User pair compatibility 전체 cache는 기본 OFF.

---

# 14. Cache Privacy

금지:

- raw birth payload in key
- user-derived natal JSON
- relationship payload
- raw request body

Birth Data Hash를 자동으로 anonymous로 간주하지 않는다.

---

# 15. AI Cache

기본:

```text
AI_RESULT_PERSISTENCE = OFF
```

사용하려면 별도 privacy review 필요.

---

# 16. Browser Storage

Browser storage는 DB schema의 일부가 아니다.

권장:

```text
IndexedDB
```

두 profile은 independent opt-in.

OFF일 때 memory-only.

---

# 17. Browser Storage 최소 데이터

재계산에 필요한 값만 저장한다.

예:

```json
{
  "schemaVersion": 1,
  "birthDate": "1990-05-10",
  "birthTime": "14:30",
  "birthTimeKnown": true,
  "calendarType": "SOLAR",
  "isLeapMonth": false,
  "locationId": "KR-SEOUL"
}
```

---

# 18. Browser Delete

지원:

- delete my info
- delete partner info
- delete all

저장 Toggle을 OFF로 변경할 때
기존 저장 데이터 삭제를 기본 UX로 한다.

---

# 19. Audit Logs

Admin 변경만 기록한다.

예:

```text
admin_user_id
action
target_type
target_id
before_summary
after_summary
reason
created_at
```

일반 사용자 calculation payload는 audit 대상이 아니다.

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

Personal calculation response:

```text
Cache-Control: no-store
```

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

# 28. Logs / External Systems

Database만 비워두면 privacy가 완료된 것이 아니다.

검토 대상:

```text
Nginx/Apache
Reverse Proxy
WAF
CDN
PHP
APM
Analytics
AI Provider
Backup
```

Request body capture를 disable/redact한다.

---

# 29. Retention

정의 필요:

- audit retention
- error retention
- aggregate analytics retention
- shared cache TTL
- external log retention

Raw Birth Data Retention:

```text
NOT ALLOWED
```

---

# 30. Backup

Backup 대상:

- celebrity
- content
- config/version metadata
- audit
- aggregate analytics

일반 사용자 Birth Profile backup은 존재하지 않아야 한다.

---

# 31. Stateless Acceptance

일반 사용자 두 사람 정보를 계산한 뒤:

```text
DB
Backup
Application Log
```

에서 개인 Birth/Relationship/History row가 생성되지 않아야 한다.

END OF DOCUMENT
