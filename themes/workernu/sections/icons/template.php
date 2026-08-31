<?php
/**
 * Icons — icon + title + description grid.
 * Receives $data — all field + modifier values for this section instance.
 *
 * Per-item visual logic:
 *   - icon_image set → render <img>.
 *   - else icon set → workernu_icon() (FA class or raw HTML).
 *   - else → no visual, text-only item.
 *
 * When card_style=card and icon_position=top:
 *   - tag renders BEFORE the visual with an auto-number prefix ("01 — TAG")
 *   - icon is centred and large inside the card
 *   - title + description sit below
 */

$heading       = workernu_t($data['heading']    ?? '');
$subheading    = workernu_t($data['subheading'] ?? '');
$items         = is_array($data['items'] ?? null) ? $data['items'] : [];
$columns       = (string) ($data['columns']       ?? '2');
$icon_position = (string) ($data['icon_position'] ?? 'left');
$card_style    = (string) ($data['card_style']    ?? 'none');
$is_rail       = ($columns === 'rail-ltr' || $columns === 'rail-rtl') && count($items) >= 2;
$rail_dir      = $columns === 'rail-rtl' ? 'rtl' : 'ltr';
$is_steps      = $columns === 'steps';
$classes       = workernu_section_classes($data, 'icons');

// In card+top mode the tag floats above the icon with an auto-number prefix.
$tag_on_top = $icon_position === 'top' && $card_style === 'card';

// $index doubles as this item's real index in $data['items'] — true for
// every call site except the rail's aria-hidden duplicate pass (which
// always passes 0 for every clone), so editing is only offered on the
// non-duplicate render; the duplicates stay plain, unwrapped output.
$render_item = function (array $item, bool $aria_hidden, int $index = 0) use ($data, $tag_on_top, $is_steps) {
    $icon             = (string) ($item['icon'] ?? '');
    $icon_image_value = $item['icon_image'] ?? 0;
    $icon_image       = workernu_image_url($icon_image_value, 'medium');
    $icon_alt         = workernu_t($item['icon_image_alt'] ?? '');
    if ($icon_alt === '') $icon_alt = workernu_image_alt($icon_image_value);
    $tag              = workernu_t($item['tag']         ?? '');
    $title            = workernu_t($item['title']       ?? '');
    $description      = workernu_t($item['description'] ?? '');
    $has_visual       = $icon_image !== '' || $icon !== '';
    // Rail clones are laid out past the right screen edge, so a lazy loader
    // never intersects them and they scroll in blank on the second lap.
    // $aria_hidden is exactly the "this is a duplicate" flag.
    $img_loading      = $aria_hidden ? 'eager' : 'lazy';
    ?>
    <li class="section--icons__item"<?php echo $aria_hidden ? ' aria-hidden="true"' : ''; ?> data-animate-item="item">

        <?php if ($is_steps): ?>
            <span class="section--icons__step-num"><?php echo esc_html((string) ($index + 1)); ?></span>
        <?php endif; ?>

        <?php if ($tag_on_top && $tag !== ''):
            $num = sprintf('%02d', $index + 1);
            $tag_html = $aria_hidden ? wp_kses_post($tag) : workernu_inline_editable($data, "items.$index.tag", 'text', wp_kses_post($tag), $tag);
            ?>
            <span class="section--icons__tag" aria-hidden="true"><?php echo esc_html($num . ' — ') . $tag_html; ?></span>
        <?php endif; ?>

        <?php if ($has_visual): ?>
            <div class="section--icons__visual" aria-hidden="<?php echo $icon_alt === '' ? 'true' : 'false'; ?>">
                <?php if ($icon_image !== ''): ?>
                    <img class="section--icons__img" <?php echo workernu_image_attrs($icon_image_value, 'medium', ['alt' => $icon_alt, 'loading' => $img_loading]); ?>>
                <?php else: ?>
                    <span class="section--icons__fa"><?php echo workernu_icon($icon); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="section--icons__content">
            <?php if (!$tag_on_top && $tag !== ''): ?>
                <span class="section--icons__tag" aria-hidden="true"><?php echo $aria_hidden ? wp_kses_post($tag) : workernu_inline_editable($data, "items.$index.tag", 'text', wp_kses_post($tag), $tag); ?></span>
            <?php endif; ?>
            <?php if ($title !== ''): ?>
                <h3 class="section--icons__title"><?php echo $aria_hidden ? wp_kses_post($title) : workernu_inline_editable($data, "items.$index.title", 'text', wp_kses_post($title), $title); ?></h3>
            <?php endif; ?>
            <?php if ($description !== ''): ?>
                <p class="section--icons__desc"><?php echo $aria_hidden ? nl2br(wp_kses_post($description)) : workernu_inline_editable($data, "items.$index.description", 'textarea', nl2br(wp_kses_post($description)), $description); ?></p>
            <?php endif; ?>
        </div>

    </li>
    <?php
};
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="icons">
    <div class="section--icons__inner container">

        <?php if ($heading !== '' || $subheading !== ''): ?>
            <header class="section--icons__header" data-animate-item="header">
                <?php if ($heading !== ''): ?>
                    <h2 class="section--icons__heading"><?php echo workernu_inline_editable($data, 'heading', 'text', wp_kses_post($heading), $heading); ?></h2>
                <?php endif; ?>
                <?php if ($subheading !== ''): ?>
                    <p class="section--icons__sub"><?php echo workernu_inline_editable($data, 'subheading', 'textarea', nl2br(wp_kses_post($subheading)), $subheading); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php if ($items): ?>
            <?php if ($is_rail): ?>
                <div class="section--icons__rail section--icons__rail--<?php echo esc_attr($rail_dir); ?>" role="region" aria-label="<?php esc_attr_e('Scrolling icons', 'workernu'); ?>" data-animate-item="rail">
                    <ul class="section--icons__track">
                        <?php foreach ($items as $idx => $item) $render_item($item, false, (int) $idx); ?>
                        <?php foreach ($items as $item) $render_item($item, true, 0); ?>
                    </ul>
                </div>
            <?php elseif ($is_steps): ?>
                <ul class="section--icons__grid section--icons__grid--steps" data-animate-item="grid">
                    <?php foreach (array_values($items) as $idx => $item):
                        if ($idx > 0): ?>
                            <li class="section--icons__step-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></li>
                        <?php endif;
                        $render_item($item, false, (int) $idx);
                    endforeach; ?>
                </ul>
            <?php else: ?>
                <ul class="section--icons__grid" data-animate-item="grid">
                    <?php foreach ($items as $idx => $item) $render_item($item, false, (int) $idx); ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</section>
