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

    if (!$active) return;

    $pending = \WorkerNu\InlineEditor\Draft\has_pending_changes($post_id);

    $bar->add_node([
        'id'     => 'wn-inline-publish',
        'parent' => 'wn-inline-edit-toggle',
        'title'  => $pending ? __('Publish changes', 'workernu-inline-editor') : __('No changes to publish', 'workernu-inline-editor'),
        'href'   => '#',
        'meta'   => [
            'class' => 'wn-admin-bar-publish' . ($pending ? '' : ' is-disabled'),
        ],
    ]);
}
