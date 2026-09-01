<?php
/**
 * Admin pages for editing site-wide "default" content on sections that opted
 * into `'content_defaults' => true` (calculator, pricing, testimonials,
 * people, pricing-calculator — see each section's section.php). One sub-page
 * per section type under Settings → WorkerNu.
 *
 * Reuses workernu-sections' own field-rendering engine (Fields\render_field /
 * Fields\sanitize_value) instead of a second implementation — these fields
 * are repeaters, translatable, image pickers, exactly like the per-page
 * section builder, so the same admin/builder.css + admin/builder.js assets
 * from that plugin are enqueued here too.
 *
 * Storage lives in workernu-sections (Defaults\get_default / save_default)
 * since the data shape is inherently a "sections" concern; this file is the
 * UI + save-handling layer only.
 */
namespace WorkerNu\Settings\SectionDefaultsAdmin;

if (!defined('ABSPATH')) exit;

const SAVE_ACTION  = 'workernu_save_section_default';
const NONCE_ACTION = 'workernu_save_section_default';

const SECTION_TYPES = [
    'calculator'         => 'Savings Calculator',
    'pricing'            => 'Pricing',
    'testimonials'       => 'Reviews',
    'people'             => 'People',
    'pricing-calculator' => 'Pricing Calculator',
];

/**
 * Hook suffixes returned by add_submenu_page(), keyed by section type — used
 * to scope asset enqueueing to just these pages. Populated by register(),
 * read by enqueue_admin(); both run on admin_menu / admin_enqueue_scripts,
 * with admin_menu firing first, so the map is always filled by the time it's read.
 */
function &page_hooks(): array {
    static $hooks = [];
    return $hooks;
}

function register(): void {
    if (!function_exists('\\WorkerNu\\Sections\\Registry\\get')) return;

    $hooks = &page_hooks();
    foreach (SECTION_TYPES as $type => $title) {
        $hook = add_submenu_page(
            'workernu-dashboard',
            $title,
            $title,
            'manage_options',
            'workernu-' . $type . '-defaults',
            function () use ($type, $title) { render($type, $title); }
        );
        if ($hook) $hooks[$hook] = $type;
    }
}

function enqueue_admin(string $hook): void {
    if (!isset(page_hooks()[$hook])) return;
    if (!defined('WORKERNU_SECTIONS_URL') || !defined('WORKERNU_SECTIONS_PATH')) return;

    wp_enqueue_media();
    wp_enqueue_style(
        'workernu-builder',
        WORKERNU_SECTIONS_URL . 'admin/builder.css',
        [],
        filemtime(WORKERNU_SECTIONS_PATH . 'admin/builder.css')
    );
    wp_enqueue_script(
        'workernu-builder',
        WORKERNU_SECTIONS_URL . 'admin/builder.js',
        [],
        filemtime(WORKERNU_SECTIONS_PATH . 'admin/builder.js'),
        true
    );
}

function render(string $type, string $title): void {
    if (!current_user_can('manage_options')) return;

    if (!function_exists('\\WorkerNu\\Sections\\Registry\\get')) {
        echo '<div class="wrap"><h1>' . esc_html($title) . '</h1><p>' . esc_html__('The workernu Sections plugin must be active.', 'workernu-settings') . '</p></div>';
        return;
    }

    $def = \WorkerNu\Sections\Registry\get($type);
    if (!$def) {
        echo '<div class="wrap"><h1>' . esc_html($title) . '</h1><p>' . esc_html__('Section type not found.', 'workernu-settings') . '</p></div>';
        return;
    }

    $data  = \WorkerNu\Sections\Defaults\get_default($type);
    $langs = \WorkerNu\Lang\LANGUAGES;
    $lang  = \WorkerNu\Lang\DEFAULT_LANG;
    $saved = isset($_GET['workernu_saved']);
    ?>
    <div class="wrap">
        <h1><?php echo esc_html($title); ?></h1>
        <p class="description">
            <?php printf(
                esc_html__('Site-wide default content for the %s section. Any page using it with "Site default" selected pulls its content from here.', 'workernu-settings'),
                esc_html($def['label'] ?? $title)
            ); ?>
        </p>

        <?php if ($saved): ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Defaults saved.', 'workernu-settings'); ?></p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="<?php echo esc_attr(SAVE_ACTION); ?>">
            <input type="hidden" name="section_type" value="<?php echo esc_attr($type); ?>">
            <?php wp_nonce_field(NONCE_ACTION); ?>

            <div class="ws-builder" data-ws-builder data-ws-lang="<?php echo esc_attr($lang); ?>">
                <div class="ws-toolbar__lang" role="tablist" aria-label="<?php esc_attr_e('Edit language', 'workernu-settings'); ?>">
                    <span class="ws-toolbar__lang-label"><?php esc_html_e('Editing in:', 'workernu-settings'); ?></span>
                    <?php foreach ($langs as $l):
                        $active = $l === $lang ? ' is-active' : ''; ?>
                        <button type="button" class="ws-lang-tab<?php echo $active; ?>" data-ws-lang-tab="<?php echo esc_attr($l); ?>" role="tab" aria-selected="<?php echo $l === $lang ? 'true' : 'false'; ?>">
                            <?php echo esc_html(strtoupper($l)); ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div data-ws-card>
                    <?php foreach ($def['fields'] as $field):
                        $name = $field['name'] ?? null;
                        if (!$name) continue;
                        // The content_source toggle + its settings-link only make
                        // sense on a per-page instance, not here — this page IS
                        // the "site default" content, always shown in full.
                        if ($name === 'content_source' || ($field['type'] ?? '') === 'settings_link') continue;
                        \WorkerNu\Sections\Fields\render_field($field, $data[$name] ?? null, 'workernu_default[' . $name . ']');
                    endforeach; ?>
                </div>
            </div>

            <?php submit_button(__('Save defaults', 'workernu-settings')); ?>
        </form>
    </div>
    <?php
}

function handle_save(): void {
    if (!current_user_can('manage_options')) wp_die(__('Not allowed.', 'workernu-settings'));
    check_admin_referer(NONCE_ACTION);

    $type = isset($_POST['section_type']) ? sanitize_key((string) $_POST['section_type']) : '';
    if (!array_key_exists($type, SECTION_TYPES)) wp_die(__('Unknown section type.', 'workernu-settings'));
    if (!function_exists('\\WorkerNu\\Sections\\Registry\\get')) wp_die(__('The workernu Sections plugin must be active.', 'workernu-settings'));

    $def = \WorkerNu\Sections\Registry\get($type);
    if (!$def) wp_die(__('Section type not found.', 'workernu-settings'));

    $raw = is_array($_POST['workernu_default'] ?? null) ? $_POST['workernu_default'] : [];

    $clean = [];
    foreach ($def['fields'] as $field) {
        $name = $field['name'] ?? null;
        if (!$name) continue;
        if ($name === 'content_source' || ($field['type'] ?? '') === 'settings_link') continue;
        $clean[$name] = \WorkerNu\Sections\Fields\sanitize_value($field, $raw[$name] ?? null);
    }

    \WorkerNu\Sections\Defaults\save_default($type, $clean);

    wp_safe_redirect(add_query_arg('workernu_saved', '1', admin_url('admin.php?page=workernu-' . $type . '-defaults')));
    exit;
}
