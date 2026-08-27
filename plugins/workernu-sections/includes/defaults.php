<?php
/**
 * Site-wide default content for sections that opt into `'content_defaults' => true`
 * (calculator, pricing, testimonials, people). Storage + resolution live here,
 * in workernu-sections, since the shape must exactly match each section's own
 * field schema; the admin UI for editing a default lives in workernu-settings
 * (Settings → WorkerNu), reusing Fields\render_field()/sanitize_value() from
 * this plugin — see workernu-settings/includes/section-defaults-admin.php.
 */
namespace WorkerNu\Sections\Defaults;

use function WorkerNu\Sections\Registry\get as get_section;

if (!defined('ABSPATH')) exit;

function option_key(string $section_type): string {
    return 'workernu_section_default_' . $section_type;
}

/**
 * The stored default field values for a section type, or [] if never saved.
 * Shape matches a section instance's fields (no _type/_id/modifiers).
 */
function get_default(string $section_type): array {
    $value = get_option(option_key($section_type), []);
    return is_array($value) ? $value : [];
}

function save_default(string $section_type, array $clean): void {
    update_option(option_key($section_type), $clean);
}

/**
 * If $data opted into the site-wide default (content_source === 'default'),
 * overlay the stored default's field values onto it before rendering/schema.
 * Only real content fields are overlaid — content_source itself, the
 * settings_link pseudo-field, _type/_id, and every modifier are left as-is,
 * so display settings (alignment, columns, layout, …) always stay per-page
 * even when the content is shared.
 *
 * Called once per section from Render\render_sections() and
 * Schema\contribute_section_schemas() — no template.php needs to know this
 * mechanism exists at all.
 */
function resolve(array $data): array {
    if (($data['content_source'] ?? 'custom') !== 'default') return $data;

    $type = (string) ($data['_type'] ?? '');
    $def  = get_section($type);
    if (!$def) return $data;

    $default = get_default($type);
    if (!$default) return $data;

    foreach ($def['fields'] as $field) {
        $name = $field['name'] ?? null;
        if (!$name || $name === 'content_source') continue;
        if (($field['type'] ?? '') === 'settings_link') continue;
        if (array_key_exists($name, $default)) {
            $data[$name] = $default[$name];
        }
    }

    return $data;
}
