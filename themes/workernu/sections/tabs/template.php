<?php
/**
 * Tabs — tabbed feature showcase.
 * Receives $data — all field + modifier values for this section instance.
 *
 * Interactivity (tab switching) lives in animations.js — vanilla JS, multi-
 * instance safe. Markup is accessible by default: first panel visible, the
 * rest hidden; JS upgrades the buttons into an ARIA tablist.
 */

$heading    = workernu_t($data['heading']    ?? '');
$subheading = workernu_t($data['subheading'] ?? '');
$tabs       = is_array($data['tabs'] ?? null) ? $data['tabs'] : [];
$classes    = workernu_section_classes($data, 'tabs');

// Stable instance id for aria wiring (sections store a stable _id).
$uid = sanitize_html_class((string) ($data['_id'] ?? uniqid('tabs-')));

// Drop tabs with no usable content.
$tabs = array_values(array_filter($tabs, function ($t) {
    return workernu_t($t['tab_label'] ?? '') !== '';
}));
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="tabs">
    <div class="section--tabs__inner container">

        <?php if ($heading !== '' || $subheading !== ''): ?>
            <header class="section--tabs__header" data-animate-item="header">
                <?php if ($heading !== ''): ?>
                    <h2 class="section--tabs__heading"><?php echo wp_kses_post($heading); ?></h2>
                <?php endif; ?>
                <?php if ($subheading !== ''): ?>
                    <p class="section--tabs__sub"><?php echo nl2br(wp_kses_post($subheading)); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php if ($tabs): ?>
            <div class="section--tabs__tablist" role="tablist" data-animate-item="tablist">
                <?php foreach ($tabs as $i => $t):
                    $label = workernu_t($t['tab_label'] ?? '');
                    ?>
                    <button type="button"
                            class="section--tabs__tab<?php echo $i === 0 ? ' is-active' : ''; ?>"
                            role="tab"
                            id="tab-<?php echo esc_attr($uid . '-' . $i); ?>"
                            aria-controls="panel-<?php echo esc_attr($uid . '-' . $i); ?>"
                            aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>">
                        <?php echo wp_kses_post($label); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="section--tabs__panels" data-animate-item="panels">
                <?php foreach ($tabs as $i => $t):
                    $panel_heading = workernu_t($t['panel_heading'] ?? '');
                    $body_html     = workernu_text($t['panel_body'] ?? null, 'section--tabs__body');
                    $cta_label     = workernu_t($t['cta_label'] ?? '');
                    $cta_url       = (string) ($t['cta_url'] ?? '');
                    $cta2_label    = workernu_t($t['cta2_label'] ?? '');
                    $cta2_url      = (string) ($t['cta2_url'] ?? '');
                    // image may be an int (legacy) or a { lt: id, en: id } map.
                    $image_value   = $t['image'] ?? 0;
                    $image_url     = workernu_image_url($image_value, 'large');
                    $image_alt     = workernu_t($t['image_alt'] ?? '');
                    if ($image_alt === '') $image_alt = workernu_image_alt($image_value);
                    ?>
                    <div class="section--tabs__panel<?php echo $i === 0 ? ' is-active' : ''; ?>"
                         role="tabpanel"
                         id="panel-<?php echo esc_attr($uid . '-' . $i); ?>"
                         aria-labelledby="tab-<?php echo esc_attr($uid . '-' . $i); ?>"
                         <?php echo $i === 0 ? '' : 'hidden'; ?>>

                        <div class="section--tabs__panel-text">
                            <?php if ($panel_heading !== ''): ?>
                                <h3 class="section--tabs__panel-heading"><?php echo wp_kses_post($panel_heading); ?></h3>
                            <?php endif; ?>
                            <?php if ($body_html !== ''): ?>
                                <div class="section--tabs__panel-body"><?php echo $body_html; ?></div>
                            <?php endif; ?>
                            <?php if (($cta_label !== '' && $cta_url !== '') || ($cta2_label !== '' && $cta2_url !== '')): ?>
                                <div class="section--tabs__ctas">
                                    <?php if ($cta_label !== '' && $cta_url !== ''): ?>
                                        <a class="btn btn--primary" href="<?php echo esc_url(workernu_localize_url($cta_url)); ?>">
                                            <?php echo wp_kses_post($cta_label); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($cta2_label !== '' && $cta2_url !== ''): ?>
                                        <a class="btn btn--outline" href="<?php echo esc_url(workernu_localize_url($cta2_url)); ?>">
                                            <?php echo wp_kses_post($cta2_label); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($image_url !== ''): ?>
                            <div class="section--tabs__panel-media">
                                <img <?php echo workernu_image_attrs($image_value, 'large', ['alt' => $image_alt]); ?>>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>
