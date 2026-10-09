# Local Frontend V1

Scope: Compatibility and manually requested Interpretation only. No persistence, accounts, Daily/Period UI or hosting deployment. Local URL generation uses WordPress rest_url, including subdirectory and plain-permalink REST query routing. The historical screen spec's opt-in browser profile storage is not implemented in this explicitly narrower task.

## Local setup

The existing localhost8080 container currently runs WordPress7.1.0/PHP8.3. The separate compatibility environment runs WordPress6.6.2. Neither version was changed by this task.

For the original Docker development environment, from the repository root:

```powershell
node scripts/prepare-frontend-local.cjs
docker compose -f compose.yaml -f .tools/frontend.compose.yaml up -d wordpress
docker compose exec -T wordpress sh -c 'mkdir -p /var/lib/love-fortune-rate && chown www-data:www-data /var/lib/love-fortune-rate && chmod 700 /var/lib/love-fortune-rate'
```

This uses random local-only signing/rate keys in ignored .tools/frontend-local.env, AI disabled, and a private container directory. No secret value is printed. Keep the override when recreating the container; recreate the private directory after container replacement. This local recipe is not a production retention/Cron deployment plan. Do not reuse these keys remotely. Tests configure their own isolated counters. If the local counter directory fills or requests are limited, run the approved cleanup CLI for expired counters; do not disable the limiter.

Create the user page manually: WordPress Admin → Pages → Add New → title LOVE FORTUNE → Shortcode block containing `[love_fortune]` → Publish. Suggested slug: love-fortune. Open http://localhost:8080/love-fortune/ (actual slug/permalink may differ). No page is created by plugin activation. No theme files or rewrite rules are modified.

The explicitly local browser acceptance fixture is a separate page at http://localhost:8080/love-fortune-local-test/. It contains only the shortcode, not birth inputs/results. It can be trashed after review.

## Behavior

129 selectable locations are rendered from the byte-pinned production reference; only ID and display name reach the selector. Golden-only locations remain excluded. Birth dates1900..2099, optional unknown time=null, supported relationship enum, explicit targetTimezone, locale ko-KR. No name, nickname, coordinates or internal pillar data sent. UI timezone default Asia/Tokyo is explicitly submitted and can be changed.

The eight category keys/order and all six backend statuses have Korean presentation labels. Score/confidence/coverage are displayed without recalculation or rounding; COMMUNICATION null is unavailable. Passion means all-ages relationship energy. Warnings use neutral localized copy rather than exposing arbitrary internal strings. Interpretation is manual; FALLBACK is success; empty evidence sections are hidden. Provider text uses textContent only.

Editing inputs cancels an in-flight request and invalidates previous result/token. Reset keeps visible inputs for correction but clears results/token; no browser storage is used. Page departure clears state and resets the form. Tokens remain in closure memory only. Controls have no name attributes, preventing accidental native form serialization; submit is disabled until JavaScript initializes. Autocomplete is disabled as a browser hint, not a guarantee against browser/extension behavior.

## Validation

Full retained backend gate: COMPOSE_FILE=compose.compatibility.yaml, node docs/contracts/validation/interpretation-application.cjs. PHP unit tests include shortcode registration/rendering/assets/options/privacy/unique IDs. Browser runner: node scripts/test-frontend.cjs, using playwright installed only in ignored .tools/frontend-tests. Install with npm.cmd install --prefix .tools/frontend-tests --no-audit --no-fund playwright; then node .tools/frontend-tests/node_modules/playwright/cli.js install chromium. TLS verification must remain enabled.

The browser runner uses synthetic input on the local fixture. Initial Compatibility and fallback are real HTTP; error/plain-text cases then use route interception. It never prints request bodies or signed contexts. Screenshots are ignored .tools artifacts. Existing hosting ZIPs predate frontend assets and are not frontend release packages; packaging/deployment remains outside this local-only task.
