=== Rawnaq – Elementor Widgets, Gutenberg Blocks & Divi Modules ===
Contributors: itsmanzur
Tags: elementor addons, gutenberg blocks, divi modules, contact form, timeline
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Elementor widgets, Gutenberg blocks and Divi modules: smart lead form, timeline, diagrams, TOC, bento grid and more. Lightweight, no jQuery.

== Description ==

Rawnaq is a modular library of interactive widgets and blocks designed for clean performance and fast page loads. Built for site creators, agencies, and marketers, it gives you advanced layout and conversion tools across Elementor, Gutenberg, and Divi Builder without bloating your site.

= Why Rawnaq? =

* **Lightweight and vanilla JS** — Powered entirely by modern JavaScript with zero jQuery runtime dependency on the frontend.
* **On-demand asset loading** — CSS and JS files load only on the pages where a module is actually placed.
* **One module across three builders** — Every feature works identically in Elementor, Gutenberg, and Divi Builder without switching tools.
* **Privacy and GDPR friendly** — Form submissions stay privately in your database with full support for WordPress personal data export and erase tools.

= 10 modules, one plugin =

1. **Smart Form** – Multi-step lead forms with file upload, signature, WhatsApp redirect, live price estimation, and CRM/webhook delivery.
2. **Hub Diagram** – Interactive radial workflow diagrams that illustrate core processes with one-click PNG and SVG export.
3. **Flow Chart** – Structured process trees, decision maps, and organizational charts with customizable node connections.
4. **Scroll Sync Timeline** – Engaging horizontal and vertical milestone stories that track progress as visitors scroll.
5. **Floating Dock** – macOS-style floating menu bar for quick site navigation with an optional WhatsApp QR contact drawer.
6. **Bento Grid** – Modern responsive marketing grids that combine feature highlights, metrics, and media in clean layouts.
7. **3D Tilt Card** – Interactive showcase cards with smooth parallax depth, light glare reflection, and flip interactions.
8. **Scroll Story** – Immersive scrollytelling presentations with sticky media viewports and narrative text chapters.
9. **Scroll Progress & TOC** – Live reading progress bar and auto-generated table of contents that improve long-form content navigation.
10. **Case-Study Grid** – Filterable portfolio and project showcases with responsive categories, lightbox galleries, and link-outs.

= Works with =

Elementor, Gutenberg (block editor), and Divi Builder.

= External services =

Rawnaq may call the following third-party services when you enable related features. No data is sent unless you configure the feature.

* **Google reCAPTCHA v3** — Optional spam protection on Smart Form. Site/secret keys are set in plugin settings. Form tokens are verified via Google’s siteverify API. [reCAPTCHA](https://www.google.com/recaptcha/) · [Terms](https://policies.google.com/terms) · [Privacy Policy](https://policies.google.com/privacy)
* **WhatsApp (wa.me)** — Optional. Opens a WhatsApp chat URL with a prefilled message when Floating Dock or Smart Form WhatsApp delivery is enabled. [WhatsApp](https://www.whatsapp.com/) · [Terms](https://www.whatsapp.com/legal/terms-of-service) · [Privacy Policy](https://www.whatsapp.com/legal/privacy-policy)
* **Mailchimp** — Optional. When Smart Form's CRM delivery is set to Mailchimp, the submitted email address (and any mapped merge fields) is sent to Mailchimp's API to subscribe the contact to the audience/list you configure. Requires your own Mailchimp API key. [Mailchimp](https://mailchimp.com/) · [Terms](https://mailchimp.com/legal/terms/) · [Privacy Policy](https://mailchimp.com/legal/privacy/)
* **HubSpot** — Optional. When Smart Form's CRM delivery is set to HubSpot, the submitted form field values are sent to HubSpot's Forms API using the portal ID and form GUID you configure. [HubSpot](https://www.hubspot.com/) · [Terms](https://legal.hubspot.com/terms-of-service) · [Privacy Policy](https://legal.hubspot.com/privacy-policy)
* **Custom webhook / Slack incoming URL** — Optional. Smart Form can POST submission JSON to a URL you provide (e.g. Slack Incoming Webhooks). Only the endpoint you configure is contacted.

= Third-party libraries =

* **QRCode.js** (davidshimjs) — MIT-compatible QR rendering for Floating Dock WhatsApp mode. Human-readable source: `assets/js/qrcode.js` (also minified as `qrcode.min.js`). Upstream: https://github.com/davidshimjs/qrcodejs

== Installation ==

1. Upload the entire `rawnaq` folder to the `/wp-content/plugins/` directory, or upload the ZIP file via WordPress Admin.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Drop the widgets into Elementor, insert blocks in Gutenberg, or add modules directly inside the Divi Visual Builder.

== Frequently Asked Questions ==

= Can I build a contact form with Rawnaq? =

Yes. The included Smart Form module lets you build single-step or multi-step forms with text, email, phone, file uploads, digital signatures, and dynamic cost estimation. Submissions can be delivered via email (`wp_mail`), WhatsApp redirect, webhook, or CRM (Mailchimp and HubSpot), with built-in spam protection including signed HMAC timestamp tokens, rate limiting, and optional Google reCAPTCHA v3.

= Is Rawnaq lightweight / does it slow down my site? =

Rawnaq is built for speed. All frontend modules run on vanilla JavaScript with zero jQuery dependency. CSS and JavaScript assets are loaded conditionally only on pages where that specific module appears, and all assets are fully minified.

= Does Rawnaq work with the WordPress block editor without Elementor? =

Yes. Every module has native Gutenberg block support with live settings in the block sidebar. You do not need to install Elementor or any other page builder to use Rawnaq.

= How do I export or delete form submissions? =

Form submissions saved with "Log submissions" enabled are stored privately in your WordPress database under **Rawnaq → Form Submissions**, where you can filter, view details, and export to CSV. Rawnaq also integrates with WordPress core **Tools → Export Personal Data** and **Tools → Erase Personal Data**, allowing you to export or permanently erase all submissions associated with an email address.

= Does Rawnaq require Elementor? =

No. Modules work seamlessly with Elementor, Gutenberg, and Divi Builder. Enable only the modules you need in Rawnaq settings.

= Does Rawnaq support Divi Builder? =

Yes! All 10 free modules and extensions include dedicated Divi Visual Builder modules with real-time controls.

= Does Smart Form send data to Rawnaq servers? =

No. Email uses WordPress `wp_mail`. Optional webhooks go only to the URL you configure. Optional reCAPTCHA talks to Google. WhatsApp opens wa.me in the visitor’s browser.

= Where is the QR code library source? =

See `assets/js/qrcode.js` in the plugin (unminified). Minified build: `assets/js/qrcode.min.js`.

= Does the table of contents work without JavaScript? =

Yes. For standard WordPress post and page content, Rawnaq renders the complete table of contents outline server-side with native HTML anchor links. Search engine crawlers can index heading structures immediately, and visitors without JavaScript can navigate sections with zero layout shift.

= Can I show progress for just the article? =

Yes. In Elementor, Gutenberg, or Divi Builder, configure the "Progress based on" setting to "Article/content area" or specify a "Custom CSS selector". The progress bar or circular ring will track reading progress strictly within that target boundary rather than the entire page.

== Privacy ==

Rawnaq does not phone home. Any personal data stays in your WordPress site or goes only to services you explicitly configure.

* **Smart Form submissions** — When "Log submissions" is enabled, form entries (which may include name, email, phone, message, and uploaded files) are stored as private `rawnaq_sf_entry` posts in your database. They are retained until you delete them under Rawnaq → Form Submissions.
* **Smart Form rate limiting** — Stores a salted hash of the visitor IP in short-lived transients (minutes to one hour); the raw IP is never stored.
* **Auto-responder** — If Auto-responder is enabled the submitter's email address is used to send them a confirmation email via wp_mail.
* **Data export / erase** — Rawnaq registers exporter and eraser callbacks with WordPress core, so entries are included in **Tools → Export Personal Data** and removed via **Tools → Erase Personal Data** when requested by email.
* **Delivery services** — Email uses your site's `wp_mail`. Optional integrations only contact the endpoints you configure: WhatsApp (wa.me link in the visitor's browser), Google reCAPTCHA (spam scoring), a webhook/Slack URL, or a CRM/ESP (Mailchimp / HubSpot) when you enable and configure it.
* **Floating Dock analytics** — Optional click counts are stored locally in the `rawnaq_dock_clicks` option; no personal data is recorded.

== Credits ==

Rawnaq is developed and maintained by itsmanzur. All code, design, and assets in this plugin are original work created specifically for Rawnaq.

Source code: https://github.com/itsmanzur/rawnaq

== Screenshots ==

1. 3D Tilt Card – Parallax depth, radial glare reflection, and 3D flip card interaction.
2. Bento Grid – Apple-style content and metric masonry grid with responsive spans.
3. Smart Form – Multi-step lead form with live cost estimator, file uploads, and signature pad.
4. Scroll Sync Timeline – Scroll-driven timeline with active step tracking and milestones.
5. Floating Dock – macOS-style floating dock menu with WhatsApp quick-contact mode and QR code.
6. Hub Diagram – Central hub with connected satellite nodes and one-click PNG/SVG export.
7. Flow Chart – Step-by-step workflow diagrams, decision trees, and organizational charts.
8. Scroll Story – Scrollytelling presentations with sticky media and narrative chapters.
9. Scroll Progress & TOC – Reading progress indicator and auto-generated sticky table of contents.
10. Case-Study Grid – Portfolio showcase grid with category filters and interactive modal lightbox.

== Changelog ==

= 1.5.0 =
* Security: Replaced transient/nonce AJAX context with a stateless HMAC-SHA256 signed token (`data-cs-ctx`) using `wp_salt('auth')`, ensuring tamper-proof display filters and privacy controls without breaking full-page caching.
* Security: Enforced server-side `querySector` constraints in AJAX queries to prevent unauthorized sector enumeration.
* Security: Excluded password-protected case studies from queries and automatically redacted excerpts and details.
* Security: Sanitized custom CSS color properties against invalid characters, delimiter breaking, and script injection.
* Fixed: Bento grid layout overflow at narrow viewports resolved with explicit column and row span mapping per container breakpoint.
* Fixed: Eliminated duplicate dialog roles on modal wrappers and ensured full screen-reader compliance with dialog semantics on the inner dialog.
* Fixed: Corrected random order pagination anomalies by enforcing deterministic date order fallback on paged queries.
* Fixed: Restored modal scroll lock CSS with overflow hidden on html and body and dynamic scrollbar width compensation (`--rawnaq-cs-sbw`).
* Fix: Random ordering is now treated as newest-first when Load more uses AJAX, so cards no longer repeat or go missing between pages.
* Improved: Admin dashboard redesign.
* Improved: Upgraded Case-Study Grid to modern container queries (`@container rawnaqcs`) with container-type isolated on a stage wrapper to prevent modal containment traps.
* Improved: Enhanced keyboard and screen reader accessibility (WCAG 2.2 AA) by replacing clickable articles with real card trigger buttons or links inside headings.
* Improved: Touch swipe gestures on modal gallery slider via pointer events (40px threshold) with polite live slide announcements and eager loading for initial images.
* Improved: Client-side filtering automatically reveals matching hidden projects up to chunk size so filtered results never look empty.
* Improved: Replaced card emoji meta icons with clean, accessible inline SVGs.
* Improved: Internationalized all frontend messages and status strings via `rawnaqCaseStudy.i18n` with support for live counts and empty states.
* Improved: Comprehensive `prefers-reduced-motion` support across card hover, animations, modal backdrops, and gallery sliders.
* Added: Introduced `.rawnaq-cs-stage` container element providing isolated inline-size containment.
* Added: Polite live status region (`.rawnaq-cs-status`) announcing visible project counts and empty search states.
* Added: Inline AJAX error alert container with a localized Retry button.
* Added: Full builder parity across Gutenberg and Divi with controls for orderby, order, query sector, load chunk, and text color.

= 1.4.0 =
* Security: Sanitized Floating Dock and mobile action trigger tooltip and label construction against potential HTML/script injection from custom TOC titles.
* Security: Rebuilt mobile floating action buttons using standard DOM creation and strict attribute assignment, avoiding HTML string concatenation with localized text.
* Fixed: Excluded TOC title and internal module headings from auto-generated table of contents lists when the widget is placed within content scopes.
* Fixed: Wrapped scope selector resolution in a robust fallback pipeline (entry-content, article, post-content, main, body) with graceful warning to prevent DOMException errors on malformed CSS selectors.
* Fixed: Resolved sticky table of contents and top progress bar vertical alignment overlap when the WordPress Admin Bar is present on desktop and mobile viewports.
* Fixed: Resolved entity double-encoding in heading titles so ampersands, quotes, and mathematical operators render cleanly in TOC links without visible `&amp;`.
* Fixed: Existing heading ID detection regex now strictly targets `id` attributes and avoids false matches against `data-id` or `aria-id`.
* Fixed: Registered `the_content` heading filter at priority 8 before `do_blocks` and added self-sufficient static cache in markup renderer so server-side TOC reliably outputs before blocks or inside template parts.
* Fixed: Restricted server-side heading extraction and anchor injection to the main queried post in the loop, preventing secondary query loops from altering heading lists or post content.
* Improved: Upgraded scrollspy active section tracking from IntersectionObserver to a resilient scroll-position calculation that accounts for admin bar offsets, bottom-of-page thresholds, and instant jump navigation.
* Improved: Table of contents list accessibility — moved glow tracker indicator out of unordered list markup into a wrapper container for strict HTML5/WCAG compliance.
* Improved: Keyboard arrow-key navigation now dynamically traverses only visible TOC links, respecting search filters and collapsed sub-sections.
* Improved: Clicking a TOC link moves focus directly to the destination heading for seamless screen reader and keyboard accessibility (WCAG 2.2).
* Improved: Set heading scrollMarginTop dynamically to align with scroll offsets and re-scrolls to initial URL hashes after full window load.
* Improved: Added fallback clipboard copying via hidden element when navigator.clipboard API is unavailable, with screen reader live region announcements.
* Improved: Cleaned up redundant ARIA landmark roles and link tabindex attributes across Elementor, Gutenberg, and Divi templates.
* Improved: Internationalization (i18n) overhaul for Scroll Progress + TOC with translatable strings via `wp_localize_script` (`rawnaqSPT.i18n`) and `languages/rawnaq.pot`.
* Improved: Replaced UI emojis (reading book, chain link, up arrow, menu icon) with accessible inline SVG icons (`aria-hidden="true"`).
* Improved: Multilingual heading slug generation with Unicode property escapes (`\p{L}\p{N}\p{M}`) for Bengali, Arabic, and other non-Latin scripts, with automatic document-wide ID uniqueness verification.
* Improved: Unicode-safe slugification for manual TOC anchors preserving non-Latin scripts without percent-encoding.
* Improved: URL hash synchronization defaults to off across all builders; when enabled, updates the URL hash with 300ms debounce on scroll stop and instant updates on link clicks.
* Improved: Idempotent lifecycle management, Elementor scoped widget initialization, and single document-level click listener for floating action buttons (FABs).
* Improved: Single-instance guard preventing duplicate page-level progress bars or rings, with informative console logging in debug mode.
* Improved: Floating Dock integration using `MutationObserver` with a 5-second graceful fallback instead of hardcoded timeouts.
* Added: Server-side rendering (SSR) for Table of Contents with automatic heading anchor ID injection on `the_content` (priority 8), improving SEO and eliminating Cumulative Layout Shift (CLS). Filterable via `rawnaq_spt_ssr_enabled`.
* Added: "Progress based on" setting with options for Whole page, Article/content area, or Custom CSS selector (`progressTarget` and `progressSelector`) with accurate bounded scroll percentage math.
* Added: Unified single markup renderer (`rawnaq_spt_markup`) and configuration sanitizer (`rawnaq_spt_cfg_sanitize`) providing full parity across Elementor, Gutenberg, and Divi Builder.
* Added: Gutenberg block editor InspectorControls for progress target, progress selector, click-to-top, collapsible TOC, URL hash sync, and section reading time badges.
* Added: Divi Builder parity controls for scroll progress target, selector, TOC source, manual items, heading levels, collapsed subheadings, scroll offset, URL hash sync, floating dock attachment, and timeline sync.
* Added: Configurable reading speed in words per minute (WPM, default 200, filter `rawnaq_spt_wpm`) with automatic CJK character support (500 characters per minute) and exclusion of comments, scripts, styles, and navigation from word counts.
* Added: Gutenberg block alignment (`align: ['wide', 'full']`) and HTML anchor (`anchor: true`) block supports.

= 1.3.0 =
* Security: Implemented signed HMAC-SHA256 timestamp tokens (rawnaq_ts) with 7-day validity to prevent replay attacks and bot flooding on cached pages.
* Security: Added per-IP rate limiting (5 submissions / 10 min per form, 30 / hour site-wide) with hashed transient keys, returning HTTP 429 when exceeded.
* Security: Hardened file uploads by storing files in a protected, non-executable folder (uploads/rawnaq-forms/) with access denials, random 12-char filename prefixes, and authenticated admin download streaming.
* Security: Blocked SVG, HTML, PHP, executable, and script MIME types unconditionally; enforced server-side max size and accept restrictions.
* Security: Canvas signatures are now securely validated and saved as PNG files to the protected uploads directory rather than stored as raw base64 text in database or email notifications.
* Security: Added HMAC signature header (X-Rawnaq-Signature) with configurable secret for outgoing webhooks, alongside SSRF private IP protections.
* Added: Submitter auto-responder email with customizable subject, sender name, plain/branded HTML templates, and personalized tokens ({name}, {first_name}, {last_name}, {email}, {site_title}).
* Added: Submissions admin manager overhaul featuring filtering by form, read/unread status, and date range; search across all field values; and private admin notes.
* Added: Dedicated 5-section submission detail screen (Fields, Files & Signatures, Delivery Log, UTM & Campaign Attribution, Consent & Privacy Record) with quick Mark Read / Mark Unread toggle.
* Added: Filtered CSV export with full spreadsheet formula injection sanitization.
* Added: Field-level help text across Gutenberg, Elementor, and Divi, seamlessly connected to input controls via aria-describedby.
* Added: Single source of truth option schema (rawnaq_smart_form_option_schema) providing unified option names and defaults across all builders.
* Added: Preset picker with instant "Apply Preset" action and visual field reordering (Up / Down) in the Block Editor (Gutenberg).
* Added: Divi visual warning banner when custom JSON syntax is invalid.
* Added: Admin settings connection test buttons for Mailchimp, test emails, and test webhooks.
* Improved: Upgraded form accessibility to WCAG 2.2 AA with aria-invalid, aria-describedby, fieldset/legend groupings, keyboard arrow-key navigation for star ratings, and accessible signature typing alternative.
* Improved: Multi-step forms now utilize an ordered progress list (<ol>) with aria-current="step" and live screen reader step announcements.
* Improved: Nonce refresh public endpoint (rawnaq_sf_token) with automatic single-retry mechanism for full-page cached pages.
* Improved: Contact resolver intelligently maps first and last names, email addresses, and phone numbers for Reply-To headers, Mailchimp merge tags, HubSpot context, and entry titles.
* Improved: Mailchimp integration now strictly requires explicit opt-in consent and supports double opt-in (pending) status.
* Improved: WordPress core Privacy Tools integration: personal data export and eraser now query indexed email meta (_rawnaq_sf_email).
* Improved: Full support for prefers-reduced-motion in animations, transitions, and celebration confetti.
* Improved: plugin title, description and readme for clearer discovery.
* Fixed: 60-second duplicate submission guard prevents multiple duplicate emails and database entries when visitors double-click submit.
* Fixed: Prevented orphan uploads by safely unlinking all temporary files on early validation failure, duplicate guard, or fatal delivery errors.
* Fixed: Form attachment downloads now include RFC 6266 filename* UTF-8 encoding alongside an ASCII fallback header for cross-browser compatibility.
* Fixed: Entries menu unread count badge now strictly checks the rawnaq_smart_form_can_manage_entries capability helper.
* Fixed: Hidden legacy WhatsApp toggle in Elementor to prevent accidental or unwanted WhatsApp redirects.
* Fixed: Eliminated redundant CSS rules and improved color contrast ratios across all themes to meet WCAG AA standards.
* Fixed: legacy upload migration no longer flags files already stored in the protected folder.
* Fixed: Preserved uploaded files and confirmed submission when lead is saved as fallback entry despite email delivery failure.
* Fixed: Cleared duplicate submission transient when both lead logging and email delivery fail to allow immediate resubmission.
* Improved: Cleaned up unused localized i18n strings in Smart Form frontend script registration.
* Security: Rate limiter now tracks repeated validation failures with a filterable threshold (rawnaq_smart_form_rate_limit_fail_max) to mitigate brute-force attempts without locking out normal users.
* Improved: Added automatic background migration of legacy uploads into the protected folder with batched processing and dismissible admin notice.

= 1.2.0 =
* Floating Dock: DST-aware IANA business timezones (legacy fixed UTC offsets still supported).
* Editor parity: Gutenberg now exposes Hub Diagram export toggle, Flow Chart zoom/pan toggle, Scroll Story pin offset, and Scroll Progress content-selector + hide-on-short-page controls to match Elementor.
* Accessibility: Case-Study modal focus trap, focus return, and aria-modal; arrow-key gallery navigation; Scroll Story progress dots support arrow/Home/End keys; safer link handling on Flow Chart nodes.
* Polish: corrected mis-encoded characters in Scroll Timeline presets and shared strings.
* 3D Tilt Card: optional flip / back-face with hover or click trigger, back title/description/CTA, and back styling.
* Flow Chart: per-node connector (edge) labels and swimlane bands, included in PNG/SVG export.

= 1.1.0 =
* Added Divi Visual Builder support — all 10 free modules now include dedicated Divi modules.
* Updated plugin title for clarity.

= 1.0.0 =
* Initial public release on WordPress.org.
* Modular Elementor + Gutenberg library: Hub Diagram, 3D Tilt Card, Scroll Sync Timeline, Floating Dock (WhatsApp mode), Flow Chart, Scroll Progress + TOC, Bento Grid, Scroll Story Chapters, Smart Form, Case-Study Grid.
* On-demand assets, vanilla JS frontend, Elements Manager, WPML config, and documented optional third-party services (Fonts, reCAPTCHA, WhatsApp, webhooks).

== Upgrade Notice ==

= 1.5.0 =
Major performance, security, and accessibility release for Case-Study Grid: stateless signed tokens, container-aware responsive layouts, WCAG 2.2 accessibility, and touch swipe galleries. Please purge any page or object cache after updating so cached pages using legacy data-cs-nonce markup refresh to the new signed context token.

= 1.4.0 =
Features server-side rendered TOC (SSR) with zero layout shift, plus key fixes for TOC heading self-listing, resilient active-section scrollspy tracking, Unicode anchors, and WCAG accessibility.

= 1.3.0 =
Security and reliability update for Smart Form: protected uploads, rate limiting, signed tokens. Recommended for all sites.
