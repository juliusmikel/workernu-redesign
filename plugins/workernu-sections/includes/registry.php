<?php
namespace WorkerNu\Sections\Registry;

if (!defined('ABSPATH')) exit;

/**
 * Pseudo-field name for the "edit defaults in Settings → WorkerNu" link
 * injected into sections that opt into `'content_defaults' => true`. Not a
 * real content field — carries no stored value (see Fields\sanitize_settings_link
 * and Defaults\resolve, both of which skip it explicitly).
 */
const CONTENT_DEFAULTS_LINK_FIELD = '_content_defaults_link';

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

            // Sections opting into `'content_defaults' => true` get two fields
            // injected at the very top: a content_source toggle (custom | default)
            // and a link to the matching "Settings → WorkerNu" defaults page. The
            // meta box wraps everything else in a show_if group keyed to
            // content_source (see MetaBox\render_card) so the admin only ever
            // sees either the full field set or just the link, never both.
            if (!empty($config['content_defaults'])) {
                array_splice($config['fields'], 0, 0, [
                    [
                        'name'      => 'content_source',
                        'type'      => 'select',
                        'label'     => 'Content',
                        'render_as' => 'buttons',
                        'options'   => ['custom' => 'Custom for this page', 'default' => 'Site default'],
                        'default'   => 'custom',
                    ],
                    [
                        'name'          => CONTENT_DEFAULTS_LINK_FIELD,
                        'type'          => 'settings_link',
                        'show_if'       => ['content_source' => 'default'],
                        'url'           => admin_url('admin.php?page=workernu-' . $slug . '-defaults'),
                        'label_section' => $config['label'],
                    ],
                ]);
            }

            // Let other code (e.g. global modifiers like margin) extend the
            // loaded definition before it's frozen into the registry.
            $config = apply_filters('workernu_section_definition', $config, $slug);

            $sections[$slug] = $config;
        }
    }

    return $sections;
}

function section_url(string $slug): string {
    return get_stylesheet_directory_uri() . '/sections/' . $slug;
}
