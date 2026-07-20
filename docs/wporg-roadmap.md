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
| D5 | Accessibility pass: focus traps on modals, TOC keyboard, form errors linked via `aria-describedby` | Directory reputation + real users | *(partly done — see 1.0.0 polish below; Smart Form `aria-describedby` still open)* |

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

## Done in 1.0.0 polish pass (richness + parity + a11y)

Correctness & parity
- [x] Fixed mis-encoded characters (mojibake) in Scroll Timeline presets + shared strings
- [x] Floating Dock: DST-aware IANA timezones via `Intl` (legacy UTC offsets still parse)
- [x] Scroll Progress + TOC: configurable content-container selector (FSE/block themes) + `hideIfShort` toggle exposed
- [x] Gutenberg ↔ Elementor parity: Hub `showExport`, Flow Chart `enableZoom`, Scroll Story `pinTop`, TOC selector/short-page — registered as real block attributes with editor controls

Accessibility
- [x] Case-Study modal: `role="dialog"` + `aria-modal`, focus trap, focus return to trigger, arrow-key gallery nav
- [x] Scroll Story dots: Arrow / Home / End keyboard navigation
- [x] Flow Chart nodes: client-side link allowlist before assigning `href`

Deferred (needs care)
- [ ] Bento InnerBlock cell `syncTimeline` attribute — requires a block `deprecated` entry to avoid invalidating existing saved cells
- [ ] Smart Form field errors linked via `aria-describedby` (remainder of D5)

## Done in 1.0.0 premium pass

- [x] 3D Tilt Card flip / back-face (hover + click, keyboard, back CTA & styling) — Elementor + GB + JS + CSS
- [x] Flow Chart edge labels + swimlane bands (live DOM + native SVG export)
- [x] Smart Form branded HTML email + CRM/ESP: built-in Mailchimp subscribe (site API key + per-form audience) and `rawnaq_smart_form_submission` action hook
- [x] Case-Study server-filtered AJAX pagination for CPT source (secure transient render-context; NDA flags never client-trusted)
- [x] Scroll Story rich chapters: rich-text body (`wp_kses_post`), per-chapter video with poster, #anchor deep-linking + hash sync
- [x] JSON-LD schema pack `includes/rawnaq-schema.php`: `rawnaq_schema_case_studies` (CreativeWork ItemList, wired to CPT grid), `rawnaq_schema_timeline` (wired to query timelines), `rawnaq_schema_reviews` (Review/AggregateRating helper). All behind `rawnaq_enable_schema`.

### Premium follow-ups
- [x] Case-Study AJAX hardening: server `perPage` aligned (12), no page-1 hidden-card conflict, `applyFilters` skipped in AJAX mode, empty-state message + loading states
- [x] Bento testimonial cells auto-emit Review/AggregateRating schema (Elementor + Gutenberg)
- [x] HubSpot CRM (Forms API v3: portal ID setting + per-form GUID) alongside Mailchimp + webhook + `rawnaq_smart_form_submission` hook
- [x] Flow Chart lane-aware layout for process mode (cross-axis banding by lane)

### Done in 1.0.0 submission-prep pass
- [x] GDPR: Smart Form personal-data exporter + eraser (Tools → Export/Erase Personal Data) matched by email
- [x] readme Privacy section + data-retention FAQ
- [x] "Settings" action link on the Plugins list row
- [x] readme Screenshots section with captions (upload PNGs to SVN /assets post-approval)
- [x] Author/Contributors/URI reconciled to github.com/itsmanzur/rawnaq
- [x] phpcs (WPCS) pass: substantive sniffs clean (security/globals/base64 annotated); remaining are cosmetic tabs/spacing

### Still open (needs live QA)
- [ ] Case-Study AJAX: end-to-end browser test of modal/discuss/masonry on appended pages; optional filter deep-links
- [ ] Flow Chart lane layout: verify same-lane/same-stage collision handling on dense process diagrams

### Larger follow-ups (deferred — not required for submission)
- [ ] Minify CSS/JS to `.min` + SCRIPT_DEBUG-aware enqueue (needs a build/minify step; partial would break asset paths)
- [ ] Refactor duplicated Elementor/Gutenberg render logic into shared helpers
- [ ] Migrate block registration to `block.json` (WP core direction; not required today)

