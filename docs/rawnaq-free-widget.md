# Rawnaq free: construction widget plan

Free-plugin modules aimed at architects, interior designers, engineers and builders.
Each one must be useful on its own, need no server or paid service, and must not copy a Pro module.

## Done

| Module | Slug | Version | Notes |
| --- | --- | --- | --- |
| Material Quantity Calculator | `material-calc` | next release (1.6.0) | Concrete, bricks and mortar, tiles, paint, steel bars. Metric or feet/inches. Elementor, Gutenberg, Divi. |

Why it does not overlap Pro: the Pro BOQ Estimator prices items. This one only returns quantities and
points the visitor to a quote (optional button).

## Planned for the next version

### 1. Project Fact Sheet
A card for project pages: client, location, area, cost, duration, status, architect or engineer,
certifications.
- Fits next to the Case-Study Grid on every project page.
- Fields repeatable, icon per row, optional status badge (planned, in progress, handed over).
- Layouts: vertical card, horizontal strip, two-column list.
- Schema.org markup is optional and off by default.

### 2. Project Programme (Gantt-style bar)
Phase bars on a date axis so a client sees the schedule at a glance:
design, approvals, structure, finishing, handover.
- Repeatable phases with start and end date, colour and progress percent.
- Today marker, month or week scale, horizontal scroll on phones.
- Different from Scroll Sync Timeline: that one tells a story as you scroll, this one shows dates.

## Later ideas (not committed)

- **Unit and area converter**: sq ft, m², katha, decimal, bigha, acre. Small and useful in South Asia.
- **Loan or EMI calculator for home building**: simple, a lead magnet for builders.
- **Licence and certification strip**: logos with numbers and expiry notes, for trust.
- **Site visit request**: only if Smart Form cannot cover it with a preset.

## Rules for every new free module

1. Elementor, Gutenberg and Divi in the same release.
2. Vanilla JS, assets load only where the module is placed.
3. Add the slug to `rawnaq_default_modules()`, the Elements manager and `bin/minify-assets.cjs`.
4. Update the module count in `readme.txt`, `README.md` and the thereadscope.com docs.
5. Keep Pro modules Pro: nothing that prices, signs, or tracks a client project.
