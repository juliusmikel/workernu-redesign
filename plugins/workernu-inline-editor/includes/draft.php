<?php
namespace WorkerNu\InlineEditor\Draft;

use function WorkerNu\Sections\Registry\get as get_section_def;
use function WorkerNu\Sections\Fields\sanitize_value;

if (!defined('ABSPATH')) exit;

const META_KEY = '_page_sections_draft';

/**
 * The draft sections array, creating it as a copy of the live array on
 * first call for this post. Always returns an array (possibly empty).
 */
function ensure_draft(int $post_id): array {
    $draft = get_post_meta($post_id, META_KEY, true);
    if (is_array($draft)) return $draft;

    $live = get_post_meta($post_id, WORKERNU_SECTIONS_META_KEY, true);
    $live = is_array($live) ? $live : [];
    update_post_meta($post_id, META_KEY, $live);
    return $live;
}

/**
 * True when this post has a draft copy that differs from its live meta.
 */
function has_pending_changes(int $post_id): bool {
    $draft = get_post_meta($post_id, META_KEY, true);
    if (!is_array($draft)) return false;

    $live = get_post_meta($post_id, WORKERNU_SECTIONS_META_KEY, true);
    $live = is_array($live) ? $live : [];
    return $draft !== $live;
}

/**
 * Copies the draft array over the live meta key — the same effect as
 * clicking Update in wp-admin. Returns false if there's no draft to publish.
 */
function publish(int $post_id): bool {
    $draft = get_post_meta($post_id, META_KEY, true);
    if (!is_array($draft)) return false;

    update_post_meta($post_id, WORKERNU_SECTIONS_META_KEY, $draft);
    return true;
}

/**
 * Writes one field's new value into the draft copy of $post_id's sections,
 * sanitized through the section type's own field schema. $field_path is
 * either a top-level field name ("heading") or a one-level-deep repeater
 * path ("ctas.0.label"). Returns false if the post, section, or field can't
 * be resolved, or the field type isn't editable.
 */
function save_field(int $post_id, string $section_id, string $field_path, $raw_value): bool {
    $sections = ensure_draft($post_id);

    foreach ($sections as $i => $section) {
        if (!is_array($section) || ($section['_id'] ?? '') !== $section_id) continue;

        $type = (string) ($section['_type'] ?? '');
        $def  = get_section_def($type);
        if (!$def) return false;

        $updated = apply_field_path($def, $section, $field_path, $raw_value);
        if ($updated === null) return false;

        $sections[$i] = $updated;
        update_post_meta($post_id, META_KEY, $sections);
        return true;
    }

    return false;
}

function apply_field_path(array $def, array $section, string $field_path, $raw_value): ?array {
    $parts = explode('.', $field_path);
    $lang  = function_exists('workernu_lang') ? workernu_lang() : 'lt';

    if (count($parts) === 1) {
        $name  = $parts[0];
        $field = find_field($def['fields'] ?? [], $name);
        if (!$field || !is_editable_type($field['type'] ?? '')) return null;

        $current        = $section[$name] ?? null;
        $merged         = merge_scalar_value($field, $current, $raw_value, $lang);
        $section[$name] = sanitize_value($field, $merged);
        return $section;
    }

    if (count($parts) === 3) {
        [$repeater_name, $index, $sub_name] = $parts;
        $index = (int) $index;

        $repeater_field = find_field($def['fields'] ?? [], $repeater_name);
        if (!$repeater_field || ($repeater_field['type'] ?? '') !== 'repeater') return null;

        $sub_field = find_field($repeater_field['fields'] ?? [], $sub_name);
        if (!$sub_field || !is_editable_type($sub_field['type'] ?? '')) return null;

        $items = is_array($section[$repeater_name] ?? null) ? $section[$repeater_name] : [];
        if (!isset($items[$index]) || !is_array($items[$index])) return null;

        $current                       = $items[$index][$sub_name] ?? null;
        $items[$index][$sub_name]      = merge_scalar_value($sub_field, $current, $raw_value, $lang);
        $section[$repeater_name]       = sanitize_value($repeater_field, $items);
        return $section;
    }

    return null;
}

function find_field(array $fields, string $name): ?array {
    foreach ($fields as $f) {
        if (($f['name'] ?? null) === $name) return $f;
    }
    return null;
}

function is_editable_type(string $type): bool {
    return in_array($type, ['text', 'textarea', 'rich_text'], true);
}

/**
 * Merges a new raw leaf value into a field's existing stored value:
 *   - rich_text: only the `value` key changes, `display` is preserved; if
 *     translatable, only the current language's sub-key changes.
 *   - translatable text/textarea: only the current language's key changes.
 *   - plain text/textarea: the whole value is replaced.
 * The result is NOT sanitized yet — the caller always runs it through
 * sanitize_value() afterward.
 */
function merge_scalar_value(array $field, $current, $raw_value, string $lang) {
    $type          = $field['type'] ?? 'text';
    $translatable  = !empty($field['translatable']);

    if ($type === 'rich_text') {
        $current = is_array($current) ? $current : [];
        $display = $current['display'] ?? '';
        $value   = $current['value']   ?? ($translatable ? [] : '');

        if ($translatable) {
            $value         = is_array($value) ? $value : [];
            $value[$lang]  = (string) $raw_value;
        } else {
            $value = (string) $raw_value;
        }

        return ['value' => $value, 'display' => $display];
    }

    if ($translatable) {
        $value        = is_array($current) ? $current : [];
        $value[$lang] = (string) $raw_value;
        return $value;
    }

    return (string) $raw_value;
}
