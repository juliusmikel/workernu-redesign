<?php
namespace WorkerNu\InlineEditor\Render;

if (!defined('ABSPATH')) exit;

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
