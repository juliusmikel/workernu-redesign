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
 * Resolve an image field value to a single attachment ID.
 * Handles three shapes:
 *   - int / numeric string  — plain ID
 *   - { id, url, ... }      — older repeater shape
 *   - { lt: id, en: id }    — translatable shape, resolved via workernu_t()
 */
function workernu_image_id($value): int {
    if (is_array($value)) {
        // Translatable shape: keys match defined language codes.
        if (defined('\\WorkerNu\\Lang\\LANGUAGES')) {
            $langs = \WorkerNu\Lang\LANGUAGES;
            foreach ($langs as $lang) {
                if (array_key_exists($lang, $value)) {
                    $resolved = function_exists('workernu_t') ? workernu_t($value) : ($value[$lang] ?? 0);
                    return (int) $resolved;
                }
            }
        }
        // Legacy shape: { id, url }.
        if (isset($value['id'])) return (int) $value['id'];
        return 0;
    }
    return is_numeric($value) ? (int) $value : 0;
}

/**
 * Resolve an image field value to a URL string.
 * Accepts a raw attachment ID, the legacy { id, url } shape, or a translatable map.
 */
function workernu_image_url($value, string $size = 'full'): string {
    if (is_array($value) && isset($value['url']) && !isset($value[\WorkerNu\Lang\DEFAULT_LANG])) {
        return (string) $value['url'];
    }
    $id = workernu_image_id($value);
    return $id ? (string) (wp_get_attachment_image_url($id, $size) ?: '') : '';
}

/**
 * Resolve an image field value to an alt-text string.
 */
function workernu_image_alt($value): string {
    if (is_array($value) && isset($value['alt']) && !isset($value[\WorkerNu\Lang\DEFAULT_LANG])) {
        return (string) $value['alt'];
    }
    $id = workernu_image_id($value);
    return $id ? (string) get_post_meta($id, '_wp_attachment_image_alt', true) : '';
}

/**
 * Build the full set of <img> attributes for an attachment — src, srcset, sizes,
 * width, height, alt, loading, decoding — as one ready-to-print string.
 *
 *   <img class="…" <?php echo workernu_image_attrs($id, 'large'); ?>>
 *
 * Outputs:
 *   src="…" alt="…" width="…" height="…" srcset="…" sizes="…" loading="lazy" decoding="async"
 *
 * Returns '' if the attachment is missing — caller should null-check the parent
 * markup. Available `$opts`:
 *   - alt      (string)      override the attachment's alt
 *   - loading  (string)      'lazy' (default) | 'eager'
 *   - sizes    (string)      override the auto-computed `sizes` attribute
 *   - fetchpriority (string) 'high' for above-the-fold LCP candidates
 *   - decoding (string)      'async' (default) | 'sync'
 */
function workernu_image_attrs($value, string $size = 'large', array $opts = []): string {
    $id = workernu_image_id($value);
    if ($id <= 0) return '';

    $src = wp_get_attachment_image_src($id, $size);
    if (!$src) return '';
    [$url, $width, $height] = [$src[0], (int) $src[1], (int) $src[2]];

    $alt          = $opts['alt']           ?? workernu_image_alt($id);
    $loading      = $opts['loading']       ?? 'lazy';
    $decoding     = $opts['decoding']      ?? 'async';
    $fetchprio    = $opts['fetchpriority'] ?? '';
    $sizes_attr   = $opts['sizes']         ?? '';

    // Responsive srcset — pulls every generated WP size of this attachment.
    $srcset = wp_get_attachment_image_srcset($id, $size);
    if (!$sizes_attr && $srcset) {
        $sizes_attr = wp_get_attachment_image_sizes($id, $size);
    }

    $parts = [
        'src="'    . esc_url($url) . '"',
        'alt="'    . esc_attr((string) $alt) . '"',
        'width="'  . $width  . '"',
        'height="' . $height . '"',
        $srcset     ? 'srcset="' . esc_attr($srcset) . '"' : '',
        $sizes_attr ? 'sizes="' . esc_attr($sizes_attr) . '"' : '',
        'loading="'  . esc_attr($loading) . '"',
        'decoding="' . esc_attr($decoding) . '"',
        $fetchprio  ? 'fetchpriority="' . esc_attr($fetchprio) . '"' : '',
    ];
    return implode(' ', array_filter($parts));
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
        // Modifiers flagged `'global' => true` are slug-independent — they emit
        // `section--<modifier>-<value>` so a single CSS rule in main.css can
        // style every section that uses them. The default emits the
        // slug-prefixed BEM form for section-specific modifiers.
        if (!empty($mod['global'])) {
            $classes[] = 'section--' . sanitize_html_class($name) . '-' . sanitize_html_class((string) $value);
        } else {
            $classes[] = 'section--' . sanitize_html_class($slug) . '--' . sanitize_html_class($name) . '-' . sanitize_html_class((string) $value);
        }
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

    // Auto mode: if every non-empty line starts with "- " or "– ", strip the
    // prefix and render as a check list. Otherwise fall back to paragraph.
    if ($display === 'auto') {
        $lines       = array_values(array_filter(array_map('trim', preg_split('/\r?\n/u', $text) ?: [])));
        $check_lines = [];
        $para_lines  = [];
        foreach ($lines as $l) {
            if (preg_match('/^[-–—]\s/u', $l)) {
                $check_lines[] = preg_replace('/^[-–—]\s+/u', '', $l);
            } else {
                $para_lines[] = $l;
            }
        }
        if ($check_lines) {
            $p_cls = $class !== '' ? ' class="' . esc_attr($class . ' ' . $class . '--paragraph') . '"' : '';
            $u_cls = $class !== '' ? ' class="' . esc_attr($class . ' ' . $class . '--checks')    . '"' : '';
            $out   = '';
            if ($para_lines) {
                $out .= '<p' . $p_cls . '>' . nl2br(esc_html(implode("\n", $para_lines))) . '</p>';
            }
            $li = '';
            foreach ($check_lines as $item) {
                $li .= '<li><i class="fa-solid fa-check" aria-hidden="true"></i><span>' . esc_html($item) . '</span></li>';
            }
            return $out . '<ul' . $u_cls . '>' . $li . '</ul>';
        }
        $display = 'paragraph';
    }

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

    if ($display === 'checks') {
        $items = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $text) ?: [])));
        if (!$items) return '';
        $li = '';
        foreach ($items as $item) {
            $li .= '<li><i class="fa-solid fa-check" aria-hidden="true"></i><span>' . esc_html($item) . '</span></li>';
        }
        return '<ul' . $attr . '>' . $li . '</ul>';
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
