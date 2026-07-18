# Bento Grid — Roadmap note

Last updated: 2026-07-17 · Plugin version when closed: **1.6.10**

## Done (১–৯)

1. Apply Preset — replaces cells + spans (Elementor + Gutenberg)
2. Mobile/tablet per-cell order + span overrides
3. Image overlay opacity
4. CTA button on cells
5. Per-cell tag color override
6. Testimonial cell type
7. YouTube/Vimeo embed (plus mp4/webm)
8. Separate row/column gap
9. Content align (top/center/bottom) per cell

## Later (ইনশাআল্লাহ) — deferred

Do these only when asked; not part of the shipped 1.6.x roadmap above.

| # | Feature | Notes |
|---|---------|--------|
| A | Canvas drag-resize | Resize/reorder cells visually in editor canvas — **shipped (Elementor edit-mode SE handle → col/row span)** |
| B | Gutenberg InnerBlocks | Nest blocks inside bento cells — **shipped (`rawnaq/bento-cell` + parent InnerBlocks; cellsJson fallback)** |
| C | Custom HTML cells | Per-cell raw HTML (sanitize carefully) |

## Related files

- Spec: `rawnaq-bento-grid-spec.md`
- Mockup: `rawnaq-bento-grid-mockup.html`
- Widget: `includes/elementor/widgets/class-bento-grid-widget.php`
- Block: `includes/gutenberg/class-gutenberg-loader.php` + `assets/js/gutenberg-editor.js`
- Assets: `assets/css/bento-grid.css`, `assets/js/bento-grid.js`
- Helpers: `includes/rawnaq-helpers.php` (`rawnaq_bento_*`)
