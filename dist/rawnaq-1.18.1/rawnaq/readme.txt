=== Rawnaq ===
Contributors: rawnaq
Tags: elementor, gutenberg, timeline, diagram, performance
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.18.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A performance-optimized suite of widgets and blocks for Elementor and Gutenberg.

== Description ==

Rawnaq is a lightweight, speed-first modular library. It introduces highly interactive, beautiful, and customizable elements without bloating your WordPress site.

Key highlights:
* **Zero jQuery Dependency:** The frontend is powered by vanilla JavaScript for near-instant rendering.
* **On-Demand Assets:** CSS/JS files are only loaded on pages where the widgets/blocks actually exist.
* **Modular Codebase:** Fully ready for developers to extend with new components.

= Included Modules =
1. **Hub Diagram:** Interactive Hub & Spoke workflow diagram (PNG/SVG export).
2. **3D Tilt Card:** Interactive tilt cards (kept ultra-light).
3. **Scroll Sync Timeline:** CSS scroll-driven timeline with JS fallback.
4. **Floating Dock Menu:** macOS dock + WhatsApp Contact Mode.
5. **Flow Chart:** Org/process/freeform + WP Users org + PNG/SVG export.
6. **Scroll Progress + TOC:** Reading progress + smart TOC.
7. **Bento Grid:** Marketing bento with Elementor resize + Gutenberg InnerBlocks.
8. **Scroll Story Chapters:** Scrollytelling with pinned media.
9. **Smart Form:** Lead form with email + WhatsApp redirect, layouts, uploads, multi-step.
10. **Case-Study Grid:** CPT/manual portfolio, multi-filter, gallery modal, link-out, load more.

= External services =

Rawnaq may call the following third-party services when you enable related features. No data is sent unless you configure the feature.

* **Google Fonts** — Optional remote font CSS for some UI surfaces. [Google Fonts](https://fonts.google.com/) · [Privacy Policy](https://policies.google.com/privacy)
* **Google reCAPTCHA v3** — Optional spam protection on Smart Form. Site/secret keys are set in plugin settings. Form tokens are verified via Google’s siteverify API. [reCAPTCHA](https://www.google.com/recaptcha/) · [Privacy Policy](https://policies.google.com/privacy)
* **WhatsApp (wa.me)** — Optional. Opens a WhatsApp chat URL with a prefilled message when Floating Dock or Smart Form WhatsApp delivery is enabled. [WhatsApp](https://www.whatsapp.com/) · [Privacy Policy](https://www.whatsapp.com/legal/privacy-policy)
* **Custom webhook / Slack incoming URL** — Optional. Smart Form can POST submission JSON to a URL you provide (e.g. Slack Incoming Webhooks). Only the endpoint you configure is contacted.

= Third-party libraries =

* **QRCode.js** (davidshimjs) — MIT-compatible QR rendering for Floating Dock WhatsApp mode. Human-readable source: `assets/js/qrcode.js` (also minified as `qrcode.min.js`). Upstream: https://github.com/davidshimjs/qrcodejs

== Installation ==

1. Upload the entire `rawnaq` folder to the `/wp-content/plugins/` directory, or upload the ZIP file via WordPress Admin.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Drop the widgets into Elementor or search for them in the Gutenberg block inserter.

== Frequently Asked Questions ==

= Does Rawnaq require Elementor? =

No. Modules work with Elementor and/or Gutenberg. Enable only the modules you need in Rawnaq settings.

= Does Smart Form send data to Rawnaq servers? =

No. Email uses WordPress `wp_mail`. Optional webhooks go only to the URL you configure. Optional reCAPTCHA talks to Google. WhatsApp opens wa.me in the visitor’s browser.

= Where is the QR code library source? =

See `assets/js/qrcode.js` in the plugin (unminified). Minified build: `assets/js/qrcode.min.js`.

== Changelog ==

= 1.18.1 =
* Fix: Top-level Rawnaq menu opens Dashboard (not Case Studies CPT list).
* Plugin Check: strip UTF-8 BOM from helpers; remove discouraged load_plugin_textdomain; fix uninstall suppress_filters / global prefixes; silence Smart Form upload nonce false-positives.

= 1.18.0 =
* Security: Hub Diagram center title/subtitle use text nodes (no innerHTML XSS).
* Security: Smart Form uploads use an explicit MIME allowlist; webhooks require HTTPS and block private/loopback targets (SSRF guard).
* Security: Dock click tracking rate-limited per IP; Smart Form admin mark-read/export require edit capabilities.
* Reliability: Smart Form trusted config also stored in `rawnaq_sf_configs` option (survives transient flush).

= 1.17.5 =
* wp.org Phase A: `.distignore` for release packaging; `load_plugin_textdomain`; admin dashboard UI strings translated; `languages/rawnaq.pot`; fuller uninstall cleanup.

= 1.17.4 =
* Security: Smart Form delivery settings (email, webhook, WhatsApp) are stored and validated server-side — client POST config is no longer trusted.
* Compliance: full GPLv2 LICENSE.txt; ship QRCode.js unminified source; plugin header Requires at least / Requires PHP; readme service disclosure.

= 1.17.3 =
* Flow Chart: PNG/SVG export rebuilt as a full native SVG (nodes + connectors), fixing blank/partial downloads from foreignObject.

= 1.17.2 =
* Flow Chart: more avatar/icon style controls (bg, color, border, fit, shadow); PNG/SVG export captures the full diagram (not a clipped viewport).

= 1.17.1 =
* Flow Chart: per-node Image upload alongside Icon; avatar shape/size so photos crop cleanly without breaking the node layout. WP Users org source uses avatars.

= 1.17.0 =
* Case-Study bridge: “Discuss this project” prefills Smart Form or opens Dock WhatsApp; Scroll Story/Timeline chapters highlight matching Case-Study cards via project ID/slug.

= 1.16.0 =
* Case-Study Grid: CPT + query source, multi-image modal gallery, link-out click modes, sector/year/service filters, Gutenberg InnerBlocks cards, client-side load more.

= 1.15.0 =
* New: Case-Study Grid (Elementor + Gutenberg) — structured projects, sector filter, bento/uniform/masonry layouts, NDA-safe budget/client hide, detail modal.

= 1.14.0 =
* Smart Form: shared site WhatsApp number with Floating Dock; {pageTitle}/{url} templates; style depth; layout presets; admin CSV + unread badge; file uploads; multi-step; conditionals; rating/number/url/hidden; optional reCAPTCHA v3; webhook/Slack.

= 1.13.0 =
* New: Smart Form (Elementor + Gutenberg) — field repeater, email via wp_mail, WhatsApp wa.me redirect delivery, honeypot + time-trap, consent option, admin submission log.

= 1.12.0 =
* Flow Chart + Hub Diagram: proposal-ready PNG/SVG export toolbar (toggleable).
* Bento Grid (Gutenberg): InnerBlocks via `rawnaq/bento-cell` — nest core blocks inside cells; cellsJson remains as fallback/migration seed.

= 1.11.0 =
* New: Scroll Story Chapters (Elementor + Gutenberg) — pinned media scrollytelling, progress dots, reduced-motion fallback.
* Flow Chart: real WordPress Users org-chart data source (meta `rawnaq_reports_to`).
* Scroll Progress + TOC: Sync Timeline ID (“Chapter …”), ring size control.
* Depth polish from 1.10.x: Timeline presets, Dock offline lead form + analytics, Bento Elementor resize handles.

= 1.10.1 =
* Trust & parity: remove Flow Chart Gutenberg fake data-source controls; Elementor TOC search filter parity; Hub/Flow Elementor Icons pickers; docs/readme list all seven modules and CSS-first Timeline positioning.
* Scroll Timeline: agency presets (Company Story / Changelog / Case Study), Gutenberg CSS-engine badge parity, JS fallback deactivates items on scroll-out, admin link to CSS vs JS benchmark demo.

= 1.10.0 =
* Flow Chart: freeform X/Y layout, node shapes (rect/circle/hex), direction + RTL flip, zoom/pan, lazy mount for 20+ nodes, and Gutenberg parent Select with DFS cycle guards.
* Scroll Progress + TOC: optional Floating Dock attach mode (Contents trigger in dock; FAB fallback when no dock).

= 1.9.0 =
* Scroll Timeline: WPML/Polylang readiness (wpml-config.xml + string registration), AJAX Load More for query mode, and an illustrative CSS-vs-JS benchmark demo (assets/demo + docs).

= 1.8.0 =
* Scroll Timeline: dynamic posts/CPT query source, Named Timeline Sync with Bento cells, and light WPML/i18n readiness (textdomain + rawnaq_timeline_steps filter).

= 1.7.0 =
* Scroll Sync Timeline: CSS scroll-driven animations with JS fallback, horizontal layout, RTL mirroring, step video embeds, line thickness, and Load More / initial visible steps (Elementor + Gutenberg).

= 1.6.10 =
* Bento Grid: per-cell content align (top / center / bottom).

= 1.6.9 =
* Bento Grid: separate column gap and row gap controls.

= 1.6.8 =
* Bento Grid: Video cells support YouTube and Vimeo embeds (plus mp4/webm).

= 1.6.7 =
* Bento Grid: new Testimonial cell type (quote, author, role, avatar, stars).

= 1.6.6 =
* Bento Grid: per-cell tag background/text color overrides.

= 1.6.5 =
* Bento Grid: per-cell CTA button (text + link) with global button colors.

= 1.6.4 =
* Bento Grid: image/video overlay opacity control.

= 1.6.3 =
* Bento Grid: per-cell tablet/mobile column/row span and order overrides.

= 1.6.2 =
* Bento Grid: Apply Preset replaces cells (spans + content) in Elementor and Gutenberg.

= 1.5.0 =
* New: Flow Chart widget/block (Org + Process modes, connectors, mobile list).
* New: Scroll Progress + TOC widget/block (bar/ring, auto headings, sticky/floating TOC).

= 1.4.1 =
* Elements Manager redesign.
* Floating Dock, Timeline, and Tilt Card professional upgrades.
* Plugin Check compatibility fixes.

= 1.0.4 =
* Rebranded plugin from Manzur Elements to Rawnaq.
* Responsive Hub Diagram mobile timeline.
* Gutenberg editor fixes.

= 1.0.0 =
* Initial Release. Includes Performance Optimized Hub Diagram Widget for Elementor and Gutenberg.
