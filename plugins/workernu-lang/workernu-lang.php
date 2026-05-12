<?php
/**
 * Plugin Name: workernu Lang
 * Description: URL-prefix multilingual layer for workernu. Provides current language detection, translatable-value resolution, language switcher, and hreflang output.
 * Version: 0.1.0
 * Author: workernu
 * Text Domain: workernu-lang
 */

if (!defined('ABSPATH')) exit;

define('WORKERNU_LANG_VERSION', '0.1.0');
define('WORKERNU_LANG_PATH',    plugin_dir_path(__FILE__));
define('WORKERNU_LANG_URL',     plugin_dir_url(__FILE__));

require_once WORKERNU_LANG_PATH . 'includes/lang.php';
require_once WORKERNU_LANG_PATH . 'includes/api.php';

add_action('init',         '\\WorkerNu\\Lang\\add_rewrite_rules');
add_filter('query_vars',   '\\WorkerNu\\Lang\\register_query_var');
add_action('wp_head',      '\\WorkerNu\\Lang\\hreflang_tags', 1);

register_activation_hook(__FILE__, function () {
    \WorkerNu\Lang\add_rewrite_rules();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
