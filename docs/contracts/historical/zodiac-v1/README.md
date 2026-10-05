# Historical Zodiac schema snapshot review

Final checkpoint review: 31 retained snapshots. Baseline: 64396abe085a5a0ec8adce4bcb749c65e147252f. Each parses and equals its baseline schema after reversing only the historical $id namespace. Relative references stay entirely inside this directory; production code does not load these schemas.

The unused daily-range-response and interpretation-response copies were removed. Their active schemas remain validated by the structural gate. Existing historical examples and token bytes were not changed.

| Snapshot | Validation use / dependency |
|---|---|
| admin.schema.json | zodiac-api.cjs direct regression; dependency of admin.schema.json |
| astrology-rule.schema.json | examples/manifest.json: astro-v1-astro_sun_moon_trine.json; examples/manifest.json: astro-v1-astro_venus_mars_square.json; examples/manifest.json: astro-v1-astro_moon_saturn_opposition.json |
| celebrity-compatibility-request.schema.json | examples/manifest.json: celebrity-calculation-valid.json; examples/manifest.json: celebrity-calculation-personB-invalid.json |
| celebrity.schema.json | examples/manifest.json: celebrity-reference-valid.json; examples/manifest.json: celebrity-raw-birth-invalid.json |
| common.schema.json | dependency of rule.schema.json; dependency of saju-rule.schema.json; dependency of context-evidence.schema.json |
| compatibility-request.schema.json | examples/manifest.json: compatibility-valid.json; examples/manifest.json: unknown-time-valid.json; examples/manifest.json: unknown-time-invalid.json |
| compatibility-response.schema.json | examples/zodiac/manifest.json: compatibility-valid.json; examples/zodiac/manifest.json: communication-50-invalid.json; examples/zodiac/manifest.json: communication-zero-invalid.json |
| context-evidence.schema.json | examples/manifest.json: saju-v1-ten-gods.json; examples/manifest.json: saju-v1-invalid-ten-god.json; examples/manifest.json: saju-v1-invalid-context-score.json |
| context-interpretation.schema.json | examples/manifest.json: saju-v1-context-interpretation.json |
| daily-evidence.schema.json | examples/manifest.json: daily-evidence-valid.json; examples/manifest.json: daily-evidence-missing-sample-invalid.json; examples/manifest.json: daily-evidence-time-invalid.json |
| daily-range-request.schema.json | examples/manifest.json: range-valid.json |
| daily-request.schema.json | examples/manifest.json: daily-valid.json; examples/manifest.json: daily-date-invalid.json |
| daily-response.schema.json | examples/zodiac/manifest.json: daily-valid.json; examples/zodiac/manifest.json: daily-communication-invalid.json; examples/zodiac/manifest.json: retained-daily-s2--5.0001.json |
| daily-rule.schema.json | examples/manifest.json: daily-rule-astro-valid.json; examples/manifest.json: daily-rule-saju-valid.json; examples/manifest.json: daily-rule-time-null-invalid.json |
| error.schema.json | examples/manifest.json: error-valid.json; examples/manifest.json: error-secret-invalid.json |
| feature.schema.json | dependency of compatibility-response.schema.json; dependency of daily-response.schema.json; dependency of signed-context.schema.json |
| interpretation-request.schema.json | examples/manifest.json: interpretation-request-valid.json |
| location.schema.json | examples/manifest.json: location-reference-valid.json; examples/manifest.json: location-latitude-invalid.json |
| period-request.schema.json | examples/manifest.json: weekly-valid.json; examples/manifest.json: monthly-valid.json; examples/manifest.json: yearly-valid.json |
| period-response.schema.json | examples/zodiac/manifest.json: retained-v3-trend-null.json; examples/zodiac/manifest.json: retained-v3-trend-null-invalid.json; examples/zodiac/manifest.json: retained-v3-trend--8.json |
| person-input.schema.json | dependency of compatibility-request.schema.json; dependency of daily-request.schema.json; dependency of daily-range-request.schema.json |
| rule.schema.json | examples/manifest.json: disabled-rule-valid.json; examples/manifest.json: mixed-rule-schema-valid.json; examples/manifest.json: mixed-missing-value-invalid.json |
| saju-rule.schema.json | examples/manifest.json: saju-v1-day_master_relation.json; examples/manifest.json: saju-v1-day_branch_relation.json; examples/manifest.json: saju-v1-stem_combination.json |
| saju-synthetic-input.schema.json | examples/manifest.json: saju-v1-input.json; examples/manifest.json: saju-v1-invalid-stem.json; examples/manifest.json: saju-v1-invalid-branch.json |
| service-config.schema.json | examples/manifest.json: service-config-valid.json |
| signed-context.schema.json | examples/zodiac/manifest.json: signed-valid.json; examples/zodiac/manifest.json: private-context-invalid.json |
| signed-header.schema.json | examples/manifest.json: signed-header-valid.json; examples/manifest.json: signed-header-alg-invalid.json |
| zodiac-catalog.schema.json | zodiac-api.cjs direct regression |
| zodiac-context.schema.json | examples/zodiac/manifest.json: pair-id-invalid.json; examples/zodiac/manifest.json: relation-invalid.json; examples/zodiac/manifest.json: context-version-invalid.json |
| zodiac-evidence.schema.json | examples/zodiac/manifest.json: zodiac-long-term-invalid.json; examples/zodiac/manifest.json: zodiac-daily-invalid.json; examples/zodiac/manifest.json: wrong-signed-value-invalid.json |
| zodiac-rule.schema.json | zodiac-api.cjs direct regression; dependency of zodiac-catalog.schema.json |
