<?php
namespace WorkerNu\PageTheme\Save;

use WorkerNu\PageTheme\MetaBox;
use function WorkerNu\PageTheme\Registry\exists;

if (!defined('ABSPATH')) exit;

function handle(int $post_id, \WP_Post $post): void {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST[MetaBox\NONCE_FIELD]) || !wp_verify_nonce($_POST[MetaBox\NONCE_FIELD], MetaBox\NONCE_ACTION)) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $post_types = apply_filters('workernu_page_theme_post_types', ['page']);
    if (!in_array($post->post_type, $post_types, true)) return;

    $slug = (string) ($_POST['workernu_page_theme'] ?? '');

    if ($slug === '' || $slug === WORKERNU_PAGE_THEME_DEFAULT || !exists($slug)) {
        delete_post_meta($post_id, WORKERNU_PAGE_THEME_META_KEY);
        return;
    }

    update_post_meta($post_id, WORKERNU_PAGE_THEME_META_KEY, sanitize_key($slug));
}
