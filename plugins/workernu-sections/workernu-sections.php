<?php
/**
 * Plugin Name: workernu Sections
 * Description: Section-based page composition engine. Auto-discovers section types from the active theme's /sections/<name>/ folders.
 * Version: 0.1.0
 * Author: workernu
 * Text Domain: workernu-sections
 *
 * Requires: workernu Lang (provides workernu_t / workernu_lang).
 */

if (!defined('ABSPATH')) exit;

define('WORKERNU_SECTIONS_VERSION',  '0.1.0');
define('WORKERNU_SECTIONS_PATH',     plugin_dir_path(__FILE__));
define('WORKERNU_SECTIONS_URL',      plugin_dir_url(__FILE__));
define('WORKERNU_SECTIONS_META_KEY', '_page_sections');

require_once WORKERNU_SECTIONS_PATH . 'includes/registry.php';
require_once WORKERNU_SECTIONS_PATH . 'includes/fields.php';
require_once WORKERNU_SECTIONS_PATH . 'includes/defaults.php';
require_once WORKERNU_SECTIONS_PATH . 'includes/render.php';
require_once WORKERNU_SECTIONS_PATH . 'includes/api.php';
require_once WORKERNU_SECTIONS_PATH . 'includes/meta-box.php';
require_once WORKERNU_SECTIONS_PATH . 'includes/save.php';
require_once WORKERNU_SECTIONS_PATH . 'includes/schema.php';
require_once WORKERNU_SECTIONS_PATH . 'includes/io.php';
require_once WORKERNU_SECTIONS_PATH . 'includes/global-modifiers.php';
require_once WORKERNU_SECTIONS_PATH . 'includes/duplicator.php';

add_action('wp_head',               '\\WorkerNu\\Sections\\Render\\preload_hero_image', 1);
add_action('add_meta_boxes',        '\\WorkerNu\\Sections\\MetaBox\\register');
add_action('save_post',             '\\WorkerNu\\Sections\\Save\\handle', 10, 2);
add_action('admin_enqueue_scripts', '\\WorkerNu\\Sections\\MetaBox\\enqueue_admin');
add_filter('workernu_seo_json_ld_graph', '\\WorkerNu\\Sections\\Schema\\contribute_section_schemas', 10, 2);

// Row-action "Duplicate" on the Posts/Pages list screens.
add_filter('post_row_actions',                     '\\WorkerNu\\Sections\\Duplicator\\add_row_action', 10, 2);
add_filter('page_row_actions',                     '\\WorkerNu\\Sections\\Duplicator\\add_row_action', 10, 2);
add_action('admin_action_' . \WorkerNu\Sections\Duplicator\ACTION, '\\WorkerNu\\Sections\\Duplicator\\handle');

// Export / import — move a page's sections, theme, and images between installs.
add_action('add_meta_boxes',                       '\\WorkerNu\\Sections\\IO\\register_meta_box');
add_action('admin_menu',                           '\\WorkerNu\\Sections\\IO\\register_admin_page');
add_action('admin_post_workernu_sections_export',  '\\WorkerNu\\Sections\\IO\\handle_export');
add_action('admin_post_workernu_sections_import',  '\\WorkerNu\\Sections\\IO\\handle_import');
add_action('admin_notices',                        '\\WorkerNu\\Sections\\IO\\maybe_import_notice');

/**
 * Remove the body editor on post types that use sections — the content is owned by the section builder.
 * Title and featured image remain (slug + OG fallback).
 */
add_action('init', function () {
    $post_types = apply_filters('workernu_sections_post_types', ['page']);
    foreach ($post_types as $post_type) {
        remove_post_type_support($post_type, 'editor');
    }
}, 99);

// Soft dependency check — surface a notice if workernu-lang isn't active.
add_action('admin_notices', function () {
    if (!function_exists('workernu_t')) {
        echo '<div class="notice notice-error"><p><strong>workernu Sections</strong> requires the <strong>workernu Lang</strong> plugin to be active.</p></div>';
    }
});
