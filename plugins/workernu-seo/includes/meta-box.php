<?php
namespace WorkerNu\SEO\MetaBox;

use WorkerNu\SEO\Options;

if (!defined('ABSPATH')) exit;

const NONCE_FIELD  = 'workernu_seo_nonce';
const NONCE_ACTION = 'workernu_seo_save';

function register(): void {
    $post_types = apply_filters('workernu_seo_post_types', ['page', 'post']);
    foreach ($post_types as $post_type) {
        add_meta_box(
            'workernu-seo',
            __('SEO', 'workernu-seo'),
            __NAMESPACE__ . '\\render',
            $post_type,
            'side',
            'low'
        );
    }
}

function render(\WP_Post $post): void {
    wp_nonce_field(NONCE_ACTION, NONCE_FIELD);

    $title       = (array) get_post_meta($post->ID, Options\META_TITLE, true);
    $description = (array) get_post_meta($post->ID, Options\META_DESCRIPTION, true);
    $og_image    = (int)   get_post_meta($post->ID, Options\META_OG_IMAGE, true);
    $noindex     = (bool)  get_post_meta($post->ID, Options\META_NOINDEX, true);

    $og_url = $og_image ? wp_get_attachment_image_url($og_image, 'medium') : '';
    ?>
    <div class="seo-mb">
        <div class="seo-mb__field">
            <label class="seo-mb__label"><?php esc_html_e('SEO title', 'workernu-seo'); ?></label>
            <p class="seo-mb__hint"><?php esc_html_e('Overrides the document &lt;title&gt;. Leave blank to use the post title.', 'workernu-seo'); ?></p>
            <?php foreach (['lt', 'en'] as $lang): ?>
                <div class="seo-mb__lang-row">
                    <span class="seo-mb__lang"><?php echo esc_html(strtoupper($lang)); ?></span>
                    <input type="text" name="workernu_seo[title][<?php echo esc_attr($lang); ?>]" value="<?php echo esc_attr($title[$lang] ?? ''); ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <div class="seo-mb__field">
            <label class="seo-mb__label"><?php esc_html_e('Meta description', 'workernu-seo'); ?></label>
            <p class="seo-mb__hint"><?php esc_html_e('Recommended length: 120–160 characters.', 'workernu-seo'); ?></p>
            <?php foreach (['lt', 'en'] as $lang): ?>
                <div class="seo-mb__lang-row">
                    <span class="seo-mb__lang"><?php echo esc_html(strtoupper($lang)); ?></span>
                    <textarea rows="2" name="workernu_seo[description][<?php echo esc_attr($lang); ?>]"><?php echo esc_textarea($description[$lang] ?? ''); ?></textarea>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="seo-mb__field">
            <label class="seo-mb__label"><?php esc_html_e('Social share image (OG)', 'workernu-seo'); ?></label>
            <p class="seo-mb__hint"><?php esc_html_e('Override the auto-picked image. Falls back to featured image, then site default.', 'workernu-seo'); ?></p>
            <div class="seo-mb__image" data-seo-image>
                <div class="seo-mb__image-preview"<?php echo $og_url ? '' : ' hidden'; ?>>
                    <?php if ($og_url): ?><img src="<?php echo esc_url($og_url); ?>" alt=""><?php endif; ?>
                </div>
                <input type="hidden" name="workernu_seo[og_image]" value="<?php echo esc_attr((string) $og_image); ?>" data-seo-image-id>
                <button type="button" class="button" data-seo-image-pick><?php esc_html_e('Choose image', 'workernu-seo'); ?></button>
                <button type="button" class="button-link seo-mb__image-clear" data-seo-image-clear<?php echo $og_image ? '' : ' hidden'; ?>><?php esc_html_e('Remove', 'workernu-seo'); ?></button>
            </div>
        </div>

        <div class="seo-mb__field">
            <label class="seo-mb__check">
                <input type="checkbox" name="workernu_seo[noindex]" value="1" <?php checked($noindex); ?>>
                <?php esc_html_e('Hide from search engines (noindex, nofollow)', 'workernu-seo'); ?>
            </label>
        </div>
    </div>
    <?php
}

function save(int $post_id, \WP_Post $post): void {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST[NONCE_FIELD]) || !wp_verify_nonce($_POST[NONCE_FIELD], NONCE_ACTION)) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $raw = $_POST['workernu_seo'] ?? [];
    if (!is_array($raw)) return;

    $title = [];
    foreach (['lt', 'en'] as $lang) {
        $title[$lang] = sanitize_text_field(wp_unslash($raw['title'][$lang] ?? ''));
    }
    $has_title = array_filter($title) !== [];
    $has_title
        ? update_post_meta($post_id, Options\META_TITLE, $title)
        : delete_post_meta($post_id, Options\META_TITLE);

    $description = [];
    foreach (['lt', 'en'] as $lang) {
        $description[$lang] = sanitize_textarea_field(wp_unslash($raw['description'][$lang] ?? ''));
    }
    $has_desc = array_filter($description) !== [];
    $has_desc
        ? update_post_meta($post_id, Options\META_DESCRIPTION, $description)
        : delete_post_meta($post_id, Options\META_DESCRIPTION);

    $og_image = (int) ($raw['og_image'] ?? 0);
    $og_image
        ? update_post_meta($post_id, Options\META_OG_IMAGE, $og_image)
        : delete_post_meta($post_id, Options\META_OG_IMAGE);

    $noindex = !empty($raw['noindex']);
    $noindex
        ? update_post_meta($post_id, Options\META_NOINDEX, 1)
        : delete_post_meta($post_id, Options\META_NOINDEX);
}
