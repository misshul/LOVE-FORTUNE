# Public Combined API changed files

Final checkpoint inventory: 82 files. Two unused snapshots removed; individual snapshot review and final checkpoint report added. No Engine/Domain/numeric catalog/golden changes. One local checkpoint commit authorized; DO NOT PUSH.

## Contracts / reports / OpenAPI (14)

- `docs/05_API_SPEC.md`
- `docs/09_TASK_LIST.md`
- `docs/contracts/README.md`
- `docs/contracts/api-details-v2.md`
- `docs/contracts/combined-lifetime-v1.md`
- `docs/contracts/openapi.yaml`
- `docs/contracts/public-combined-api-v1.md`
- `docs/contracts/public-combined-files-changed.md`
- `docs/contracts/public-combined-final-review.md`
- `docs/contracts/public-combined-implementation-report.md`
- `docs/contracts/readiness.md`
- `docs/contracts/runtime-contract-v2.md`
- `docs/contracts/validation-report.md`
- `docs/contracts/validation-results-public-combined-v1.json`

## Current synthetic examples (9)

- `docs/contracts/examples/public-combined/compatibility-valid.json`
- `docs/contracts/examples/public-combined/insufficient-valid.json`
- `docs/contracts/examples/public-combined/lineage-invalid.json`
- `docs/contracts/examples/public-combined/manifest.json`
- `docs/contracts/examples/public-combined/numeric-unsigned-invalid.json`
- `docs/contracts/examples/public-combined/old-version-invalid.json`
- `docs/contracts/examples/public-combined/request-valid.json`
- `docs/contracts/examples/public-combined/signed-valid.json`
- `docs/contracts/examples/public-combined/unsigned-valid.json`

## Historical snapshot schemas and review (32)

- `docs/contracts/historical/zodiac-v1/README.md`
- `docs/contracts/historical/zodiac-v1/schemas/admin.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/astrology-rule.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/celebrity-compatibility-request.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/celebrity.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/common.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/compatibility-request.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/compatibility-response.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/context-evidence.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/context-interpretation.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/daily-evidence.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/daily-range-request.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/daily-request.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/daily-response.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/daily-rule.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/error.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/feature.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/interpretation-request.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/location.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/period-request.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/period-response.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/person-input.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/rule.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/saju-rule.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/saju-synthetic-input.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/service-config.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/signed-context.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/signed-header.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/zodiac-catalog.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/zodiac-context.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/zodiac-evidence.schema.json`
- `docs/contracts/historical/zodiac-v1/schemas/zodiac-rule.schema.json`

## Current schemas (4)

- `docs/contracts/schemas/common.schema.json`
- `docs/contracts/schemas/compatibility-response.schema.json`
- `docs/contracts/schemas/compatibility-result.schema.json`
- `docs/contracts/schemas/signed-context.schema.json`

## Validation gates (5)

- `docs/contracts/validation/public-combined-application.cjs`
- `docs/contracts/validation/public-combined.cjs`
- `docs/contracts/validation/structural.cjs`
- `docs/contracts/validation/zodiac-api.cjs`
- `docs/contracts/validation/zodiac-application.cjs`

## Production PHP / registration (11)

- `wp-content/plugins/love-fortune-core/config/bootstrap.php`
- `wp-content/plugins/love-fortune-core/src/Api/CanonicalJson.php`
- `wp-content/plugins/love-fortune-core/src/Api/CompatibilityCalculation.php`
- `wp-content/plugins/love-fortune-core/src/Api/CompatibilityEndpoint.php`
- `wp-content/plugins/love-fortune-core/src/Api/CompatibilityInput.php`
- `wp-content/plugins/love-fortune-core/src/Api/CompatibilityProjection.php`
- `wp-content/plugins/love-fortune-core/src/Api/CoreRateLimit.php`
- `wp-content/plugins/love-fortune-core/src/Api/InterpretationContext.php`
- `wp-content/plugins/love-fortune-core/src/Api/PublicError.php`
- `wp-content/plugins/love-fortune-core/src/Api/PublicRelease.php`
- `wp-content/plugins/love-fortune-core/src/Api/PublicResultValidation.php`

## PHP tests / WordPress harness (7)

- `wp-content/plugins/love-fortune-core/tests/Unit/CombinedLifetimeTest.php`
- `wp-content/plugins/love-fortune-core/tests/Unit/PublicCombinedApiTest.php`
- `wp-content/plugins/love-fortune-core/tests/Unit/SajuLifetimeScoringTest.php`
- `wp-content/plugins/love-fortune-core/tests/bootstrap.php`
- `wp-content/plugins/love-fortune-core/tests/public-fixtures.php`
- `wp-content/plugins/love-fortune-core/tests/wordpress-public-api.php`
- `wp-content/plugins/love-fortune-core/tests/wordpress-smoke.php`
