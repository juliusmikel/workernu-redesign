<?php
/**
 * Plugin Name: workernu Inline Editor
 * Description: Admin-only front-end inline editing for text/rich_text fields, with a Save Draft / Publish workflow. Requires workernu Sections + workernu Lang.
 * Version: 0.1.0
 * Author: workernu
 * Text Domain: workernu-inline-editor
 */

if (!defined('ABSPATH')) exit;

define('WORKERNU_INLINE_EDITOR_VERSION', '0.1.0');
define('WORKERNU_INLINE_EDITOR_PATH',    plugin_dir_path(__FILE__));
define('WORKERNU_INLINE_EDITOR_URL',     plugin_dir_url(__FILE__));

require_once WORKERNU_INLINE_EDITOR_PATH . 'includes/mode.php';
require_once WORKERNU_INLINE_EDITOR_PATH . 'includes/draft.php';

add_action('template_redirect', '\\WorkerNu\\InlineEditor\\Mode\\handle_toggle');

// Soft dependency check, mirrors workernu-sections.php's own pattern.
add_action('admin_notices', function () {
    if (!defined('WORKERNU_SECTIONS_META_KEY') || !function_exists('workernu_lang')) {
        echo '<div class="notice notice-error"><p><strong>workernu Inline Editor</strong> requires the <strong>workernu Sections</strong> and <strong>workernu Lang</strong> plugins to be active.</p></div>';
    }
});
