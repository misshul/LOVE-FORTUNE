# Contract validation

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
