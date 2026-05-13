<?php
/**
 * workernu Sections — theme-facing global API.
 *
 * These functions are the only public surface theme templates should call from this plugin.
 * Language helpers live in workernu-lang and are loaded from there.
 */

if (!defined('ABSPATH')) exit;

/**
 * Render every section attached to a post, in order.
 *
 *   workernu_render_sections(get_the_ID());
 */
function workernu_render_sections(int $post_id): void {
    \WorkerNu\Sections\Render\render_sections($post_id);
}

/**
 * Enqueue the style.css and animations.js for every section type present on a post.
 */
function workernu_enqueue_section_assets(int $post_id): void {
    \WorkerNu\Sections\Render\enqueue_section_assets($post_id);
}

/**
 * Resolve an image field value to a URL string.
 * Accepts either a raw attachment ID or an array shaped { id, url, ... }.
 */
function workernu_image_url($value, string $size = 'full'): string {
    if (is_array($value) && isset($value['url'])) return (string) $value['url'];
    if (is_numeric($value)) return (string) (wp_get_attachment_image_url((int) $value, $size) ?: '');
    return '';
}

/**
 * Resolve an image field value to an alt-text string.
 */
function workernu_image_alt($value): string {
    if (is_array($value) && isset($value['alt'])) return (string) $value['alt'];
    if (is_numeric($value)) return (string) get_post_meta((int) $value, '_wp_attachment_image_alt', true);
    return '';
}

/**
 * Build the BEM class string for a section, based on its modifier values.
 *
 *   <section class="<?php echo esc_attr(workernu_section_classes($data, 'hero')); ?>">
 *
 * Produces something like:
 *   "section section--hero section--hero--layout-right section--hero--spacing-normal"
 *
 * The base classes (.section + .section--<slug>) are always present.
 * One BEM modifier class per modifier value, named: section--<slug>--<modifier>-<value>
 */
function workernu_section_classes(array $data, string $slug): string {
    $classes = ['section', 'section--' . sanitize_html_class($slug)];

    $def = \WorkerNu\Sections\Registry\get($slug);
    if (!$def) return implode(' ', $classes);

    foreach ($def['modifiers'] ?? [] as $mod) {
        $name = $mod['name'] ?? null;
        if (!$name) continue;
        $value = $data[$name] ?? ($mod['default'] ?? null);
        if ($value === null || $value === '' || $value === false) continue;
        if (is_array($value)) continue; // skip non-scalar modifier values
        $classes[] = 'section--' . sanitize_html_class($slug) . '--' . sanitize_html_class($name) . '-' . sanitize_html_class((string) $value);
    }

    return implode(' ', $classes);
}

/**
 * Render a rich_text field value to safe HTML.
 *
 * Receives the field value (an array shaped { value, display }) and a base class.
 * Outputs <p>/<ul>/<ol> with the class plus an auto-appended modifier:
 *   base + " " + base + "--" + display
 *
 *   echo workernu_text($data['body'], 'section--hero__body');
 *
 * Produces one of:
 *   <p  class="section--hero__body section--hero__body--paragraph">...</p>
 *   <ul class="section--hero__body section--hero__body--bullets"><li>...</li>...</ul>
 *   <ol class="section--hero__body section--hero__body--numbered"><li>...</li>...</ol>
 *
 * Lists split the value on newlines; blank lines are dropped.
 */
function workernu_text($field_value, string $class = ''): string {
    if (!is_array($field_value)) return '';

    $value   = $field_value['value']   ?? '';
    $display = (string) ($field_value['display'] ?? 'paragraph');
    $text    = function_exists('workernu_t') ? workernu_t($value) : (is_array($value) ? '' : (string) $value);

    if ($text === '') return '';

    $classes = $class !== ''
        ? $class . ' ' . $class . '--' . sanitize_html_class($display)
        : '';
    $attr = $classes !== '' ? ' class="' . esc_attr($classes) . '"' : '';

    if ($display === 'bullets' || $display === 'numbered') {
        $items = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $text) ?: [])));
        if (!$items) return '';
        $tag = $display === 'numbered' ? 'ol' : 'ul';
        return '<' . $tag . $attr . '><li>' . implode('</li><li>', array_map('esc_html', $items)) . '</li></' . $tag . '>';
    }

    return '<p' . $attr . '>' . nl2br(esc_html($text)) . '</p>';
}

/**
 * Render an icon field value to safe HTML.
 *
 * Accepts either:
 *   - A class string like "fa-solid fa-star" → wrapped in <i class="..."></i>
 *   - A pre-built HTML snippet like <i class="..."></i> or <svg>...</svg> → returned as-is
 *
 * Already sanitized at save time, so output is safe to echo directly:
 *   echo workernu_icon($data['badge_icon']);
 */
function workernu_icon($value): string {
    if (is_array($value)) {
        $value = function_exists('workernu_t') ? workernu_t($value) : '';
    }
    $value = trim((string) $value);
    if ($value === '') return '';

    if (str_starts_with($value, '<')) {
        // Already an HTML snippet (sanitized via wp_kses at save time).
        return $value;
    }
    // Bare class string → wrap in an <i>.
    return '<i class="' . esc_attr($value) . '" aria-hidden="true"></i>';
}
