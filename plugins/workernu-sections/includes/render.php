<?php
namespace WorkerNu\Sections\Render;

use function WorkerNu\Sections\Registry\get as get_section;

if (!defined('ABSPATH')) exit;

/**
 * Renders all sections for a post in order. Each section is wrapped — its template
 * outputs only its inner contents. Missing templates are silently skipped.
 */
function render_sections(int $post_id): void {
    $sections = get_post_meta($post_id, WORKERNU_SECTIONS_META_KEY, true);
    if (!is_array($sections)) return;

    foreach ($sections as $section) {
        if (!is_array($section)) continue;
        $type = $section['_type'] ?? '';
        $def  = get_section($type);
        if (!$def) continue;

        $template = $def['path'] . '/template.php';

        if (!file_exists($template)) {
            // Fallback so editors can keep populating content while the frontend dev
            // hasn't built this section's template yet.
            render_pending_section($def);
            continue;
        }

        $data = \WorkerNu\Sections\Defaults\resolve($section);
        include $template;
    }
}

/**
 * If the page has a Hero section, emit a <link rel="preload"> for its image so
 * the browser starts downloading it during HTML parse — typically shaves
 * 200-800ms off LCP since the Hero image is the LCP candidate on most pages.
 * Hooked into wp_head from the plugin entry file.
 */
function preload_hero_image(): void {
    if (!is_singular()) return;
    $post_id = get_queried_object_id();
    if (!$post_id) return;

    $sections = get_post_meta($post_id, WORKERNU_SECTIONS_META_KEY, true);
    if (!is_array($sections)) return;

    foreach ($sections as $section) {
        if (!is_array($section)) continue;
        if (($section['_type'] ?? '') !== 'hero') continue;

        // Resolve translatable shape ({ lt: id, en: id }) → current language's ID.
        $image_id = function_exists('workernu_image_id')
            ? workernu_image_id($section['image'] ?? 0)
            : (int) ($section['image'] ?? 0);
        if (!$image_id) return;

        $src = wp_get_attachment_image_src($image_id, 'full');
        if (!$src) return;
        $url    = (string) $src[0];
        $srcset = wp_get_attachment_image_srcset($image_id, 'full');
        $sizes  = $srcset ? wp_get_attachment_image_sizes($image_id, 'full') : '';

        echo '<link rel="preload" as="image" ';
        if ($srcset) {
            echo 'imagesrcset="' . esc_attr($srcset) . '" ';
            if ($sizes) echo 'imagesizes="' . esc_attr($sizes) . '" ';
        } else {
            echo 'href="' . esc_url($url) . '" ';
        }
        echo 'fetchpriority="high">' . "\n";
        return; // Only one hero per page; first wins.
    }
}

function render_pending_section(array $def): void {
    if (!current_user_can('edit_posts')) return;
    ?>
    <section class="section section--pending" style="padding: 2rem; background: #fef9c3; border: 1px dashed #ca8a04; color: #713f12; font-family: ui-monospace, monospace;">
        <strong><?php echo esc_html($def['label'] ?? $def['slug']); ?></strong>
        — template not yet built
        <code style="opacity:.6; font-size:11px;">(<?php echo esc_html($def['slug']); ?>/template.php)</code>
    </section>
    <?php
}

/**
 * Enqueues style.css for every section type present on the current post.
 * Only loads CSS for sections that are actually used — keeps payload lean.
 */
function enqueue_section_assets(int $post_id): void {
    $sections = get_post_meta($post_id, WORKERNU_SECTIONS_META_KEY, true);
    if (!is_array($sections)) return;

    $used_types = [];
    foreach ($sections as $section) {
        if (!is_array($section)) continue;
        $type = $section['_type'] ?? '';
        if ($type) $used_types[$type] = true;
    }

    foreach (array_keys($used_types) as $type) {
        $def = get_section($type);
        if (!$def) continue;

        $css = $def['path'] . '/style.css';
        if (file_exists($css)) {
            wp_enqueue_style(
                'workernu-section-' . $type,
                $def['url'] . '/style.css',
                ['workernu-main'],
                filemtime($css)
            );
        }

        $js = $def['path'] . '/animations.js';
        if (file_exists($js)) {
            wp_enqueue_script(
                'workernu-section-' . $type,
                $def['url'] . '/animations.js',
                ['workernu-main'],
                filemtime($js),
                true
            );
        }
    }
}
