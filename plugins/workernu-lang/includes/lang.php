<?php
namespace WorkerNu\Lang;

if (!defined('ABSPATH')) exit;

const LANGUAGES    = ['lt', 'en'];
const DEFAULT_LANG = 'lt';
const QUERY_VAR    = 'workernu_lang';

/**
 * Returns the current request's language code.
 * Detected from the URL prefix: `/en/...` is English, everything else is Lithuanian (default).
 */
function current_lang(): string {
    static $lang = null;
    if ($lang !== null) return $lang;

    $uri  = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $path = strtok($uri, '?') ?: '/';

    $lang = preg_match('#^/en(/|$)#', $path) ? 'en' : DEFAULT_LANG;
    return $lang;
}

/**
 * Picks the right value out of a translation array, or returns scalar values unchanged.
 *   t(['lt' => 'Sveiki', 'en' => 'Hello'])   -> 'Sveiki' (when LT is current)
 *   t('static text')                          -> 'static text'
 */
function t($value, ?string $lang = null) {
    $lang = $lang ?? current_lang();
    if (is_array($value)) {
        if (isset($value[$lang]) && $value[$lang] !== '') return $value[$lang];
        return $value[DEFAULT_LANG] ?? '';
    }
    return $value;
}

/**
 * Map `/en/<slug>` to the same WP query as `/<slug>` but with a language flag.
 */
function add_rewrite_rules(): void {
    add_rewrite_rule('^en/?$',       'index.php?' . QUERY_VAR . '=en',                       'top');
    add_rewrite_rule('^en/(.+?)/?$', 'index.php?pagename=$matches[1]&' . QUERY_VAR . '=en',  'top');
}

function register_query_var(array $vars): array {
    $vars[] = QUERY_VAR;
    return $vars;
}

/**
 * Output the two-language switcher.
 */
function language_switcher(): void {
    $current = current_lang();
    $uri     = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';

    [$lt_url, $en_url] = build_switcher_urls($uri, $current);

    echo '<ul class="lang-switcher">';
    foreach (['lt' => $lt_url, 'en' => $en_url] as $lang => $url) {
        $class = $current === $lang ? ' class="is-active"' : '';
        echo '<li' . $class . '><a href="' . esc_url($url) . '" hreflang="' . esc_attr($lang) . '">' . esc_html(strtoupper($lang)) . '</a></li>';
    }
    echo '</ul>';
}

/**
 * Emit hreflang link tags in <head>.
 */
function hreflang_tags(): void {
    if (!is_singular() && !is_home() && !is_front_page() && !is_archive()) return;

    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
    [$lt_url, $en_url] = build_switcher_urls($uri, current_lang());

    $base = home_url();
    echo "\n<link rel=\"alternate\" hreflang=\"lt\" href=\""        . esc_url($base . $lt_url) . "\">\n";
    echo "<link rel=\"alternate\" hreflang=\"en\" href=\""          . esc_url($base . $en_url) . "\">\n";
    echo "<link rel=\"alternate\" hreflang=\"x-default\" href=\""   . esc_url($base . $lt_url) . "\">\n";
}

/**
 * Given a request URI and current language, build the equivalent URLs for both variants.
 * Returns [lt_url, en_url].
 */
function build_switcher_urls(string $uri, string $current): array {
    if ($current === 'en') {
        $path_no_lang = preg_replace('#^/en(/|$)#', '/', $uri);
        if ($path_no_lang === '' || $path_no_lang === null) $path_no_lang = '/';
        return [$path_no_lang, $uri];
    }
    return [$uri, $uri === '/' ? '/en/' : '/en' . $uri];
}
