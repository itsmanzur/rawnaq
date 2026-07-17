=== Rawnaq ===
Contributors: rawnaq
Tags: elementor, gutenberg, timeline, diagram, performance
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 1.10.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A premium, performance-optimized suite of widgets and blocks for Elementor and Gutenberg.

== Description ==

Rawnaq is a lightweight, speed-first modular library. It introduces highly interactive, beautiful, and customizable elements without bloating your WordPress site.

Key highlights:
* **Zero jQuery Dependency:** The frontend is powered by vanilla JavaScript for near-instant rendering.
* **On-Demand Assets:** CSS/JS files are only loaded on pages where the widgets/blocks actually exist.
* **Modular Codebase:** Fully ready for developers to extend with new components.

= Included Modules =
1. **Hub Diagram:** An interactive and fully customizable Hub & Spoke workflow diagram.
2. **3D Tilt Card:** Interactive tilt cards for Gutenberg and Elementor.
3. **Scroll Sync Timeline:** Scroll-driven timeline layouts.
4. **Floating Dock Menu:** macOS-style floating dock navigation.
5. **Flow Chart:** Org trees and process flows with animated connectors.
6. **Scroll Progress + TOC:** Reading progress indicator and smart table of contents.

== Installation ==

1. Upload the entire `rawnaq` folder to the `/wp-content/plugins/` directory, or upload the ZIP file via WordPress Admin.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Drop the widgets into Elementor or search for them in the Gutenberg block inserter.

== Changelog ==

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
