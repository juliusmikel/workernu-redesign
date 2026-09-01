<?php
/**
 * Feature Highlight — "why choose us" value-prop band.
 * Receives $data — all field + modifier values for this section instance.
 */

$eyebrow    = workernu_t($data['eyebrow'] ?? '');
$heading    = workernu_t($data['heading'] ?? '');
$body       = workernu_t($data['body']    ?? '');
$ctas       = is_array($data['ctas']  ?? null) ? $data['ctas']  : [];
$items      = is_array($data['items'] ?? null) ? $data['items'] : [];
$layout     = (string) ($data['layout'] ?? 'split');
$is_columns = $layout === 'columns';
$classes    = workernu_section_classes($data, 'feature-highlight');

$has_intro = $eyebrow !== '' || $heading !== '' || $body !== '' || $ctas;

// Columns layout: two headed lists of bold-title + bulleted-description
// pairs, no icons — a different shape than items[]'s icon+title+desc rows,
// so it gets its own pair of fields/repeaters rather than reusing items[].
$col1_heading = workernu_t($data['column_1_heading'] ?? '');
$col1_items   = is_array($data['column_1_items'] ?? null) ? $data['column_1_items'] : [];
$col2_heading = workernu_t($data['column_2_heading'] ?? '');
$col2_items   = is_array($data['column_2_items'] ?? null) ? $data['column_2_items'] : [];
$has_columns  = $col1_heading !== '' || $col1_items || $col2_heading !== '' || $col2_items;

$render_column = function (string $heading_field, string $items_field, string $heading, array $col_items) use ($data) {
    if ($heading === '' && !$col_items) return;
    ?>
    <div class="section--feature-highlight__column">
        <?php if ($heading !== ''): ?>
            <h3 class="section--feature-highlight__column-heading"><?php echo workernu_inline_editable($data, $heading_field, 'text', wp_kses_post($heading), $heading); ?></h3>
        <?php endif; ?>
        <?php if ($col_items): ?>
            <ul class="section--feature-highlight__column-list">
                <?php foreach ($col_items as $it_i => $it):
                    $it_title = workernu_t($it['title']       ?? '');
                    $it_desc  = workernu_t($it['description'] ?? '');
                    if ($it_title === '' && $it_desc === '') continue;
                    ?>
                    <li class="section--feature-highlight__column-item">
                        <?php if ($it_title !== ''): ?>
                            <p class="section--feature-highlight__column-item-title"><?php echo workernu_inline_editable($data, "$items_field.$it_i.title", 'text', wp_kses_post($it_title), $it_title); ?></p>
                        <?php endif; ?>
                        <?php if ($it_desc !== ''): ?>
                            <div class="section--feature-highlight__column-item-row">
                                <span class="section--feature-highlight__column-dot" aria-hidden="true"></span>
                                <p class="section--feature-highlight__column-item-desc"><?php echo workernu_inline_editable($data, "$items_field.$it_i.description", 'textarea', nl2br(wp_kses_post($it_desc)), $it_desc); ?></p>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
};
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="feature-highlight">
    <div class="section--feature-highlight__inner container">

        <?php if ($has_intro): ?>
            <div class="section--feature-highlight__intro" data-animate-item="intro">
                <?php if ($eyebrow !== ''): ?>
                    <span class="section--feature-highlight__eyebrow"><?php echo workernu_inline_editable($data, 'eyebrow', 'text', wp_kses_post($eyebrow), $eyebrow); ?></span>
                <?php endif; ?>
                <?php if ($heading !== ''): ?>
                    <h2 class="section--feature-highlight__heading"><?php echo workernu_inline_editable($data, 'heading', 'text', wp_kses_post($heading), $heading); ?></h2>
                <?php endif; ?>
                <?php if ($body !== ''): ?>
                    <p class="section--feature-highlight__body"><?php echo workernu_inline_editable($data, 'body', 'textarea', nl2br(wp_kses_post($body)), $body); ?></p>
                <?php endif; ?>

                <?php if ($ctas): ?>
                    <div class="section--feature-highlight__ctas">
                        <?php foreach ($ctas as $cta_i => $cta):
                            $cta_label   = workernu_t($cta['label'] ?? '');
                            $cta_url     = (string) ($cta['url']     ?? '');
                            $cta_variant = (string) ($cta['variant'] ?? 'primary');
                            $cta_target  = (string) ($cta['target']  ?? '_self');
                            $cta_icon    = !empty($cta['icon']);
                            if ($cta_label === '' || $cta_url === '') continue;
                            ?>
                            <a class="btn btn--<?php echo esc_attr($cta_variant); ?>"
                               href="<?php echo esc_url(workernu_localize_url($cta_url)); ?>"
                               target="<?php echo esc_attr($cta_target); ?>"
                               <?php echo $cta_target === '_blank' ? 'rel="noopener"' : ''; ?>>
                                <?php if ($cta_icon): ?><i class="fa-solid fa-circle-play" aria-hidden="true"></i><?php endif; ?>
                                <?php echo workernu_inline_editable($data, "ctas.$cta_i.label", 'text', wp_kses_post($cta_label), $cta_label); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($has_columns): ?>
            <div class="section--feature-highlight__columns" data-animate-item="columns">
                <?php $render_column('column_1_heading', 'column_1_items', $col1_heading, $col1_items); ?>
                <?php $render_column('column_2_heading', 'column_2_items', $col2_heading, $col2_items); ?>
            </div>
        <?php endif; ?>

        <?php if (!$is_columns && $items): ?>
            <ul class="section--feature-highlight__items" data-animate-item="items">
                <?php foreach ($items as $item_i => $item):
                    $icon             = (string) ($item['icon'] ?? '');
                    // icon_image may be an int (legacy) or a { lt: id, en: id } map.
                    $icon_image_value = $item['icon_image'] ?? 0;
                    $icon_image       = workernu_image_url($icon_image_value, 'medium');
                    // Prefer per-item alt → attachment alt fallback.
                    $icon_alt         = workernu_t($item['icon_image_alt'] ?? '');
                    if ($icon_alt === '') $icon_alt = workernu_image_alt($icon_image_value);
                    $title            = workernu_t($item['title']       ?? '');
                    $description      = workernu_t($item['description'] ?? '');
                    if ($title === '' && $description === '') continue;
                    $has_visual       = $icon_image !== '' || $icon !== '';
                    ?>
                    <li class="section--feature-highlight__item" data-animate-item="item">
                        <div class="section--feature-highlight__item-head">
                            <?php if ($has_visual): ?>
                                <span class="section--feature-highlight__icon" aria-hidden="<?php echo $icon_alt === '' ? 'true' : 'false'; ?>">
                                    <?php if ($icon_image !== ''): ?>
                                        <img class="section--feature-highlight__img" <?php echo workernu_image_attrs($icon_image_value, 'medium', ['alt' => $icon_alt]); ?>>
                                    <?php else: ?>
                                        <?php echo workernu_icon($icon); ?>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($title !== ''): ?>
                                <h3 class="section--feature-highlight__item-title"><?php echo workernu_inline_editable($data, "items.$item_i.title", 'text', wp_kses_post($title), $title); ?></h3>
                            <?php endif; ?>
                        </div>
                        <?php if ($description !== ''): ?>
                            <div class="section--feature-highlight__item-text">
                                <p class="section--feature-highlight__item-desc"><?php echo workernu_inline_editable($data, "items.$item_i.description", 'textarea', nl2br(wp_kses_post($description)), $description); ?></p>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    </div>
</section>
