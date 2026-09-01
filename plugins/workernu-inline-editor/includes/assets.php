<?php
namespace WorkerNu\InlineEditor\Assets;

if (!defined('ABSPATH')) exit;

function enqueue(): void {
    if (!is_singular()) return;

    $post_id = get_queried_object_id();
    if (!$post_id || !\WorkerNu\InlineEditor\Mode\is_active($post_id)) return;

    wp_enqueue_style(
        'workernu-inline-editor',
        WORKERNU_INLINE_EDITOR_URL . 'assets/editor.css',
        ['workernu-main'],
        WORKERNU_INLINE_EDITOR_VERSION
    );

    wp_enqueue_script(
        'workernu-inline-editor',
        WORKERNU_INLINE_EDITOR_URL . 'assets/editor.js',
        [],
        WORKERNU_INLINE_EDITOR_VERSION,
        true
    );

    wp_localize_script('workernu-inline-editor', 'wnInlineEditor', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce(\WorkerNu\InlineEditor\Ajax\NONCE_ACTION),
        'postId'  => $post_id,
        'i18n'    => [
            'save'    => __('Save', 'workernu-inline-editor'),
            'cancel'  => __('Cancel', 'workernu-inline-editor'),
            'saving'  => __('Saving…', 'workernu-inline-editor'),
            'error'   => __('Something went wrong.', 'workernu-inline-editor'),
        ],
    ]);
}
