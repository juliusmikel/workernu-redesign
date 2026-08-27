<?php
/**
 * Admin page — Settings → WorkerNu, with sections for each subsystem.
 * Currently just Trustpilot, but designed to grow as more global settings move
 * out of per-page sections.
 */
namespace WorkerNu\Settings\AdminPage;

use WorkerNu\Settings\Trustpilot;
use WorkerNu\Settings\Seo;

if (!defined('ABSPATH')) exit;

/**
 * Register the top-level "WorkerNu" menu and its sub-pages.
 *
 * Pattern: the top-level menu's slug intentionally matches the FIRST sub-page's
 * slug (`workernu-dashboard`). WordPress would otherwise auto-generate a
 * duplicate sub-menu item labeled "WorkerNu"; using the same slug for both
 * means the second `add_submenu_page` call simply renames that duplicate to
 * the proper label. Clicking the top-level "WorkerNu" item in the sidebar
 * lands on the first sub-page (Dashboard).
 *
 * To add more pages later: just call `add_submenu_page('workernu-dashboard',
 * <title>, <label>, 'manage_options', <slug>, <callback>)`.
 */
function register(): void {
    $parent = 'workernu-dashboard';

    add_menu_page(
        __('WorkerNu', 'workernu-settings'),           // <title>
        __('WorkerNu', 'workernu-settings'),           // sidebar label
        'manage_options',
        $parent,
        __NAMESPACE__ . '\\render_dashboard',
        'dashicons-admin-generic',
        3                                              // high priority — just below Dashboard
    );

    add_submenu_page(
        $parent,
        __('Dashboard', 'workernu-settings'),
        __('Dashboard', 'workernu-settings'),
        'manage_options',
        'workernu-dashboard',
        __NAMESPACE__ . '\\render_dashboard'
    );

    add_submenu_page(
        $parent,
        __('SEO', 'workernu-settings'),
        __('SEO', 'workernu-settings'),
        'manage_options',
        'workernu-seo',
        __NAMESPACE__ . '\\render_seo'
    );

    add_submenu_page(
        $parent,
        __('Trustpilot', 'workernu-settings'),
        __('Trustpilot', 'workernu-settings'),
        'manage_options',
        'workernu-trustpilot',
        __NAMESPACE__ . '\\render_trustpilot'
    );
}

function render_dashboard(): void {
    if (!current_user_can('manage_options')) return;
    $tp_ok = Trustpilot\is_configured();
    $pr_ok = Seo\is_configured();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('WorkerNu', 'workernu-settings'); ?></h1>
        <p class="description">
            <?php esc_html_e('Site-wide configuration for the workernu theme. Pick a sub-page on the left to edit a specific subsystem.', 'workernu-settings'); ?>
        </p>

        <div class="card" style="max-width:640px;">
            <h2 class="title"><?php esc_html_e('SEO', 'workernu-settings'); ?></h2>
            <p>
                <?php if ($pr_ok): ?>
                    <span style="color:#00a32a;">●</span>
                    <?php esc_html_e('Configured. Sections will contribute structured-data fragments to pages they appear on.', 'workernu-settings'); ?>
                <?php else: ?>
                    <span style="color:#dba617;">●</span>
                    <?php esc_html_e('Not configured. Sections won\'t emit their SoftwareApplication / Offer / Review schemas until the basics are filled.', 'workernu-settings'); ?>
                <?php endif; ?>
            </p>
            <p>
                <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=workernu-seo')); ?>">
                    <?php esc_html_e('Edit SEO settings', 'workernu-settings'); ?>
                </a>
            </p>
        </div>

        <div class="card" style="max-width:640px; margin-top:20px;">
            <h2 class="title"><?php esc_html_e('Trustpilot', 'workernu-settings'); ?></h2>
            <p>
                <?php if ($tp_ok): ?>
                    <span style="color:#00a32a;">●</span>
                    <?php esc_html_e('Live widget configured.', 'workernu-settings'); ?>
                <?php else: ?>
                    <span style="color:#dba617;">●</span>
                    <?php esc_html_e('Not configured. The live TrustBox will not render until business unit ID and template ID are set.', 'workernu-settings'); ?>
                <?php endif; ?>
            </p>
            <p>
                <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=workernu-trustpilot')); ?>">
                    <?php esc_html_e('Edit Trustpilot settings', 'workernu-settings'); ?>
                </a>
            </p>
        </div>
    </div>
    <?php
}

function render_seo(): void {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('SEO', 'workernu-settings'); ?></h1>

        <form method="post" action="options.php">
            <?php settings_fields(Seo\OPTION_GROUP); ?>

            <p class="description">
                <?php esc_html_e('Describes the product entity. Sections that emit structured data (Hero → SoftwareApplication, Pricing → Offer, Testimonials → Review, etc.) read from here. Fill once.', 'workernu-settings'); ?>
            </p>

            <table class="form-table" role="presentation">
                <?php foreach (Seo\SEO_FIELDS as $key => $field):
                    $option = $field['option'];
                    $value  = get_option($option, $field['default']);
                    ?>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($option); ?>"><?php echo esc_html($field['label']); ?></label>
                        </th>
                        <td>
                            <?php if ($key === 'screenshot_id'): ?>
                                <?php render_image_picker($option, (int) $value); ?>
                            <?php elseif ($key === 'description'): ?>
                                <textarea id="<?php echo esc_attr($option); ?>"
                                          name="<?php echo esc_attr($option); ?>"
                                          rows="3"
                                          class="large-text"><?php echo esc_textarea((string) $value); ?></textarea>
                            <?php elseif ($key === 'category'): ?>
                                <select id="<?php echo esc_attr($option); ?>" name="<?php echo esc_attr($option); ?>">
                                    <?php foreach (Seo\CATEGORY_OPTIONS as $v => $lbl): ?>
                                        <option value="<?php echo esc_attr($v); ?>" <?php selected($value, $v); ?>><?php echo esc_html($lbl); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text"
                                       id="<?php echo esc_attr($option); ?>"
                                       name="<?php echo esc_attr($option); ?>"
                                       value="<?php echo esc_attr((string) $value); ?>"
                                       class="regular-text">
                            <?php endif; ?>
                            <?php if (!empty($field['description'])): ?>
                                <p class="description"><?php echo esc_html($field['description']); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function render_trustpilot(): void {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Trustpilot', 'workernu-settings'); ?></h1>

        <form method="post" action="options.php">
            <?php settings_fields(Trustpilot\OPTION_GROUP); ?>

            <p class="description">
                <?php esc_html_e('Used by section templates that show the live TrustBox widget (hero, testimonials footer). Copy these from the embed snippet in your Trustpilot Business dashboard.', 'workernu-settings'); ?>
            </p>

            <table class="form-table" role="presentation">
                <?php foreach (Trustpilot\TRUSTPILOT_FIELDS as $key => $field):
                    $option = $field['option'];
                    $value  = get_option($option, $field['default']);
                    ?>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($option); ?>"><?php echo esc_html($field['label']); ?></label>
                        </th>
                        <td>
                            <?php if ($field['type'] === 'integer' && $key === 'image_id'): ?>
                                <?php render_image_picker($option, (int) $value); ?>
                            <?php elseif ($key === 'theme'): ?>
                                <select id="<?php echo esc_attr($option); ?>" name="<?php echo esc_attr($option); ?>">
                                    <?php foreach (['light' => 'Light', 'dark' => 'Dark'] as $v => $lbl): ?>
                                        <option value="<?php echo esc_attr($v); ?>" <?php selected($value, $v); ?>><?php echo esc_html($lbl); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text"
                                       id="<?php echo esc_attr($option); ?>"
                                       name="<?php echo esc_attr($option); ?>"
                                       value="<?php echo esc_attr((string) $value); ?>"
                                       class="regular-text">
                            <?php endif; ?>
                            <?php if (!empty($field['description'])): ?>
                                <p class="description"><?php echo esc_html($field['description']); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Lightweight image-picker (uses the WordPress media library uploader).
 * Stores the attachment ID. Renders a thumbnail preview + clear button.
 */
function render_image_picker(string $name, int $id): void {
    $url = $id ? wp_get_attachment_image_url($id, 'thumbnail') : '';
    ?>
    <div class="workernu-image-picker" data-name="<?php echo esc_attr($name); ?>" style="display:flex; align-items:center; gap:12px;">
        <div class="workernu-image-picker__preview" style="width:80px; height:80px; border:1px solid #ccd0d4; background:#f0f0f1 center / contain no-repeat; border-radius:4px;<?php echo $url ? 'background-image:url(' . esc_url($url) . ');' : ''; ?>"></div>
        <div>
            <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr((string) $id); ?>" class="workernu-image-picker__id">
            <button type="button" class="button workernu-image-picker__choose"><?php esc_html_e('Choose image', 'workernu-settings'); ?></button>
            <button type="button" class="button-link-delete workernu-image-picker__clear" <?php echo $id ? '' : 'style="display:none;"'; ?>><?php esc_html_e('Clear', 'workernu-settings'); ?></button>
        </div>
    </div>
    <script>
    (function() {
        var pickers = document.querySelectorAll('.workernu-image-picker[data-name="<?php echo esc_js($name); ?>"]');
        pickers.forEach(function(p) {
            var preview = p.querySelector('.workernu-image-picker__preview');
            var idInput = p.querySelector('.workernu-image-picker__id');
            var clearBtn = p.querySelector('.workernu-image-picker__clear');
            p.querySelector('.workernu-image-picker__choose').addEventListener('click', function() {
                if (!window.wp || !wp.media) return;
                var frame = wp.media({ multiple: false, library: { type: 'image' } });
                frame.on('select', function() {
                    var att = frame.state().get('selection').first().toJSON();
                    idInput.value = att.id;
                    var url = (att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url);
                    preview.style.backgroundImage = "url(" + url + ")";
                    clearBtn.style.display = '';
                });
                frame.open();
            });
            clearBtn.addEventListener('click', function() {
                idInput.value = '';
                preview.style.backgroundImage = '';
                clearBtn.style.display = 'none';
            });
        });
    })();
    </script>
    <?php
    wp_enqueue_media();
}
