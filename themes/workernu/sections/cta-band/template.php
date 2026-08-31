<?php
/**
 * CTA Band — closing call-to-action band.
 * Receives $data — all field + modifier values for this section instance.
 */

$heading = workernu_t($data['heading'] ?? '');
$body    = workernu_t($data['body']    ?? '');
$ctas    = is_array($data['ctas'] ?? null) ? $data['ctas'] : [];
$classes = workernu_section_classes($data, 'cta-band');
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="cta-band">
    <div class="section--cta-band__inner container">

        <?php if ($heading !== ''): ?>
            <h2 class="section--cta-band__heading" data-animate-item="heading"><?php echo workernu_inline_editable($data, 'heading', 'text', wp_kses_post($heading), $heading); ?></h2>
        <?php endif; ?>

        <?php if ($body !== ''): ?>
            <p class="section--cta-band__body" data-animate-item="body"><?php echo workernu_inline_editable($data, 'body', 'textarea', nl2br(wp_kses_post($body)), $body); ?></p>
        <?php endif; ?>

        <?php if ($ctas): ?>
            <div class="section--cta-band__ctas" data-animate-item="ctas">
                <?php foreach ($ctas as $cta_i => $cta):
                    $cta_label   = workernu_t($cta['label'] ?? '');
                    $cta_url     = (string) ($cta['url']     ?? '');
                    $cta_variant = (string) ($cta['variant'] ?? 'primary');
                    $cta_target  = (string) ($cta['target']  ?? '_self');
                    if ($cta_label === '' || $cta_url === '') continue;
                    ?>
                    <a class="btn btn--<?php echo esc_attr($cta_variant); ?>"
                       href="<?php echo esc_url(workernu_localize_url($cta_url)); ?>"
                       target="<?php echo esc_attr($cta_target); ?>"
                       <?php echo $cta_target === '_blank' ? 'rel="noopener"' : ''; ?>>
                        <?php echo workernu_inline_editable($data, "ctas.$cta_i.label", 'text', wp_kses_post($cta_label), $cta_label); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>
