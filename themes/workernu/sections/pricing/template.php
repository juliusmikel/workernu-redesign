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

        <?php
        // Pre-filtered once so the dots below index-match the cards exactly —
        // the old inline loop's `continue` on an empty tier would otherwise
        // let a dot and a card fall out of sync.
        $visible_tiers = [];
        foreach ($tiers as $tier) {
            $t_title = workernu_t($tier['title'] ?? '');
            $t_price = workernu_t($tier['price'] ?? '');
            if ($t_title === '' && $t_price === '') continue;
            $visible_tiers[] = [
                'title'    => $t_title,
                'price'    => $t_price,
                'suffix'   => workernu_t($tier['price_suffix'] ?? ''),
                'unit'     => workernu_t($tier['unit']         ?? ''),
                'features' => array_values(array_filter(array_map('trim',
                                preg_split('/\r?\n/', workernu_t($tier['features'] ?? '')) ?: []))),
                'cta_l'    => workernu_t($tier['cta_label'] ?? ''),
                'cta_u'    => (string) ($tier['cta_url']    ?? ''),
                'cta_ic'   => !empty($tier['cta_icon']),
                'badge'    => workernu_t($tier['badge']     ?? ''),
            ];
        }
        ?>
        <?php if ($visible_tiers): ?>
            <?php
            // __tiers-wrap: below 600px this becomes the swipeable-carousel
            // viewport (see style.css) and __dots becomes its pagination —
            // above that it's a plain wrapper, desktop is unchanged.
            ?>
            <div class="section--pricing__tiers-wrap" data-animate-item="tiers">
                <ul class="section--pricing__tiers">
                    <?php foreach ($visible_tiers as $t):
                        $is_highlighted = $t['badge'] !== '';
                        ?>
                        <li class="section--pricing__tier<?php echo $is_highlighted ? ' is-highlighted' : ''; ?>" data-animate-item="tier">
                            <?php if ($t['badge'] !== ''): ?>
                                <span class="section--pricing__badge"><?php echo wp_kses_post($t['badge']); ?></span>
                            <?php endif; ?>

                            <?php if ($t['title'] !== ''): ?>
                                <h3 class="section--pricing__title"><?php echo wp_kses_post($t['title']); ?></h3>
                            <?php endif; ?>

                            <?php if ($t['price'] !== '' || $t['suffix'] !== ''): ?>
                                <div class="section--pricing__price-row">
                                    <?php if ($t['price'] !== ''): ?>
                                        <span class="section--pricing__price"><?php echo wp_kses_post($t['price']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($t['suffix'] !== ''): ?>
                                        <span class="section--pricing__price-suffix"><?php echo wp_kses_post($t['suffix']); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($t['unit'] !== ''): ?>
                                <p class="section--pricing__unit"><?php echo wp_kses_post($t['unit']); ?></p>
                            <?php endif; ?>

                            <?php if ($t['features']): ?>
                                <ul class="section--pricing__features">
                                    <?php foreach ($t['features'] as $f): ?>
                                        <li><i class="fa-solid fa-check" aria-hidden="true"></i><span><?php echo wp_kses_post($f); ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if ($t['cta_l'] !== '' && $t['cta_u'] !== ''): ?>
                                <a class="btn btn--primary" href="<?php echo esc_url(workernu_localize_url($t['cta_u'])); ?>">
                                    <?php if ($t['cta_ic']): ?><i class="fa-solid fa-circle-play" aria-hidden="true"></i><?php endif; ?>
                                    <?php echo wp_kses_post($t['cta_l']); ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (count($visible_tiers) > 1): ?>
                    <div class="section--pricing__dots" role="group" aria-label="<?php esc_attr_e('Pricing plans', 'workernu'); ?>">
                        <?php foreach ($visible_tiers as $i => $t): ?>
                            <button type="button"
                                    class="section--pricing__dot<?php echo $i === 0 ? ' is-active' : ''; ?>"
                                    data-pricing-dot="<?php echo esc_attr($i); ?>"
                                    aria-label="<?php echo esc_attr($t['title'] !== '' ? $t['title'] : sprintf(__('Plan %d', 'workernu'), $i + 1)); ?>"
                                    <?php echo $i === 0 ? 'aria-current="true"' : ''; ?>></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
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
