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
 * Frontend locale follows the URL language: /en/… → en_GB, everything else
 * → lt_LT. This is what makes gettext (.mo) translations of hardcoded
 * template strings switch with the language — the theme ships
 * /languages/en_GB.mo (LT source → EN) and /languages/lt_LT.mo (EN-source
 * aria-labels → LT). English is deliberately en_GB, NOT en_US: WordPress
 * hard-skips loading any en_US translation file on the assumption that
 * source strings are already English — ours are Lithuanian, so en_US would
 * silently never translate. Admin is left on the site's own locale so
 * wp-admin doesn't flip languages based on the referring URL.
 */
function frontend_locale(string $locale): string {
    if (is_admin()) return $locale;
    return current_lang() === 'en' ? 'en_GB' : 'lt_LT';
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
 * `/en/` alone (no slug) needs the static front page to be loaded — the rewrite
 * rule only sets workernu_lang=en, and WP's default front-page detection runs
 * only when the URL matches the home URL (/). Without this filter `/en/`
 * falls back to the blog index instead of the same page `/` shows.
 */
function resolve_front_page_for_lang(array $vars): array {
    if (!isset($vars[QUERY_VAR]) || $vars[QUERY_VAR] !== 'en') return $vars;
    if (!empty($vars['pagename']) || !empty($vars['page_id']) || !empty($vars['name'])) return $vars;

    if (get_option('show_on_front') === 'page') {
        $front_id = (int) get_option('page_on_front');
        if ($front_id) $vars['page_id'] = $front_id;
    }
    return $vars;
}

/**
 * WP's redirect_canonical compares the request URL against the queried page's
 * permalink. Our /en/<slug>/ URLs always mismatch the LT permalink (/<slug>/),
 * so without this filter clicking the EN link would bounce back to LT.
 * Returning false tells WP not to redirect.
 */
function suppress_canonical_redirect_for_lang($redirect_url) {
    return current_lang() === 'en' ? false : $redirect_url;
}

/**
 * Per-language display metadata for the switcher (flag emoji + native name).
 */
function language_meta(): array {
    return [
        'lt' => ['flag' => '🇱🇹', 'name' => 'Lietuvių'],
        'en' => ['flag' => '🇬🇧', 'name' => 'English'],
    ];
}

/**
 * Output the language switcher as a hover/focus dropdown: a globe + the current
 * language's flag triggers a list of languages (flag + native name).
 */
function language_switcher(): void {
    $current = current_lang();
    $uri     = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';

    [$lt_url, $en_url] = build_switcher_urls($uri, $current);
    $urls = ['lt' => $lt_url, 'en' => $en_url];
    $meta = language_meta();

    $current_flag = $meta[$current]['flag'] ?? '';
    ?>
    <div class="lang-switcher" data-lang-switcher>
        <button type="button" class="lang-switcher__current" aria-haspopup="true" aria-expanded="false"
                aria-label="<?php esc_attr_e('Change language', 'workernu-lang'); ?>">
            <i class="fa-solid fa-language" aria-hidden="true"></i>
            <span class="lang-switcher__flag"><?php echo $current_flag; ?></span>
        </button>
        <ul class="lang-switcher__menu">
            <?php foreach (LANGUAGES as $lang):
                $m = $meta[$lang] ?? ['flag' => '', 'name' => strtoupper($lang)];
                $active = $lang === $current ? ' is-active' : '';
                ?>
                <li class="lang-switcher__item<?php echo $active; ?>">
                    <a href="<?php echo esc_url($urls[$lang] ?? '/'); ?>" hreflang="<?php echo esc_attr($lang); ?>">
                        <span class="lang-switcher__flag"><?php echo $m['flag']; ?></span>
                        <span class="lang-switcher__name"><?php echo esc_html($m['name']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
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

/**
 * Make an internal URL point at the current language's variant.
 *
 * When the current language is EN, prefixes `/en` onto internal URLs so links
 * keep visitors (and crawlers) inside the same language tree. In the default
 * language it's a no-op. Left untouched: external hosts, protocol-relative URLs,
 * `#anchors`, `mailto:`/`tel:`/`javascript:`/`data:`, and already-`/en` URLs.
 *
 *   localize_url('/kaina')                 // '/en/kaina'  (when current lang = en)
 *   localize_url('https://site.lt/kaina')  // 'https://site.lt/en/kaina'
 *   localize_url('https://other.com/x')    // unchanged (external)
 *   localize_url('#contact')               // unchanged
 */
function localize_url($url, ?string $lang = null): string {
    $url  = (string) $url;
    $lang = $lang ?? current_lang();
    if ($url === '' || $lang === DEFAULT_LANG) return $url;

    if ($url[0] === '#') return $url;
    if (preg_match('~^(mailto:|tel:|javascript:|data:)~i', $url)) return $url;
    if (strncmp($url, '//', 2) === 0) return $url; // protocol-relative → treat as external

    $prefix = '';   // scheme://host[:port] for absolute URLs
    $path   = '';
    $suffix = '';   // ?query and/or #fragment

    if ($url[0] === '/') {
        $path = $url;
    } else {
        $parts = wp_parse_url($url);
        if (empty($parts['host'])) return $url; // not a URL we can localize (e.g. bare "about")
        $home_host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
        if (strcasecmp($parts['host'], $home_host) !== 0) return $url; // external host
        $prefix = (isset($parts['scheme']) ? $parts['scheme'] . ':' : '') . '//' . $parts['host']
                . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $path = $parts['path'] ?? '/';
        if (isset($parts['query']))    $suffix .= '?' . $parts['query'];
        if (isset($parts['fragment'])) $suffix .= '#' . $parts['fragment'];
    }

    // Split query/fragment off a root-relative path.
    if ($prefix === '') {
        if (($h = strpos($path, '#')) !== false) { $suffix = substr($path, $h) . $suffix; $path = substr($path, 0, $h); }
        if (($q = strpos($path, '?')) !== false) { $suffix = substr($path, $q) . $suffix; $path = substr($path, 0, $q); }
    }

    if (preg_match('#^/en(/|$)#', $path)) return $url; // already localized

    $path = '/en' . ($path === '/' ? '/' : $path);
    return $prefix . $path . $suffix;
}

/**
 * Filter callback for `nav_menu_link_attributes` — localizes every menu link's
 * href so nav menus stay in the active language. No-op in the default language.
 */
function localize_nav_link($atts) {
    if (current_lang() === DEFAULT_LANG) return $atts;
    if (is_array($atts) && !empty($atts['href'])) {
        $atts['href'] = localize_url($atts['href']);
    }
    return $atts;
}
