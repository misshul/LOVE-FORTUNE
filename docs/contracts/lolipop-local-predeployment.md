# Lolipop local pre-deployment compatibility

Scope: local implementation/validation only. No deployment, commit or push. Numeric formulas, catalogs, wire versions and signing semantics remain unchanged.

## Configuration and isolation

Current source order is explicit environment variables only, with no implicit fallback. Existing signing/rate/AI getenv support is preserved. A private secret-file adapter is deferred until actual hosting environment-variable support is known; defining WordPress constants alone does not configure these loaders. Never put secrets in plugin source, wp_options, a public .env or command-line arguments. No production secret is included.

Required additional non-secret variables:

- LOVE_FORTUNE_RATE_ROOT: existing absolute POSIX directory, private, readable/writable by the PHP/Cron identity, mode0700. No symlink components or traversal. No default temporary/public root.
- LOVE_FORTUNE_RATE_SITE: explicit1..64 alphanumeric/underscore/hyphen environment/site label. Use different values and preferably separate roots/identities for staging and production. This is deployment configuration, not a user identifier.

The application creates site/bucket children0700 and counter/lock files0600. Paths beneath known WordPress/document roots are refused; the operator MUST also verify all other domain aliases, initial hosting domain, backup exclusions and actual ownership. Same-UID compromised applications are outside a private-directory isolation guarantee. No0777. Unsupported storage returns existing503 SERVICE_UNAVAILABLE; no limiter bypass.

Compatibility/Daily retain the shared Core60/600 bucket. Range/Week/Month/Year each retain independent60/600 buckets. Interpretation retains10/600. REMOTE_ADDR remains the only client-address source. Forwarding headers are not trusted. HMAC normalization/hourly derivation is unchanged. Files contain count only; filenames contain windowStart and the short-lived HMAC. Persistent storage.lock contains no identifier or counter. No raw IP, Birth, token, prompt or output is stored.

## Locking, capacity and retention

Writers and cleaners acquire the same bucket mutex then the counter lock. Both locks have bounded250ms acquisition; contention fails closed. Open files are verified against lstat/fstat, regular-file type, permissions, owner, inode/device and link count. Cleanup rechecks before unlink. Non-regular/symlink/malformed state raises an error and is never silently reset. No unsafe files are removed.

Maximum1024 counters per bucket bounds scans/work (six buckets, at most6144 counters). Capacity exhaustion returns503 without eviction of active counters. Existing-counter requests do not scan the directory. A new counter performs a bounded capacity scan. This is an operational capacity bound, not a change to per-source request allowance. Validate capacity and cleanup throughput before release.

For a window starting W, requests use [W,W+600). Cleanup eligibility is W+660 (60-second race/scheduling cushion after the window). This is earlier than the maximum retention deadline W+3600; every creation is at or after W. There is2940 seconds between eligibility and the conservative deletion deadline. Cleanup does not refresh counter age. The independent CLI command is `php /private/path/to/rate-cleanup.php` with the same environment configuration. The packaged bin entrypoint is CLI-only and may be invoked in place or copied together with its required runtime hierarchy to a private tools location.

Proposed server schedule: every minute, with measured execution/retry delay fitting inside2940 seconds. Local tests verify eligibility and simulated delayed cleanup; they cannot guarantee a scheduler that stops indefinitely. Actual Cron/Web filesystem, scheduling delay, downtime, deletion and backup behavior remain server-only gates. Monitor cleanup success without HMAC filenames. A failed cleaner does not erase existing data; closing API traffic cannot substitute for deletion. Do not claim strict wall-clock deletion during arbitrary hosting outage. No Lolipop Cron has been configured. No WP-Cron fallback.

## Local reproduction

The original compose.yaml and its volumes remain untouched. compose.compatibility.yaml is an isolated local WordPress6.6.2/PHP8.3 environment with separate named volumes and localhost8086. Its database credential is disposable local-only, never a production credential. Tests create and remove private synthetic rate directories inside the container, outside the plugin bind mount.

1. Run `node scripts/prepare-compatibility.cjs` to generate or recover the disposable credential into ignored `.tools/compatibility-db-password`, then `docker compose -f compose.compatibility.yaml up -d`. Compose mounts the credential as a secret; no credential value is tracked.
2. Run tests/local-install.php using that WordPress service; this initializes only the disposable local installation and reports CLI runtime values, not secrets.
3. Set COMPOSE_FILE=compose.compatibility.yaml in the local shell, then run `node docs/contracts/validation/interpretation-application.cjs`. Save its JSON as .tools/lolipop-full-regression.json.
4. Run `node scripts/validate-lolipop.cjs` for cache and sequential/concurrent Yearly dispatcher checks.
5. Only after PASS, run `node scripts/package-lolipop.cjs`.

Existing provider adapter regressions use interception/mocks; actual local additional checks use AI disabled. No live provider is called. PHPUnit includes real local multi-process flock tests. These do not prove Lolipop locking semantics. CLI max_execution_time and memory limits do not prove Apache/LiteSpeed limits.

## Packaging and rollback

Current Frontend V1 candidate command: `node scripts/package-lolipop.cjs`. Run from the repository root with the existing local compatibility WordPress environment running. No frontend build, bundler, minification or composer install is required. The command runs the approved full Interpretation/Period/Core/PHP/schema/reference regression and additional local cache/concurrent Yearly checks afresh. Child-process failures abort packaging; successful PHPUnit counts are recorded rather than compared with a forever-fixed total. Additional runtime inspection reads the WordPress version file without installation or activation.

The allowlist includes bootstrap/autoload, src PHP (including the service template), frontend CSS/JS, config/bootstrap.php, config/zodiac.php, runtime reference JSON/attribution Markdown and bin/rate-cleanup.php. Required files are asserted individually. Tests, fixtures, env files, Node/Docker/Git, reports, screenshots and logs are excluded. Environment-variable names in loaders remain necessary code; no secret values belong in the package.

Output is ignored .tools/releases/frontend-v1-*/candidate.zip with a single love-fortune-core directory, candidate-sha256.json (sorted relative paths to SHA-256), and validation.json. The ZIP is extracted and its complete file list and every byte hash compared with the manifest. Extracted PHP is copied to a unique container /tmp directory for lint, without replacing the active plugin. Extracted runtime readers load all five frozen JSON references plus Zodiac configuration; reference bytes also match the source checkpoint. Secret/path scans are heuristic and supplement reviewed source, not a proof against arbitrary encoded secrets. Source identity is checked across validation/packaging. File timestamps need not be reproducible; the manifest is deterministic for identical bytes, while the ZIP checksum identifies that particular ZIP. Asset filemtime remains readable after extraction; production cache refresh still needs deployment verification.

No rollback ZIP is generated. HEAD is source provenance, not a known production rollback. Select and verify the actual previously deployed artifact/config separately. Restore complete matched plugin/reference versions, not individual files; never restore compromised keys. Public traffic must remain controlled during any rollback whose storage/privacy properties are unverified. Neither a candidate ZIP nor local tests authorize production deployment. The former backend-only ZIPs and historical packaging evidence are not Frontend V1 release artifacts.

## LiteSpeed and account gates

User reports PHP8.3, WordPress6.6, SSL/backup/LiteSpeed Cache/WAF ON, SSH/Cron(10 slots)/domain support YES. These are user-confirmed settings, not server verification performed here.

Prepare targeted no-cache exclusions for the actual installation prefix plus these seven paths:

```
/wp-json/love-fortune/v1/compatibility/calculate
/wp-json/love-fortune/v1/fortune/daily
/wp-json/love-fortune/v1/fortune/daily-range
/wp-json/love-fortune/v1/fortune/weekly
/wp-json/love-fortune/v1/fortune/monthly
/wp-json/love-fortune/v1/fortune/yearly
/wp-json/love-fortune/v1/interpretation/generate
```

Keep application no-store on success/errors. Do not infer actual cache exclusion from headers alone. No WAF/cache/SSL settings were changed. Verify WAF errors, actual Web resource limits, REMOTE_ADDR, private directory mapping, Cron execution, cross-node flock, body capture/access/WAF logs, backups/restores, outbound HTTPS and live AI separately. Local PASS is not production approval.
