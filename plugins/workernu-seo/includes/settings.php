<?php
namespace WorkerNu\SEO\Settings;

use WorkerNu\SEO\Options;

if (!defined('ABSPATH')) exit;

const NONCE_FIELD  = 'workernu_seo_settings_nonce';
const NONCE_ACTION = 'workernu_seo_settings_save';

function register_menu(): void {
    add_menu_page(
        __('SEO', 'workernu-seo'),
        __('SEO', 'workernu-seo'),
        'manage_options',
        'workernu-seo',
        __NAMESPACE__ . '\\render_page',
        'dashicons-search',
        80
    );
}

function render_page(): void {
    if (!current_user_can('manage_options')) wp_die(__('Insufficient permissions.', 'workernu-seo'));

    if (isset($_POST[NONCE_FIELD]) && wp_verify_nonce($_POST[NONCE_FIELD], NONCE_ACTION)) {
        save_options($_POST);
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'workernu-seo') . '</p></div>';
    }

    $opts        = Options\all();
    $sitemap_url = home_url('/wp-sitemap.xml');
    $robots_url  = home_url('/robots.txt');
    $llms_url    = home_url('/llms.txt');
    ?>
    <div class="wrap workernu-seo-settings">
        <h1><?php esc_html_e('SEO Settings', 'workernu-seo'); ?></h1>

        <form method="post">
            <?php wp_nonce_field(NONCE_ACTION, NONCE_FIELD); ?>

            <h2 class="title"><?php esc_html_e('Sitemap', 'workernu-seo'); ?></h2>
            <p><?php esc_html_e('Generated automatically by WordPress core.', 'workernu-seo'); ?></p>
            <p>
                <a href="<?php echo esc_url($sitemap_url); ?>" class="button" target="_blank" rel="noopener">
                    <?php esc_html_e('Open sitemap.xml', 'workernu-seo'); ?> ↗
                </a>
                <code style="margin-left:10px;"><?php echo esc_html($sitemap_url); ?></code>
            </p>

            <hr>

            <h2 class="title"><?php esc_html_e('robots.txt', 'workernu-seo'); ?></h2>
            <p>
                <?php
                printf(
                    /* translators: %s = robots.txt URL */
                    esc_html__('Served at %s. Leave blank to use the default WordPress output.', 'workernu-seo'),
                    '<code>' . esc_html($robots_url) . '</code>'
                );
                ?>
            </p>
            <textarea name="<?php echo esc_attr(Options\OPT_ROBOTS); ?>" rows="10" class="ws-mono"><?php echo esc_textarea($opts['robots_txt']); ?></textarea>
            <p>
                <a href="<?php echo esc_url($robots_url); ?>" target="_blank" rel="noopener" class="button-link">
                    <?php esc_html_e('Preview robots.txt', 'workernu-seo'); ?> ↗
                </a>
            </p>

            <hr>

            <h2 class="title"><?php esc_html_e('llms.txt', 'workernu-seo'); ?></h2>
            <p>
                <?php
                printf(
                    /* translators: 1 = llms.txt URL, 2 = link to llmstxt.org */
                    esc_html__('Served at %1$s. A Markdown file that helps AI crawlers (Anthropic, OpenAI, etc.) understand your site. See %2$s.', 'workernu-seo'),
                    '<code>' . esc_html($llms_url) . '</code>',
                    '<a href="https://llmstxt.org/" target="_blank" rel="noopener">llmstxt.org</a>'
                );
                ?>
            </p>
            <textarea name="<?php echo esc_attr(Options\OPT_LLMS); ?>" rows="14" class="ws-mono"><?php echo esc_textarea($opts['llms_txt']); ?></textarea>
            <p>
                <a href="<?php echo esc_url($llms_url); ?>" target="_blank" rel="noopener" class="button-link">
                    <?php esc_html_e('Preview llms.txt', 'workernu-seo'); ?> ↗
                </a>
            </p>

            <hr>

            <h2 class="title"><?php esc_html_e('Defaults', 'workernu-seo'); ?></h2>
            <p><?php esc_html_e('Used when a specific page has no SEO metadata of its own.', 'workernu-seo'); ?></p>

            <table class="form-table" role="presentation">
                <tr>
                    <th><label><?php esc_html_e('Title format', 'workernu-seo'); ?></label></th>
                    <td>
                        <input type="text" name="<?php echo esc_attr(Options\OPT_TITLE_FORMAT); ?>" value="<?php echo esc_attr($opts['title_format']); ?>" class="regular-text" placeholder="{title} | {site_name}">
                        <p class="description"><code>{title}</code>, <code>{site_name}</code></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Default description (LT)', 'workernu-seo'); ?></label></th>
                    <td><textarea name="<?php echo esc_attr(Options\OPT_DEFAULT_DESC); ?>[lt]" rows="3" class="large-text"><?php echo esc_textarea($opts['default_description']['lt']); ?></textarea></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Default description (EN)', 'workernu-seo'); ?></label></th>
                    <td><textarea name="<?php echo esc_attr(Options\OPT_DEFAULT_DESC); ?>[en]" rows="3" class="large-text"><?php echo esc_textarea($opts['default_description']['en']); ?></textarea></td>
                </tr>
            </table>

            <hr>

            <h2 class="title"><?php esc_html_e('Organization (JSON-LD)', 'workernu-seo'); ?></h2>
            <p><?php esc_html_e('Used in the structured-data Organization schema emitted on every page.', 'workernu-seo'); ?></p>

            <table class="form-table" role="presentation">
                <tr>
                    <th><label><?php esc_html_e('Name', 'workernu-seo'); ?></label></th>
                    <td><input type="text" name="<?php echo esc_attr(Options\OPT_ORG_NAME); ?>" value="<?php echo esc_attr($opts['org_name']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Logo URL', 'workernu-seo'); ?></label></th>
                    <td><input type="text" name="<?php echo esc_attr(Options\OPT_ORG_LOGO); ?>" value="<?php echo esc_attr($opts['org_logo']); ?>" class="large-text" placeholder="https://..."></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Social profile URLs', 'workernu-seo'); ?></label></th>
                    <td>
                        <textarea name="<?php echo esc_attr(Options\OPT_ORG_SOCIAL); ?>" rows="4" class="large-text" placeholder="https://linkedin.com/company/..."><?php echo esc_textarea($opts['org_social']); ?></textarea>
                        <p class="description"><?php esc_html_e('One URL per line.', 'workernu-seo'); ?></p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php esc_html_e('Save changes', 'workernu-seo'); ?></button>
            </p>
        </form>
    </div>
    <?php
}

function save_options(array $post): void {
    update_option(Options\OPT_ROBOTS,         wp_unslash((string) ($post[Options\OPT_ROBOTS] ?? '')));
    update_option(Options\OPT_LLMS,           wp_unslash((string) ($post[Options\OPT_LLMS]   ?? '')));
    update_option(Options\OPT_TITLE_FORMAT,   sanitize_text_field(wp_unslash((string) ($post[Options\OPT_TITLE_FORMAT] ?? '{title} | {site_name}'))));
    update_option(Options\OPT_ORG_NAME,       sanitize_text_field(wp_unslash((string) ($post[Options\OPT_ORG_NAME] ?? ''))));
    update_option(Options\OPT_ORG_LOGO,       esc_url_raw(wp_unslash((string) ($post[Options\OPT_ORG_LOGO] ?? ''))));
    update_option(Options\OPT_ORG_SOCIAL,     wp_unslash((string) ($post[Options\OPT_ORG_SOCIAL] ?? '')));

    $desc_raw = (array) wp_unslash($post[Options\OPT_DEFAULT_DESC] ?? []);
    $desc = [];
    foreach (['lt', 'en'] as $lang) {
        $desc[$lang] = sanitize_textarea_field((string) ($desc_raw[$lang] ?? ''));
    }
    update_option(Options\OPT_DEFAULT_DESC, $desc);
}

function enqueue_admin(string $hook): void {
    if (in_array($hook, ['toplevel_page_workernu-seo'], true)) {
        wp_enqueue_style(
            'workernu-seo-admin',
            WORKERNU_SEO_URL . 'admin/admin.css',
            [],
            filemtime(WORKERNU_SEO_PATH . 'admin/admin.css')
        );
    }

    if (in_array($hook, ['post.php', 'post-new.php'], true)) {
        wp_enqueue_media();
        wp_enqueue_style(
            'workernu-seo-admin',
            WORKERNU_SEO_URL . 'admin/admin.css',
            [],
            filemtime(WORKERNU_SEO_PATH . 'admin/admin.css')
        );
        wp_enqueue_script(
            'workernu-seo-admin',
            WORKERNU_SEO_URL . 'admin/admin.js',
            [],
            filemtime(WORKERNU_SEO_PATH . 'admin/admin.js'),
            true
        );
    }
}
