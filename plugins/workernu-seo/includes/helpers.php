<?php
namespace WorkerNu\SEO\Helpers;

use WorkerNu\SEO\Options;

if (!defined('ABSPATH')) exit;

/**
 * Resolve the SEO title for a post (or current request when $post_id = 0).
 * Falls back to: post title → site name.
 */
function resolve_title(int $post_id = 0): string {
    if ($post_id) {
        $stored = get_post_meta($post_id, Options\META_TITLE, true);
        $resolved = function_exists('workernu_t') ? workernu_t($stored) : $stored;
        if ($resolved) return (string) $resolved;
        return (string) get_the_title($post_id);
    }
    if (is_singular()) return (string) get_the_title(get_queried_object_id());
    return (string) wp_get_document_title();
}

/**
 * Resolve the meta description for a post, falling back to:
 *  - post's stored description
 *  - post excerpt
 *  - site-wide default description (current lang)
 *  - blog tagline
 */
function resolve_description(int $post_id = 0): string {
    if ($post_id) {
        $stored = get_post_meta($post_id, Options\META_DESCRIPTION, true);
        $resolved = function_exists('workernu_t') ? workernu_t($stored) : $stored;
        if ($resolved) return (string) $resolved;

        $excerpt = (string) get_post_field('post_excerpt', $post_id);
        if ($excerpt !== '') return wp_strip_all_tags($excerpt);
    }

    $defaults = Options\all()['default_description'];
    $lang     = function_exists('workernu_lang') ? workernu_lang() : 'lt';
    $default  = $defaults[$lang] ?? '';
    if ($default !== '') return $default;

    return (string) get_bloginfo('description');
}

/**
 * Resolve the OG image URL. Priority: post override → post featured image → site-wide default.
 */
function resolve_og_image(int $post_id = 0): string {
    if ($post_id) {
        $id = (int) get_post_meta($post_id, Options\META_OG_IMAGE, true);
        if ($id) {
            $url = wp_get_attachment_image_url($id, 'full');
            if ($url) return $url;
        }
        $featured = get_post_thumbnail_id($post_id);
        if ($featured) {
            $url = wp_get_attachment_image_url($featured, 'full');
            if ($url) return $url;
        }
    }

    $default = (int) get_option(Options\OPT_DEFAULT_OG, 0);
    if ($default) {
        $url = wp_get_attachment_image_url($default, 'full');
        if ($url) return $url;
    }

    return '';
}

function is_noindex(int $post_id = 0): bool {
    if (!$post_id) return false;
    return (bool) get_post_meta($post_id, Options\META_NOINDEX, true);
}

function canonical_url(): string {
    if (is_singular()) {
        $url = get_permalink(get_queried_object_id());
        return $url ?: home_url('/');
    }
    if (is_home() || is_front_page()) return home_url('/');
    $request = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
    return home_url($request);
}
