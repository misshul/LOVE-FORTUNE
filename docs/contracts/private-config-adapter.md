# Private runtime configuration adapter

Local implementation only. No hosting change, deployment, commit or push.

The code-owned Support/ConfigLoader and config/bootstrap.php remain registration
configuration only. Support/RuntimeConfig is the common Web/CLI resolver.

## Selection and precedence

LOVE_FORTUNE_CONFIG_FILE may be an environment variable or a PHP constant.
Absent both: unchanged ENV-only mode. Equal values: accepted. Conflicting values,
empty/invalid paths or an explicitly selected broken file: fixed configuration
failure, even if the requested individual value exists in ENV.

For each allowed value, getenv(name) !== false wins, including empty string and
"0". Only an absent ENV value falls back to the private JSON. Missing values then
retain the existing consumer's unconfigured behavior. No secret is copied into
putenv, WordPress options, public DTOs or logs.

One parsed snapshot per selected path per PHP process/request. RuntimeConfig::reset
supports isolated tests/explicit long-running-worker lifecycle boundaries; no
persistent/shared cache. Deployments must not edit a snapshot in place: write a
private temporary file, validate, set restrictive permissions, then safely replace.
Existing in-flight requests may finish with their prior complete snapshot.

## Private file

Absolute POSIX regular file outside ALL public roots and domain aliases. No URL
wrapper, traversal, symlink components or hardlinks. Parent 0700, file 0600, matching
owner, readable by Web PHP and Cron. The reader checks known ABSPATH/DOCUMENT_ROOT;
operators must verify other aliases and CLI placement independently. Same-account
compromised PHP code is not isolated by these permissions.

Maximum 65,536 bytes, UTF-8 JSON object, string values only, no unknown or duplicate
keys (including escaped equivalent names). No executable PHP configuration.
Semantic validation remains in existing signing/rate/provider consumers.

Allowed keys:

- LOVE_FORTUNE_SIGNING_KID
- LOVE_FORTUNE_SIGNING_KEYS
- LOVE_FORTUNE_SIGNING_PREVIOUS_KID
- LOVE_FORTUNE_RATE_SECRET
- LOVE_FORTUNE_RATE_ROOT
- LOVE_FORTUNE_RATE_SITE
- LOVE_FORTUNE_AI_ENABLED
- LOVE_FORTUNE_AI_ENDPOINT
- LOVE_FORTUNE_AI_KEY
- LOVE_FORTUNE_AI_PROVIDER
- LOVE_FORTUNE_AI_MODEL

Placeholder-only example (intentionally unusable secrets):

```json
{
  "LOVE_FORTUNE_SIGNING_KID": "REPLACE_ME",
  "LOVE_FORTUNE_SIGNING_KEYS": "{\"REPLACE_ME\":\"REPLACE_ME\"}",
  "LOVE_FORTUNE_RATE_SECRET": "REPLACE_ME",
  "LOVE_FORTUNE_RATE_ROOT": "/ABSOLUTE/PRIVATE/RATE_ROOT",
  "LOVE_FORTUNE_RATE_SITE": "production",
  "LOVE_FORTUNE_AI_ENABLED": "0"
}
```

Keep this file outside the repository and release ZIP, not under config/references.
Define backup access/retention explicitly. Never dump it or print parsing exceptions.
Do not put it inside a bucket scanned by cleanup.

## Web and actual Cron

Web .htaccess contains only a non-secret path:

```apache
SetEnv LOVE_FORTUNE_CONFIG_FILE /ABSOLUTE/PRIVATE/love-fortune.json
```

No wp-config modification is required. Existing deployment configuration must
remove unintended individual ENV overrides after separately approved migration.

An operator-owned wrapper, NOT a packaged/account-specific runtime file:

```php
<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('LOVE_FORTUNE_CONFIG_FILE', '/ABSOLUTE/PRIVATE/love-fortune.json');
require '/ABSOLUTE/PLUGIN/PATH/bin/rate-cleanup.php';
```

The Cron UI executes this file path. No inline shell environment is required.
The wrapper contains no secrets or parser. Cleanup needs only ROOT/SITE, not
RATE_SECRET, signing keys or AI settings. Its deletion behavior is unchanged;
do not use production cleanup as a readability probe.

Before deployment use a separate non-secret probe through Web and actual Cron.
Confirm file identity/permissions/readability, public-root isolation, override
parity, Web/Cron storage ownership and flock. Then test cleanup only on synthetic
private counters. No public phpinfo page. Diagnostics must be fixed PASS/FAIL,
without values, paths or config dumps.

## Operational limits

FALLBACK-only initial release remains a recommendation, not a release-mode decision.
AI remains disabled unless the exact existing "1" configuration is supplied;
incomplete AI fields preserve provider fallback. Malformed selected source files
fail closed. Signing TTL/rotation and rate semantics do not change.

RATE_SECRET rotation changes HMAC counter identities and can reset effective
allowance within an active window; old counters still require cleanup. Coordinate
rotation separately. Signing KID/key-map rotation must replace one coherent file.

Packaging format/allowlist is unchanged; new Support PHP ships normally. Private
JSON and operator wrapper never belong in the plugin tree. Real Web/Cron access,
all domain aliases, logging, backups and hosting retention remain unverified.
