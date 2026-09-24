# Advanced Astrology contracts

Scope: ADVANCED / DEFERRED; not active V1.

The original Astrology catalogs remain at `../rules/astrology-rules.json` (109 rules/228 mappings) and in `../rules/daily-rules.json` (125 Astrology rules/266 mappings). Saju entries in the latter remain active under `../product-scope.json`. Existing numeric catalog records and approval/research history are preserved unchanged.

`schemas/` snapshots the response, Feature, version and signed-context graph that changed with Zodiac. Absolute local-registry references distinguish archived schemas from active `../schemas/`. Shared unchanged schemas use their original IDs. Earlier examples are preserved byte-for-byte; `../examples/manifest.json` explicitly tags affected cases ADVANCED_LEGACY. Original canonical/token vectors remain historical regression vectors. They do not authorize V1 planetary Features or metadata.

The current OpenAPI selects only active V1 schemas. New examples are in `../examples/zodiac/`. Ephemeris remains a deferred Advanced blocker. `enabled=true` in a preserved catalog is insufficient to activate it: the V1 source/period allowlist must also admit the source.
