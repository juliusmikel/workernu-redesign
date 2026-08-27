<?php
/**
 * Testimonials — social-proof grid or sliding marquee.
 * Receives $data — all field + modifier values for this section instance.
 *
 * The card markup is pulled into a closure so the same shape renders in both the
 * static grid and the marquee tracks (which render each card twice for a seamless
 * loop: translateX 0% → -50% lands on an identical duplicate).
 */

$heading      = workernu_t($data['heading']    ?? '');
$subheading   = workernu_t($data['subheading'] ?? '');
$testimonials = is_array($data['testimonials'] ?? null) ? $data['testimonials'] : [];
$footer_type  = (string) ($data['footer_type'] ?? 'none');
// footer_image may be an int (legacy) or a { lt: id, en: id } map.
$footer_value = $data['footer_image'] ?? 0;
$footer_url   = (string) ($data['footer_url'] ?? '');
$footer_img   = workernu_image_url($footer_value, 'large');
$footer_alt   = workernu_t($data['footer_image_alt'] ?? '');
if ($footer_alt === '') $footer_alt = workernu_image_alt($footer_value);
$layout       = (string) ($data['layout'] ?? 'marquee');
$is_marquee   = $layout === 'marquee' && count($testimonials) >= 2;
$classes      = workernu_section_classes($data, 'testimonials');

$render_card = function (array $t, bool $aria_hidden = false) {
    $avatar_id    = (int) ($t['avatar'] ?? 0);
    $avatar_attrs = workernu_image_attrs($avatar_id, 'thumbnail');
    $avatar_url   = workernu_image_url($avatar_id, 'thumbnail');
    $avatar_alt   = workernu_image_alt($avatar_id);
    $flag_id      = (int) ($t['country_flag'] ?? 0);
    $flag_url     = workernu_image_url($flag_id, 'thumbnail');
    $name       = (string) ($t['name'] ?? '');
    $country    = (string) ($t['country_label'] ?? '');
    $rating     = max(1, min(5, (int) ($t['rating'] ?? 5) ?: 5));
    $quote      = workernu_t($t['quote'] ?? '');
    $role       = workernu_t($t['role']    ?? '');
    $company    = workernu_t($t['company'] ?? '');
    $highlight  = !empty($t['highlight']);
    // "Owner | Statybos UAB" / "Owner" / "Statybos UAB" — join whatever's filled.
    $role_line  = trim($role . ($role !== '' && $company !== '' ? ' | ' : '') . $company);
    $card_class = 'section--testimonials__card' . ($highlight ? ' is-highlighted' : '');
    ?>
    <div class="<?php echo esc_attr($card_class); ?>"<?php echo $aria_hidden ? ' aria-hidden="true"' : ''; ?> data-animate-item="card">
        <?php if ($flag_url !== ''): ?>
            <img class="section--testimonials__flag" <?php echo workernu_image_attrs($flag_id, 'thumbnail', ['alt' => $country]); ?>>
        <?php endif; ?>

        <div class="section--testimonials__head">
            <?php if ($avatar_url !== ''): ?>
                <img class="section--testimonials__avatar" <?php echo workernu_image_attrs($avatar_id, 'thumbnail', ['alt' => $avatar_alt !== '' ? $avatar_alt : $name]); ?>>
            <?php else: ?>
                <span class="section--testimonials__avatar section--testimonials__avatar--placeholder" aria-hidden="true">
                    <i class="fa-solid fa-user"></i>
                </span>
            <?php endif; ?>

            <div class="section--testimonials__meta">
                <div class="section--testimonials__stars" role="img" aria-label="<?php echo esc_attr(sprintf(__('Rated %d out of 5 stars', 'workernu'), $rating)); ?>">
                    <?php for ($i = 1; $i <= 5; $i++):
                        $cls = 'fa-solid fa-star' . ($i > $rating ? ' is-empty' : '');
                        ?>
                        <i class="<?php echo esc_attr($cls); ?>" aria-hidden="true"></i>
                    <?php endfor; ?>
                </div>
                <?php if ($name !== ''): ?>
                    <p class="section--testimonials__name"><?php echo wp_kses_post($name); ?></p>
                <?php endif; ?>
                <?php if ($role_line !== ''): ?>
                    <p class="section--testimonials__role"><?php echo wp_kses_post($role_line); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($quote !== ''): ?>
            <p class="section--testimonials__quote"><?php echo nl2br(wp_kses_post($quote)); ?></p>
        <?php endif; ?>
    </div>
    <?php
};

// For marquee, split the cards into two rows scrolling opposite directions.
$row1 = [];
$row2 = [];
if ($is_marquee) {
    $half = (int) ceil(count($testimonials) / 2);
    $row1 = array_slice($testimonials, 0, $half);
    $row2 = array_slice($testimonials, $half);
}
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="testimonials">
    <div class="section--testimonials__inner container">

        <?php if ($heading !== '' || $subheading !== ''): ?>
            <header class="section--testimonials__header" data-animate-item="header">
                <?php if ($heading !== ''): ?>
                    <h2 class="section--testimonials__heading"><?php echo wp_kses_post($heading); ?></h2>
                <?php endif; ?>
                <?php if ($subheading !== ''): ?>
                    <p class="section--testimonials__sub"><?php echo nl2br(wp_kses_post($subheading)); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php if ($testimonials): ?>
            <?php if ($is_marquee): ?>
                <div class="section--testimonials__marquee" role="region" aria-label="<?php esc_attr_e('Customer testimonials', 'workernu'); ?>" data-animate-item="marquee">
                    <?php foreach ([['row' => $row1, 'dir' => 'ltr'], ['row' => $row2, 'dir' => 'rtl']] as $row):
                        if (!$row['row']) continue;
                        ?>
                        <div class="section--testimonials__row section--testimonials__row--<?php echo esc_attr($row['dir']); ?>">
                            <div class="section--testimonials__track">
                                <?php foreach ($row['row'] as $t) $render_card($t, false); ?>
                                <?php foreach ($row['row'] as $t) $render_card($t, true); /* duplicate for seamless loop */ ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <ul class="section--testimonials__grid" data-animate-item="grid">
                    <?php foreach ($testimonials as $t): ?>
                        <li><?php $render_card($t, false); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($footer_type === 'image' && $footer_img !== ''): ?>
            <div class="section--testimonials__footer" data-animate-item="footer">
                <?php if ($footer_url !== ''): ?>
                    <a href="<?php echo esc_url($footer_url); ?>" target="_blank" rel="noopener">
                        <img <?php echo workernu_image_attrs($footer_value, 'large', ['alt' => $footer_alt]); ?>>
                    </a>
                <?php else: ?>
                    <img <?php echo workernu_image_attrs($footer_value, 'large', ['alt' => $footer_alt]); ?>>
                <?php endif; ?>
            </div>
        <?php elseif ($footer_type === 'trustpilot'): ?>
            <div class="section--testimonials__footer" data-animate-item="footer">
                <?php \WorkerNu\Settings\Trustpilot\render(); ?>
            </div>
        <?php endif; ?>

    </div>
</section>
