<?php
namespace WorkerNu\InlineEditor\AdminBar;

if (!defined('ABSPATH')) exit;

function register(\WP_Admin_Bar $bar): void {
    if (is_admin() || !is_singular()) return;

    $post_id = get_queried_object_id();
    if (!$post_id || !current_user_can('edit_post', $post_id)) return;

    $active = \WorkerNu\InlineEditor\Mode\is_active($post_id);

    $bar->add_node([
        'id'    => 'wn-inline-edit-toggle',
        'title' => $active ? __('Exit Edit Text', 'workernu-inline-editor') : __('Edit Text', 'workernu-inline-editor'),
        'href'  => \WorkerNu\InlineEditor\Mode\toggle_url(!$active),
    ]);
}
