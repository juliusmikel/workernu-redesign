<?php
/**
 * Pricing — centered header + tier cards + optional add-on pills.
 * Receives $data — all field + modifier values for this section instance.
 */

$heading        = workernu_t($data['heading']    ?? '');
$subheading     = workernu_t($data['subheading'] ?? '');
$tiers          = is_array($data['tiers']  ?? null) ? $data['tiers']  : [];
$addons_heading = workernu_t($data['addons_heading'] ?? '');
$addons         = is_array($data['addons'] ?? null) ? $data['addons'] : [];
$classes        = workernu_section_classes($data, 'pricing');
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="pricing">
    <div class="section--pricing__inner container">

        <?php if ($heading !== '' || $subheading !== ''): ?>
            <header class="section--pricing__header" data-animate-item="header">
                <?php if ($heading !== ''): ?>
                    <h2 class="section--pricing__heading"><?php echo wp_kses_post($heading); ?></h2>
                <?php endif; ?>
                <?php if ($subheading !== ''): ?>
                    <p class="section--pricing__sub"><?php echo nl2br(wp_kses_post($subheading)); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php if ($tiers): ?>
            <ul class="section--pricing__tiers" data-animate-item="tiers">
                <?php foreach ($tiers as $tier):
                    $title  = workernu_t($tier['title']        ?? '');
                    $price  = workernu_t($tier['price']        ?? '');
                    $suffix = workernu_t($tier['price_suffix'] ?? '');
                    $unit   = workernu_t($tier['unit']         ?? '');
                    $feat_raw = workernu_t($tier['features']   ?? '');
                    $cta_l  = workernu_t($tier['cta_label']    ?? '');
                    $cta_u  = (string) ($tier['cta_url'] ?? '');
                    $badge  = workernu_t($tier['badge']        ?? '');
                    $features = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $feat_raw) ?: [])));
                    $is_highlighted = $badge !== '';
                    if ($title === '' && $price === '') continue;
                    ?>
                    <li class="section--pricing__tier<?php echo $is_highlighted ? ' is-highlighted' : ''; ?>" data-animate-item="tier">
                        <?php if ($badge !== ''): ?>
                            <span class="section--pricing__badge"><?php echo wp_kses_post($badge); ?></span>
                        <?php endif; ?>

                        <?php if ($title !== ''): ?>
                            <h3 class="section--pricing__title"><?php echo wp_kses_post($title); ?></h3>
                        <?php endif; ?>

                        <?php if ($price !== '' || $suffix !== ''): ?>
                            <div class="section--pricing__price-row">
                                <?php if ($price !== ''): ?>
                                    <span class="section--pricing__price"><?php echo wp_kses_post($price); ?></span>
                                <?php endif; ?>
                                <?php if ($suffix !== ''): ?>
                                    <span class="section--pricing__price-suffix"><?php echo wp_kses_post($suffix); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($unit !== ''): ?>
                            <p class="section--pricing__unit"><?php echo wp_kses_post($unit); ?></p>
                        <?php endif; ?>

                        <?php if ($features): ?>
                            <ul class="section--pricing__features">
                                <?php foreach ($features as $f): ?>
                                    <li><i class="fa-solid fa-check" aria-hidden="true"></i><span><?php echo wp_kses_post($f); ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if ($cta_l !== '' && $cta_u !== ''): ?>
                            <a class="btn btn--primary" href="<?php echo esc_url(workernu_localize_url($cta_u)); ?>">
                                <?php echo wp_kses_post($cta_l); ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($addons): ?>
            <div class="section--pricing__addons" data-animate-item="addons">
                <?php if ($addons_heading !== ''): ?>
                    <p class="section--pricing__addons-heading"><?php echo wp_kses_post($addons_heading); ?></p>
                <?php endif; ?>
                <ul class="section--pricing__addons-list">
                    <?php foreach ($addons as $a):
                        $icon  = (string) ($a['icon'] ?? '');
                        $lbl   = workernu_t($a['label'] ?? '');
                        $price = workernu_t($a['price'] ?? '');
                        if ($lbl === '' && $price === '') continue;
                        ?>
                        <li class="section--pricing__addon">
                            <?php if ($icon !== ''): ?>
                                <span class="section--pricing__addon-icon"><?php echo workernu_icon($icon); ?></span>
                            <?php endif; ?>
                            <?php if ($lbl !== ''): ?>
                                <span class="section--pricing__addon-label"><?php echo wp_kses_post($lbl); ?></span>
                            <?php endif; ?>
                            <?php if ($price !== ''): ?>
                                <span class="section--pricing__addon-price"><?php echo wp_kses_post($price); ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

    </div>
</section>
