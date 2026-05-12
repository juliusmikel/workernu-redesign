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
            <span class="ws-card__handle dashicons dashicons-move" title="<?php esc_attr_e('Drag to reorder', 'workernu-sections'); ?>"></span>
            <strong class="ws-card__title"><?php echo esc_html($def['label']); ?></strong>
            <span class="ws-card__type"><?php echo esc_html($type); ?></span>
            <button type="button" class="ws-card__btn ws-card__toggle" data-ws-toggle aria-label="<?php esc_attr_e('Collapse', 'workernu-sections'); ?>">▾</button>
            <button type="button" class="ws-card__btn ws-card__remove" data-ws-remove aria-label="<?php esc_attr_e('Remove', 'workernu-sections'); ?>">×</button>
        </div>
        <div class="ws-card__body">
            <input type="hidden" name="<?php echo esc_attr($input_base . '[_type]'); ?>" value="<?php echo esc_attr($type); ?>">
            <input type="hidden" name="<?php echo esc_attr($input_base . '[_id]'); ?>"   value="<?php echo esc_attr($id); ?>">

            <?php foreach ($def['fields'] as $field):
                $name  = $field['name'] ?? null;
                if (!$name) continue;
                $value = $data[$name] ?? null;
                Fields\render_field($field, $value, $input_base . '[' . $name . ']');
            endforeach; ?>

            <?php if (!empty($def['modifiers'])): ?>
                <div class="ws-modifiers">
                    <div class="ws-modifiers__header"><?php esc_html_e('Display', 'workernu-sections'); ?></div>
                    <?php foreach ($def['modifiers'] as $mod):
                        $name  = $mod['name'] ?? null;
                        if (!$name) continue;
                        $value = $data[$name] ?? ($mod['default'] ?? null);
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

    // SortableJS via CDN — vanilla, no jQuery dependency.
    wp_enqueue_script(
        'workernu-sortablejs',
        'https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js',
        [],
        '1.15.6',
        true
    );

    wp_enqueue_style(
        'workernu-builder',
        WORKERNU_SECTIONS_URL . 'admin/builder.css',
        [],
        filemtime(WORKERNU_SECTIONS_PATH . 'admin/builder.css')
    );

    wp_enqueue_script(
        'workernu-builder',
        WORKERNU_SECTIONS_URL . 'admin/builder.js',
        ['workernu-sortablejs'],
        filemtime(WORKERNU_SECTIONS_PATH . 'admin/builder.js'),
        true
    );
}
