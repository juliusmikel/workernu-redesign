<?php
namespace WorkerNu\Sections\Save;

use WorkerNu\Sections\Fields;
use WorkerNu\Sections\MetaBox;
use function WorkerNu\Sections\Registry\get as get_section;

if (!defined('ABSPATH')) exit;

/**
 * Persist the section list on post save.
 * Triggered by `save_post` (any post type). We bail unless the post type opted in
 * via the `workernu_sections_post_types` filter.
 */
function handle(int $post_id, \WP_Post $post): void {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST[MetaBox\NONCE_FIELD]) || !wp_verify_nonce($_POST[MetaBox\NONCE_FIELD], MetaBox\NONCE_ACTION)) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $post_types = apply_filters('workernu_sections_post_types', ['page']);
    if (!in_array($post->post_type, $post_types, true)) return;

    $raw = $_POST['workernu_sections'] ?? [];
    if (!is_array($raw) || empty($raw)) {
        delete_post_meta($post_id, WORKERNU_SECTIONS_META_KEY);
        return;
    }

    $clean = [];
    foreach ($raw as $section_raw) {
        if (!is_array($section_raw)) continue;
        $type = (string) ($section_raw['_type'] ?? '');
        $def  = get_section($type);
        if (!$def) continue;

        $section = [
            '_type' => $type,
            '_id'   => sanitize_id((string) ($section_raw['_id'] ?? ''), $type),
        ];

        foreach ($def['fields'] as $field) {
            $name = $field['name'] ?? null;
            if (!$name) continue;
            $section[$name] = Fields\sanitize_value($field, $section_raw[$name] ?? null);
        }

        foreach ($def['modifiers'] ?? [] as $mod) {
            $name = $mod['name'] ?? null;
            if (!$name) continue;
            $section[$name] = Fields\sanitize_value($mod, $section_raw[$name] ?? ($mod['default'] ?? null));
        }

        $clean[] = $section;
    }

    if ($clean) {
        update_post_meta($post_id, WORKERNU_SECTIONS_META_KEY, $clean);
    } else {
        delete_post_meta($post_id, WORKERNU_SECTIONS_META_KEY);
    }
}

function sanitize_id(string $id, string $type): string {
    $clean = preg_replace('/[^a-zA-Z0-9_\-]/', '', $id);
    if ($clean === '' || $clean === '__ID__') {
        return $type . '-' . bin2hex(random_bytes(4));
    }
    return $clean;
}
