<?php
/**
 * workernu Inline Editor — theme-facing global API.
 * The only function template.php files should call from this plugin.
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('workernu_inline_editable')) {
    function workernu_inline_editable(array $data, string $field_path, string $type, string $rendered_html, string $raw_value, string $wrapper = 'span'): string {
        return \WorkerNu\InlineEditor\Markup\field(
            (string) ($data['_id'] ?? ''),
            $field_path,
            $type,
            $rendered_html,
            $raw_value,
            $wrapper
        );
    }
}
