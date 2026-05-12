<?php
namespace WorkerNu\Sections\Registry;

if (!defined('ABSPATH')) exit;

/**
 * Returns the registry of all section types, keyed by slug.
 * Discovered from the active theme's /sections/<name>/section.php files on first call.
 */
function all(): array {
    static $sections = null;
    if ($sections === null) {
        $sections = discover();
    }
    return $sections;
}

function get(string $slug): ?array {
    $sections = all();
    return $sections[$slug] ?? null;
}

/**
 * Scans the theme's /sections/ folder for section.php files. Each file must return an array shaped:
 *   [
 *     'label'       => 'Hero',
 *     'description' => 'Big banner...',
 *     'fields'      => [ ['name'=>'heading','type'=>'text','translatable'=>true], ... ],
 *   ]
 */
function discover(): array {
    $sections = [];
    $dirs = [get_template_directory() . '/sections'];

    // Also support a child theme's /sections/ override
    if (get_stylesheet_directory() !== get_template_directory()) {
        $dirs[] = get_stylesheet_directory() . '/sections';
    }

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) continue;

        foreach (glob($dir . '/*/section.php') ?: [] as $file) {
            $config = include $file;
            if (!is_array($config) || empty($config['label'])) continue;

            $slug = basename(dirname($file));
            $config['slug']      = $slug;
            $config['path']      = dirname($file);
            $config['url']       = section_url($slug);
            $config['fields']    = isset($config['fields'])    && is_array($config['fields'])    ? $config['fields']    : [];
            $config['modifiers'] = isset($config['modifiers']) && is_array($config['modifiers']) ? $config['modifiers'] : [];

            $sections[$slug] = $config;
        }
    }

    return $sections;
}

function section_url(string $slug): string {
    return get_stylesheet_directory_uri() . '/sections/' . $slug;
}
