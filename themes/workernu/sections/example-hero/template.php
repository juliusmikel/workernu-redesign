<?php
/**
 * Example template for the Hero section.
 *
 * Receives:
 *   $data — associative array of all field + modifier values for this section instance.
 *
 * Helpers used here:
 *   workernu_t()                — resolves a possibly-translatable value to a string in the current language
 *   workernu_image_url()        — resolves an image field to a URL
 *   workernu_image_alt()        — resolves an image field to alt text
 *   workernu_icon()             — resolves an icon field (class string OR raw HTML) to safe HTML
 *   workernu_section_classes()  — builds the BEM class string (section + slug + modifiers)
 */

$badge_icon  = (string) ($data['badge_icon'] ?? '');
$badge_label = workernu_t($data['badge_label'] ?? '');
$heading     = workernu_t($data['heading']     ?? '');
$subheading  = workernu_t($data['subheading']  ?? '');
$ctas        = is_array($data['ctas'] ?? null) ? $data['ctas'] : [];
$users_num   = workernu_t($data['users_count_number'] ?? '');
$users_lbl   = workernu_t($data['users_count_label']  ?? '');
$image_url   = workernu_image_url($data['image'] ?? 0, 'full');
$image_alt   = workernu_image_alt($data['image'] ?? 0);

$has_badge       = $badge_icon !== '' || $badge_label !== '';
$has_users_count = $users_num !== '' || $users_lbl !== '';
$classes         = workernu_section_classes($data, 'example-hero');
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="example-hero">
    <div class="section--example-hero__inner container">

        <div class="section--example-hero__content">

            <?php if ($has_badge): ?>
                <div class="section--example-hero__badge" data-animate-item="badge">
                    <?php if ($badge_icon !== ''): ?>
                        <span class="section--example-hero__badge-icon"><?php echo workernu_icon($badge_icon); ?></span>
                    <?php endif; ?>
                    <?php if ($badge_label !== ''): ?>
                        <span class="section--example-hero__badge-label"><?php echo esc_html($badge_label); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($heading !== ''): ?>
                <h1 class="section--example-hero__heading" data-animate-item="heading"><?php echo esc_html($heading); ?></h1>
            <?php endif; ?>

            <?php if ($subheading !== ''): ?>
                <p class="section--example-hero__sub" data-animate-item="sub"><?php echo nl2br(esc_html($subheading)); ?></p>
            <?php endif; ?>

            <?php if ($ctas): ?>
                <div class="section--example-hero__ctas" data-animate-item="ctas">
                    <?php foreach ($ctas as $cta):
                        $cta_label   = workernu_t($cta['label'] ?? '');
                        $cta_url     = (string) ($cta['url']    ?? '');
                        $cta_variant = (string) ($cta['variant'] ?? 'primary');
                        $cta_target  = (string) ($cta['target']  ?? '_self');
                        if ($cta_label === '' || $cta_url === '') continue;
                        ?>
                        <a class="section--example-hero__cta section--example-hero__cta--<?php echo esc_attr($cta_variant); ?>"
                           href="<?php echo esc_url($cta_url); ?>"
                           target="<?php echo esc_attr($cta_target); ?>"
                           <?php echo $cta_target === '_blank' ? 'rel="noopener"' : ''; ?>>
                            <?php echo esc_html($cta_label); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($has_users_count): ?>
                <div class="section--example-hero__users" data-animate-item="users">
                    <?php if ($users_num !== ''): ?>
                        <span class="section--example-hero__users-number"><?php echo esc_html($users_num); ?></span>
                    <?php endif; ?>
                    <?php if ($users_lbl !== ''): ?>
                        <span class="section--example-hero__users-label"><?php echo esc_html($users_lbl); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>

        <?php if ($image_url !== ''): ?>
            <div class="section--example-hero__media" data-animate-item="media">
                <img src="<?php echo esc_url($image_url); ?>"
                     alt="<?php echo esc_attr($image_alt); ?>"
                     loading="eager"
                     fetchpriority="high">
            </div>
        <?php endif; ?>

    </div>
</section>
