# Rawnaq — Scroll Timeline: মার্কেট-লিডিং প্ল্যান

*রিসার্চ ভিত্তি: Timeline Widget for Elementor/Cool Timeline (২৪৪+ রিভিউ, মার্কেট লিডার), ElementsKit Timeline, Ultimate Addons Timeline, Elfsight Timeline, The Plus Addons Timeline-এর ফিচার/চেঞ্জলগ/রিভিউ বিশ্লেষণ + CSS Scroll-Driven Animations (২০২৬-এর নতুন ব্রাউজার স্ট্যান্ডার্ড) নিয়ে টেকনিক্যাল রিসার্চ।*

---

## ১. কম্পিটিটর ল্যান্ডস্কেপ — কী আছে, কী দুর্বলতা

### মার্কেট লিডার: Cool Timeline / "Timeline Widget For Elementor"
- ২৪৪+ রিভিউ, ফিচার-সমৃদ্ধ: vertical/horizontal, one-sided layout, media slider, YouTube Shorts, iframe এম্বেড, dynamic post-timeline (taxonomy filter + Load More), WPML/Polylang/Loco মাল্টিলিঙ্গুয়াল সাপোর্ট
- **দুর্বলতা যা রিভিউ/চেঞ্জলগে পাওয়া গেছে:**
  - পারফরম্যান্স ডকুমেন্টেশনেই স্বীকার করা: *"large images, many stories, or heavy animations may affect performance — pagination/Load More ensures optimal speed"* — অর্থাৎ ডিফল্ট রেন্ডারিং ভারী, ইউজারকে নিজে থেকে সীমাবদ্ধ করতে হয়
  - অতীতে Font Awesome আইকন-লোডিং পারফরম্যান্স সমস্যা ছিল (পরে SVG-তে সরানো হয়েছে — মানে তারা নিজেরাই এই সমস্যা স্বীকার করেছে)
  - হরাইজন্টাল টাইমলাইন ভিউয়ারে card-display bug রিপোর্ট আছে ইউজার ফোরামে
  - কোর অ্যানিমেশন এখনো jQuery/JS-স্ক্রল-ইভেন্ট বা স্লাইডার-লাইব্রেরি নির্ভর — **compositor-thread-ভিত্তিক না**, তাই ভারী পেজে jank/স্টাটার হতে পারে

### বাকি প্রতিযোগীরা (ElementsKit, Ultimate Addons, Elfsight, The Plus Addons)
- সবাই একই paradigm অনুসরণ করে: static card/slider layout + hover বা basic fade-in effect
- কেউই সত্যিকারের **"scroll-progress-linked" motion** (যেখানে টাইমলাইনের লাইন/প্রোগ্রেস স্ক্রল পজিশনের সাথে directly bound, ঠিক ভিডিও স্ক্রাবিং-এর মতো) দেয় না — সবগুলোই হয় "scroll-এ trigger হয়ে একবার অ্যানিমেট" (IntersectionObserver-স্টাইল) নয়তো "auto-slide/carousel"
- মাল্টিলিঙ্গুয়াল/RTL সাপোর্ট Cool Timeline বাদে বাকিদের মধ্যে অসামঞ্জস্যপূর্ণ

**সংক্ষেপে:** ফিচার-ডেপথ-এ প্রতিযোগিতা করা কঠিন (Cool Timeline বছরের পর বছর ধরে ফিচার যোগ করেছে), কিন্তু **আন্ডারলায়িং টেকনোলজি প্রায় সবাই একই যুগের (jQuery/JS-স্ক্রল-ইভেন্ট)** — এখানেই বড় ফাঁক আছে।

---

## ২. মূল ডিফারেন্সিয়েটর: Native CSS Scroll-Driven Animations

২০২৬ সালের একটা গুরুত্বপূর্ণ ব্রাউজার-প্ল্যাটফর্ম আপডেট রিসার্চে পাওয়া গেছে — **CSS Scroll-Driven Animations Spec** (`animation-timeline: scroll()` / `view()`) এখন প্রায়-সর্বজনীনভাবে সমর্থিত:

| ব্রাউজার | সাপোর্ট স্ট্যাটাস (২০২৬) |
|---|---|
| Chrome / Edge / Opera | পূর্ণ সাপোর্ট (v115+) |
| Safari | পূর্ণ সাপোর্ট (v26+) |
| Firefox | আংশিক (flag-এর পেছনে), পলিফিল দিয়ে কভার করা যায় |

**এর মানে কী:** স্ক্রল-লিঙ্কড প্রোগ্রেস/লাইন-ফিল/স্ট্যাগার-রিভিল অ্যানিমেশন এখন **কোনো জাভাস্ক্রিপ্ট ছাড়াই, শুধু CSS দিয়ে**, ব্রাউজারের **compositor thread**-এ রান করানো সম্ভব — মেইন থ্রেড ব্লক থাকলেও অ্যানিমেশন স্মুথ থাকে (jank হয় না)। কোনো প্রতিযোগী addon এখনো এই টেকনোলজি ব্যবহার করছে বলে রিসার্চে পাওয়া যায়নি — সবাই এখনো IntersectionObserver/scroll-event/GSAP-নির্ভর।

### কেন এটা Rawnaq-এর জন্য একদম ফিট
- তোমার কোর ফিলোসফি এমনিতেই "zero-jQuery" — এটা সেটাকে চূড়ান্ত পর্যায়ে নিয়ে যায়: **"zero-JS" কোর অ্যানিমেশন** (শুধু ফলব্যাক-এর জন্য সামান্য JS)
- Cool Timeline-এর নিজেদের স্বীকার করা পারফরম্যান্স-দুর্বলতা (অনেক স্টোরি/অ্যানিমেশনে স্লো হওয়া) এখানে সরাসরি সমাধান হয়ে যায়, কারণ কম্পোজিটর-থ্রেড অ্যানিমেশন মেইন-থ্রেড লোড দ্বারা প্রভাবিত হয় না
- মার্কেটিং-এ ব্যবহারযোগ্য একটা টেকনিক্যাল "ফার্স্ট" ক্লেইম: *"Rawnaq Scroll Timeline — Elementor-এর প্রথম টাইমলাইন উইজেট যেটা নেটিভ ব্রাউজার স্ক্রল-অ্যানিমেশন টেকনোলজিতে বানানো, কোনো ভারী JS লাইব্রেরি ছাড়াই"*

---

## ৩. আর্কিটেকচার প্ল্যান

### রেন্ডারিং স্ট্র্যাটেজি (Progressive Enhancement)
```
@supports (animation-timeline: scroll()) {
  /* মডার্ন ব্রাউজার: pure CSS scroll-driven animation */
  /* লাইন-ফিল, নোড পপ-ইন, স্ট্যাগার রিভিল — সব animation-timeline দিয়ে */
}
@supports not (animation-timeline: scroll()) {
  /* Firefox (flag ছাড়া) ও পুরনো ব্রাউজার: IntersectionObserver+CSS transition ফলব্যাক */
  /* zero-jQuery, কিন্তু হালকা vanilla JS */
}
```
এই স্তরায়িত পদ্ধতিতে বেশিরভাগ ভিজিটর (Chrome/Edge/Safari ব্যবহারকারী) সম্পূর্ণ zero-JS অভিজ্ঞতা পাবে, আর বাকিরা graceful JS ফলব্যাকে — কোনো ব্রাউজারেই ভাঙা অভিজ্ঞতা হবে না।

### কোর অ্যানিমেশন টাইপ
| ইফেক্ট | মেকানিজম |
|---|---|
| মূল টাইমলাইন-লাইন ফিল (নিচের দিকে progress bar-এর মতো ভরে ওঠা) | `animation-timeline: scroll()` — পুরো কন্টেইনারের স্ক্রল প্রোগ্রেসের সাথে bound |
| প্রতিটা নোড/কার্ড ভিউপোর্টে ঢোকার সময় pop-in/fade | `animation-timeline: view()` + `animation-range: entry 0% entry 100%` |
| নোড ভিউপোর্টে "সম্পূর্ণ দৃশ্যমান" অবস্থায় হাইলাইট স্টেট | `animation-range: contain` |
| প্যারালাক্স-স্টাইল ইমেজ/আইকন মুভমেন্ট | নেমড scroll timeline (`scroll-timeline-name`) কন্টেইনারে সেট করে চাইল্ড এলিমেন্টে reference |
| Reduced-motion ইউজারদের জন্য | `@media (prefers-reduced-motion: reduce)` — অ্যানিমেশন বন্ধ, স্ট্যাটিক ফাইনাল-স্টেট দেখানো (Elementor নিজেও এটা রিকমেন্ড করে) |

---

## ৪. ফিচার প্যারিটি (কম্পিটিটরদের সাথে সমান থাকতে যা লাগবে)

| ফিচার | প্রায়োরিটি | নোট |
|---|---|---|
| Vertical / Horizontal / One-sided লেআউট | Must-have | সব বড় কম্পিটিটরেই আছে, বেসলাইন |
| Alternating (zig-zag) লেআউট | Must-have | ক্লাসিক টাইমলাইন লুক |
| Media সাপোর্ট (ছবি, ভিডিও, YouTube এম্বেড) | Must-have | কনটেন্ট রিপিটারে |
| Dynamic Post-type লুপ (CPT থেকে অটো-জেনারেট) | High | Elementor Loop Builder / Gutenberg Query Loop-এর সাথে ইন্টিগ্রেশন — ব্লগ/নিউজ টাইমলাইনের জন্য |
| WPML / Polylang / RTL সাপোর্ট | High | Cool Timeline-এর এজ, এটা ম্যাচ না করলে পিছিয়ে থাকবে — এটা তোমার নিজের RTL/বাংলা-ফার্স্ট পজিশনিং-এর সাথেও সরাসরি সাংঘর্ষিক হবে যদি না থাকে |
| Load More / Pagination (বড় টাইমলাইনের জন্য) | High | পারফরম্যান্স সেফগার্ড হিসেবে |

## ৫. ডিফারেন্সিয়েটেড ফিচার (মার্কেট-লিডিং বানানোর জন্য অতিরিক্ত)

| ফিচার | বিবরণ |
|---|---|
| **Scroll-Scrub Progress Line** | মূল টাইমলাইন-লাইন ব্যবহারকারীর স্ক্রল পজিশনের সাথে ১:১ bound — উপরে-নিচে স্ক্রল করলে লাইন সেই অনুযায়ী ভরে/খালি হয় (ভিডিও স্ক্রাবারের মতো), pure CSS দিয়ে |
| **Named Timeline Sync** | একটা টাইমলাইনের স্ক্রল প্রোগ্রেস অন্য এলিমেন্টের (যেমন পাশের একটা স্ট্যাট কাউন্টার বা ইমেজ) সাথে sync করা যাবে — CSS-এর `scroll-timeline-name` শেয়ার করে, কোনো JS ছাড়াই। (উদাহরণ: টাইমলাইন স্ক্রল করলে পাশের ইমেজ ক্রমান্বয়ে বদলায়) |
| **Zero-JS মোড ব্যাজ** | Elementor এডিটরে একটা ইন্ডিকেটর দেখাবে "এই ব্রাউজারে এই উইজেট 0 KB অতিরিক্ত JS দিয়ে চলছে" — ডেভেলপার/এজেন্সি ক্লায়েন্টদের জন্য বিশ্বাসযোগ্য প্রমাণ |
| **Reduced-motion-first ডিজাইন** | ডিফল্টভাবেই WCAG-সচেতন — কম্পিটিটরদের কেউ এটা প্রধান ফিচার হিসেবে মার্কেট করে না |
| **Compositor-safe পারফরম্যান্স গ্যারান্টি** | মার্কেটিং কপিতে ব্যবহারযোগ্য: "৫০+ আইটেমের টাইমলাইনেও jank-free, কারণ অ্যানিমেশন মেইন থ্রেডে না" |

---

## ৬. রোডম্যাপ (ফেজভিত্তিক)

### Status (v1.9.0)

**Done**
- [x] Phase 1: CSS `animation-timeline` / `view()` + JS feature-detect fallback + `prefers-reduced-motion`
- [x] Horizontal layout + alternating/left/right (mobile stacks horizontal)
- [x] RTL mirroring for one-sided / alternating layouts
- [x] Step media: image OR YouTube/Vimeo/mp4 via `rawnaq_bento_video_markup()`
- [x] Line thickness (`--tl-line-width`)
- [x] Initial visible steps + Load More chunk
- [x] Elementor + Gutenberg controls/render parity
- [x] Editor engine badge when CSS path active (`tl-css-driven`)
- [x] `data-tl-name` + per-instance `scroll-timeline-name`
- [x] Dynamic post-type / CPT query source (`rawnaq_timeline_query_steps`)
- [x] Named Timeline Sync with Bento (`sync_timeline` / `timelineSync`)
- [x] WPML light + pragmatic full: textdomain, `rawnaq_timeline_steps`, `wpml_object_id`, `wpml-config.xml`, `rawnaq_translate` / Polylang register
- [x] Query-mode AJAX Load More (`rawnaq_timeline_load_more`)
- [x] Marketing benchmark: [`../assets/demo/scroll-timeline-benchmark.html`](../assets/demo/scroll-timeline-benchmark.html) + [`scroll-timeline-benchmark.md`](scroll-timeline-benchmark.md)

**Later**
- [ ] Official WPML Go Global marketplace submission
- [ ] Elementor Loop Builder deep integration
- [ ] Hosted live marketing site / produced jank GIF

### WPML checklist
See [`scroll-timeline-benchmark.md`](scroll-timeline-benchmark.md) — activate WPML/Polylang, translate query posts, translate `rawnaq` strings, verify RTL.

**Named Sync usage:** Set the same ID on Timeline “Named Timeline ID” and Bento cell “Sync Timeline ID” (e.g. `rawnaq-tl-hero`). Filter hook: `rawnaq_timeline_steps`.

---

## ৭. পজিশনিং / মার্কেটিং লাইন (রেফারেন্সের জন্য)
- *"Elementor-এর প্রথম Scroll Timeline যা নেটিভ ব্রাউজার স্ক্রল-অ্যানিমেশন টেকনোলজিতে তৈরি — ভারী JS লাইব্রেরি ছাড়াই বাটারি-স্মুথ পারফরম্যান্স।"*
- *"অন্য টাইমলাইন প্লাগইন 'অনেক বেশি স্টোরি থাকলে পেজিনেশন ব্যবহার করুন' বলে — আমাদেরটা কম্পোজিটর-থ্রেডে চলে বলে সেই সমস্যাই নেই।"*

## ৮. ঝুঁকি / বিবেচনা
- Firefox-এর আংশিক সাপোর্ট মানে ফলব্যাক পাথ অবশ্যই প্রোডাকশন-রেডি ও ভালোভাবে টেস্ট করা থাকতে হবে (না হলে একটা বড় ব্রাউজার সেগমেন্টে খারাপ অভিজ্ঞতা হবে)
- `animation-timeline`-ভিত্তিক জটিল ইন্টারঅ্যাকশন (named timeline sync) ডিবাগ করা ডেভেলপারদের জন্য নতুন প্যারাডাইম — ডকুমেন্টেশন/টিউটোরিয়ালে বিশেষ যত্ন লাগবে
- এই টেকনোলজি অপেক্ষাকৃত নতুন (২০২৬-এই মূলধারায় এসেছে) — স্পেকে ভবিষ্যতে ছোটখাটো পরিবর্তন হতে পারে, তাই কোর লজিক abstraction লেয়ারের পেছনে রাখা ভালো যাতে স্পেক বদলালে সহজে আপডেট করা যায়
