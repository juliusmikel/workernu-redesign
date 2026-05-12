<?php
/**
 * Plugin Name: workernu SEO
 * Description: Minimal SEO layer for workernu — per-page meta, OpenGraph, canonical, JSON-LD, robots.txt and llms.txt management.
 * Version: 0.1.0
 * Author: workernu
 * Text Domain: workernu-seo
 *
 * Requires: workernu Lang (provides workernu_t / workernu_lang).
 */

if (!defined('ABSPATH')) exit;

define('WORKERNU_SEO_VERSION', '0.1.0');
define('WORKERNU_SEO_PATH',    plugin_dir_path(__FILE__));
define('WORKERNU_SEO_URL',     plugin_dir_url(__FILE__));

require_once WORKERNU_SEO_PATH . 'includes/options.php';
require_once WORKERNU_SEO_PATH . 'includes/helpers.php';
require_once WORKERNU_SEO_PATH . 'includes/meta-box.php';
require_once WORKERNU_SEO_PATH . 'includes/output.php';
require_once WORKERNU_SEO_PATH . 'includes/settings.php';
require_once WORKERNU_SEO_PATH . 'includes/txt-files.php';

// Settings page
add_action('admin_menu',            '\\WorkerNu\\SEO\\Settings\\register_menu');
add_action('admin_enqueue_scripts', '\\WorkerNu\\SEO\\Settings\\enqueue_admin');

// Per-post meta box
add_action('add_meta_boxes',        '\\WorkerNu\\SEO\\MetaBox\\register');
add_action('save_post',             '\\WorkerNu\\SEO\\MetaBox\\save', 10, 2);

// Frontend output
add_filter('document_title_parts',  '\\WorkerNu\\SEO\\Output\\filter_title_parts');
add_filter('pre_get_document_title','\\WorkerNu\\SEO\\Output\\override_title', 10);
add_action('wp_head',               '\\WorkerNu\\SEO\\Output\\head_tags', 2);

// robots.txt + llms.txt
add_filter('robots_txt',            '\\WorkerNu\\SEO\\TxtFiles\\robots_txt', 10, 2);
add_action('init',                  '\\WorkerNu\\SEO\\TxtFiles\\add_rewrite');
add_filter('query_vars',            '\\WorkerNu\\SEO\\TxtFiles\\add_query_var');
add_action('template_redirect',     '\\WorkerNu\\SEO\\TxtFiles\\serve_llms');

// Soft dependency: workernu-lang
add_action('admin_notices', function () {
    if (!function_exists('workernu_t')) {
        echo '<div class="notice notice-error"><p><strong>workernu SEO</strong> requires the <strong>workernu Lang</strong> plugin to be active.</p></div>';
    }
});

register_activation_hook(__FILE__, function () {
    \WorkerNu\SEO\TxtFiles\add_rewrite();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
