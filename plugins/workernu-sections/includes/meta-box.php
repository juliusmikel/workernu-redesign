<?php
namespace WorkerNu\Sections\MetaBox;

use WorkerNu\Sections\Fields;
use function WorkerNu\Sections\Registry\all as all_sections;
use function WorkerNu\Sections\Registry\get as get_section;

if (!defined('ABSPATH')) exit;

const NONCE_FIELD  = 'workernu_sections_nonce';
const NONCE_ACTION = 'workernu_sections_save';

/**
 * Register the Page Sections meta box on any post type that supports it.
 * By default: pages. Other post types can opt in via filter.
 */
function register(): void {
    $post_types = apply_filters('workernu_sections_post_types', ['page']);
    foreach ($post_types as $post_type) {
        add_meta_box(
            'workernu-sections',
            __('Page Sections', 'workernu-sections'),
            __NAMESPACE__ . '\\render',
            $post_type,
            'normal',
            'high'
        );
    }
}

/**
 * Render the meta box UI.
 */
function render(\WP_Post $post): void {
    wp_nonce_field(NONCE_ACTION, NONCE_FIELD);

    $registry = all_sections();
    $sections = get_post_meta($post->ID, WORKERNU_SECTIONS_META_KEY, true);
    if (!is_array($sections)) $sections = [];

    if (empty($registry)) {
        echo '<p class="ws-empty-registry">';
        echo esc_html__('No sections discovered. Add a section folder under your theme: ', 'workernu-sections');
        echo '<code>themes/' . esc_html(get_template()) . '/sections/&lt;name&gt;/section.php</code>';
        echo '</p>';
        return;
    }
    $langs   = \WorkerNu\Lang\LANGUAGES;
    $default = \WorkerNu\Lang\DEFAULT_LANG;
    ?>
    <div class="ws-builder" data-ws-builder data-ws-lang="<?php echo esc_attr($default); ?>">
        <div class="ws-toolbar">
            <div class="ws-toolbar__add">
                <select class="ws-toolbar__select" data-ws-add-type>
                    <option value="">— <?php esc_html_e('Add section', 'workernu-sections'); ?> —</option>
                    <?php foreach ($registry as $slug => $section): ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($section['label']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button button-primary" data-ws-add><?php esc_html_e('Add', 'workernu-sections'); ?></button>
                <button type="button" class="button ws-toolbar__paste" data-ws-paste title="<?php esc_attr_e('Paste a section copied to your clipboard from any page', 'workernu-sections'); ?>">
                    <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                    <?php esc_html_e('Paste', 'workernu-sections'); ?>
                </button>
            </div>

            <div class="ws-toolbar__lang" role="tablist" aria-label="<?php esc_attr_e('Edit language', 'workernu-sections'); ?>">
                <span class="ws-toolbar__lang-label"><?php esc_html_e('Editing in:', 'workernu-sections'); ?></span>
                <?php foreach ($langs as $lang):
                    $active = $lang === $default ? ' is-active' : ''; ?>
                    <button type="button" class="ws-lang-tab<?php echo $active; ?>" data-ws-lang-tab="<?php echo esc_attr($lang); ?>" role="tab" aria-selected="<?php echo $lang === $default ? 'true' : 'false'; ?>">
                        <?php echo esc_html(strtoupper($lang)); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <ol class="ws-list" data-ws-list>
            <?php foreach ($sections as $index => $section):
                $type = $section['_type'] ?? '';
                $def  = get_section($type);
                if (!$def) continue;
                render_card((int) $index, $type, $def, $section);
            endforeach; ?>
        </ol>

        <?php foreach ($registry as $slug => $section):
            $blank = ['_type' => $slug, '_id' => '__ID__'];
            ?>
            <template data-ws-template="<?php echo esc_attr($slug); ?>">
                <?php render_card(-1, $slug, $section, $blank); ?>
            </template>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Render one section card. Pass $index < 0 for the template version.
 */
function render_card(int $index, string $type, array $def, array $data): void {
    $idx_token = $index < 0 ? '__INDEX__' : (string) $index;
    $input_base = 'workernu_sections[' . $idx_token . ']';
    $id = $data['_id'] ?? '';
    ?>
    <li class="ws-card" data-ws-card data-ws-type="<?php echo esc_attr($type); ?>">
        <div class="ws-card__header">
            <div class="ws-card__move">
                <button type="button" class="ws-card__move-btn" data-ws-move="up" aria-label="<?php esc_attr_e('Move up', 'workernu-sections'); ?>">
                    <span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
                </button>
                <button type="button" class="ws-card__move-btn" data-ws-move="down" aria-label="<?php esc_attr_e('Move down', 'workernu-sections'); ?>">
                    <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                </button>
            </div>
            <strong class="ws-card__title"><?php echo esc_html($def['label']); ?></strong>
            <span class="ws-card__type"><?php echo esc_html($type); ?></span>
            <button type="button" class="ws-card__btn ws-card__toggle" data-ws-toggle aria-label="<?php esc_attr_e('Collapse', 'workernu-sections'); ?>">
                <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
            </button>
            <button type="button" class="ws-card__btn ws-card__duplicate" data-ws-duplicate aria-label="<?php esc_attr_e('Duplicate', 'workernu-sections'); ?>">
                <svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <rect x="3.5" y="0.75" width="9" height="9" rx="1.5"/>
                    <rect x="1" y="3.5" width="9" height="9" rx="1.5" fill="#f9fafb"/>
                </svg>
            </button>
            <button type="button" class="ws-card__btn ws-card__copy" data-ws-copy aria-label="<?php esc_attr_e('Copy section to clipboard', 'workernu-sections'); ?>" title="<?php esc_attr_e('Copy section to clipboard', 'workernu-sections'); ?>">
                <svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <rect x="4" y="1" width="8" height="10" rx="1.5"/>
                    <path d="M10 11.5V12.25A1.25 1.25 0 0 1 8.75 13.5h-5.5A1.25 1.25 0 0 1 2 12.25v-7.5A1.25 1.25 0 0 1 3.25 3.5H4"/>
                </svg>
            </button>
            <button type="button" class="ws-card__btn ws-card__remove" data-ws-remove aria-label="<?php esc_attr_e('Remove', 'workernu-sections'); ?>">
                <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
            </button>
        </div>
        <div class="ws-card__body">
            <input type="hidden" name="<?php echo esc_attr($input_base . '[_type]'); ?>" value="<?php echo esc_attr($type); ?>">
            <input type="hidden" name="<?php echo esc_attr($input_base . '[_id]'); ?>"   value="<?php echo esc_attr($id); ?>">

            <?php
            // Sections with content_defaults=true have two fields injected at
            // the front by Registry\discover() (content_source + the settings
            // link). Those render normally; everything after them is wrapped
            // in one show_if group keyed to content_source, so the whole
            // field set hides as a unit when "Site default" is picked —
            // reusing the existing per-field show_if mechanism (Fields\open_field
            // + builder.js's bindConditionalFields), just applied to a wrapper
            // instead of a single field. Fields inside the group keep any
            // show_if of their own (e.g. testimonials' footer_image) working
            // exactly as before — an ancestor being hidden doesn't affect how
            // JS evaluates a descendant's own condition.
            $wrapper_open = false;
            foreach ($def['fields'] as $field):
                $name  = $field['name'] ?? null;
                if (!$name) continue;
                $value = $data[$name] ?? null;

                $is_injected = $name === 'content_source' || $name === \WorkerNu\Sections\Registry\CONTENT_DEFAULTS_LINK_FIELD;

                if (!$is_injected && !empty($def['content_defaults']) && !$wrapper_open) {
                    echo '<div class="ws-content-fields" data-ws-show-if-field="content_source" data-ws-show-if-equals=\'["custom"]\'>';
                    $wrapper_open = true;
                }

                Fields\render_field($field, $value, $input_base . '[' . $name . ']');
            endforeach;
            if ($wrapper_open) echo '</div>';
            ?>

            <?php if (!empty($def['modifiers'])): ?>
                <div class="ws-modifiers">
                    <div class="ws-modifiers__header"><?php esc_html_e('Display', 'workernu-sections'); ?></div>
                    <?php foreach ($def['modifiers'] as $mod):
                        $name  = $mod['name'] ?? null;
                        if (!$name) continue;
                        $value = $data[$name] ?? ($mod['default'] ?? null);
                        // Modifiers are short option lists by nature — render selects as segmented buttons by default.
                        if (($mod['type'] ?? '') === 'select' && empty($mod['render_as'])) {
                            $mod['render_as'] = 'buttons';
                        }
                        Fields\render_field($mod, $value, $input_base . '[' . $name . ']');
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </li>
    <?php
}

/**
 * Enqueue admin assets on post-edit screens that show the meta box.
 */
function enqueue_admin(string $hook): void {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;

    $screen = get_current_screen();
    if (!$screen) return;
    $post_types = apply_filters('workernu_sections_post_types', ['page']);
    if (!in_array($screen->post_type, $post_types, true)) return;

    add_filter('admin_body_class', function ($classes) {
        return $classes . ' has-workernu-sections';
    });

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
