<?php
/**
 * Plugin Name: workernu Page Theme
 * Description: Per-page color theme picker. Themes are registered by the active theme via the `workernu_themes` filter and applied as a `theme--<slug>` body class on the frontend so CSS tokens swap.
 * Version: 0.1.0
 * Author: workernu
 * Text Domain: workernu-page-theme
 */

if (!defined('ABSPATH')) exit;

define('WORKERNU_PAGE_THEME_VERSION',     '0.1.0');
define('WORKERNU_PAGE_THEME_PATH',        plugin_dir_path(__FILE__));
define('WORKERNU_PAGE_THEME_URL',         plugin_dir_url(__FILE__));
define('WORKERNU_PAGE_THEME_META_KEY',    '_workernu_page_theme');
define('WORKERNU_PAGE_THEME_DEFAULT',     'default');

require_once WORKERNU_PAGE_THEME_PATH . 'includes/registry.php';
require_once WORKERNU_PAGE_THEME_PATH . 'includes/api.php';
require_once WORKERNU_PAGE_THEME_PATH . 'includes/meta-box.php';
require_once WORKERNU_PAGE_THEME_PATH . 'includes/save.php';
require_once WORKERNU_PAGE_THEME_PATH . 'includes/body-class.php';

add_action('add_meta_boxes',        '\\WorkerNu\\PageTheme\\MetaBox\\register');
add_action('save_post',             '\\WorkerNu\\PageTheme\\Save\\handle', 10, 2);
add_action('admin_enqueue_scripts', '\\WorkerNu\\PageTheme\\MetaBox\\enqueue');
add_filter('body_class',            '\\WorkerNu\\PageTheme\\BodyClass\\add');
