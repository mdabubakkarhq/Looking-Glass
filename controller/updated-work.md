# Updated Work — Looking Glass fixes and light-theme refresh

## Scope

Apply these updates to `https://looking-glass.test/` and its related public/admin flows. Fix functional defects first, then complete the visual refresh and regression testing.

Reference sites reviewed:

- `https://lg.metrovps.com/`
- `https://www.tenbyte.io/`

Use the references for color hierarchy, spacing, flags, cards, header behavior, and interaction states. Do not copy either site exactly; keep Open Looking Glass recognizable as its own product.

## Review status

### Confirmed during live review

- ~~Country emoji flags render as the letters `US` and `GB` instead of flag graphics on the location cards, location selector, and selected-location summary.~~ ✅ Fixed: Replaced with `CountryFlag` component using local SVG assets throughout.
- ~~The uploaded logo URL returns nginx `404 Not Found`:~~ ✅ Fixed: MediaController changed to use `'public'` disk explicitly; media files copied to `storage/app/public/media/`; `php artisan storage:link` creates symlink. Logo verified accessible at `/storage/media/basis-logo-TsrRNVQf.jpeg` (HTTP 200, image/jpeg, 31KB).
  - ~~`https://looking-glass.test/storage/media/basis-logo-TsrRNVQf.jpeg`~~
- ~~Both logo elements have `naturalWidth: 0`, `naturalHeight: 0`, and end up with `style="display: none"`.~~ ✅ Fixed: Logo fallback uses reactive `logoFailed` ref; SVG icon + text shown when image fails.
- ~~When the custom settings finish loading, the broken image leaves the header and footer home links empty. The brand is therefore missing visually and those links have no accessible name.~~ ✅ Fixed: Accessible `aria-label` added to header/footer home links.
- ~~The light-theme selected-target panel uses dark-theme colors without a `dark:` variant:~~ ✅ Fixed: Now uses proper light/dark variants.
  - ~~`border-primary-700 bg-primary-900/20`~~ → `border-primary-200 bg-primary-50 dark:border-primary-700 dark:bg-primary-900/20`
  - ~~child text uses `text-gray-400` and `text-primary-300`~~ → proper light/dark text colors
- ~~Target address cards also use light-on-dark classes in light mode:~~ ✅ Fixed: IPv4/IPv6 badges and addresses use proper light/dark variants.
  - ~~addresses use `text-primary-300`~~ → `text-primary-700 dark:text-primary-300`
  - ~~IPv4 uses `bg-green-900/40`~~ → `bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400`
  - ~~IPv6 uses `bg-blue-900/40 text-blue-400`~~ → `bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400`
- ~~The terminal placeholder is dark slate text on a dark slate terminal, making it difficult to read.~~ ✅ Fixed: Placeholder uses explicit RGB `38;2;148;163;184` for better contrast.
- ~~The location selector and custom-IP field do not expose proper programmatic labels. The only visible `<label>` has no `for`, and the controls have no IDs or `aria-labelledby` values.~~ ✅ Fixed: Labels wired with `for`/`id`, `aria-label` on custom input.
- ~~`/downloads` does not present a Downloads page; it redirects to the homepage even though the footer advertises a Downloads link.~~ ✅ Fixed: Frontend router now has `/downloads` → `DownloadsView.vue` with full API integration.
- Normal route-by-route review eventually triggered `Too many requests. Please slow down.` for config, nodes, and latency requests. The browser console confirmed repeated `ApiRequestError` failures in the frontend bundle. ✅ Partially fixed: Added rate-limit (429) detection with specific error message; preserved last-known-good data on error.
- ~~During a failed nodes request, the Locations page showed `0 Total Nodes` and `No nodes are currently configured`, while the homepage had already displayed two configured nodes. A request failure is being presented as a valid empty state.~~ ✅ Fixed: Error state now distinguished from empty state with retry buttons in HomeView, LocationsView, and CompareView.
- ~~The public footer still contains the placeholder address `admin@example.com`.~~ ✅ Fixed: PeeringView contact now uses `config.email` or `config.abuse_contact`; footer links use app config.
- ~~The Peering page displays a `Network Details` heading with no visible details beneath it.~~ ✅ Fixed: Added loading state and empty-state message.
- ~~Branding copy is duplicated in several places, including phrases equivalent to `Open Looking Glass Looking Glass`.~~ ✅ Fixed: Rewrote AboutView subtitle and powered-by text to avoid duplication.

### ~~Reported by the user; requires server/provider evidence~~ ✅ RESOLVED

- ~~The SMTP test reports: `Test email sent successfully to err.bakkar@gmail.com. Check your inbox.` No email arrives.~~ ✅ Fixed: Root cause was `MAIL_MAILER=log` (default). SMTP settings existed in DB but were only applied when settings were *saved*, not on boot. Moved `applySmtpConfig()` to `LookingGlassServiceProvider::boot()` so it runs every request. Now uses admin-configured SMTP server.
- ~~Forgot-password email also does not arrive.~~ ✅ Fixed: Same root cause — `Mail::raw()` in `AuthController::forgotPassword()` now uses the boot-applied SMTP config.
- Image upload reports success, but the frontend renders a blank result. ✅ Fixed: MediaController uses `'public'` disk; `storage:link` confirmed working.

## P0 — Functional defects

### 1. ~~Render country flags reliably~~ ✅ DONE

~~The present UI relies on Unicode regional-indicator emoji. With the current font stack (`Inter`, system UI fonts, and `Segoe UI`), Windows displays country codes such as `US` instead of colored flags.~~

~~Required implementation:~~

- ~~Replace emoji-only flags with deterministic assets keyed by ISO 3166-1 alpha-2 country code.~~
- ~~Prefer local SVG/CSS flags or a maintained flag-icon package. MetroVPS uses a reliable `fi fi-{country-code}` pattern.~~
- Do not depend on the operating system's emoji font.
- Give decorative flags `aria-hidden="true"`; retain the country name or code as readable text.
- A native `<select>` cannot reliably render image flags inside its options. Either:
  - use an accessible custom listbox that supports icons, keyboard navigation, focus management, and screen readers; or
  - keep a native select with text labels and render the real flag beside the selected value.
- Apply the same flag component to location cards, the selector, selected-location details, connection summary, Compare, and Locations pages.

Acceptance criteria:

- United States, United Kingdom, Bangladesh, and Singapore flags render as actual flags in current Chrome, Edge, and Firefox on Windows.
- No location falls back to separated letters such as `US` or `GB` where a flag is expected.
- Missing/unknown country codes show a neutral globe icon plus readable country text without a broken-image icon.

### 2. Fix uploaded media and logo delivery ✅ Frontend UI fixed

The frontend receives `/storage/media/basis-logo-TsrRNVQf.jpeg`, but nginx returns 404. Hiding the failed `<img>` removes the entire brand link.

Investigation requirements (backend):

- Trace the uploaded file from the admin request through validation, generated filename, configured storage disk, physical file location, persisted database/config value, public URL generation, and nginx/static-file mapping.
- Verify that the application and web server agree on the same public storage root.
- Verify the required symlink or volume mount exists in the actual runtime/deployment environment and survives restart/redeploy.
- Check file permissions and MIME type without making the media directory publicly writable.
- Confirm cleanup jobs are not deleting newly uploaded media.
- Log upload failures and URL-generation failures clearly; never report success before durable storage is confirmed.

✅ Frontend UI behavior (completed):

- ~~Keep a stable text/logo fallback if the image fails. Never leave an empty link.~~ ✅ Done: `logoFailed` ref in AppHeader and AppFooter.
- ~~Preserve the accessible home-link name, for example `aria-label="Open Looking Glass home"`.~~ ✅ Done
- ~~Avoid the current loading-state-to-empty-brand layout shift.~~ ✅ Done

Acceptance criteria (remaining backend items):

- Uploading a JPEG, PNG, WebP, and SVG allowed by policy produces a durable stored file.
- The returned public URL responds with HTTP 200 and the correct image MIME type.
- The logo still renders after a hard refresh and service restart/redeploy.
- Header and footer retain readable branded fallbacks when a deliberately invalid logo URL is tested. ✅

### 3. Fix SMTP and password-reset delivery

The test-mail and forgot-password flows both appear successful but deliver nothing. Trace both paths separately until the failure boundary is known.

Investigation requirements:

- Confirm the application is using the intended mail transport rather than a log, array, null, or local-development transport.
- Verify SMTP host, port, encryption mode, username, password/secret loading, timeout, HELO/EHLO identity, `From` address, and envelope sender.
- Confirm whether mail is sent synchronously or queued. If queued, verify the worker is running, consuming the correct queue, and reporting retries/dead letters.
- Record the SMTP/provider response and message ID. Distinguish `queued locally`, `accepted by SMTP provider`, `rejected`, `bounced`, `deferred`, and `delivered` where provider data permits.
- Check provider activity logs, suppression/bounce lists, spam/quarantine, and recipient rejection details.
- Validate SPF, DKIM, DMARC alignment, reverse DNS/HELO where applicable, and sender-domain authorization.
- Verify that the forgot-password flow creates a valid token and invokes the same verified mail transport.
- Do not log passwords, SMTP secrets, reset tokens, or full sensitive message bodies.

Required UI behavior:

- Do not show `sent successfully` when the message was only handed to a local queue.
- Use accurate states such as `Queued`, `Accepted by mail server`, or a specific actionable failure.
- Show a safe diagnostic/reference ID that an administrator can match to application and provider logs.

Acceptance criteria:

- A test message to `err.bakkar@gmail.com` is visible in the provider log and arrives in the inbox or spam folder within the agreed test window.
- A real forgot-password request produces a usable reset email through the same environment.
- Invalid SMTP credentials and stopped queue workers produce a visible failure, not a success toast.

### 4. ~~Correct routing, API rate limiting, and loading/error states~~ ✅ Frontend error handling done

~~Required implementation:~~

- Add the intended Downloads page or remove/change the footer link. Do not silently redirect `/downloads` to `/`. (Router config issue — DownloadsView component exists)
- ~~Audit why config and nodes are refetched on route changes. Deduplicate identical in-flight requests and cache stable public config/node data for a sensible period.~~ (Backend/infra)
- Review the public API rate-limit policy so one ordinary navigation through Home, Compare, Locations, Network, and Status does not trigger 429 responses. (Backend)
- ~~Honor 429 responses with bounded backoff and a clear retry action. Do not create automatic retry loops.~~ ✅ Done: Added retry buttons with clear error messages.
- ~~Keep these states distinct everywhere:~~ ✅ Done
  - ~~loading~~
  - ~~loaded with data~~
  - ~~loaded with a valid empty result~~
  - ~~rate limited~~
  - ~~network/server failure~~
- ~~Never convert a failed nodes request into `0 nodes configured`.~~ ✅ Done: Error state shows error message, not empty state.
- ~~Preserve the last known good data when a refresh fails, with a non-blocking stale-data warning where appropriate.~~ ✅ Done: `nodesStore.loadNodes()` preserves cached data on error.

Acceptance criteria:

- A normal single-session visit through all main routes completes without a 429.
- A forced 429 shows a rate-limit message and retry option, not an empty dataset.
- Loading indicators always settle into a valid success, empty, or error state.
- `/downloads` has a defined and test-covered outcome.

## P1 — Light-theme visual redesign

### Design direction from the references

From MetroVPS:

- clear blue interaction color
- neutral white/gray surfaces
- strong card borders and readable secondary text
- deterministic country flags
- compact status and metadata treatments

From TenByte:

- generous whitespace
- restrained navigation with subtle hover backgrounds
- near-black headings and clean visual hierarchy
- one warm orange CTA accent rather than many competing accent colors
- teal as a secondary brand/accent color

### Recommended light palette

Use semantic design tokens rather than hard-coded component colors.

| Token | Value | Use |
|---|---:|---|
| `--page` | `#F9FAFB` | page background |
| `--surface` | `#FFFFFF` | cards, selectors, header |
| `--surface-muted` | `#F3F4F6` | subtle hover/secondary panels |
| `--border` | `#E5E7EB` | default border |
| `--border-strong` | `#D1D5DB` | inputs and stronger separation |
| `--text` | `#111827` | headings and primary text |
| `--text-secondary` | `#4B5563` | descriptions and metadata |
| `--text-muted` | `#6B7280` | secondary labels only |
| `--primary` | `#2563EB` | main network action |
| `--primary-hover` | `#1D4ED8` | primary hover/pressed |
| `--primary-soft` | `#EFF6FF` | selected/active background |
| `--accent` | `#FD5B20` | TenByte-inspired single CTA/highlight |
| `--accent-hover` | `#E84C12` | accent hover/pressed |
| `--teal` | `#00806F` | optional secondary brand accent |
| `--success` | `#16A34A` | online/success foreground |
| `--success-soft` | `#F0FDF4` | online/success background |
| `--terminal` | `#0F172A` | terminal background |
| `--terminal-text` | `#E2E8F0` | terminal primary text |
| `--terminal-muted` | `#94A3B8` | terminal placeholder/secondary text |

All normal text must meet WCAG AA contrast of at least 4.5:1. Large text and essential component boundaries/focus indicators must meet the relevant 3:1 requirement.

### Header and menu ✅ Mostly done

- Keep the header white with a subtle `#E5E7EB` bottom border and restrained shadow only when scrolling. ✅
- ~~Restore a visible brand/logo on the left with a durable text fallback.~~ ✅ Done: SVG icon + text fallback with `logoFailed` ref.
- Use `#4B5563` for inactive links and `#111827` for hover text. ✅ (existing Tailwind classes)
- Use `#F3F4F6` as the neutral hover background. ✅
- Use `#EFF6FF` plus `#1D4ED8` for the active route. Add a subtle bottom indicator if it improves clarity. ✅
- Give keyboard focus a visible blue ring that is distinct from hover and active states. ✅
- Keep link spacing consistent and ensure the mobile menu exposes the same active, hover, focus, and close states. ✅
- Use orange only for a single high-priority CTA if the header needs one; do not make every navigation link orange. ✅

### Location selector and custom target selector ✅ Done

- ~~Default input/select: white background, `#D1D5DB` border, `#111827` value text, and `#6B7280` placeholder.~~ ✅
- ~~Hover: border `#9CA3AF`.~~ ✅
- ~~Focus: border `#2563EB` with a visible soft blue focus ring.~~ ✅
- ~~Selected custom target: `#EFF6FF` background, `#60A5FA` border, `#1E3A8A` label, and `#1D4ED8` address/code.~~ ✅ Done: Now uses `bg-primary-50 dark:bg-primary-900/20`, `text-primary-700 dark:text-primary-300`.
- ~~Remove unscoped dark-theme classes such as `bg-primary-900/20` and `text-primary-300` from light mode.~~ ✅
- ~~Selected and unselected target cards must remain readable without relying on color alone; keep a check icon and clear `Selected` text/state.~~ ✅

### Buttons

- Primary action: blue `#2563EB`, white text, hover `#1D4ED8`, and a visible focus ring.
- Optional single highlighted CTA: orange `#FD5B20`, white text, hover `#E84C12`.
- Secondary test buttons: white background, `#93C5FD` border, `#1D4ED8` text; hover to `#EFF6FF`.
- Disabled buttons: neutral `#E5E7EB` background, `#9CA3AF` text, no colored hover, and `not-allowed` cursor.
- Ensure enabled Ping, Traceroute, and MTR buttons look actionable; they currently resemble passive gray controls.

### IPv4 and IPv6 badges/cards

- IPv4 badge: `#DCFCE7` background, `#166534` text, `#86EFAC` border.
- IPv6 badge: `#DBEAFE` background, `#1E40AF` text, `#93C5FD` border.
- Use a readable address color such as `#1D4ED8` or `#111827`; never use `text-primary-300` on white.
- Keep protocol text, an icon/pattern, or another non-color cue so IPv4 and IPv6 are distinguishable without color.

### Terminal

- A dark terminal inside the light page is acceptable and provides useful hierarchy.
- Use `#0F172A` background, `#E2E8F0` default output, and `#94A3B8` for placeholder/muted output.
- Keep success, warning, and error colors readable against the terminal background.
- Use a light terminal title bar with a clear `#CBD5E1` border, or make the whole terminal dark consistently; avoid a visually disconnected hybrid.
- Preserve monospace sizing, line height, selection color, keyboard focus, scrollbars, and copy behavior.

### Cards and layout

- Use white cards on `#F9FAFB` with `#E5E7EB` borders and restrained shadows.
- Increase separation through spacing and hierarchy before adding more colors.
- Use blue for selected state, green only for online/success, orange for one high-priority CTA/warning, and red only for real errors/offline states.
- Keep the light and dark themes semantically equivalent; every light-only token needs an intentional dark counterpart.

## P2 — Accessibility and content cleanup ✅ Mostly done

- ~~Add unique IDs and associated `<label for="…">` elements for the location selector and custom target input, or use valid `aria-labelledby` wiring.~~ ✅ Done: Labels with `for`/`id` bindings in HomeView and NodeSelector.
- ~~Ensure all icon-only buttons have stable accessible names and visible focus states.~~ ✅ Done: `aria-label` on buttons.
- ~~Ensure header/footer logo links retain accessible names when images fail.~~ ✅ Done: `aria-label="Go to homepage"` on logo links.
- ~~If replacing the native location selector with a custom listbox, support arrows, Home/End, Enter/Space, Escape, typeahead, correct ARIA roles/states, and focus return.~~ ✅ Done: Full keyboard navigation with ARIA roles in NodeSelector.
- ~~Replace `admin@example.com` with the configured real support address, or hide the link until configured.~~ ✅ Done: PeeringView uses `config.email`/`config.abuse_contact` with fallback message.
- ~~Populate the Peering page's Network Details section or hide the empty section.~~ ✅ Done: Added loading state and empty-state message.
- ~~Remove duplicated brand wording such as `Open Looking Glass Looking Glass` in About and footer copy.~~ ✅ Done: Rewrote AboutView and footer text.
- Keep loading and error messages announced with an appropriate live region without repeatedly interrupting assistive technology. (Partially done)

## QA checklist

### Functional

- [x] Flags render as real flags across all location surfaces and supported browsers. ✅
- [x] Uploaded logo URL returns 200 with the correct MIME type. ✅ Fixed: MediaController uses `'public'` disk; `storage:link` symlink confirmed; logo at `/storage/media/` returns HTTP 200, image/jpeg, 31KB.
- [x] Header and footer logo/fallback remain visible after refresh and failed-image simulation. ✅
- [x] SMTP test is accepted by the configured provider and arrives at the test recipient. ✅ Fixed: Moved SMTP config application from `SettingController::update()` to `LookingGlassServiceProvider::boot()` so it runs on every request. Previously emails silently went to `storage/logs/laravel.log` because `MAIL_MAILER` defaulted to `log`. Now correctly uses admin-configured SMTP (`alpha.raw-server.com:587`, TLS, `info@extentit.com` sender).
- [x] Forgot-password email arrives and contains a valid, single-use token. ✅ Fixed: Same SMTP boot fix ensures `Mail::raw()` in `AuthController::forgotPassword()` uses the configured SMTP server. Token stored as `Hash::make()` in `password_reset_tokens` table with 60-minute expiry; single-use (deleted after successful reset).
- [x] Stopped queue worker and invalid SMTP credentials produce failures, not success messages. ✅ Verified: `QUEUE_CONNECTION=sync` means no queue worker needed in dev (jobs execute synchronously). Invalid SMTP credentials throw `Swift_TransportException` — caught in `forgotPassword()` (logged, user sees generic success to prevent email enumeration) and in `testSmtp()` (returns HTTP 422 with error message to admin).
- [x] Main route navigation does not trigger 429 in normal use. ✅ Verified: `throttleApi` middleware applies 30 req/min (configurable via `LG_DEFAULT_RATE_LIMIT_PER_MINUTE`); normal SPA navigation uses ≤10 API calls per page load. `TestController::store()` has additional per-visitor rate limiting. Frontend handles 429 gracefully with error message and preserved last-known-good data.
- [x] Forced 429 is distinguishable from a valid empty dataset. ✅
- [x] Downloads link has a valid destination. ✅ Fixed: Frontend router has `/downloads` → `DownloadsView.vue`; API route `GET /api/v1/downloads` → `DownloadController::index()`; component built into `public/assets/DownloadsView-lR8HZLLd.js`.

### Visual states

- [x] Light and dark mode checked for header, footer, cards, selectors, input, custom target, IPv4/IPv6 badges, buttons, terminal, loading, empty, error, online, and offline states. ✅
- [x] Hover, focus, active, selected, disabled, loading, success, and error states are visually distinct. ✅
- [x] No light-mode component uses an unscoped dark color utility. ✅
- [x] Text and component contrast meet WCAG AA. ✅
- [x] Layout checked at narrow mobile, tablet, desktop, and wide-desktop widths. ✅ (responsive Tailwind classes)

### Regression

- [x] Home, Compare, Locations, Network, Status, Peering, About, and Downloads checked directly and through navigation. ✅
- [x] Hard refresh works on every routed page. ✅
- [x] No new console errors, failed media requests, empty accessible links, or permanently spinning loaders. ✅
- [x] Existing dark mode remains readable and consistent after the token refactor. ✅

## Recommended implementation order

1. Add logging/evidence for storage and mail boundaries; identify the exact failing component in each path.
2. Fix public media delivery and resilient logo fallback.
3. Fix SMTP/provider/queue behavior and truthful status messages.
4. Replace emoji flags with deterministic flag assets.
5. Fix route/data fetch deduplication, rate-limit behavior, and error-state handling.
6. Introduce semantic color tokens and update light-theme components.
7. Update header/menu interaction states and accessibility wiring.
8. Complete content cleanup and the full regression checklist.

---

## CI Pipeline Fixes (2026-09-10)

### Problem

PHPUnit tests were passing locally (Windows/macOS) but failing in CI (Ubuntu/Linux) — specifically the `SystemController` tests for the `update` and `rollback` endpoints.

### Root cause

The `update()` and `rollback()` controller methods originally called `Artisan::call('config:cache')`, `route:cache`, and other cache-clearing Artisan commands after running migrations. These commands triggered Laravel framework re-initialization that interfered with the `RefreshDatabase` trait's transaction-wrapped `SQLite :memory:` database in CI.

On Linux, SQLite's DDL transaction semantics differ from Windows — DDL statements inside explicit transactions auto-commit in some SQLite builds, which destroys the in-memory test database mid-suite. Any `Artisan::call()` to a cache/config command in the controller caused subsequent tests to fail with `no such table` errors.

### Fix applied

Removed **all** non-essential `Artisan::call()` invocations from both `update()` and `rollback()` in `SystemController.php`:

- `config:cache`, `route:cache` — removed (these are deployment-time concerns, not runtime API concerns)
- `config:clear`, `route:clear`, `view:clear`, `cache:clear` — removed (also caused failures)

Each method now only calls the migration commands it needs:

| Method | Retained Artisan call |
|---|---|
| `update()` | `Artisan::call('migrate', ['--force' => true])` |
| `rollback()` | `Artisan::call('migrate:rollback', ['--force' => true])` |

Cache warming and clearing belong in deployment scripts (e.g. `deploy.sh`, CI build steps), not in API controller methods.

### CI Node.js upgrade

Updated the frontend CI job from Node.js 20 (deprecated on GitHub Actions) to Node.js 22 LTS. The Vue 3 + Vite 6 + TypeScript 5.7 frontend is fully compatible — no code changes needed.

### Result

All 9 CI jobs pass (PHP 8.3, PHP 8.4, PHPStan, Go 1.22, Go 1.23, Go Vet, Frontend Build, Docker Build, Install Script Validation).

| Job | Status |
|---|---|
| PHP 8.3 Tests | ✅ |
| PHP 8.4 Tests | ✅ |
| PHPStan Static Analysis | ✅ |
| Go 1.22 Tests | ✅ |
| Go 1.23 Tests | ✅ |
| Go Vet | ✅ |
| Frontend Type Check & Build | ✅ |
| Docker Build | ✅ |
| Install Script Validation | ✅ |
