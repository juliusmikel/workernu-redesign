<?php
namespace WorkerNu\InlineEditor\Render;

if (!defined('ABSPATH')) exit;

/**
 * Swaps the live `_page_sections` meta for the draft copy, but ONLY when:
 *   - we're not in wp-admin (the section builder must always show live data)
 *   - nothing else already resolved this meta read
 *   - the viewer has edit mode active for this post (see Mode\is_active())
 *   - a draft copy actually exists
 * Every other request (logged-out visitors, admins without the toggle on,
 * wp-admin itself) is untouched and reads the real live meta as normal.
 */
function swap_live_for_draft($value, $object_id, $meta_key, $single) {
    if ($value !== null) return $value;
    if (is_admin()) return $value;
    if ($meta_key !== WORKERNU_SECTIONS_META_KEY || !$single) return $value;
    if (!\WorkerNu\InlineEditor\Mode\is_active((int) $object_id)) return $value;

    $draft = get_post_meta((int) $object_id, \WorkerNu\InlineEditor\Draft\META_KEY, true);
    if (!is_array($draft)) return $value;

    return [$draft]; // get_metadata() unwraps single=true via $check[0]
}

/**
 * Flags <body> with wn-edit-mode when the current viewer has the toggle on,
 * so editor.css only ever needs to key off one class.
 */
function flag_body_class(array $classes): array {
    $post_id = get_queried_object_id();
    if ($post_id && \WorkerNu\InlineEditor\Mode\is_active($post_id)) {
        $classes[] = 'wn-edit-mode';
    }
    return $classes;
}
