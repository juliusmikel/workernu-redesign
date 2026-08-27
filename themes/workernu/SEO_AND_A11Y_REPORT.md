# SEO & Accessibility — Implementation Report

_Workernu marketing site — `wp-content/themes/workernu`_

This document maps every SEO and accessibility decision in the theme/plugins to the file and rationale behind it. It's the canonical reference for what's wired up, where, and why.

---

## 1. Structured data (Schema.org / JSON-LD)

### Architecture

| Layer | What it does | Where |
|---|---|---|
| `workernu-seo` plugin | Builds the page's `@graph` and emits one `<script type="application/ld+json">` in `<head>` per page. Always includes `Organization` + `WebPage`. | `plugins/workernu-seo/includes/output.php` |
| `workernu_seo_json_ld_graph` filter | Public hook so other code can contribute entries to the graph | same file |
| `workernu-sections` plugin | Walks every section on the post and calls each section's `'schema' => fn($data)` callback; appends fragments to the graph | `plugins/workernu-sections/includes/schema.php` |
| `workernu-settings` plugin | Stores site-wide entity data (Product/Trustpilot) consumed by the schema callbacks | `plugins/workernu-settings/includes/seo.php`, `trustpilot.php` |

### What's emitted on every page

- **`Organization`** — from workernu-seo settings (name, logo, sameAs URLs)
- **`WebPage`** — title, URL, description, primaryImageOfPage

### What's emitted per-section (section presence triggers the schema)

| Section | Schema contribution | Source of truth | File |
|---|---|---|---|
| **Hero** | `SoftwareApplication` with stable `@id` (`/#software-application`). Includes `name`, `description`, `url` (app subdomain), `applicationCategory`, `operatingSystem`, `screenshot` | Settings → WorkerNu → SEO | `sections/hero/section.php` |
| **Pricing** | One `Offer` per tier, each with `itemOffered: @id` → SoftwareApplication. Auto-extracts numeric price from "€5", "5,00", etc. Auto-detects currency from symbol (€/$/£/¥) | The pricing repeater | `sections/pricing/section.php` |
| **Testimonials** | `Review` per testimonial with `author`, `reviewBody`, `reviewRating` + one `AggregateRating` if Trustpilot aggregate is configured. All reference `SoftwareApplication` via `@id`. | The testimonials repeater + Settings → WorkerNu → Trustpilot (aggregate rating + count fields) | `sections/testimonials/section.php` |
| **People** | One `Person` per card with `name`, `jobTitle`, `email`, `telephone`, `image`, `worksFor: @id` → Organization | The people repeater | `sections/people/section.php` |

### The `@id` linking pattern

All cross-section entities share stable IDs so Google merges fragments into single entities:

- `https://workernu.lt/#organization` — declared by workernu-seo, referenced by `People.worksFor`
- `https://workernu.lt/#software-application` — declared by Hero, referenced by `Pricing.Offer.itemOffered`, `Testimonials.Review.itemReviewed`, `Testimonials.AggregateRating.itemReviewed`

### Section-driven emission rule

A page emits structured data only for the sections actually present on it. Editors don't toggle schema on/off per page — if you add a Hero to a page, that page presents the product; if you don't, it doesn't. Eliminates the "someone forgot to check the schema box" failure mode.

### Schemas deliberately NOT used

- ~~`SoftwareApplication` site-wide on every page~~ → only on pages with a Hero (i.e., pages actually about the product). Prevents Google from seeing misleading data on blog posts/policies.
- ~~`Product`~~ → not needed; `SoftwareApplication` covers the same rich-result eligibility for SaaS. Adding `Product` as a co-type doesn't unlock anything new for our case.
- ~~`FAQPage`~~ → Google deprecated FAQ rich results in 2023 for most sites. Skipped until we have a real FAQ section.
- ~~`BreadcrumbList`~~ → not implemented yet; would be a quick win when we add a multi-level page hierarchy.

---

## 2. Image performance & SEO

### Helper

`workernu_image_attrs($id, $size, $opts = [])` — emits the full attribute string (`src`, `alt`, `width`, `height`, `srcset`, `sizes`, `loading`, `decoding`, optional `fetchpriority`) for any attachment ID.

`plugins/workernu-sections/includes/api.php`

### What every image now gets

| Attribute | What it solves |
|---|---|
| `width` + `height` (intrinsic) | Browser reserves space → no Cumulative Layout Shift (CLS) when images load |
| `srcset` (all WP-generated sizes) | Mobile browsers download the smallest sufficient file → bandwidth savings + LCP boost |
| `sizes` (auto-computed) | Tells browser how the image is sized in layout so srcset picks correctly |
| `loading="lazy"` (below-the-fold) / `"eager"` (Hero only) | Defers off-screen images, preloads above-the-fold |
| `decoding="async"` | Image decoding happens off the main thread, not blocking it |
| `fetchpriority="high"` (Hero only) | Tells browser this is the LCP candidate; prioritize over other images |

### Section coverage

All sections emitting `<img>` tags route through the helper:
- `hero` (main image: eager + fetchpriority="high", trustpilot fallback, avatar stack)
- `logos` (logo grid)
- `map` (background map image)
- `people` (avatar)
- `tabs` (panel media)
- `testimonials` (avatar, country flag, footer badge)
- `icons` (custom icon image variant)
- `zigzag-rows` (per-row image)

The `cards` section uses CSS `background-image` (no `<img>` tag), which can't carry srcset — but cards have a fixed height (`clamp()`) so no CLS risk.

### Hero LCP preload

`plugins/workernu-sections/includes/render.php → preload_hero_image()`

If a page has a Hero section, the plugin injects `<link rel="preload" as="image" imagesrcset="…" imagesizes="…" fetchpriority="high">` into `<head>` at priority 1 of `wp_head`. The browser starts the hero image download during HTML parsing, parallel with CSS — usually shaves 200–800ms off LCP.

---

## 3. Section-aware enqueueing

`plugins/workernu-sections/includes/render.php → enqueue_section_assets()`

CSS and JS for a section type are only enqueued on pages where that section is present. No blanket `all-sections.css`. Smaller payload per page, particularly on light/landing pages.

---

## 4. Heading hierarchy

Audited and enforced manually:

- **`<h1>`** — `hero/template.php` only. Every page has at most one Hero, so at most one `<h1>`.
- **`<h2>`** — every other section's main heading (testimonials, cards, pricing, icons, etc.)
- **`<h3>`** — per-item titles inside section repeaters (each card's title, each pricing tier's name, each feature-highlight item's title, each tabs panel heading)

Output of the audit: every section follows this convention. No `<h4>+` is used.

---

## 5. Accessibility

### Skip link

`themes/workernu/header.php` emits `<a class="skip-link" href="#site-main">` as the very first thing inside `<body>`. The CSS in `assets/css/main.css` hides it off-screen until keyboard focus brings it on-screen.

`<main>` carries `id="site-main"` to match the skip anchor.

### Landmark navigation

The site already has the basic landmarks:
- `<header class="site-header">`
- `<nav id="site-nav">` with `aria-label`
- `<main id="site-main">`
- Each section is a `<section class="section section--<slug>">`

Decorative ARIA regions added:
- **Testimonials marquee** — `role="region" aria-label="Customer testimonials"` so screen readers can find/skip the auto-scrolling block
- **Icons rail** — `role="region" aria-label="Scrolling icons"` for the same reason

### Reduced-motion handling

`@media (prefers-reduced-motion: reduce) { animation: none; }` is set on:
- The testimonials marquee track
- The icons rail track

Users who've opted out of motion at the OS level see static rows instead of scrolling animation.

### Per-section ARIA already in place (verified)

| Section | ARIA |
|---|---|
| tabs | `role="tablist"` / `role="tab"` / `role="tabpanel"` / `aria-controls` / `aria-labelledby` / `aria-selected` — full tabs pattern |
| testimonials | `aria-label="N / 5"` on the star row; cloned cards (for seamless marquee loop) carry `aria-hidden="true"` so SR users don't hear duplicates |
| map | `aria-label` on labeled pins, `aria-hidden="true"` on unlabeled |
| cards | `role="img" aria-label` on link cards that use `background-image` instead of `<img>` |
| feature-highlight | `aria-hidden="true"` on decorative icons |
| icons | `aria-hidden` on font-awesome icon containers |
| hero | `aria-hidden="true"` on user-avatar placeholder, badge icons |
| calculator | `<output for="slider-id">` semantic element; `<label for="slider-id">` on every slider |

### Decorative-image handling

- Testimonials country flag — emitted with `alt={country name}` (matches the visible `country_label`). Screen readers announce country when navigating the card, which is meaningful info.
- Avatar placeholders (when no image) use `<span aria-hidden="true"><i class="fa-solid fa-user"></i></span>` instead of an `<img>` — no SR pollution.

### Form elements

- Calculator's sliders use proper `<input type="range">` with `<label for>` and `<output>` — no custom widgets, native a11y. Focus styles included.
- All buttons use `<button type="button">` with descriptive text. No icon-only buttons missing labels (the mobile nav toggle has `aria-label="Toggle menu"`, `aria-expanded`, `aria-controls`).

### What we deliberately did NOT do

- No `role="navigation"` on the `<nav>` — `<nav>` is implicit. Adding the explicit role is redundant.
- No `role="contentinfo"` on `<footer>` — implicit landmark.
- No `tabindex="0"` on non-interactive elements (this is an anti-pattern — only interactive things should be in the tab order).

---

## 6. Settings → WorkerNu admin panel

`Settings → WorkerNu` (top-level menu item, slug `workernu-dashboard`).

| Sub-page | Stores | Consumed by |
|---|---|---|
| **Dashboard** | (read-only status overview) | n/a |
| **SEO** | Product name, description, app URL, applicationCategory, operatingSystem, screenshot | Hero section's `schema` callback |
| **Trustpilot** | Business unit ID, template ID, locale, height, theme, review URL, fallback image, **aggregate rating value + count** | Hero section (renders live widget), Testimonials section's `schema` callback (AggregateRating numbers), workernu-settings → `Trustpilot\render()` helper |

---

## 7. What's coded in (no DB / no manual setup) vs needs configuration

### Coded in (deploys with files)

- All schema templates
- Image perf attrs across all sections
- Skip link + landmarks + ARIA roles
- Reduced-motion handling
- Heading hierarchy
- Section-aware asset enqueueing
- Hero LCP preload
- The `workernu_image_attrs` helper

### Needs to be configured per-environment (in WP Admin)

- **Settings → WorkerNu → SEO** — fill in product fields (Workernu, app URL, etc.)
- **Settings → WorkerNu → Trustpilot** — business unit ID, template ID, **aggregate rating value, aggregate review count**
- **Appearance → Customize → Site Identity** — logo (Organization schema's `logo` reads from this)
- **Appearance → Menus → Screen Options → CSS Classes** — not needed anymore; the auto-tagging filter in `functions.php` injects `is-cta` / `is-login` based on menu item position (last + penultimate)

---

## 8. Open items / things still worth doing later

| Win | Effort | Where it would live |
|---|---|---|
| **`hreflang` tags for lt/en URL variants** | Small | `workernu-lang` plugin (audit) |
| **`BreadcrumbList`** structured data per page | Small | `workernu-seo` plugin (add to output.php) |
| **OG image fallback to Hero image** | Small | `workernu-seo` plugin (verify behavior) |
| **WebP/AVIF serving** | Small (install Imagify/ShortPixel) | Plugin install on live |
| **Cards section: switch `background-image` → `<img>`** to enable srcset on those tiles | Medium | Each card template + CSS |
| **Section landmark labels** — `aria-labelledby` on every `<section>` pointing to its heading ID | Medium | Touch every section template |
| **FAQPage schema** (if/when an FAQ section is added) | Small per section | New section + schema callback |
| **Article/BlogPosting** schema (if blog posts are added) | Small | Once blog post template exists |

---

## 9. How to verify each piece is working

| What | How to verify |
|---|---|
| Schema is emitting | View source of any page → search for `<script type="application/ld+json">`. Should contain `@graph` array with Organization + WebPage + (if Hero present) SoftwareApplication + (if Pricing) Offer entries + (if Testimonials) Review entries + (if People) Person entries |
| Schema is valid | Paste page URL into [Google Rich Results Test](https://search.google.com/test/rich-results) or [Schema.org Validator](https://validator.schema.org/) |
| Image perf | DevTools → Network → Img tab. Confirm `srcset` URLs, no LCP image waiting on CSS, lazy images don't load until scrolled |
| LCP | Lighthouse → Performance → expand LCP. Should be the Hero image, < 2.5s on a fast connection |
| CLS | Lighthouse → Performance → expand CLS. Should be ~0 with the width/height attrs |
| Skip link | Tab once after page load. The first focus stop should be "Skip to content" appearing in the top-left |
| Reduced motion | OS Settings → Accessibility → "Reduce motion" → reload page. Marquees/rails should be static |
| ARIA regions | Use a screen reader (VoiceOver: Cmd+F5). Navigate by landmarks (VO+U → Landmarks). Should list nav, main, and the labelled regions |

---

_Last updated: 2026-05-29_
