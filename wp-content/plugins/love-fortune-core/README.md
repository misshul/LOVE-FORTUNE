# LOVE FORTUNE Core

`LF-BOOT-002` — T01 Plugin Bootstrap, plugin version `0.1.0`.
PHP 8.3 이상 / WordPress 6.6 이상. 현재 로컬 대상은 WordPress 7.1.0입니다.

## 활성화

WordPress 초기 설치 후 `http://localhost:8080/wp-admin/plugins.php`에서
**LOVE FORTUNE Core → 활성화**를 선택합니다. 별도 Composer 설치는 필요 없습니다.
활성화 후 화면이나 메뉴가 추가되지 않는 것이 정상입니다.
비활성화 hook은 등록되어 있으며 현재 정리할 리소스가 없어 데이터를 변경하지 않습니다.
삭제 시 데이터 삭제 동작은 등록하지 않았습니다.

## 구조와 범위

| 구성 | 구현 |
| --- | --- |
| 진입점 | `love-fortune-core.php`: 헤더, 직접 접근 차단, 활성화/비활성화 및 plugins_loaded hook |
| 자동 로딩 | `autoload.php`: `LoveFortune\Core\` → `src/` |
| DI | `PluginFactory`: 의존성을 생성하고 생성자로 주입, 전역 컨테이너 없음 |
| 설정 | `ConfigLoader`: 코드 소유 `config/bootstrap.php`의 필수 등록 목록 검증 |
| Route/Admin loader | `HookLoader`: `RegistrationInterface` 구현체를 rest_api_init/admin_menu에서 실행 |
| Migration runner | `MigrationRunner`: 명시적인 활성화 실행, 완료 ID 기록, 동시 실행 잠금, 실패 후 재시도 |
| Engine registry | `EngineRegistry`: 요청 내 엔진 객체 조회, 미등록 엔진은 오류 |
| Version registry | `VersionRegistryInterface` / `VersionRegistry`: 불변 버전 메타데이터 조회, 미등록 버전은 오류 |
| 오류 처리 | `ErrorHandler`: 플러그인 lifecycle 오류만 고정 코드로 처리, 원문/trace 로깅 없음 |

`config/bootstrap.php`는 신뢰된 PHP 코드이며 사용자 입력으로 파일을 선택하거나 수정하지 않습니다.
등록 객체는 각 loader/interface 계약을 따라 명시적으로 구성합니다.
현재 등록 목록은 모두 비어 있습니다. 실제 엔진이나 버전 값을 임의로 등록하지 않습니다.
플러그인 버전은 진입점 헤더에만 정의하며 서비스/계산 버전과 구분합니다.

T02는 실제 버전 종류·설정·활성화 정책, T03은 실제 schema/migration,
T14는 REST endpoint·오류 응답 계약, T23은 Admin 기능을 담당합니다.
현재 API, Admin 메뉴, 계산, AI, 개인정보 저장, 외부 호출은 없습니다.

`src/Api`, `src/Application`, `src/Domain`, `src/Admin`, `src/Privacy`는
후속 구현 경계를 표시하는 빈 폴더이며 `.gitkeep`으로 Git에서 유지합니다.
Route/Admin 등록 동작은 공통 `src/Support/HookLoader.php`를 각 hook에 별도로 연결합니다.

## Migration 계약

현재 migration이 없으므로 활성화 시 전용 테이블, migration journal, lock 모두 생성하지 않습니다.
향후 등록된 migration이 있을 때만 WordPress Options API에 완료 ID와 실행 잠금을 기록합니다.
실제 SQL에서는 `$wpdb->prefix`를 사용하고 migration의 `up()`은 재실행 가능해야 합니다.
MySQL DDL의 자동 commit 때문에 이 runner는 전체 rollback을 보장하지 않습니다.
실패한 migration은 완료 처리하지 않고 다음 명시적 실행에서 재시도합니다.
플러그인 파일 업데이트만으로 자동 migration하지 않습니다.
Multisite migration은 지원하지 않으며 등록된 migration이 있을 경우 명시적으로 실패합니다.
프로세스 강제 종료로 잠금이 남은 경우 동작 중인 migration이 없는지 운영자가 확인한 뒤 별도 복구해야 합니다.

## 검증

프로젝트 루트 PowerShell에서 PHPUnit 및 전체 PHP 구문 검사를 실행합니다.
Windows 시스템 실행 정책은 변경하지 않습니다.

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File scripts/test.ps1
```

첫 실행에는 네트워크가 필요합니다. 공식 PHPUnit `12.5.0` PHAR(약 5.88 MiB)를
프로젝트 루트 `.tools/`에 캐시하고, 공식 배포 목록의 고정 SHA256과 비교한 뒤
컨테이너 `/tmp/`에서 실행합니다. 도구는 Git 및 WordPress 웹 루트에서 제외됩니다.
PHPUnit은 BSD-3-Clause 라이선스의 개발용 의존성이며 PHP 8.3과 호환됩니다.
기존 독립 테스트 실행기를 PHPUnit으로 전환하여 실제 PHPUnit 결과를 제공합니다.
버전을 올릴 때 `scripts/test.ps1`의 버전과 공식 SHA256을 함께 검증·갱신하세요.

`tests/Unit/BootstrapTest.php`는 WordPress 함수의 테스트 대역을 사용하며 실제 DB를 변경하지 않습니다.
테스트용 임시 파일은 OS 임시 폴더에 생성 후 제거합니다. 테스트 PHP의 HTTP 직접 호출은 404로 종료합니다.

실제 WordPress 통합 검증은 다음과 같습니다. **첫 명령은 비활성화·활성화 후 활성 상태로 남깁니다.**
두 번째 명령은 새 PHP 프로세스에서 활성 플러그인 로딩을 확인합니다.
WordPress 초기 설치가 완료된 로컬 환경에서만 사용하세요.

```powershell
docker compose exec -T wordpress php /var/www/html/wp-content/plugins/love-fortune-core/tests/wordpress-smoke.php --cycle
docker compose exec -T wordpress php /var/www/html/wp-content/plugins/love-fortune-core/tests/wordpress-smoke.php
```

검증 대상은 플러그인 metadata, 실제 활성화·비활성화, Core 관리자 목록 HTML의 활성 상태 표시,
전용 테이블·옵션 변경 없음, 비즈니스 REST route 없음입니다.
관리자 목록은 기존 운영자 ID를 사용하여 CLI 프로세스 안에서 렌더링하며 계정이나 비밀번호를 변경하지 않습니다.
이 검증은 브라우저 클릭·화면 스크린샷 검증과 별개입니다.

PHP 구문 검사는 전체 정적 타입 분석을 대신하지 않습니다. PHPStan/CI는 후속 작업입니다.

참고: [WordPress 헤더 규칙](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/),
[활성화 hook](https://developer.wordpress.org/plugins/plugin-basics/activation-deactivation-hooks/).
테스트 도구 참고: [PHPUnit 설치](https://docs.phpunit.de/en/12.5/installation.html), [공식 PHAR 및 SHA256](https://phar.phpunit.de/).
