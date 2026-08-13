# Rawnaq — Template Kit System Roadmap & Feature Checklist

This document tracks all planned features and architectural enhancements for the **Rawnaq Template Kit System** across Elementor and Gutenberg builders.

---

## 📋 Feature Checklist

### Phase 1: Smart UX & Interactivity ✅
- [x] **Smart Module Auto-Activation**
  - [x] `auto_enable=1` AJAX support in `ajax_import()`
  - [x] Auto-activate missing modules in `rawnaq_settings` on-the-fly
  - [x] `⚡ Enable & Insert` CTA replaces disabled card state
- [x] **Live Interactive Preview Lightbox**
  - [x] `👁️ Live Preview` hover button on template cards
  - [x] Fullscreen glassmorphism modal overlay
  - [x] Device viewport toggles: Desktop / Tablet 768px / Mobile 375px
  - [x] Direct `Insert Section` CTA inside Lightbox header

---

### Phase 2: Design Systems & Content Expansion ✅
- [x] **Dynamic Color Palette & Typography Alignment**
  - [x] `{{COLOR_*}}` token system in all 12 template files (6 Elementor + 6 Gutenberg)
  - [x] `ajax_get_site_palette()` endpoint — reads WordPress `theme.json` + Elementor Kit Global Colors
  - [x] `resolve_color_tokens()` PHP method — replaces tokens before import
  - [x] Color Customizer Panel (slide-in sidebar) — pre-fills site palette, lets user override
  - [x] `color_overrides` sent to both `ajax_import` and `ajax_import_page_kit`
  - [x] `🎨` button on each section card + Lightbox header
- [x] **Complete Niche Page Kits**
  - [x] `get_page_kit_registry()` — 4 page kits defined (SaaS Landing, Agency Showcase, Portfolio Pro, Business Classic)
  - [x] `get_page_kit_registry_with_status()` — enriched with missing modules + section badges
  - [x] `ajax_import_page_kit()` endpoint — combines multiple sections into one payload
  - [x] `🗂️ Page Kits` tab in Template Kit popup
  - [x] Page Kit cards with section badge list, tag pills, description
  - [x] `🗂️ Import Full Page` action button
  - [x] Color Customizer works for Page Kits too

---

### Phase 3: Cloud & Advanced Utilities (Planned)
- [ ] **Cloud Sync & Dynamic Template API Server**
  - [ ] Remote Rawnaq API endpoint for template registry
  - [ ] 5-day transient cache for remote templates
  - [ ] Background sync without plugin updates
- [ ] **Micro-Filters & Instant Search Engine**
  - [ ] Real-time keyword search bar in popup
  - [ ] Design style filters (Glassmorphism, Minimalist, Dark Glow, Neumorphic)
- [ ] **User Presets & Export/Import Manager ("My Presets")**
  - [ ] "Save as Rawnaq Preset" action for custom sections
  - [ ] Local JSON export/import tool for cross-site use

---

## 🛠️ Color Token Reference

| Token | Default | Used in |
|---|---|---|
| `{{COLOR_PRIMARY}}` | `#6366f1` | Agency Hero, Services Hub |
| `{{COLOR_SECONDARY}}` | `#a855f7` | Portfolio Bento |
| `{{COLOR_ACCENT}}` | `#22d3ee` | Timeline About |
| `{{COLOR_DARK}}` | `#0a0a0a` | All templates (bg) |
| `{{COLOR_LIGHT}}` | `#ffffff` | All templates (text) |
| `{{COLOR_MUTED}}` | `#a1a1aa` | Subtitles |
| `{{COLOR_SUCCESS}}` | `#10b981` | Contact Section |
| `{{COLOR_WARNING}}` | `#f59e0b` | Flow Process |

## 🗂️ Page Kit Registry

| Kit ID | Sections | Target Niche |
|---|---|---|
| `saas-landing` | Hero + Services Hub + Process Flow + Contact | SaaS / App |
| `agency-showcase` | Hero + Portfolio Bento + Timeline + Contact | Creative Agency |
| `portfolio-pro` | Hero + Portfolio Bento + Timeline | Freelancer / Designer |
| `business-classic` | Hero + Services Hub + Contact | Small Business |

---

*Last Updated: 2026-08-13*
