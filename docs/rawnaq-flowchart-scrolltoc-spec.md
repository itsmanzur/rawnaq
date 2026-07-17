# Rawnaq — ফিচার স্পেক: Hub Diagram / Scroll Timeline সম্প্রসারণ

> **Status (v1.10.0)** — Done checklist
> - [x] Flow Chart: freeform X/Y, shapes (rect/circle/hex), direction TB/LR/RL + RTL flip
> - [x] Flow Chart: zoom/pan canvas, lazy mount when ≥20 nodes, DFS parent-cycle guards
> - [x] Flow Chart: Gutenberg parent SelectControl; Elementor parent_id text + cycle break
> - [x] Scroll Progress + TOC: `dock_attach` / `dockAttach` → Floating Dock Contents trigger (FAB fallback)
> - [ ] Custom SVG node shapes (out of scope)
> - [ ] Full Elementor dynamic parent SELECT (out of scope)
> - [ ] Multiple TOC links as separate dock items (out of scope)

দুটো নতুন মডিউল কভার করা হয়েছে:
1. **Flow Chart** (Hub Diagram-এর সম্প্রসারণ — org chart + process flow)
2. **Scroll Progress + TOC** (Scroll Timeline-এর সম্প্রসারণ — reading progress + auto-highlighting সূচিপত্র)

---

# মডিউল ১: Flow Chart Widget

## ১.১ Overview
Hub Diagram একটা কেন্দ্রীয় হাব থেকে radiate হওয়া নোড দেখায় (spoke pattern)। Flow Chart সেই একই ভিজ্যুয়াল ভাষা (নোড + কানেক্টর লাইন, একই রঙ/স্টাইল সিস্টেম) নিয়ে দুই ধরনের বড় স্ট্রাকচার দেখানোর জন্য বানানো হবে:
- **Org Mode** — উপর-থেকে-নিচে hierarchy (CEO → বিভাগ → টিম)
- **Process Mode** — বাম-থেকে-ডান বা উপর-থেকে-নিচে ধাপে-ধাপে ফ্লো (Step 1 → Step 2 → Step 3), branching/decision point সহ

## ১.২ টার্গেট ইউজার ও ইউজ-কেস
- ইঞ্জিনিয়ারিং/করপোরেট সাইট: "Our Team Structure", "How We Work" পেজ (NTEL-টাইপ ক্লায়েন্ট)
- এজেন্সি: সার্ভিস ডেলিভারি প্রসেস দেখানো
- SaaS/প্রোডাক্ট সাইট: ইউজার অনবোর্ডিং ফ্লো, ওয়ার্কফ্লো ডায়াগ্রাম

## ১.৩ কোর ফিচার লিস্ট

### Content Controls (Elementor Content Tab)
| কন্ট্রোল | বিবরণ |
|---|---|
| Layout Mode | Org (vertical tree) / Process (horizontal or vertical flow) / Freeform (manual x-y পজিশন) |
| Node Repeater | প্রতিটা নোডের জন্য: Title, Subtitle/Role, Icon বা Image, Description (tooltip/popup-এ দেখানোর জন্য), Link |
| Node Parent Selector | কোন নোড কোন নোডের চাইল্ড — dropdown দিয়ে সিলেক্ট (org hierarchy বানানোর জন্য) |
| Connector Style | Straight / Curved / Elbow (90° bend) / Dashed |
| Branch/Decision Node | Process mode-এ একটা নোড থেকে একাধিক আউটপুট (Yes/No, বা মাল্টি-পাথ) |
| Node Shape | Rounded rectangle / Circle / Hexagon / Custom SVG |
| Direction | Top-to-bottom / Left-to-right / Right-to-left (RTL সাইটের জন্য) |

### Style Controls
- নোড: background (solid/gradient), border, shadow, padding, icon size/color
- কানেক্টর লাইন: রঙ, thickness, animated "flow" effect (dash-offset animation দিয়ে ডেটা প্রবাহের ভিজ্যুয়াল ইঙ্গিত — Hub Diagram-এর glow-accent-এর মতো একটা accent color ব্যবহার করা যায়)
- Active/hover state: নোড হাইলাইট + কানেক্টেড লাইন হাইলাইট (parent-child রিলেশন ভিজ্যুয়ালি বোঝানোর জন্য)
- Typography: প্রতিটা টেক্সট লেভেলের জন্য আলাদা কন্ট্রোল (title/subtitle/description)

### Interaction ফিচার
- ক্লিক/হোভারে নোড এক্সপ্যান্ড হয়ে ডিটেইল দেখানো (popup বা inline expand — accordion-এর মতো ভারী না করে lightweight CSS transition দিয়ে)
- Zoom/Pan সাপোর্ট (বড় org chart মোবাইলে দেখার জন্য জরুরি)
- Scroll-triggered draw-in animation: কানেক্টর লাইনগুলো ভিউপোর্টে এলে ধীরে ধীরে "আঁকা" হওয়ার effect (SVG stroke-dasharray animation — zero-jQuery, CSS/IntersectionObserver দিয়ে)

## ১.৪ টেকনিক্যাল আর্কিটেকচার
- রেন্ডারিং: SVG (কানেক্টর লাইনের জন্য) + HTML/CSS (নোড কন্টেন্টের জন্য) — Hub Diagram-এর existing রেন্ডারিং প্যাটার্নের সাথে কনসিস্টেন্ট রাখা
- ডেটা স্ট্রাকচার: প্রতিটা নোড একটা flat array-তে `id`, `parent_id`, `title`, `content`, `position` (freeform mode-এ) রাখবে — JS-এ tree/flow বিল্ড হবে render-টাইমে
- রেসপন্সিভনেস: মোবাইলে org chart অটো-কোলাপ্স হয়ে অ্যাকর্ডিয়ন-স্টাইল ভার্টিক্যাল লিস্টে রূপান্তরিত হবে (breakpoint-based fallback)
- পারফরম্যান্স: ২০+ নোড হলে lazy render (viewport-এ আসার আগে DOM-এ যোগ না করা)

## ১.৫ Gutenberg প্যারিটি
- একই কন্ট্রোল সেট Gutenberg block হিসেবেও (InspectorControls প্যানেলে Layout Mode, নোড রিপিটার একটা InnerBlocks বা custom repeater প্যানেল দিয়ে)

## ১.৬ Edge Cases / বিবেচনা
- খুব বড় org chart (৫০+ নোড) — পারফরম্যান্স টেস্ট করা জরুরি, ভার্চুয়ালাইজেশন লাগতে পারে
- সার্কুলার parent-child রেফারেন্স ঠেকাতে ভ্যালিডেশন
- RTL সাইটে কানেক্টর ডিরেকশন ঠিকমতো ফ্লিপ হওয়া

## ১.৭ Priority-এর যুক্তি
এটা Hub Diagram-এর কোড/রেন্ডারিং বেসের ওপর সবচেয়ে বেশি reuse করা যাবে — নতুন core engine লাগবে না, existing SVG-connector লজিক এক্সটেন্ড করলেই হবে। ফলে ডেভেলপমেন্ট কস্ট কম, কিন্তু মার্কেট ভ্যালু বেশি (org chart addon-গুলোতে দুর্লভ)।

---

# মডিউল ২: Scroll Progress + TOC Widget

## ২.১ Overview
দুইটা সাব-কম্পোনেন্ট একসাথে (বা আলাদাভাবে) ব্যবহারযোগ্য:
- **Progress Indicator** — পেজ/আর্টিকেল স্ক্রল করার সাথে সাথে প্রগ্রেস বার/রিং ফিল হওয়া
- **Smart TOC** — হেডিং থেকে অটো-জেনারেটেড সূচিপত্র, বর্তমানে কোন সেকশন দেখা যাচ্ছে সেটা অটো-হাইলাইট

## ২.২ টার্গেট ইউজার ও ইউজ-কেস
- ব্লগ/আর্টিকেল সাইট, ডকুমেন্টেশন পেজ
- লম্বা ল্যান্ডিং পেজ (SaaS, সার্ভিস পেজ) — সেকশন নেভিগেশনের জন্য

## ২.৩ কোর ফিচার লিস্ট

### Content Controls
| কন্ট্রোল | বিবরণ |
|---|---|
| Progress Style | Top bar (fixed) / Circular ring (কর্নারে ফ্লোটিং) / Both |
| TOC Source | Auto-detect (H2/H3/H4 থেকে) / Manual (নিজে এন্ট্রি লিখে দেওয়া) |
| TOC Position | Sidebar (sticky) / Floating dock (তোমার Floating Dock-এর সাথে ইন্টিগ্রেশন সম্ভব) / Inline (আর্টিকেলের শুরুতে বক্স আকারে) |
| Heading Levels | কোন কোন heading level TOC-তে দেখাবে (checkbox: H2, H3, H4) |
| Collapse Behavior | সাব-হেডিং nested/collapsible কিনা |
| Smooth Scroll | TOC আইটেমে ক্লিক করলে smooth scroll + offset (fixed header থাকলে সেটার হাইট বিবেচনা করে) |

### Style Controls
- Progress bar: রঙ, thickness, gradient (Rawnaq-এর glow-accent রঙ ব্যবহার করা যায় ব্র্যান্ড কনসিস্টেন্সির জন্য), position (top/bottom)
- Circular ring: সাইজ, স্ট্রোক উইড্থ, ভেতরে % টেক্সট দেখানো/না দেখানো
- TOC: active-item হাইলাইট স্টাইল (রঙ, বোল্ড, left-border accent), ইনডেন্টেশন, ফন্ট

### Interaction ফিচার
- স্ক্রলের সাথে TOC-তে active heading অটো-হাইলাইট (IntersectionObserver-ভিত্তিক, scroll-event পোলিং না — পারফরম্যান্স-ফ্রেন্ডলি)
- মোবাইলে TOC অটো-কোলাপ্স হয়ে একটা ছোট ফ্লোটিং বাটনে পরিণত হবে (ট্যাপ করলে খুলবে) — Floating Dock প্যাটার্নের সাথে ভিজ্যুয়াল কনসিস্টেন্সি রাখা
- Reading time estimate (optional): TOC-এর ওপরে "৫ মিনিট রিডিং" টাইপ টেক্সট

## ২.৪ টেকনিক্যাল আর্কিটেকচার
- হেডিং ডিটেকশন: পেজ কন্টেন্ট থেকে DOM parse করে (server-side render টাইমে PHP দিয়ে হেডিং স্ক্যান, বা client-side JS ফলব্যাক)
- Progress ক্যালকুলেশন: `scrollY / (docHeight - viewportHeight)` — rAF (requestAnimationFrame) থ্রটলড, jQuery ছাড়া
- Active-section ডিটেকশন: IntersectionObserver দিয়ে প্রতিটা heading-এর viewport entry/exit ট্র্যাক করা — CPU-হালকা পদ্ধতি
- Sticky positioning: CSS `position: sticky` ব্যবহার (পুরনো ব্রাউজার ফলব্যাক দরকার হলে ছাড়া দেওয়া যেতে পারে, যেহেতু zero-jQuery/modern-first ফিলোসফি)

## ২.৫ Gutenberg প্যারিটি
- Gutenberg-এ heading blocks থেকে অটো TOC জেনারেট করা (ব্লক এডিটরে ইতিমধ্যেই heading structure পাওয়া যায় বলে implementation সহজ)

## ২.৬ Edge Cases / বিবেচনা
- ডুপ্লিকেট heading টেক্সট হ্যান্ডলিং (anchor ID কনফ্লিক্ট এড়াতে unique slug generation)
- খুব ছোট পেজ (স্ক্রল করার মতো কনটেন্ট নেই) হলে widget অটো-হাইড
- RTL সাইটে left-border accent → right-border-এ ফ্লিপ

## ২.৭ Priority-এর যুক্তি
Scroll Timeline-এর existing scroll-tracking ইঞ্জিন প্রায় সরাসরি reuse করা যাবে (IntersectionObserver লজিক আগে থেকেই আছে ধরে নিচ্ছি)। কম ডেভ-এফোর্টে হাই-ইউটিলিটি ফিচার — ব্লগ/কনটেন্ট-হেভি সাইটে ইনস্টল-রেট বেশি হওয়ার সম্ভাবনা।

---

## দুটো মডিউলের কমন ডিজাইন নীতি
- Rawnaq ব্র্যান্ডের glow-accent (amber) রঙ progress/active-state হাইলাইটে ব্যবহার করে ভিজ্যুয়াল কনসিস্টেন্সি বজায় রাখা
- সব অ্যানিমেশন CSS/SVG/IntersectionObserver-ভিত্তিক — কোনো jQuery ডিপেন্ডেন্সি নেই (কোর ফিলোসফি বজায় থাকবে)
- Per-widget asset loading — শুধু যে পেজে widget ব্যবহার হচ্ছে সেখানেই CSS/JS লোড হবে
