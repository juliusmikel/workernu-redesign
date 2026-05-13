<?php
namespace WorkerNu\Sections\Fields;

if (!defined('ABSPATH')) exit;

/**
 * The supported field types. Each entry has:
 *   - 'render'   : callable($field, $value, $input_name) -> outputs admin form HTML
 *   - 'sanitize' : callable($field, $raw) -> returns a clean value for storage
 *
 * A field is declared in a section's section.php like:
 *   ['name' => 'heading', 'type' => 'text', 'label' => 'Heading', 'translatable' => true]
 */
function types(): array {
    return [
        'text'      => ['render' => __NAMESPACE__ . '\\render_text',      'sanitize' => __NAMESPACE__ . '\\sanitize_text'],
        'textarea'  => ['render' => __NAMESPACE__ . '\\render_textarea',  'sanitize' => __NAMESPACE__ . '\\sanitize_textarea'],
        'rich_text' => ['render' => __NAMESPACE__ . '\\render_rich_text', 'sanitize' => __NAMESPACE__ . '\\sanitize_rich_text'],
        'icon'      => ['render' => __NAMESPACE__ . '\\render_icon',      'sanitize' => __NAMESPACE__ . '\\sanitize_icon'],
        'image'     => ['render' => __NAMESPACE__ . '\\render_image',     'sanitize' => __NAMESPACE__ . '\\sanitize_image'],
        'link'      => ['render' => __NAMESPACE__ . '\\render_link',      'sanitize' => __NAMESPACE__ . '\\sanitize_link'],
        'select'    => ['render' => __NAMESPACE__ . '\\render_select',    'sanitize' => __NAMESPACE__ . '\\sanitize_select'],
        'boolean'   => ['render' => __NAMESPACE__ . '\\render_boolean',   'sanitize' => __NAMESPACE__ . '\\sanitize_boolean'],
        'number'    => ['render' => __NAMESPACE__ . '\\render_number',    'sanitize' => __NAMESPACE__ . '\\sanitize_number'],
        'repeater'  => ['render' => __NAMESPACE__ . '\\render_repeater',  'sanitize' => __NAMESPACE__ . '\\sanitize_repeater'],
    ];
}

function render_field(array $field, $value, string $input_name): void {
    $types = types();
    $type  = $field['type'] ?? 'text';
    if (!isset($types[$type])) {
        echo '<div class="ws-field ws-field--unknown">Unknown field type: ' . esc_html($type) . '</div>';
        return;
    }
    call_user_func($types[$type]['render'], $field, $value, $input_name);
}

function sanitize_value(array $field, $raw) {
    $types = types();
    $type  = $field['type'] ?? 'text';
    if (!isset($types[$type])) return null;
    return call_user_func($types[$type]['sanitize'], $field, $raw);
}

/* ─────────────────────────────────────────────────────────────────
   Shared helpers
   ───────────────────────────────────────────────────────────────── */

function label_for(array $field): string {
    return esc_html($field['label'] ?? $field['name'] ?? '');
}

function is_translatable(array $field): bool {
    return !empty($field['translatable']);
}

function open_field(array $field, string $extra_class = ''): void {
    $type     = $field['type'] ?? 'text';
    $required = !empty($field['required']) ? ' ws-field--required' : '';
    $width    = !empty($field['width']) ? ' ws-field--width-' . sanitize_html_class((string) $field['width']) : '';
    echo '<div class="ws-field ws-field--' . esc_attr($type) . $required . $width . ($extra_class ? ' ' . esc_attr($extra_class) : '') . '">';
    if (!empty($field['label'])) {
        $required_mark = !empty($field['required']) ? ' <span class="ws-field__required" aria-hidden="true">*</span>' : '';
        echo '<label class="ws-field__label">' . label_for($field) . $required_mark . '</label>';
    }
    if (!empty($field['hint'])) {
        echo '<p class="ws-field__hint">' . esc_html((string) $field['hint']) . '</p>';
    }
}

/**
 * Render a segmented-control input. Native radios styled as a button bar.
 * Used by select fields with render_as=buttons and by rich_text's display picker.
 */
function render_segmented(array $options, string $current, string $input_name): void {
    if (!$options) return;
    if (!array_key_exists($current, $options)) $current = (string) array_key_first($options);
    echo '<div class="ws-segmented" role="radiogroup">';
    foreach ($options as $opt_value => $opt_label) {
        $checked = $current === (string) $opt_value ? ' checked' : '';
        echo '<label class="ws-segmented__option">';
        echo '<input type="radio" name="' . esc_attr($input_name) . '" value="' . esc_attr((string) $opt_value) . '"' . $checked . '>';
        echo '<span class="ws-segmented__label">' . esc_html((string) $opt_label) . '</span>';
        echo '</label>';
    }
    echo '</div>';
}

function close_field(): void {
    echo '</div>';
}

/**
 * Render N inputs for a translatable field. Language tabs are global (top of the meta box).
 * Each language's input is rendered into its own panel; JS toggles visibility based on the active lang.
 *
 * The `$render_input` callback receives ($input_name_for_lang, $value_for_lang, $lang).
 */
function render_translatable(array $field, $value, string $input_name, callable $render_input): void {
    $langs   = \WorkerNu\Lang\LANGUAGES;
    $default = \WorkerNu\Lang\DEFAULT_LANG;
    $values  = is_array($value) ? $value : [];

    echo '<div class="ws-translatable">';
    foreach ($langs as $lang) {
        $active = $lang === $default ? ' is-active' : '';
        $name   = $input_name . '[' . $lang . ']';
        $val    = $values[$lang] ?? '';
        echo '<div class="ws-translatable__panel' . $active . '" data-lang="' . esc_attr($lang) . '">';
        $render_input($name, $val, $lang);
        echo '</div>';
    }
    echo '</div>';
}

/* ─────────────────────────────────────────────────────────────────
   TEXT
   ───────────────────────────────────────────────────────────────── */

function render_text(array $field, $value, string $input_name): void {
    open_field($field);
    if (is_translatable($field)) {
        render_translatable($field, $value, $input_name, function ($name, $val) {
            echo '<input type="text" class="ws-input" name="' . esc_attr($name) . '" value="' . esc_attr((string) $val) . '">';
        });
    } else {
        $val = is_array($value) ? '' : (string) $value;
        echo '<input type="text" class="ws-input" name="' . esc_attr($input_name) . '" value="' . esc_attr($val) . '">';
    }
    close_field();
}

/* ─────────────────────────────────────────────────────────────────
   ICON
   Accepts either:
     - A Font Awesome class string, e.g. "fa-solid fa-star"
     - Or a full <i class="..."></i> / <svg>...</svg> HTML snippet
   Render templates should use the workernu_icon() helper, which handles both forms.
   ───────────────────────────────────────────────────────────────── */

function icon_allowed_html(): array {
    return [
        'i'    => ['class' => true, 'aria-hidden' => true, 'style' => true, 'data-icon' => true],
        'span' => ['class' => true, 'aria-hidden' => true, 'style' => true],
        'svg'  => ['class' => true, 'aria-hidden' => true, 'viewBox' => true, 'fill' => true, 'xmlns' => true, 'width' => true, 'height' => true, 'role' => true, 'focusable' => true],
        'path' => ['d' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true],
        'g'    => ['fill' => true, 'transform' => true],
        'use'  => ['href' => true, 'xlink:href' => true],
    ];
}

function render_icon(array $field, $value, string $input_name): void {
    open_field($field, 'ws-field--icon');
    $val = is_array($value) ? '' : (string) $value;
    echo '<input type="text" class="ws-input ws-input--icon" name="' . esc_attr($input_name) . '" value="' . esc_attr($val) . '" placeholder=\'fa-solid fa-star  OR  <i class="fa-solid fa-star"></i>\'>';
    close_field();
}

function sanitize_icon(array $field, $raw): string {
    $raw = is_array($raw) ? '' : (string) $raw;
    $trimmed = trim($raw);

    // If it looks like HTML, sanitize via wp_kses with icon-safe allow-list.
    if ($trimmed !== '' && str_starts_with($trimmed, '<')) {
        return trim(wp_kses($trimmed, icon_allowed_html()));
    }
    // Otherwise treat as a class string — strip HTML and other dangerous chars.
    return sanitize_text_field($raw);
}

function sanitize_text(array $field, $raw): mixed {
    if (is_translatable($field) && is_array($raw)) {
        $clean = [];
        foreach (\WorkerNu\Lang\LANGUAGES as $lang) {
            $clean[$lang] = sanitize_text_field((string) ($raw[$lang] ?? ''));
        }
        return $clean;
    }
    return sanitize_text_field((string) $raw);
}

/* ─────────────────────────────────────────────────────────────────
   TEXTAREA
   ───────────────────────────────────────────────────────────────── */

function render_textarea(array $field, $value, string $input_name): void {
    $rows = (int) ($field['rows'] ?? 4);
    open_field($field);
    if (is_translatable($field)) {
        render_translatable($field, $value, $input_name, function ($name, $val) use ($rows) {
            echo '<textarea class="ws-input ws-input--textarea" name="' . esc_attr($name) . '" rows="' . $rows . '">' . esc_textarea((string) $val) . '</textarea>';
        });
    } else {
        $val = is_array($value) ? '' : (string) $value;
        echo '<textarea class="ws-input ws-input--textarea" name="' . esc_attr($input_name) . '" rows="' . $rows . '">' . esc_textarea($val) . '</textarea>';
    }
    close_field();
}

function sanitize_textarea(array $field, $raw): mixed {
    if (is_translatable($field) && is_array($raw)) {
        $clean = [];
        foreach (\WorkerNu\Lang\LANGUAGES as $lang) {
            $clean[$lang] = sanitize_textarea_field((string) ($raw[$lang] ?? ''));
        }
        return $clean;
    }
    return sanitize_textarea_field((string) $raw);
}

/* ─────────────────────────────────────────────────────────────────
   RICH TEXT
   A textarea bundled with a "Display as" select. Stored shape:
     ['value' => string|['lt'=>..,'en'=>..], 'display' => 'paragraph'|'bullets'|'numbered']
   Templates render via workernu_text($data['<field>'], $class) — the helper
   reads both keys and outputs <p>/<ul>/<ol> with the variant class appended.
   ───────────────────────────────────────────────────────────────── */

function rich_text_default_variants(): array {
    return [
        'paragraph' => 'Paragraph',
        'bullets'   => 'Bullet list',
        'numbered'  => 'Numbered list',
    ];
}

function rich_text_variants(array $field): array {
    $variants = $field['variants'] ?? null;
    return is_array($variants) && $variants ? $variants : rich_text_default_variants();
}

function render_rich_text(array $field, $value, string $input_name): void {
    $value      = is_array($value) ? $value : [];
    $text_value = $value['value'] ?? (is_translatable($field) ? [] : '');
    $display    = (string) ($value['display'] ?? '');
    $variants   = rich_text_variants($field);
    if (!array_key_exists($display, $variants)) $display = (string) array_key_first($variants);

    $rows = (int) ($field['rows'] ?? 5);

    open_field($field, 'ws-field--rich-text');

    if (is_translatable($field)) {
        render_translatable($field, $text_value, $input_name . '[value]', function ($name, $val) use ($rows) {
            echo '<textarea class="ws-input ws-input--textarea" name="' . esc_attr($name) . '" rows="' . $rows . '">' . esc_textarea((string) $val) . '</textarea>';
        });
    } else {
        $val = is_array($text_value) ? '' : (string) $text_value;
        echo '<textarea class="ws-input ws-input--textarea" name="' . esc_attr($input_name . '[value]') . '" rows="' . $rows . '">' . esc_textarea($val) . '</textarea>';
    }

    echo '<div class="ws-rich-text__display">';
    echo '<span class="ws-rich-text__display-label">' . esc_html__('Display as', 'workernu-sections') . '</span>';
    render_segmented($variants, $display, $input_name . '[display]');
    echo '</div>';

    close_field();
}

function sanitize_rich_text(array $field, $raw): array {
    $raw       = is_array($raw) ? $raw : [];
    $value_raw = $raw['value'] ?? '';

    if (is_translatable($field) && is_array($value_raw)) {
        $value = [];
        foreach (\WorkerNu\Lang\LANGUAGES as $lang) {
            $value[$lang] = sanitize_textarea_field((string) ($value_raw[$lang] ?? ''));
        }
    } else {
        $value = sanitize_textarea_field((string) (is_array($value_raw) ? '' : $value_raw));
    }

    $variants    = rich_text_variants($field);
    $display_raw = (string) ($raw['display'] ?? '');
    $display     = array_key_exists($display_raw, $variants) ? $display_raw : (string) array_key_first($variants);

    return [
        'value'   => $value,
        'display' => $display,
    ];
}

/* ─────────────────────────────────────────────────────────────────
   IMAGE
   Stores an attachment ID. Render-side, theme uses workernu_image_url().
   ───────────────────────────────────────────────────────────────── */

function render_image(array $field, $value, string $input_name): void {
    $id  = is_array($value) ? (int) ($value['id'] ?? 0) : (int) $value;
    $url = $id ? wp_get_attachment_image_url($id, 'medium') : '';
    open_field($field, 'ws-field--image');
    ?>
    <div class="ws-image" data-ws-image>
        <div class="ws-image__preview"<?php echo $url ? '' : ' hidden'; ?>>
            <?php if ($url): ?><img src="<?php echo esc_url($url); ?>" alt=""><?php endif; ?>
        </div>
        <input type="hidden" name="<?php echo esc_attr($input_name); ?>" value="<?php echo esc_attr((string) $id); ?>" data-ws-image-id>
        <div class="ws-image__controls">
            <button type="button" class="button" data-ws-image-pick>Choose image</button>
            <button type="button" class="button-link ws-image__remove" data-ws-image-clear<?php echo $id ? '' : ' hidden'; ?>>Remove</button>
        </div>
    </div>
    <?php
    close_field();
}

function sanitize_image(array $field, $raw): int {
    return (int) $raw;
}

/* ─────────────────────────────────────────────────────────────────
   LINK — { label: translatable, url: scalar, target: '_self'|'_blank' }
   ───────────────────────────────────────────────────────────────── */

function render_link(array $field, $value, string $input_name): void {
    $value  = is_array($value) ? $value : [];
    $label  = $value['label']  ?? '';
    $url    = $value['url']    ?? '';
    $target = $value['target'] ?? '_self';

    open_field($field, 'ws-field--link');
    ?>
    <div class="ws-link">
        <div class="ws-link__row">
            <span class="ws-link__sublabel">Label</span>
            <?php if (is_translatable($field)): ?>
                <?php render_translatable(
                    ['translatable' => true, 'name' => 'label'],
                    $label,
                    $input_name . '[label]',
                    function ($name, $val) {
                        echo '<input type="text" class="ws-input" name="' . esc_attr($name) . '" value="' . esc_attr((string) $val) . '">';
                    }
                ); ?>
            <?php else: ?>
                <input type="text" class="ws-input" name="<?php echo esc_attr($input_name . '[label]'); ?>" value="<?php echo esc_attr((string) (is_array($label) ? '' : $label)); ?>">
            <?php endif; ?>
        </div>
        <div class="ws-link__row">
            <span class="ws-link__sublabel">URL</span>
            <input type="text" class="ws-input" name="<?php echo esc_attr($input_name . '[url]'); ?>" value="<?php echo esc_attr((string) $url); ?>" placeholder="/path or https://...">
        </div>
        <div class="ws-link__row">
            <label class="ws-link__check">
                <input type="checkbox" name="<?php echo esc_attr($input_name . '[target]'); ?>" value="_blank" <?php checked($target, '_blank'); ?>>
                Open in new tab
            </label>
        </div>
    </div>
    <?php
    close_field();
}

function sanitize_link(array $field, $raw): array {
    $raw = is_array($raw) ? $raw : [];
    $label_raw = $raw['label'] ?? '';

    if (is_translatable($field) && is_array($label_raw)) {
        $label = [];
        foreach (\WorkerNu\Lang\LANGUAGES as $lang) {
            $label[$lang] = sanitize_text_field((string) ($label_raw[$lang] ?? ''));
        }
    } else {
        $label = sanitize_text_field((string) (is_array($label_raw) ? '' : $label_raw));
    }

    return [
        'label'  => $label,
        'url'    => esc_url_raw((string) ($raw['url'] ?? '')),
        'target' => ($raw['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
    ];
}

/* ─────────────────────────────────────────────────────────────────
   SELECT
   ───────────────────────────────────────────────────────────────── */

function render_select(array $field, $value, string $input_name): void {
    $options = $field['options'] ?? [];
    $val     = is_scalar($value) ? (string) $value : '';
    open_field($field);
    if (($field['render_as'] ?? '') === 'buttons') {
        render_segmented($options, $val, $input_name);
    } else {
        echo '<select class="ws-input" name="' . esc_attr($input_name) . '">';
        foreach ($options as $opt_value => $opt_label) {
            $selected = $val === (string) $opt_value ? ' selected' : '';
            echo '<option value="' . esc_attr((string) $opt_value) . '"' . $selected . '>' . esc_html((string) $opt_label) . '</option>';
        }
        echo '</select>';
    }
    close_field();
}

function sanitize_select(array $field, $raw): string {
    $options = array_keys($field['options'] ?? []);
    $raw = (string) $raw;
    return in_array($raw, array_map('strval', $options), true) ? $raw : (string) ($options[0] ?? '');
}

/* ─────────────────────────────────────────────────────────────────
   BOOLEAN
   ───────────────────────────────────────────────────────────────── */

function render_boolean(array $field, $value, string $input_name): void {
    $checked = !empty($value) && $value !== 'false';
    echo '<div class="ws-field ws-field--boolean">';
    echo '<label class="ws-boolean">';
    echo '<input type="checkbox" name="' . esc_attr($input_name) . '" value="1"' . ($checked ? ' checked' : '') . '>';
    echo '<span class="ws-boolean__label">' . esc_html((string) ($field['label'] ?? '')) . '</span>';
    echo '</label>';
    if (!empty($field['hint'])) {
        echo '<p class="ws-field__hint">' . esc_html((string) $field['hint']) . '</p>';
    }
    echo '</div>';
}

function sanitize_boolean(array $field, $raw): bool {
    return $raw === '1' || $raw === 1 || $raw === true || $raw === 'true' || $raw === 'on';
}

/* ─────────────────────────────────────────────────────────────────
   NUMBER
   ───────────────────────────────────────────────────────────────── */

function render_number(array $field, $value, string $input_name): void {
    $val  = is_numeric($value) ? (string) $value : '';
    $min  = isset($field['min'])  ? ' min="'  . esc_attr((string) $field['min'])  . '"' : '';
    $max  = isset($field['max'])  ? ' max="'  . esc_attr((string) $field['max'])  . '"' : '';
    $step = isset($field['step']) ? ' step="' . esc_attr((string) $field['step']) . '"' : '';
    open_field($field);
    echo '<input type="number" class="ws-input ws-input--number" name="' . esc_attr($input_name) . '" value="' . esc_attr($val) . '"' . $min . $max . $step . '>';
    close_field();
}

function sanitize_number(array $field, $raw): float|int {
    if (!is_numeric($raw)) return 0;
    if (isset($field['step']) && (float) $field['step'] < 1) return (float) $raw;
    return (int) $raw;
}

/* ─────────────────────────────────────────────────────────────────
   REPEATER
   A field that contains an array of sub-records. Each sub-record has
   the sub-fields declared under `fields`. Sub-fields can be any registered
   type (including translatable ones), but cannot themselves be another repeater.
   ───────────────────────────────────────────────────────────────── */

function render_repeater(array $field, $value, string $input_name): void {
    $items      = is_array($value) ? array_values($value) : [];
    $sub_fields = is_array($field['fields'] ?? null) ? $field['fields'] : [];

    open_field($field, 'ws-field--repeater');
    ?>
    <div class="ws-repeater" data-ws-repeater="<?php echo esc_attr($field['name'] ?? ''); ?>">
        <ol class="ws-repeater__list" data-ws-repeater-list>
            <?php foreach ($items as $i => $item):
                render_repeater_item($sub_fields, is_array($item) ? $item : [], $input_name . '[' . $i . ']');
            endforeach; ?>
        </ol>
        <button type="button" class="button ws-repeater__add" data-ws-repeater-add>
            + <?php echo esc_html($field['add_label'] ?? __('Add item', 'workernu-sections')); ?>
        </button>
        <template data-ws-repeater-template>
            <?php render_repeater_item($sub_fields, [], $input_name . '[__ITEM__]'); ?>
        </template>
    </div>
    <?php
    close_field();
}

function render_repeater_item(array $sub_fields, array $item, string $base_name): void {
    ?>
    <li class="ws-repeater__item" data-ws-repeater-item>
        <div class="ws-repeater__bar">
            <div class="ws-repeater__move">
                <button type="button" class="ws-repeater__move-btn" data-ws-repeater-move="up" aria-label="<?php esc_attr_e('Move up', 'workernu-sections'); ?>">
                    <span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
                </button>
                <button type="button" class="ws-repeater__move-btn" data-ws-repeater-move="down" aria-label="<?php esc_attr_e('Move down', 'workernu-sections'); ?>">
                    <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                </button>
            </div>
            <button type="button" class="ws-repeater__remove" data-ws-repeater-remove aria-label="<?php esc_attr_e('Remove', 'workernu-sections'); ?>">
                <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
            </button>
        </div>
        <div class="ws-repeater__fields">
            <?php foreach ($sub_fields as $sub):
                $sub_name = $sub['name'] ?? null;
                if (!$sub_name) continue;
                if (($sub['type'] ?? '') === 'repeater') continue; // disallow nested repeaters in v0.1
                render_field($sub, $item[$sub_name] ?? null, $base_name . '[' . $sub_name . ']');
            endforeach; ?>
        </div>
    </li>
    <?php
}

function sanitize_repeater(array $field, $raw): array {
    if (!is_array($raw)) return [];
    $sub_fields = is_array($field['fields'] ?? null) ? $field['fields'] : [];

    $clean = [];
    foreach (array_values($raw) as $item_raw) {
        if (!is_array($item_raw)) continue;
        $item = [];
        foreach ($sub_fields as $sub) {
            $sub_name = $sub['name'] ?? null;
            if (!$sub_name) continue;
            if (($sub['type'] ?? '') === 'repeater') continue;
            $item[$sub_name] = sanitize_value($sub, $item_raw[$sub_name] ?? null);
        }
        if ($item) $clean[] = $item;
    }
    return $clean;
}
