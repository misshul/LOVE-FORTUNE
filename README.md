# LOVE FORTUNE — 로컬 Docker 개발환경

이번 구성은 `LF-BOOT-001`(T00 중 Docker 로컬 환경)에 해당합니다.
WordPress, PHP 8.3/Apache, MySQL 8.4를 실행합니다. T01 플러그인 부트스트랩 코드가 포함되어 있으며 Composer, CI는 후속 작업입니다.
승인된 SAJU_DAY_PILLAR_EPOCH_V1 일진 계산기와 날짜 adapter가 추가되었습니다.
일반 사용자 Birth Data 테이블, 전체 Saju/Score Engine, REST 비즈니스 로직, AI, 사용자 UI, Admin 기능은 후속 범위입니다.

## 준비

Windows의 Docker Desktop에서 WSL2 기반 Linux 컨테이너를 사용합니다.
VS Code PowerShell 터미널을 이 프로젝트 루트에서 열어 실행하세요.
호스트에 PHP/MySQL을 별도 설치할 필요는 없습니다.

실제 `.env`가 이미 있으면 그대로 사용합니다. 새 체크아웃에서만 다음 명령으로 복사하세요.

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

복사한 경우 `.env`의 `MYSQL_PASSWORD`와 `MYSQL_ROOT_PASSWORD`를 서로 다른 로컬 비밀번호로 변경합니다.
파일은 UTF-8로 저장하세요. `$` 또는 `#` 등 특수문자가 있는 값은 작은따옴표로 감싸세요.
`.env`와 `.env.*`는 `.gitignore`로 제외하고 `.env.example`만 공유합니다.
현재 폴더를 Git 저장소로 초기화하거나 기존 저장소에 포함한 뒤 `git check-ignore .env`로 확인할 수 있습니다.
`docker compose config` 전체 출력에는 비밀번호가 포함되므로 검증에는 `--quiet`를 사용합니다.

## 실행 및 확인

```powershell
docker compose config --quiet
docker compose up -d --wait --wait-timeout 180
docker compose ps
```

첫 실행은 이미지 다운로드와 MySQL 초기화 때문에 시간이 걸립니다.
MySQL healthcheck가 실제 앱 계정으로 데이터베이스에 접속하여 `SELECT 1`에 성공한 뒤 WordPress가 시작됩니다.

- WordPress: http://localhost:8080
- 초기 설치: http://localhost:8080/wp-admin/install.php
- 설치 후 운영자 로그인: http://localhost:8080/wp-admin/

WordPress는 `127.0.0.1`에만 공개합니다. 포트 충돌 시 **최초 설치 전에** `.env`의 `WORDPRESS_PORT`를 변경하고 해당 포트로 접속하세요.
MySQL은 호스트 포트를 공개하지 않으며 WordPress가 Compose 내부 주소 `mysql:3306`으로 연결합니다.

## WordPress 초기 설치

1. 초기 설치 URL에서 언어(예: 한국어)를 선택합니다.
2. 사이트 제목에 `LOVE FORTUNE Local`을 입력합니다.
3. 로컬 운영자 사용자명, 비밀번호, 이메일을 입력합니다. 이는 MySQL 계정과 별개입니다.
4. 로컬 사이트이므로 검색 엔진 노출 차단 항목을 선택하고 WordPress 설치를 누릅니다.
5. 생성한 운영자 계정으로 로그인합니다. DB 연결 정보는 환경변수로 제공되므로 입력할 필요가 없습니다.

WordPress 기본 설치 테이블과 운영 계정만 생성됩니다. LOVE FORTUNE 전용 테이블은 생성하지 않습니다.
설치 후 **플러그인 → LOVE FORTUNE Core → 활성화**를 선택합니다.
현재 공통 부트스트랩만 포함하여 별도 메뉴/화면은 추가하지 않습니다.
구조, 범위와 테스트 명령은 [플러그인 README](wp-content/plugins/love-fortune-core/README.md)를 참고하세요.

## 종료 / 재시작

```powershell
# 일시 중지 (컨테이너 유지)
docker compose stop

# 중지 후 다시 시작
docker compose up -d --wait --wait-timeout 180

# 실행 중인 서비스 재시작
docker compose restart

# 종료 및 컨테이너/네트워크 제거 (데이터 볼륨 유지)
docker compose down

# .env 또는 Compose 설정 변경 적용 (restart만으로는 적용되지 않음)
docker compose up -d --wait --wait-timeout 180
```

`docker compose down -v`는 DB와 WordPress 데이터를 삭제하므로 일반 종료에 사용하지 마세요.
기존 MySQL 볼륨에서는 `.env`의 DB 이름/계정/비밀번호를 바꿔도 초기 계정이 다시 생성되지 않습니다.
설치 후 접속 포트를 변경하면 WordPress의 사이트 주소 설정도 함께 변경해야 합니다.

## 파일과 영구 저장 구조

| 호스트 / 볼륨 | 컨테이너 경로 | 용도 |
| --- | --- | --- |
| `mysql_data` named volume | `/var/lib/mysql` | WordPress DB 영구 저장 |
| `wordpress_data` named volume | `/var/www/html` | WordPress 설치 파일, 테마, 업로드 영구 저장 |
| `./wp-content/plugins/love-fortune-core` | `/var/www/html/wp-content/plugins/love-fortune-core` | VS Code에서 편집하는 플러그인 소스 bind mount |

볼륨의 실제 이름에는 기본 프로젝트 이름 `love-fortune`이 붙습니다.
WordPress Core는 공식 이미지가 볼륨에 초기화하며 직접 수정하지 않습니다.
플러그인 폴더의 호스트 변경은 컨테이너에 즉시 반영됩니다.
이 프로젝트는 Windows PowerShell 또는 WSL 터미널 중 한 환경에서 일관되게 실행하는 것을 권장합니다.

MySQL 서버 및 WordPress DB 연결은 `utf8mb4` / `utf8mb4_unicode_ci`를 사용하여 한글, 일본어, 이모지를 지원합니다.
MySQL 기본 스토리지 엔진은 InnoDB이며 서버 시간대는 UTC입니다.
컨테이너 시간대는 향후 운세 계산의 출생지/대상일 시간대 규칙을 대신하지 않습니다.

## 문제 확인

```powershell
docker compose ps
docker compose logs --tail 100 mysql wordpress
```

Docker Engine 연결 오류가 나면 Docker Desktop의 Linux Engine 실행 상태를 확인하세요.
플러그인 mount 오류가 나면 위 호스트 폴더의 존재 및 Docker Desktop의 파일 접근 권한을 확인하세요.
실제 Birth Data나 요청 본문을 로그에 입력하지 마세요.

구성 참고: [WordPress 공식 이미지 설정](https://github.com/docker-library/wordpress/blob/master/wp-config-docker.php),
[Compose 서비스 준비 상태 및 시작 순서](https://docs.docker.com/compose/how-tos/startup-order/).

## Zodiac V1 development status

Zodiac date/pair/source scoring and exact score primitives are implemented. Contracts/catalogs are applied; full Four Pillars, Core scoring orchestration and REST API remain incomplete. No V1 external ephemeris blocker. See [current readiness](docs/contracts/readiness.md) and [Zodiac contract](docs/contracts/zodiac-catalog-v1.md).

Runtime config is generated from authoritative JSON: `node scripts/generate-zodiac-config.cjs --check`. With Docker running, use `node docs/contracts/validation/zodiac-application.cjs` for the complete application gate, including existing regressions and PHP/JS cross checks. Setup for documentation-only validator dependencies is in [validation instructions](docs/contracts/validation/README.md).
