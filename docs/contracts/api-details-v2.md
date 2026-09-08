# API v2 wire normalization

이 문서는 Final Decision v2를 기존 endpoint 및 관리 기능에 연결하는 DTO·method 명세다. 새 Saju/Astrology 해석 규칙이나 구현 코드를 포함하지 않는다. 정확한 object 구조는 `schemas/`와 `openapi.yaml`을 따른다.

## Public calculation

기본 여섯 Core POST 외에, 기존 celebrity calculation을 `POST /celebrities/{celebrityId}/compatibility`로 유지한다. 요청은 `personA`, `relationshipType`, `targetTimezone`, 선택 `locale`이다. 서버는 승인된 celebrity reference를 PERSON_B로 resolve한다. 응답은 일반 compatibility 응답과 동일하다. 공개 Celebrity DTO에 raw birth profile을 노출하지 않는다. 이 경로도 Core body limit/rate/no-store 규칙을 적용한다.

`relationshipType`은 기존 pair contract대로 필수이며 COUPLE/MARRIED/DATING/CRUSH/FRIEND/UNKNOWN이다. gender와 relationshipType으로 base score를 변경하지 않는다.

## Public reference

`GET /locations`, `/locations/{locationId}`, `/service-config`, `/celebrities`, `/celebrities/{celebrityId}`는 읽기 전용이다. 이전 `/locations/search`, `/config`, `/version`은 alias로 제공하지 않는다.

목록 응답은 DTO 배열이다. `q`는 선택, 최대128자이며 NFC 정규화 후 대소문자를 구분하지 않는 reference ID/display name 부분 문자열 검색이다. `limit` 기본50, 범위1..100; `offset` 기본0, 0 이상의 정수다. 정렬은 locationId 또는 celebrityId 오름차순이다. q를 응답이나 로그에 echo하지 않는다. 상세 DTO의 ID는 path ID와 같아야 한다.

`publicBirthPrecision`은 UNKNOWN/DATE_ONLY/DATE_AND_TIME이다. `availableFeatures`는 해당 reference에서 지원되는 등록 Feature 식별자 목록이며 미지원 feature를 넣지 않는다. `imageReference`는 승인된 reference 또는 null이며 원격 fetch 지시문이 아니다.

## Admin

모든 Admin endpoint는 WordPress 인증, 아래 capability, 유효한 `X-WP-Nonce`를 요구한다. Cookie 존재만으로 인증하지 않는다. 인증401 → capability403 → nonce403 순으로 검증하며, 각각 v2에 지정된 오류 code를 사용한다. 모든 Admin 응답은 no-store다.

| Endpoint | Method | Capability | Request / Response |
|---|---|---|---|
| `/admin/versions` | GET | lf_manage_engine | 없음 / VersionRecord 배열 |
| `/admin/versions` | POST | lf_manage_engine | VersionMutation / VersionRecord |
| `/admin/celebrities` | GET | lf_manage_celebrities | 없음 / CelebrityRecord 배열 |
| `/admin/celebrities` | POST | lf_manage_celebrities | CelebrityRecord / 201 CelebrityRecord |
| `/admin/celebrities/{celebrityId}` | PUT | lf_manage_celebrities | CelebrityRecord / CelebrityRecord |
| `/admin/celebrities/{celebrityId}` | DELETE | lf_manage_celebrities | 없음 / 204, body 없음 |
| `/admin/calculation/test` | POST | lf_view_calculations | CalculationTestRequest / CalculationTestResponse |

위 capability는 기존 07_ADMIN_SPEC 목록에서 연결했다. DTO는 `admin.schema.json`의 `$defs`를 따른다. GET mutation, 임의 SQL/code, 임의 파일 실행을 제공하지 않는다.

VersionMutation은 기존 등록 version의 ACTIVATE/DEPRECATE/DISABLE이다. code/config에 존재하지 않거나 검증되지 않은 version은 활성화할 수 없다. Production engine의 두 외부 검증 gate도 통과해야 한다. 검증 실패는422 `VERSION_NOT_READY`다. 이 API가 계산식이나 version 내용을 덮어쓰지는 않는다.

CelebrityRecord의 birthReference는 일반 사용자 profile이 아니라 관리자가 검증한 공개/라이선스 reference다. sourceUrl, profileVersion 및 상태 DRAFT/ACTIVE/DISABLED를 포함한다. sourceUrl은 http/https 출처 표기이며 서버가 임의로 fetch하는 명령이 아니다. POST의 기존 ID는422 `REFERENCE_EXISTS`; PUT path/body ID 불일치는422 `REFERENCE_ID_MISMATCH`; 없는 ID는404다. DELETE는 reference를 DISABLED로 만들어 공개 목록에서 제외하며, 검증된 과거 version의 내용을 수정하지 않는다. Raw body를 감사 로그에 저장하지 않는다.

CalculationTestRequest는 등록된 synthetic/licensed/approved fixtureId와 versions만 받는다. 일반 사용자 Birth Data를 시험용 fixture로 저장하거나 임의 code/SQL을 실행하지 않는다. Response failures에는 code와 선택 field만 담는다. 이 문서는 실제 PHPUnit/엔진 실행을 구현하지 않는다.

## Semantic validation

Schema 외에 날짜 범위/실제 달력 유효성, IANA timezone membership, reference 존재, ISO Monday, path/body ID 일치, registered versions, approved evidenceRefs 및 Feature ID 재계산을 검사한다. signed context의 engineVersions/scoreVersion/configVersion은 result.meta.versions와 일치해야 하며 evidence는 result.features에서 승인된 subset이어야 한다. 문자열 값을 허용한다는 이유로 arbitrary personal data를 metadata에 넣지 않는다.

Canonical numeric serialization은 유한 JSON 수의 불필요한 소수0과 exponent를 제거한 decimal 표기이며 -0은0이다. 스키마의 identity key들은 ASCII이고 문자열 값은 NFC다. token과 Feature ID는 같은 canonical byte 규칙을 사용한다. 제공된 예제는 이 byte 계약의 검증용이며 운영 secret이나 계산 Golden Dataset이 아니다.

## Approved v3 response semantics

Authority: [v3](final-decision-v3.md) and [runtime contract](runtime-contract-v2.md). No usable evidence yields category50/confidence0/INSUFFICIENT_DATA while retaining structural coverage. Period responses require periodDelta (full-precision internal difference), trendStatus and trendDirection. dailyPeriodStatus in the decision maps to the existing dailyStatus wire field. Numeric lifetime fallback days are displayable but excluded from period statistics. Weekly is the arithmetic mean, not the superseded mean/peak/low blend. Score status still uses canonical four-decimal score; trend uses full-precision delta. Action evidenceRefs must resolve to actual Features and confidence is their positive-preConfidenceWeight weighted Feature.confidence. AI cannot change it. Catalog approval is required before score-engine activation independently of contract validity and the two production external gates.
