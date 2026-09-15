# Contract validation

## Current epoch implementation gate

Run `node docs/contracts/validation/epoch-application.cjs` from the repository root with Docker Desktop and the existing WordPress container running. This executes all five existing contract validators and `scripts/test.ps1` (PHP lint and PHPUnit), then checks authorized file scope, unchanged catalogs/public wire, fixed Golden values, privacy-sensitive operations and current readiness. It prints JSON without rewriting reports. First PHPUnit setup uses the existing pinned/hash-verified tool script.

`daily-application.cjs --contracts-only` retains all contract regressions while delegating the previous docs-only worktree scope check to this new application gate. Default mode retains the original restriction. Daily scoring simulations are still synthetic and are not replaced with the production epoch.

Run from the repository root with Node.js. These are documentation validators, not production engine implementations.

```powershell
node docs/contracts/validation/structural.cjs
node docs/contracts/validation/semantic-regression.cjs
node docs/contracts/validation/astrology.cjs
git diff --check
```

The structural validator uses the existing validation-only dependencies under `$env:TEMP/love-fortune-contract-validation/node_modules`: ajv8.20.0, ajv-formats3.0.1, @apidevtools/swagger-parser12.1.0 and yaml2.9.0. It does not install or modify application dependencies. Tested with Node24.13.1. Semantic and Astrology validators use Node built-ins only.

Each command must exit0. Scripts read current files and print results without rewriting reports. structural validates all local schemas, manifest cases, both catalogs, OpenAPI, canonical/token and HTTP/privacy vectors. semantic-regression runs the retained36 and v3's44 arithmetic/period/DST vectors. astrology compares every rule/mapping to the preserved approval and tests weights, scope, orb detection, uncertainty, Feature projection/identity and deliberate semantic mutations.

Schema errors and semantic errors are tested separately: numeric pairWeight resolution, exact approval membership, duplicate IDs/categories and ID-field consistency require semantic checks. Empty or disabled Saju fixtures cannot establish Astrology readiness. Legacy generic rule examples are not approved Astrology v1 rules.

Exact decimal multipleOf avoids binary divisibility artifacts. Catalog generic weights retain the binary64 result of Math.sqrt without manual decimal truncation. Validation fixtures are synthetic; no real-user birth records or production secrets are used.

## Current Saju / Guardrail v2

Run: node docs/contracts/validation/saju-guardrail-v2.cjs
Reads actual catalog and verifies the approved matrix, references, counts,57,600 synthetic combinations and Astrology. Semantic regression uses current v2 vectors; legacy vectors are historical only. No production implementation or provider calls.

## Daily C21-R application

Run `node docs/contracts/validation/daily-application.cjs` for all current checks, or `node docs/contracts/validation/daily.cjs` for Daily only. The application gate executes structural, semantic, Astrology, Saju/guardrail and Daily regressions, checks current metadata, active Daily contracts, and unchanged protected files/Lifetime catalogs. It prints JSON and does not rewrite reports.

Daily reads actual applied rules and compares all mappings to `daily-v1-approved-matrix.json`. Recovered longitude/pillar generators cover 128 x 1000 base pairs, plus a persisted three-way missing-source schedule: mode0 both sources; mode1 `(astroIndex+sajuIndex)%3` selects Saju only, Astrology only, or neither. Total256,000. The previous read-only run's in-memory availability selector was lost at interruption; identical overall old population/distribution is NOT claimed. No matrix/weight/fixture tuning was used to recover its statistics. Full-data108,000 cases and the six individually retained sample-missing deltas are checked exactly against the prior invariants/results.

Daily identity is an internal evidence schema; public Feature and signed LFIC wire remain unchanged. Schema failures and semantic ID/period/lineage failures are separate. Tests use synthetic data; no actual epoch, ephemeris or user data. Test arithmetic is documentation tooling, not production implementation.
