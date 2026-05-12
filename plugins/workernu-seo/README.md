# workernu SEO

Minimal SEO layer for workernu.

## Frontend output (`<head>`)

- `<title>` — uses post's SEO title if set, else post title, formatted via `{title} | {site_name}`
- `<meta name="description">` — post override → post excerpt → site default → blog tagline
- `<link rel="canonical">` — current URL
- `<meta name="robots">` — emitted as `noindex,nofollow` when toggled per page
- OpenGraph (`og:type`, `og:title`, `og:description`, `og:url`, `og:site_name`, `og:locale`, `og:image`)
- Twitter card (`summary_large_image` if OG image exists, else `summary`)
- JSON-LD: Organization (site-wide) + WebPage (per page)

## Per-post fields

Every Page (and Post — configurable) gets an **SEO** meta box with:

- SEO title (translatable: LT + EN)
- Meta description (translatable)
- Social share image (OG override; falls back to featured image, then site default)
- Hide from search engines (noindex,nofollow)

Stored as post meta:
- `_workernu_seo_title` — `['lt' => ..., 'en' => ...]`
- `_workernu_seo_description` — `['lt' => ..., 'en' => ...]`
- `_workernu_seo_og_image` — attachment ID
- `_workernu_seo_noindex` — `1` if checked

## Site settings (SEO menu in wp-admin)

A single page at **SEO** in the admin menu:

- **Sitemap** — link to `/wp-sitemap.xml` (WP core generates this automatically since 5.5)
- **robots.txt** — textarea; if set, replaces WP's default output. Leave blank for default.
- **llms.txt** — textarea; served at `/llms.txt`. See [llmstxt.org](https://llmstxt.org/) for the format. AI crawlers (Anthropic, OpenAI) use this to discover content.
- **Defaults** — title format, default LT/EN description
- **Organization (JSON-LD)** — name, logo URL, social profile URLs

## Opting other post types in

```php
// Show SEO meta box on a custom post type:
add_filter('workernu_seo_post_types', function ($types) {
    $types[] = 'case_study';
    return $types;
});
```

## URLs served

| URL | What it is |
|---|---|
| `/robots.txt` | WP-generated, optionally overridden by saved content |
| `/llms.txt` | Saved from the SEO settings page |
| `/wp-sitemap.xml` | WordPress core sitemap (multilingual-aware via `workernu-lang`'s hreflang) |

## What's deliberately NOT in here

- SEO "analysis" (readability, keyword density) — pseudo-science
- Internal linking suggestions
- Redirect manager — use the dedicated [Redirection](https://wordpress.org/plugins/redirection/) plugin
- Bulk-editing SEO meta — possible if needed later, but not in v0.1
- Schema for specific post types beyond WebPage — extend `Output\build_json_ld()` if you need Article, Product, Event, etc.

## Dependency

Requires `workernu-lang` for `workernu_t()` (resolving translatable values).
