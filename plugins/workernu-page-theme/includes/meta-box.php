<?php
namespace WorkerNu\PageTheme\MetaBox;

use function WorkerNu\PageTheme\Registry\all;

if (!defined('ABSPATH')) exit;

const NONCE_FIELD  = 'workernu_page_theme_nonce';
const NONCE_ACTION = 'workernu_page_theme_save';

function register(): void {
    $post_types = apply_filters('workernu_page_theme_post_types', ['page']);
    foreach ($post_types as $pt) {
        add_meta_box(
            'workernu-page-theme',
            __('Page Theme', 'workernu-page-theme'),
            __NAMESPACE__ . '\\render',
            $pt,
            'side',
            'default'
        );
    }
}

function render(\WP_Post $post): void {
    wp_nonce_field(NONCE_ACTION, NONCE_FIELD);

    $themes = all();
    if (!$themes) {
        echo '<p class="pt-empty">';
        echo esc_html__('No themes registered. Add one via the ', 'workernu-page-theme');
        echo '<code>workernu_themes</code>';
        echo esc_html__(' filter in your theme.', 'workernu-page-theme');
        echo '</p>';
        return;
    }

    $current = (string) get_post_meta($post->ID, WORKERNU_PAGE_THEME_META_KEY, true);
    if ($current === '' || !isset($themes[$current])) {
        $current = isset($themes[WORKERNU_PAGE_THEME_DEFAULT]) ? WORKERNU_PAGE_THEME_DEFAULT : array_key_first($themes);
    }
    ?>
    <div class="pt-picker">
        <?php foreach ($themes as $slug => $theme):
            $checked = $slug === $current;
            $swatch  = $theme['swatch'] ?? '#ffffff';
            ?>
            <label class="pt-option<?php echo $checked ? ' is-active' : ''; ?>">
                <input type="radio" name="workernu_page_theme" value="<?php echo esc_attr($slug); ?>" <?php checked($checked); ?>>
                <span class="pt-swatch" style="background: <?php echo esc_attr($swatch); ?>"></span>
                <span class="pt-label"><?php echo esc_html($theme['label'] ?? $slug); ?></span>
            </label>
        <?php endforeach; ?>
    </div>
    <?php
}

function enqueue(string $hook): void {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;
    $screen = get_current_screen();
    if (!$screen) return;
    $post_types = apply_filters('workernu_page_theme_post_types', ['page']);
    if (!in_array($screen->post_type, $post_types, true)) return;

    wp_enqueue_style(
        'workernu-page-theme-admin',
        WORKERNU_PAGE_THEME_URL . 'admin/admin.css',
        [],
        filemtime(WORKERNU_PAGE_THEME_PATH . 'admin/admin.css')
    );
}
