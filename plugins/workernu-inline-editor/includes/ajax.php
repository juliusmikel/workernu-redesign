<?php
namespace WorkerNu\InlineEditor\Ajax;

use function WorkerNu\InlineEditor\Draft\save_field;
use function WorkerNu\InlineEditor\Draft\publish;
use function WorkerNu\InlineEditor\Draft\has_pending_changes;

if (!defined('ABSPATH')) exit;

const NONCE_ACTION = 'workernu_inline_editor';

function require_access(int $post_id): void {
    if (!check_ajax_referer(NONCE_ACTION, 'nonce', false)) {
        wp_send_json_error(['message' => __('Session expired — reload the page.', 'workernu-inline-editor')]);
    }
    if ($post_id <= 0 || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => __('Not allowed.', 'workernu-inline-editor')]);
    }
}

function handle_save_draft(): void {
    $post_id = (int) ($_POST['post_id'] ?? 0);
    require_access($post_id);

    $section_id = sanitize_text_field(wp_unslash((string) ($_POST['section_id'] ?? '')));
    $field_path = sanitize_text_field(wp_unslash((string) ($_POST['field_path'] ?? '')));
    $value      = wp_unslash((string) ($_POST['value'] ?? ''));

    if ($section_id === '' || $field_path === '') {
        wp_send_json_error(['message' => __('Missing field.', 'workernu-inline-editor')]);
    }

    if (!save_field($post_id, $section_id, $field_path, $value)) {
        wp_send_json_error(['message' => __('Could not save that field.', 'workernu-inline-editor')]);
    }

    wp_send_json_success(['has_pending' => has_pending_changes($post_id)]);
}

function handle_publish(): void {
    $post_id = (int) ($_POST['post_id'] ?? 0);
    require_access($post_id);

    if (!publish($post_id)) {
        wp_send_json_error(['message' => __('Nothing to publish.', 'workernu-inline-editor')]);
    }

    wp_send_json_success(['has_pending' => false]);
}
