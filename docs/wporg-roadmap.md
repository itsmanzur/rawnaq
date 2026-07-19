# Rawnaq — post–wp.org improvement roadmap

Critical blockers for WordPress.org were addressed in **1.17.4**. This plan covers remaining compliance polish and product quality.

## Phase A — Ship-ready polish (before / right after first .org submit)

| # | Item | Why |
|---|------|-----|
| A1 | Exclude from release zip: `docs/`, `*.md` (except if needed), root `*-mockup.html`, `assets/demo/` (or keep demo only if documented) | Reviewers flag non-production files |
| A2 | Run [Plugin Check](https://wordpress.org/plugins/plugin-check/) + `WP_DEBUG` smoke test on all modules | Catch PHPCS / i18n / enqueue issues early |
| A3 | `load_plugin_textdomain( 'rawnaq', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' )` | Domain Path is declared; load it |
| A4 | Wrap admin dashboard hardcoded strings in `__()` / `esc_html__()` | Translate.wordpress.org readiness |
| A5 | Generate `languages/rawnaq.pot` | Helps translators |
| A6 | Complete `uninstall.php`: delete `rawnaq_dock_clicks`, CPT `rawnaq_sf_entry` (+ meta), case-study CPT/tax if owned by plugin, Smart Form config transients where practical | Guideline: leave no orphan data (or document what remains) |
| A7 | Confirm WordPress.org username matches `Contributors: rawnaq` | Upload account must own the slug |
| A8 | Screenshots (1–5) + `assets` banner/icon for plugin directory | Required for a complete listing |

## Phase B — Security & hardening (next release)

| # | Item | Why |
|---|------|-----|
| B1 | `hub-diagram.js`: escape `centerTitle` / `centerSubtitle` before `innerHTML` | Stored XSS risk from editor content |
| B2 | Smart Form uploads: explicit mime allowlist (don’t pass `mimes => null`) | Defense in depth |
| B3 | Rate-limit / capability for dock click AJAX writes | Option churn / DoS-ish abuse |
| B4 | Webhook URL allowlist: `https` only, block private IP ranges (SSRF) | User-configured URL still dangerous |
| B5 | Persist Smart Form config in post meta on save (not only transients) | Survives object-cache flush / long-lived pages |
| B6 | Explicit `current_user_can` on Smart Form admin mark-read / bulk export | Defense in depth |

## Phase C — Privacy & assets

| # | Item | Why |
|---|------|-----|
| C1 | Bundle admin/UI fonts locally (or make Google Fonts opt-in) | GDPR / EU hosting expectations |
| C2 | Document local dock analytics (`rawnaq_dock_clicks`) in readme Privacy note | Transparency |
| C3 | Prefer `SCRIPT_DEBUG` path for all minified first-party assets when added later | Guideline 4 habit |

## Phase D — Product quality

| # | Item | Why |
|---|------|-----|
| D1 | Gutenberg Smart Form: stable block `clientId`-based form id in save/render | Cleaner than attribute hash |
| D2 | Shared “site settings” panel for reCAPTCHA keys + default WA (if not already clear in UI) | Agency UX |
| D3 | E2E checklist doc: each widget in Elementor + Gutenberg, mobile, reduced-motion | Fewer regressions |
| D4 | Optional: PHPUnit / Playwright smoke for Smart Form submit + nonce path | CI confidence |
| D5 | Accessibility pass: focus traps on modals, TOC keyboard, form errors linked via `aria-describedby` | Directory reputation + real users |

## Phase E — Growth (after .org listing is live)

| # | Item | Why |
|---|------|-----|
| E1 | Support forum cadence + FAQ from real tickets | Guideline: support expectations |
| E2 | Keep “premium” / upsell out of free plugin; Pro as separate product if ever built | Trialware / upsell rules |
| E3 | Version cadence: security patches fast; features in minor bumps | Trust |

## Suggested order of work

1. **A1–A8** → submit / pass review  
2. **B1–B4** → 1.18 security polish  
3. **B5 + C1–C2** → privacy & durability  
4. **D** as capacity allows  
5. **E** once listed  

## Done in 1.17.4 (critical)

- [x] Full GPLv2 `LICENSE.txt`
- [x] Smart Form server-side trusted config (no client `cfg` for delivery)
- [x] `qrcode.js` source + `SCRIPT_DEBUG` switch
- [x] Header `Requires at least` / `Requires PHP`
- [x] readme: no “premium”, Requires PHP, external services, FAQ, library credit

## Done in 1.18.0 (Phase B)

- [x] Hub Diagram XSS: textContent for center title/subtitle
- [x] Smart Form MIME allowlist for uploads
- [x] Dock click rate-limit (60/hour/IP)
- [x] Webhook HTTPS + private IP / SSRF guards
- [x] Durable SF config option `rawnaq_sf_configs`
- [x] SF admin capability checks on mark-read / bulk export
- [x] Release zip: `dist/rawnaq-1.18.0.zip` via `php bin/build-release-zip.php`

