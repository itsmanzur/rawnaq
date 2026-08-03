# Rawnaq — Template Kit System Roadmap & Feature Checklist

This document tracks all planned features and architectural enhancements for the **Rawnaq Template Kit System** across Elementor and Gutenberg builders.

---

## 📋 Feature Checklist

### Phase 1: Smart UX & Interactivity (Completed ✅)
- [x] **Smart Module Auto-Activation**
  - [x] Update `rawnaq_template_kit_import` AJAX handler to support `auto_enable=1`.
  - [x] Automatically activate missing Rawnaq modules in `rawnaq_settings` on-the-fly during template import.
  - [x] Replace disabled card state with a "⚡ Enable & Insert" action button in the template picker popup.
- [x] **Live Interactive Preview Lightbox**
  - [x] Add "👁️ Live Preview" buttons to template cards in Elementor & Gutenberg editor popups.
  - [x] Build a modal overlay lightbox with responsive device viewport toggles (Desktop 100%, Tablet 768px, Mobile 375px).
  - [x] Include a direct "Insert Section" CTA inside the preview header bar.

---

### Phase 2: Design Systems & Content Expansion (Planned)
- [ ] **Dynamic Color Palette & Typography Alignment**
  - [ ] Auto-map template JSON colors to active theme/Elementor Global Swatches (`Primary`, `Secondary`, `Accent`).
  - [ ] Inherit site typography tokens dynamically upon import.
- [ ] **Complete Niche Page Kits**
  - [ ] Multi-section Full Page Kits (e.g., SaaS Landing Page, Agency Showcase, Medical Clinic, E-commerce Product Page).
  - [ ] Single-click full page bundle import mechanism.

---

### Phase 3: Cloud & Advanced Utilities (Planned)
- [ ] **Cloud Sync & Dynamic Template API Server**
  - [ ] Connect template registry to a remote Rawnaq API endpoint.
  - [ ] Instant background sync for newly released templates without requiring plugin updates (with 5-day transient cache).
- [ ] **Micro-Filters & Instant Search Engine**
  - [ ] Real-time keyword search bar in template selection modal.
  - [ ] Design style filters (`Glassmorphism`, `Minimalist`, `Dark Glow`, `Neumorphic`).
- [ ] **User Presets & Export/Import Manager ("My Presets")**
  - [ ] "Save as Rawnaq Preset" action for custom built sections.
  - [ ] Local storage and JSON export/import tool for user-created templates across sites.

---

## 🛠️ Module Dependencies Matrix

| Template ID | Category | Required Module | Live Demo Target |
|---|---|---|---|
| `agency-hero` | Hero | `floating-dock` | Agency Landing Hero |
| `services-hub` | Services | `hub-diagram` | Interactive Services Radial Hub |
| `portfolio-bento` | Portfolio | `bento-grid` | Modern Portfolio Grid |
| `timeline-about` | About | `scroll-timeline` | Interactive Story Timeline |
| `contact-form` | Contact | `smart-form` | Multi-step Lead Capture |
| `flow-process` | Process | `flow-chart` | Step-by-step Process Tree |

---

*Last Updated: 2026-08-02*
