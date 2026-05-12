# workernu Sections

Section-based page composition engine for the workernu theme.

Each page is composed of a sequence of **sections**. A section is one folder in the active theme:

```
themes/workernu/sections/<section-name>/
├── section.php       ← declaration (fields, label)
├── template.php      ← outputs the HTML
├── style.css         ← scoped CSS (auto-enqueued only when section is on the page)
└── animations.js     ← (optional) GSAP hooks, auto-enqueued like style.css
```

The plugin auto-discovers sections — no PHP registration calls needed. Drop a folder in, restart admin, and it appears in the **Page Sections** meta box dropdown.

---

## Adding a section

### 1. `section.php` — declaration

Return an associative array describing the label, optional description, and field list.

```php
<?php
return [
    'label'       => 'FAQ',
    'description' => 'Accordion list of questions and answers.',
    'fields'      => [
        ['name' => 'heading',  'type' => 'text',     'label' => 'Heading', 'translatable' => true],
        ['name' => 'subhead',  'type' => 'textarea', 'label' => 'Subheading', 'translatable' => true],
        ['name' => 'cta',      'type' => 'link',     'label' => 'Button',  'translatable' => true],
        ['name' => 'compact',  'type' => 'boolean',  'label' => 'Compact layout', 'hint' => 'Tighter spacing'],
    ],
];
```

### 2. `template.php` — the HTML

Receives a `$data` array shaped like the section's stored values (after sanitization).
Translatable fields are arrays — unwrap them with `workernu_t()`.

```php
<section class="section section--faq" data-animate="faq">
    <?php $heading = workernu_t($data['heading'] ?? ''); ?>
    <?php if ($heading): ?>
        <h2 class="section__heading"><?php echo esc_html($heading); ?></h2>
    <?php endif; ?>

    <?php $subhead = workernu_t($data['subhead'] ?? ''); ?>
    <?php if ($subhead): ?>
        <p class="section__sub"><?php echo esc_html($subhead); ?></p>
    <?php endif; ?>

    <?php $cta = $data['cta'] ?? null; ?>
    <?php if (!empty($cta['url'])): ?>
        <a href="<?php echo esc_url($cta['url']); ?>"
           class="btn"
           target="<?php echo esc_attr($cta['target'] ?? '_self'); ?>">
            <?php echo esc_html(workernu_t($cta['label'])); ?>
        </a>
    <?php endif; ?>
</section>
```

### 3. `style.css`

Plain CSS. Scope class names to your section to avoid bleed.

```css
.section--faq { padding: var(--space-6) 0; }
.section--faq .section__heading { font-size: 2rem; }
```

### 4. `animations.js` (optional)

When GSAP is added (later phase), use the `data-animate` convention:

```js
gsap.utils.toArray('[data-animate="faq"]').forEach((faq) => {
  gsap.from(faq.querySelectorAll('[data-animate-item]'), {
    opacity: 0, y: 20, stagger: 0.05,
    scrollTrigger: { trigger: faq, start: 'top 75%' },
  });
});
```

---

## Field types

| Type | Stored as | Translatable? |
|---|---|---|
| `text` | string (or `{lt,en}` if translatable) | yes |
| `textarea` | string (or `{lt,en}`) | yes |
| `image` | attachment ID (int) | no |
| `link` | `{label, url, target}` (label translatable) | yes (label only) |
| `select` | string (one of the keys in `options`) | no |
| `boolean` | bool | no |
| `number` | int or float | no |

### Field config keys

```php
[
    'name'         => 'heading',         // required — the array key in $data
    'type'         => 'text',            // required — must match a registered type
    'label'        => 'Heading',         // shown in the admin form
    'translatable' => true,              // applies to text/textarea/link
    'rows'         => 4,                 // textarea only
    'options'      => ['a' => 'A', 'b' => 'B'],  // select only
    'min'          => 1, 'max' => 10, 'step' => 1,  // number only
    'hint'         => 'Helper text',     // boolean — shown next to the checkbox
]
```

---

## Theme helpers

Provided as global functions for theme templates:

| Function | What it does |
|---|---|
| `workernu_render_sections($post_id)` | Iterate the post's sections and include each template |
| `workernu_enqueue_section_assets($post_id)` | Auto-enqueue style.css + animations.js for every section type on the page |
| `workernu_t($value, $lang = null)` | Resolve translatable value → string for current language |
| `workernu_lang()` | Current language code: `'lt'` or `'en'` |
| `workernu_language_switcher()` | Output the two-language switcher (`<ul>`) |
| `workernu_image_url($value, $size = 'full')` | Resolve image field → URL |
| `workernu_image_alt($value)` | Resolve image field → alt text |

---

## Data model

Sections live in a single post meta row: `_page_sections`. The value is a PHP-serialized array:

```php
[
    [
        '_type'   => 'faq',
        '_id'     => 'faq-a1b2c3d4',         // stable per-card ID for client-side state
        'heading' => ['lt' => 'DUK', 'en' => 'FAQ'],
        'subhead' => ['lt' => '...', 'en' => '...'],
        'cta'     => [
            'label'  => ['lt' => 'Pradėti', 'en' => 'Get started'],
            'url'    => '/signup',
            'target' => '_self',
        ],
        'compact' => false,
    ],
    // ... more sections, in order ...
]
```

Editor-side, sections are added/reordered/removed via the **Page Sections** meta box on every Page edit screen. Drag-handles for reorder, `+` to add a new section from the dropdown, `×` to remove, `▾` to collapse.

The global language tab at the top of the meta box (LT / EN) toggles which language's input is currently visible for every translatable field. Switching language doesn't lose unsaved values — both languages' values stay in the DOM, only visibility changes.

---

## Internationalization

URL prefix routing — Lithuanian is the default, English uses `/en/`:

| URL | Language |
|---|---|
| `/` | LT |
| `/about` | LT |
| `/en/` | EN |
| `/en/about` | EN |

Detection happens once per request, cached in `current_lang()`. Templates use `workernu_t()` to pluck the right value out of a `['lt' => ..., 'en' => ...]` array.

The language switcher (`workernu_language_switcher()` in `header.php`) just toggles `/en/` on or off in the current URL — same post, different language values rendered.

`hreflang` link tags are output in `<head>` automatically.

---

## Opt other post types in

By default the meta box appears on Pages. To enable it on a custom post type:

```php
add_filter('workernu_sections_post_types', function ($types) {
    $types[] = 'case_study';
    return $types;
});
```

---

## Conventions

- **`data-animate="<section-slug>"`** on the section wrapper, and **`data-animate-item="<role>"`** on inner elements. JS attaches GSAP timelines by selector.
- **BEM-ish CSS:** `.section--<slug>`, `.section__<part>`. Scope everything to avoid bleed.
- **`esc_html()` / `esc_url()` / `esc_attr()`** on every output. Don't trust stored values.
- **Translatable fields:** always unwrap with `workernu_t()` before output.
