# Rawnaq Scroll Timeline — Benchmark & QA notes

Illustrative contrast for agencies: **native CSS scroll-driven animation** vs a **main-thread scroll thrash** simulation. Not a scientific lab score.

## Positioning

- Elementor/Gutenberg timeline that can run on the compositor via `animation-timeline` / `view()` — no heavy motion library.
- Competitors often warn that “too many stories” need pagination; Rawnaq’s CSS path stays smooth because progress is not stuck on the main thread (with a JS fallback where needed).

## Open the HTML demo

1. Open [`assets/demo/scroll-timeline-benchmark.html`](../assets/demo/scroll-timeline-benchmark.html) in Chrome/Edge/Safari (CSS path) and Firefox (fallback feel).
2. Scroll both columns: left = CSS scrub (when supported), right = forced-layout jank simulation.

## Named Timeline Sync

1. On Scroll Sync Timeline, set **Named Timeline ID** (e.g. `rawnaq-tl-hero`).
2. On a Bento cell, paste the same ID into **Sync Timeline ID**.
3. Scroll the timeline — synced cells scrub opacity via the shared CSS name.

## Manual test checklist

- [ ] Chrome/Edge: wrapper gets `tl-css-driven`; line/items scrub without rAF fill
- [ ] Firefox (no flag): `tl-js-driven` + IntersectionObserver + rAF fill
- [ ] `prefers-reduced-motion`: all items active, no motion
- [ ] RTL: alternating / left / right mirrored
- [ ] Query source + Initial Visible: first N only; Load More uses AJAX (`data-tl-ajax="1"`)
- [ ] Manual source + Initial Visible: client `.tl-hidden` reveal (no AJAX)
- [ ] WPML/Polylang: switch language — query posts follow language; UI strings via String Translation / Polylang (`rawnaq` group)
- [ ] `wpml-config.xml` present at plugin root for Gutenberg block keys

## WPML / Polylang checklist

1. Activate WPML or Polylang (+ String Translation for WPML).
2. Translate posts/CPT used by **Query** mode.
3. Translate registered strings (`Load more`, `Read more`, engine badge) under domain/group **rawnaq**.
4. Gutenberg: `stepsJson` / `cellsJson` listed in `wpml-config.xml`.
5. Verify RTL theme + timeline layouts.

## Filter for custom tooling

```php
add_filter( 'rawnaq_timeline_steps', function ( $steps, $context ) {
    return $steps;
}, 10, 2 );
```
