# workernu Page Theme

Per-page color theme selector. The plugin owns the meta box, save handler, and body-class output. The active theme owns the list of themes and their CSS.

## How it works

1. Active theme registers themes via the `workernu_themes` filter (label + swatch).
2. Page editor picks one in the side meta box. Stored as `_workernu_page_theme` post meta.
3. On the frontend, `body` gets the class `theme--<slug>`.
4. Theme's CSS defines the design tokens for each theme on `body.theme--<slug> { --color-bg: ... }`.

All sections use the same tokens, so the whole page reskins automatically.

## Registering a theme

In `themes/<your-theme>/functions.php`:

```php
add_filter('workernu_themes', function ($themes) {
    return array_merge($themes, [
        'default'  => ['label' => 'Default',  'swatch' => '#ffffff'],
        'midnight' => ['label' => 'Midnight', 'swatch' => '#0a0a0b'],
        'sky'      => ['label' => 'Sky',      'swatch' => 'linear-gradient(180deg, #e0f2fe, #ffffff)'],
    ]);
});
```

Then add matching CSS in your theme's stylesheet:

```css
body.theme--default {
    --color-bg:        #ffffff;
    --color-fg:        #18181b;
    --color-muted:     #71717a;
    --color-line:      #e4e4e7;
    --color-accent:    #18181b;
    --color-accent-fg: #ffffff;
}

body.theme--midnight {
    --color-bg:        #0a0a0b;
    --color-fg:        #fafafa;
    --color-muted:     #a1a1aa;
    --color-line:      #27272a;
    --color-accent:    #fafafa;
    --color-accent-fg: #0a0a0b;
}
```

## Theme API

| Function | What it returns |
|---|---|
| `workernu_page_theme($post_id = null)` | Current page's theme slug (or `'default'`) |
| `workernu_page_themes()` | All registered themes, keyed by slug |

## Opting other post types in

```php
add_filter('workernu_page_theme_post_types', fn($pts) => array_merge($pts, ['case_study']));
```
