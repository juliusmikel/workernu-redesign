<?php
namespace WorkerNu\InlineEditor\Markup;

if (!defined('ABSPATH')) exit;

/**
 * Wraps $rendered_html with the data attributes editor.js needs, but only
 * when the current viewer is in active edit mode for the current post —
 * otherwise returns $rendered_html completely unchanged (no added markup
 * for regular visitors, or for admins not currently editing).
 *
 * $field_path addresses the field within its section: "heading" for a
 * top-level field, "ctas.0.label" for a repeater item's sub-field.
 * $raw_value is the actual stored (already language-resolved) text — used
 * to seed the edit input, since $rendered_html may have been transformed
 * (e.g. rich_text's bullet/numbered HTML) in a way plain text extraction
 * in the browser can't reliably reverse.
 */
function field(string $section_id, string $field_path, string $type, string $rendered_html, string $raw_value, string $wrapper = 'span'): string {
    if ($section_id === '' || !\WorkerNu\InlineEditor\Mode\is_active((int) get_the_ID())) {
        return $rendered_html;
    }

    $wrapper = in_array($wrapper, ['span', 'div'], true) ? $wrapper : 'span';

    // The pencil is a <span role="button">, not a <button> — several call
    // sites (e.g. CTA labels) render inside an <a>, and a real <button>
    // nested inside an <a> is invalid HTML (interactive content inside
    // interactive content), which visibly distorted the parent .btn's box.
    return sprintf(
        '<%1$s class="wn-editable" data-wn-field="%2$s" data-wn-type="%3$s" data-wn-raw="%4$s">'
        . '<span class="wn-editable__content">%5$s</span>'
        . '<span role="button" tabindex="0" class="wn-editable__pencil" aria-label="%6$s"><i class="fa-solid fa-pencil" aria-hidden="true"></i></span>'
        . '</%1$s>',
        $wrapper,
        esc_attr($section_id . '::' . $field_path),
        esc_attr($type),
        esc_attr($raw_value),
        $rendered_html,
        esc_attr__('Edit this text', 'workernu-inline-editor')
    );
}
