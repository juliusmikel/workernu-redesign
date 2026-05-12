<?php
/**
 * workernu Page Theme — theme-facing global API.
 */

if (!defined('ABSPATH')) exit;

/**
 * Resolve the theme slug for a post (or the current request when $post_id is omitted).
 * Falls back to the default when the page has no theme set or references a missing one.
 */
function workernu_page_theme(?int $post_id = null): string {
    if ($post_id === null) {
        $post_id = is_singular() ? (int) get_queried_object_id() : 0;
    }
    if (!$post_id) return WORKERNU_PAGE_THEME_DEFAULT;

    $slug = (string) get_post_meta($post_id, WORKERNU_PAGE_THEME_META_KEY, true);
    if ($slug === '' || !\WorkerNu\PageTheme\Registry\exists($slug)) {
        return WORKERNU_PAGE_THEME_DEFAULT;
    }
    return $slug;
}

/**
 * Returns the registered theme definitions, keyed by slug.
 */
function workernu_page_themes(): array {
    return \WorkerNu\PageTheme\Registry\all();
}
