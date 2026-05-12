<?php
namespace WorkerNu\PageTheme\Registry;

if (!defined('ABSPATH')) exit;

/**
 * Returns all registered themes, keyed by slug.
 *
 * Themes are registered by the active theme via the `workernu_themes` filter:
 *
 *   add_filter('workernu_themes', fn($t) => $t + [
 *       'midnight' => ['label' => 'Midnight', 'swatch' => '#0a0a0b'],
 *   ]);
 *
 * Each entry shape:
 *   [
 *     'label'  => 'Display name',
 *     'swatch' => '#hex or any valid CSS background value (gradient OK)',
 *   ]
 */
function all(): array {
    $themes = apply_filters('workernu_themes', []);
    if (!is_array($themes)) return [];
    return $themes;
}

function get(string $slug): ?array {
    return all()[$slug] ?? null;
}

function exists(string $slug): bool {
    return get($slug) !== null;
}
