<?php
/**
 * Zigzag Rows — alternating media + text feature rows.
 * Receives $data — all field + modifier values for this section instance.
 *
 * Each row mirrors the hero's two-column structure: a content column
 * (eyebrow + title + body + CTA) and a media column with the image
 * absolutely positioned for height-locking and outer-side bleed.
 * Backward-compat: legacy modifier `first_media` still resolves if present.
 */

$heading    = workernu_t($data['heading']    ?? '');
$subheading = workernu_t($data['subheading'] ?? '');
$rows       = is_array($data['rows'] ?? null) ? $data['rows'] : [];
$first_side = (string) ($data['first_side'] ?? $data['first_media'] ?? 'right');
$classes    = workernu_section_classes($data, 'zigzag-rows');
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="zigzag-rows">
    <div class="section--zigzag-rows__inner container">

        <?php if ($heading !== '' || $subheading !== ''): ?>
            <div class="section--zigzag-rows__header" data-animate-item="header">
                <?php if ($heading !== ''): ?>
                    <h2 class="section--zigzag-rows__heading"><?php echo workernu_inline_editable($data, 'heading', 'text', wp_kses_post($heading), $heading); ?></h2>
                <?php endif; ?>
                <?php if ($subheading !== ''): ?>
                    <p class="section--zigzag-rows__sub"><?php echo workernu_inline_editable($data, 'subheading', 'textarea', nl2br(wp_kses_post($subheading)), $subheading); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($rows): ?>
            <div class="section--zigzag-rows__rows" data-animate-item="rows">
                <?php foreach (array_values($rows) as $i => $row):
                    // image may be an int (legacy) or a { lt: id, en: id } map.
                    $image_value = $row['image'] ?? 0;
                    $image_url   = workernu_image_url($image_value, 'large');
                    // Per-row alt override → attachment alt fallback.
                    $image_alt   = workernu_t($row['image_alt'] ?? '');
                    if ($image_alt === '') $image_alt = workernu_image_alt($image_value);

                    $eyebrow       = workernu_t($row['eyebrow'] ?? '');
                    $eyebrow_style = (string) ($row['eyebrow_style'] ?? 'plain');
                    $title         = workernu_t($row['title']   ?? '');
                    $body_html     = workernu_text($row['body'] ?? null, 'section--zigzag-rows__body');
                    $body_raw      = workernu_t($row['body']['value'] ?? '');
                    $cta_label     = workernu_t($row['cta_label'] ?? '');
                    $cta_url       = (string) ($row['cta_url'] ?? '');
                    if ($title === '' && $body_html === '' && $image_url === '') continue;

                    // Image side: row 0 uses the section's first_side modifier;
                    // every subsequent row flips. Even index = first_side, odd = opposite.
                    $row_side = ($i % 2 === 0)
                        ? $first_side
                        : ($first_side === 'right' ? 'left' : 'right');
                    ?>
                    <div class="section--zigzag-rows__row section--zigzag-rows__row--image-<?php echo esc_attr($row_side); ?>" data-animate-item="row">

                        <div class="section--zigzag-rows__content">
                            <?php if ($eyebrow !== ''): ?>
                                <span class="section--zigzag-rows__eyebrow section--zigzag-rows__eyebrow--<?php echo esc_attr($eyebrow_style); ?>"><?php echo workernu_inline_editable($data, "rows.$i.eyebrow", 'text', wp_kses_post($eyebrow), $eyebrow); ?></span>
                            <?php endif; ?>
                            <?php if ($title !== ''): ?>
                                <h3 class="section--zigzag-rows__title"><?php echo workernu_inline_editable($data, "rows.$i.title", 'text', wp_kses_post($title), $title); ?></h3>
                            <?php endif; ?>
                            <?php if ($body_html !== ''): ?>
                                <div class="section--zigzag-rows__body-wrap"><?php echo workernu_inline_editable($data, "rows.$i.body", 'rich_text', $body_html, $body_raw, 'div'); ?></div>
                            <?php endif; ?>
                            <?php if ($cta_label !== '' && $cta_url !== ''): ?>
                                <a class="btn btn--primary" href="<?php echo esc_url(workernu_localize_url($cta_url)); ?>">
                                    <?php echo workernu_inline_editable($data, "rows.$i.cta_label", 'text', wp_kses_post($cta_label), $cta_label); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ($image_url !== ''): ?>
                            <div class="section--zigzag-rows__media">
                                <img class="section--zigzag-rows__image" <?php echo workernu_image_attrs($image_value, 'large', ['alt' => $image_alt]); ?>>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>
