# Frontend Developer Guide — workernu theme

You write HTML, CSS, and JS. The plugins handle data, admin UI, language, SEO, themes. This doc walks through what you need to know to ship.

---

## What this codebase is

- **Custom WordPress theme** at `wp-content/themes/workernu/`. No parent theme. No Elementor.
- **Custom plugins** at `wp-content/plugins/workernu-*`. Don't edit these unless asked — they're the framework.
- **Bootstrap-free for now.** Use design tokens (`var(--color-*)`, `var(--space-*)`) defined in `assets/css/main.css`.
- **GSAP** is loaded globally for animations (CDN). ScrollTrigger is registered.
- **Font Awesome 6.7.2** is loaded globally — use `<i class="fa-solid fa-..."></i>` anywhere.
- **All sections are server-rendered PHP.** No JavaScript framework.

---

## Git workflow

Repo: `git@github.com:juliusmikel/workernu-redesign.git`. The repo root is `wp-content/`.

```bash
git clone git@github.com:juliusmikel/workernu-redesign.git wp-content
git checkout -b frontend/<your-name>/<feature>
# ... work ...
git add -p
git commit -m "section(hero): build figma layout"
git push -u origin frontend/<your-name>/<feature>
# open a PR on GitHub against main
```

Branches:
- `main` — production-quality, gets deployed to live
- `dev` — local-only working branch (Julius)
- `frontend/<your-name>/*` — your branches; PR into `main` when done

---

## Adding a section: the contract

Sections live under `wp-content/themes/workernu/sections/<slug>/`. Each section is one folder with four files:

```
sections/<slug>/
├── section.php       ← schema (you don't write this — Julius does)
├── template.php      ← HTML — YOU
├── style.css         ← CSS — YOU, auto-loaded when section is used
└── animations.js     ← optional GSAP — YOU, auto-loaded when section is used
```

The reference section is `sections/example-hero/`. **Copy that folder when scaffolding a new one.** It demonstrates every field type and the modifier system.

---

## What `template.php` receives

A single `$data` array containing every field and modifier value for this section instance. Shape per field type:

| Field type | What's in `$data['name']` |
|---|---|
| `text`, `textarea` (not translatable) | string |
| `text`, `textarea` (translatable) | `['lt' => '...', 'en' => '...']` — always pass through `workernu_t()` |
| `rich_text` | `['value' => string\|{lt,en}, 'display' => 'paragraph'\|'bullets'\|'numbered']` — render with `workernu_text()` |
| `icon` | string (FA class) OR raw HTML `<i>` / `<svg>` — pass through `workernu_icon()` |
| `image` | int (attachment ID) — pass through `workernu_image_url()` / `workernu_image_alt()` |
| `link` | `['label' => ..., 'url' => ..., 'target' => '_self|_blank']` — `label` is translatable |
| `select` | string (one of the option keys) |
| `boolean` | `true` / `false` |
| `number` | int or float |
| `repeater` | array of sub-records; each is an associative array of its own sub-field values |
| **modifier values** | scalar (same array, just rendered into BEM classes via the helper) |

**Always escape on output.** `esc_html()`, `esc_url()`, `esc_attr()`. Never echo raw user content.

**Optional vs required fields.** All fields are technically optional at the storage layer — the editor can leave any of them blank. A red `*` in the meta-box label means the field is marked `required` (an editor hint, not enforced). Your template must still defensively skip empty values:

```php
$heading = workernu_t($data['heading'] ?? '');
if ($heading !== '') {
    echo '<h1>' . esc_html($heading) . '</h1>';
}
```

Empty-check by type: `text`/`textarea` → `''`, `rich_text` → `workernu_text()` returns `''`, `image` → `workernu_image_url()` returns `''`, `link` → `empty($link['url'])`, `repeater` → `empty($items)`, `icon` → `''`.

---

## Helpers you can call

All of these are global PHP functions:

| Function | Use case |
|---|---|
| `workernu_t($value)` | Unwrap a translatable value. Returns the current-language string. |
| `workernu_text($field_value, $class)` | Render a `rich_text` field as `<p>`/`<ul>`/`<ol>` with `$class` + auto-appended `--<variant>` modifier. |
| `workernu_image_url($value, $size = 'full')` | Resolve an image field → URL. `$size` is a registered WP image size. |
| `workernu_image_alt($value)` | Resolve an image field → alt text. |
| `workernu_icon($value)` | Resolve an icon field → safe HTML `<i>...</i>` or pre-built snippet. |
| `workernu_section_classes($data, $slug)` | Build the BEM class string: `"section section--<slug> section--<slug>--<modifier>-<value>"`. Echo into the section's outer class. |
| `workernu_lang()` | Returns current language: `'lt'` or `'en'`. |
| `workernu_language_switcher()` | Outputs the language switcher `<ul>` (used in `header.php`). |

---

## The modifier system

Modifiers are display knobs the editor controls per section instance — layout variant, spacing scale, alignment, anything visual. They're defined in `section.php` and become BEM modifier classes on the section wrapper.

**Example.** `section.php` declares:

```php
'modifiers' => [
    ['name' => 'layout',  'type' => 'select', 'options' => ['right' => 'Right', 'left' => 'Left'], 'default' => 'right'],
    ['name' => 'spacing', 'type' => 'select', 'options' => ['tight' => 'Tight', 'normal' => 'Normal', 'loose' => 'Loose'], 'default' => 'normal'],
],
```

`template.php` outputs:

```php
<section class="<?php echo esc_attr(workernu_section_classes($data, 'hero')); ?>">
```

…which renders as:

```html
<section class="section section--hero section--hero--layout-right section--hero--spacing-normal">
```

`style.css` styles each modifier-value combo:

```css
.section--hero--layout-left  .section--hero__inner { grid-template-columns: 40fr 60fr; }
.section--hero--layout-left  .section--hero__media { order: -1; }

.section--hero--spacing-tight  { padding: var(--space-5) 0; }
.section--hero--spacing-normal { padding: var(--space-7) 0; }
.section--hero--spacing-loose  { padding: var(--space-7) 0 calc(var(--space-7) * 1.5); }
```

Adding a new modifier value = one entry in `section.php`'s options + one CSS rule.

---

## The `rich_text` field type

A text field that the **editor** can switch between display modes — paragraph, bulleted list, or numbered list — without touching code. The framework controls the HTML structure; you control the CSS.

**Declared in `section.php` (Julius writes this):**

```php
['name' => 'body', 'type' => 'rich_text', 'translatable' => true, 'rows' => 4]
```

**In the editor:** a textarea plus a "Display as" dropdown right beneath it. The editor puts each item on its own line if they pick bullets/numbered.

**Stored shape:**

```php
$data['body'] = [
    'value'   => ['lt' => "Line 1\nLine 2", 'en' => "..."],
    'display' => 'bullets',
];
```

**In `template.php` you write one line:**

```php
<?php echo workernu_text($data['body'] ?? null, 'section--<slug>__body'); ?>
```

The helper outputs one of:

```html
<p  class="section--<slug>__body section--<slug>__body--paragraph">…</p>
<ul class="section--<slug>__body section--<slug>__body--bullets"><li>…</li>…</ul>
<ol class="section--<slug>__body section--<slug>__body--numbered"><li>…</li>…</ol>
```

Same BEM pattern as section modifiers: **base class + auto-appended `--<variant>` modifier**.

**Your CSS** writes rules for each variant against the modifier classes:

```css
.section--hero__body                 { font-size: 1.125rem; color: var(--color-muted); }
.section--hero__body--paragraph      { max-width: 56ch; line-height: 1.55; }
.section--hero__body--bullets        { padding-left: 1.25rem; }
.section--hero__body--bullets li     { margin-bottom: var(--space-2); }
.section--hero__body--numbered       { padding-left: 1.25rem; }
.section--hero__body--numbered li    { margin-bottom: var(--space-2); }
```

**What you don't control:** the wrapping tag (`<p>` vs `<ul>` vs `<ol>`), the `<li>` structure, escaping. The framework owns those so output is consistent and safe across every section that uses `rich_text`.

**Where to put it in the DOM:** wherever you want. The helper returns a string of HTML — `echo` it inside any wrapper. See `sections/example-hero/template.php` for a working reference.

---

## Design tokens (CSS variables)

Defined in `assets/css/main.css`. Page theme picker swaps them per page.

| Token | What it is |
|---|---|
| `--color-bg` | Page background |
| `--color-fg` | Primary text |
| `--color-muted` | Secondary text |
| `--color-line` | Borders, dividers |
| `--color-accent` | Brand/CTA color |
| `--color-accent-fg` | Text on accent backgrounds |
| `--font-sans` | Default font family |
| `--max-width` | Container max width |
| `--space-1` … `--space-7` | Spacing scale (`.25rem` to `6rem`) |

**Always reference tokens** — never hardcode hex codes or pixel values for color/spacing. That's what lets page-theme swapping work and what keeps the design consistent.

---

## CSS conventions

### BEM with the section as block

```
.section--<slug>             ← block (the section itself)
.section--<slug>__<element>  ← child element inside it
.section--<slug>--<modifier>-<value>  ← modifier class (auto-generated)
```

Example:
```css
.section--hero { ... }
.section--hero__heading { ... }
.section--hero__cta--primary { ... }
.section--hero--align-center .section--hero__content { ... }
```

### Scope to your section

**Never** write a global selector like `.btn { ... }` or `h2 { ... }` in a section's `style.css`. It'll bleed into every other section. If you want shared button styles, ask Julius — they go in `main.css`.

### Use design tokens

```css
/* ❌ Don't */
.section--hero__heading { color: #18181b; padding: 2rem 0; }

/* ✓ Do */
.section--hero__heading { color: var(--color-fg); padding: var(--space-5) 0; }
```

---

## JS / GSAP conventions

GSAP and ScrollTrigger are loaded globally. Your `animations.js` runs after them.

### `data-animate` attribute on the section wrapper

Wrap your section in:

```html
<section class="..." data-animate="hero">
```

Inside, mark elements that should animate individually:

```html
<h1 data-animate-item="heading">...</h1>
<p data-animate-item="sub">...</p>
```

### Handle multiple instances

The editor might add two Hero sections on the same page. **Always use `gsap.utils.toArray()` + `forEach`** so each instance gets its own timeline:

```js
gsap.utils.toArray('[data-animate="hero"]').forEach((hero) => {
    const tl = gsap.timeline({
        scrollTrigger: { trigger: hero, start: 'top 70%' }
    });
    tl.from(hero.querySelector('[data-animate-item="heading"]'), { opacity: 0, y: 40, duration: 0.8 });
    tl.from(hero.querySelector('[data-animate-item="sub"]'),     { opacity: 0, y: 20, duration: 0.6 }, '-=0.4');
});
```

### What NOT to put in section JS

- **Site-wide concerns** (sticky nav, lightbox, video modal, language detection) → those live in `assets/js/main.js`.
- **Cross-section coordination** (highlight current section in nav as you scroll) → also `main.js`, not in any section.
- **Big vendor libraries** (Swiper, Lottie, etc.) → enqueue globally in `functions.php`; ask Julius.

---

## i18n / translatable values

Translatable fields are stored as `['lt' => '...', 'en' => '...']`. Always unwrap with `workernu_t()`:

```php
<?php $heading = workernu_t($data['heading'] ?? ''); ?>
<?php if ($heading): ?>
    <h2><?php echo esc_html($heading); ?></h2>
<?php endif; ?>
```

If you forget to unwrap, the output prints `Array` and PHP emits a warning. Easy to catch in review.

**Hardcoded strings** in your template that need translation: wrap with `__()`:

```php
<?php echo esc_html__('Read more', 'workernu'); ?>
```

These get translated via Polylang or future i18n config — out of scope for daily work, just use `__()` for any literal text.

---

## What NOT to do

- Don't write `page-<slug>.php` page templates. Pages compose sections; there are no per-page chrome files.
- Don't write global CSS in a section's `style.css`.
- Don't query the database or call `get_post_meta()` directly from templates. You get a clean `$data`.
- Don't assume order or count of sections on a page.
- Don't add a body class without coordinating with Julius — body class is owned by the theme/plugin layer.
- Don't bundle CSS/JS with a build tool — files are loaded directly. We don't have a build pipeline.
- Don't introduce a JS framework. Vanilla + GSAP only.

---

## Quick checklist before opening a PR

- [ ] All CSS is scoped to `.section--<slug>...` (no global selectors)
- [ ] All colors and spacing use design tokens, not hardcoded
- [ ] Translatable fields unwrapped via `workernu_t()`
- [ ] Images go through `workernu_image_url()` / `workernu_image_alt()`
- [ ] Outer section class uses `workernu_section_classes($data, '<slug>')`
- [ ] JS uses `gsap.utils.toArray('[data-animate="..."]').forEach(...)` for multi-instance safety
- [ ] No global CSS rules added to `main.css` without discussing with Julius
- [ ] No `wp_enqueue_*` calls in section files — let the framework handle it
