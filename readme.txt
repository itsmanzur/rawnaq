=== Rawnaq ===
Contributors: itsmanzur
Tags: elementor, gutenberg, timeline, diagram, performance
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
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

= 1.0.0 =
* Initial public release on WordPress.org.
* Modular Elementor + Gutenberg library: Hub Diagram, 3D Tilt Card, Scroll Sync Timeline, Floating Dock (WhatsApp mode), Flow Chart, Scroll Progress + TOC, Bento Grid, Scroll Story Chapters, Smart Form, Case-Study Grid.
* On-demand assets, vanilla JS frontend, Elements Manager, WPML config, and documented optional third-party services (Fonts, reCAPTCHA, WhatsApp, webhooks).
* Floating Dock: DST-aware IANA business timezones (legacy fixed UTC offsets still supported).
* Editor parity: Gutenberg now exposes Hub Diagram export toggle, Flow Chart zoom/pan toggle, Scroll Story pin offset, and Scroll Progress content-selector + hide-on-short-page controls to match Elementor.
* Accessibility: Case-Study modal focus trap, focus return, and aria-modal; arrow-key gallery navigation; Scroll Story progress dots support arrow/Home/End keys; safer link handling on Flow Chart nodes.
* Polish: corrected mis-encoded characters in Scroll Timeline presets and shared strings.
* 3D Tilt Card: optional flip / back-face with hover or click trigger, back title/description/CTA, and back styling.
* Flow Chart: per-node connector (edge) labels and swimlane bands, included in PNG/SVG export.
* Smart Form: branded HTML email receipts and CRM/ESP delivery — built-in Mailchimp subscribe plus a `rawnaq_smart_form_submission` hook for Zapier/HubSpot/custom.
* Case-Study Grid: server-filtered AJAX pagination for the CPT source (sector/year/service + paging on the server).
* Scroll Story: rich-text chapters, per-chapter video, and #anchor deep-linking with active-chapter hash sync.
* SEO: JSON-LD schema pack — CreativeWork ItemList for CPT case studies and ItemList for query-based timelines (filterable; Review/AggregateRating helper included).
