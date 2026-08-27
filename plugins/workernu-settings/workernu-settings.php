<?php
/**
 * Plugin Name: WorkerNu Settings
 * Description: Site-wide settings (Trustpilot account, etc.) consumed by the
 *              workernu theme's section templates.
 * Version: 0.1.0
 * Author: workernu
 * Text Domain: workernu-settings
 */

if (!defined('ABSPATH')) exit;

define('WORKERNU_SETTINGS_VERSION', '0.1.0');
define('WORKERNU_SETTINGS_PATH',    plugin_dir_path(__FILE__));

require_once WORKERNU_SETTINGS_PATH . 'includes/trustpilot.php';
require_once WORKERNU_SETTINGS_PATH . 'includes/seo.php';
require_once WORKERNU_SETTINGS_PATH . 'includes/admin-page.php';
require_once WORKERNU_SETTINGS_PATH . 'includes/section-defaults-admin.php';

add_action('admin_menu', '\\WorkerNu\\Settings\\AdminPage\\register');
add_action('admin_init', '\\WorkerNu\\Settings\\Trustpilot\\register_settings');
add_action('admin_init', '\\WorkerNu\\Settings\\Seo\\register_settings');

// Site-wide default content for content_defaults sections (calculator,
// pricing, testimonials, people) — see includes/section-defaults-admin.php.
add_action('admin_menu',            '\\WorkerNu\\Settings\\SectionDefaultsAdmin\\register', 20); // after AdminPage\register creates the parent menu
add_action('admin_enqueue_scripts', '\\WorkerNu\\Settings\\SectionDefaultsAdmin\\enqueue_admin');
add_action('admin_post_' . \WorkerNu\Settings\SectionDefaultsAdmin\SAVE_ACTION, '\\WorkerNu\\Settings\\SectionDefaultsAdmin\\handle_save');
