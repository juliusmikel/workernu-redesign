<?php
/**
 * Map — geographic coverage band.
 * Receives $data — all field + modifier values for this section instance.
 */

$heading   = workernu_t($data['heading'] ?? '');
$body      = workernu_t($data['body']    ?? '');
$image_id  = (int) ($data['image'] ?? 0);
$image_url = workernu_image_url($image_id, 'large');
// Per-section alt override → attachment alt fallback. Image itself is not
// translatable (geography is universal), only the alt text varies per language.
$image_alt = workernu_t($data['image_alt'] ?? '');
if ($image_alt === '') $image_alt = workernu_image_alt($image_id);
$pins      = is_array($data['pins'] ?? null) ? $data['pins'] : [];
$cta_label = workernu_t($data['cta_label'] ?? '');
$cta_url   = (string) ($data['cta_url'] ?? '');
$cta_icon  = !empty($data['cta_icon']);
$classes   = workernu_section_classes($data, 'map');
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="map">
    <div class="section--map__inner container">

        <?php if ($heading !== '' || $body !== ''): ?>
            <header class="section--map__header" data-animate-item="header">
                <?php if ($heading !== ''): ?>
                    <h2 class="section--map__heading"><?php echo workernu_inline_editable($data, 'heading', 'text', wp_kses_post($heading), $heading); ?></h2>
                <?php endif; ?>
                <?php if ($body !== ''): ?>
                    <p class="section--map__body"><?php echo workernu_inline_editable($data, 'body', 'textarea', nl2br(wp_kses_post($body)), $body); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php if ($image_url !== ''): ?>
            <div class="section--map__figure" data-animate-item="figure">
                <img class="section--map__image" <?php echo workernu_image_attrs($image_id, 'large', ['alt' => $image_alt]); ?>>

                <?php foreach ($pins as $pin):
                    $label = workernu_t($pin['label'] ?? '');
                    $x = max(0, min(100, (float) ($pin['x'] ?? 0)));
                    $y = max(0, min(100, (float) ($pin['y'] ?? 0)));
                    ?>
                    <span class="section--map__pin"
                          style="left: <?php echo esc_attr($x); ?>%; top: <?php echo esc_attr($y); ?>%;"
                          <?php echo $label !== '' ? 'title="' . esc_attr($label) . '" aria-label="' . esc_attr($label) . '"' : 'aria-hidden="true"'; ?>>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($cta_label !== '' && $cta_url !== ''): ?>
            <div class="section--map__cta-wrap" data-animate-item="cta">
                <a class="btn btn--primary" href="<?php echo esc_url(workernu_localize_url($cta_url)); ?>">
                    <?php if ($cta_icon): ?><i class="fa-solid fa-circle-play" aria-hidden="true"></i><?php endif; ?>
                    <?php echo workernu_inline_editable($data, 'cta_label', 'text', wp_kses_post($cta_label), $cta_label); ?>
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>
