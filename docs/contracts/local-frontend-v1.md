# Local Frontend V1

## Approved V1.1 visual polish

Authority: user request2711ba3b-8145-4831-af61-e049420057da; UI-01 approved. This section supersedes V1 display copy/layout below, not calculation/API/privacy contracts. Implementation and validation: [V1.1 report](local-frontend-v1-1-implementation-report.md).

An ordinary, non-password-protected page containing only `[love_fortune]` (with optional WordPress Shortcode block comments) uses the plugin-owned service template. Mixed content, shortcode attributes, other shortcodes, posts, admin, feeds and embeds retain their existing templates. The service template retains WordPress head/body/footer hooks and admin toolbar support but does not render theme navigation, page title or footer. It uses no slug/host/page-ID binding, DB change or theme file edit. Existing page content/title is not deleted. The body class and document title filters use the same eligibility guard.

UI preserves exact API numeric values and canonical category order. Overall number and unit are separate; null has no `/ 100`. Only explicit COMMUNICATION score=null displays `점수 제공 안 함`; other missing scores remain `계산 정보 없음`. SUPPORT is `서로의 지지`. Status display: CAUTION `배려가 필요한 관계`, BALANCED `균형을 알아가는 관계`, GOOD `좋은 조화`, VERY_GOOD `매우 좋은 조화`, EXCELLENT `뛰어난 조화`, INSUFFICIENT_DATA `아직 결과를 내기 어려워요`. These are enum labels, not frontend classifications.

Interpretation CTA follows categories; interpretation result precedes collapsed details. `입력 수정하기` appears only with a result; it retains inputs, clears results/token, cancels pending work and returns focus without submitting. Unknown time remains null. Date range guidance, grouped time controls, `결과 기준 시간대`, concise privacy/loading/errors and `살펴볼 점` polish presentation only. Locations remain native and exactly the approved129 IDs/order; grouping/search/translation is deferred. Mobile category rows and full-width CTA apply at<=640px; tablet categories have two columns through959px; desktop four columns. Interactive keyboard outlines remain prominent; programmatically focused result sections have a subtle outline.

No account/storage/analytics/new API or Daily/Period UI. No semantic/scoring version change. Final regression and authorized local checkpoint execution are recorded in the [V1.1 final review](local-frontend-v1-1-final-review.md); earlier no-commit statements describe the implementation phase. No push or deployment is authorized.

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
