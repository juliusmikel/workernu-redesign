<?php
namespace WorkerNu\InlineEditor\Mode;

if (!defined('ABSPATH')) exit;

const COOKIE = 'wn_inline_edit';

/**
 * True when the current visitor is logged in, can edit $post_id, and has
 * flipped the "Edit Text" toggle on. Any other visitor (logged out, or an
 * admin who hasn't toggled it) always gets false here.
 */
function is_active(int $post_id): bool {
    if ($post_id <= 0) return false;
    if (!is_user_logged_in()) return false;
    if (!current_user_can('edit_post', $post_id)) return false;
    return !empty($_COOKIE[COOKIE]);
}

/**
 * URL that flips the cookie on/off for the current page when visited.
 */
function toggle_url(bool $enable): string {
    return esc_url(add_query_arg('wn_edit_toggle', $enable ? '1' : '0'));
}

/**
 * Runs on template_redirect. If ?wn_edit_toggle=0|1 is present, sets/clears
 * the cookie and 302s back to the clean URL — keeps the toggle a one-time
 * action instead of a permanent query string.
 */
function handle_toggle(): void {
    if (!isset($_GET['wn_edit_toggle'])) return;
    if (!is_user_logged_in()) return;

    $enable = $_GET['wn_edit_toggle'] === '1';
    $path   = COOKIEPATH ?: '/';
    if ($enable) {
        setcookie(COOKIE, '1', time() + DAY_IN_SECONDS, $path, COOKIE_DOMAIN, is_ssl(), true);
    } else {
        setcookie(COOKIE, '', time() - HOUR_IN_SECONDS, $path, COOKIE_DOMAIN, is_ssl(), true);
    }

    wp_safe_redirect(remove_query_arg('wn_edit_toggle'));
    exit;
}
