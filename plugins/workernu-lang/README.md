# workernu Lang

URL-prefix multilingual layer for workernu.

## URL scheme

| URL | Language |
|---|---|
| `/` | Lithuanian (default) |
| `/about/` | Lithuanian |
| `/en/` | English |
| `/en/about/` | English |

Same WP post serves both — only the language values resolved differ.

## Theme API

| Function | What it does |
|---|---|
| `workernu_t($value, $lang = null)` | Resolve `['lt' => ..., 'en' => ...]` to current language's string. Scalar values pass through. |
| `workernu_lang()` | Current language code (`'lt'` or `'en'`) |
| `workernu_language_switcher()` | Output the `<ul class="lang-switcher">` switcher |
| `workernu_languages()` | All supported language codes |
| `workernu_default_language()` | The default code (used as fallback) |

## Translatable values

By convention, any string that can vary by language is stored as an array shaped:

```php
['lt' => 'Sveiki', 'en' => 'Hello']
```

`workernu_t()` picks the right one. If a translation is missing, falls back to the default language. If passed a scalar (no translation), passes through unchanged.

## Switcher styling

The plugin outputs unstyled HTML:

```html
<ul class="lang-switcher">
  <li class="is-active"><a href="/about/" hreflang="lt">LT</a></li>
  <li><a href="/en/about/" hreflang="en">EN</a></li>
</ul>
```

Style it in the theme's CSS.

## SEO

`hreflang` link tags emit automatically on every singular/archive page:

```html
<link rel="alternate" hreflang="lt" href="...">
<link rel="alternate" hreflang="en" href="...">
<link rel="alternate" hreflang="x-default" href="...">
```

## Adding a third language

Edit `includes/lang.php`:

1. Add the code to `LANGUAGES` (e.g. `['lt', 'en', 'de']`).
2. Add a rewrite rule mapping `/de/...` to the relevant query.
3. Extend `build_switcher_urls()` and `hreflang_tags()` for the new code.
4. Flush rewrites (deactivate + reactivate the plugin).
