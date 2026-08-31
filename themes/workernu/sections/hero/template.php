<?php
/**
 * Hero — homepage hero section.
 *
 * Receives $data — all field + modifier values for this section instance.
 * Schema lives in section.php (owned by Julius). Fields used here:
 *   badge_icon, badge_label, heading, body (rich_text), ctas[],
 *   trustpilot_* (live TrustBox widget params + image fallback),
 *   users_avatars[], users_badge_label, users_count_number, users_count_label, image.
 * Modifiers: layout (right|left), spacing (tight|normal|loose).
 *
 * Layout note: content + media are a two-column grid inside .container. The media
 * cell stretches the full height of the hero (flush to the top, under the nav, and
 * to the bottom), with square corners.
 *
 * Trustpilot: we emit Trustpilot's own `.trustpilot-widget` div + their bootstrap
 * loader so the live rating renders from their system — no faked stars. If no
 * business unit ID is set, a static fallback image is shown instead.
 */

// Backward-compat: read the new `eyebrow_*` keys, falling back to the legacy
// `badge_*` keys for content saved before the rename.
$eyebrow_icon  = (string) ($data['eyebrow_icon']  ?? $data['badge_icon']  ?? '');
$eyebrow_label = workernu_t($data['eyebrow_label'] ?? $data['badge_label'] ?? '');
$heading     = workernu_t($data['heading']     ?? '');
$ctas        = is_array($data['ctas'] ?? null) ? $data['ctas'] : [];

// Trustpilot mode: which social-proof element to render (live widget vs static
// image vs nothing). Widget config now lives globally at Settings → WorkerNu.
$tp_mode      = (string) ($data['tp_mode'] ?? 'trustpilot');
$tp_image_id  = (int) ($data['trustpilot_image'] ?? 0);
$tp_image     = workernu_image_url($tp_image_id, 'medium');
$tp_image_alt = workernu_image_alt($tp_image_id);
$tp_review    = (string) ($data['trustpilot_review_url'] ?? '');

$badge_5th   = workernu_t($data['users_badge_label'] ?? '');
$users_num   = workernu_t($data['users_count_number'] ?? '');
$users_lbl   = workernu_t($data['users_count_label']  ?? '');
// Image may be a raw ID or a translatable map { lt: id, en: id } — pass the raw
// value to the helpers, which resolve it via workernu_t().
$image_value = $data['image'] ?? 0;
$image_url   = workernu_image_url($image_value, 'full');
$image_alt   = workernu_t($data['image_alt'] ?? '');
if ($image_alt === '') $image_alt = workernu_image_alt($image_value);

// Resolve avatar URLs — handles both the legacy repeater format
// ([['image'=>id], ...]) and the new gallery format ([id, id, ...]).
$avatars_raw = is_array($data['users_avatars'] ?? null) ? $data['users_avatars'] : [];
$avatar_urls = [];
foreach ($avatars_raw as $a) {
    $aid = is_array($a) ? (int) ($a['image'] ?? 0) : (int) $a;
    $url = workernu_image_url($aid, 'thumbnail');
    if ($url !== '') $avatar_urls[] = ['id' => $aid, 'url' => $url, 'alt' => workernu_image_alt($aid)];
}

$has_eyebrow = $eyebrow_icon !== '' || $eyebrow_label !== '';
$has_stack   = $avatar_urls || $badge_5th !== '';
$has_caption = $users_num !== '' || $users_lbl !== '';
$has_users   = $has_stack || $has_caption;

// tp_mode "trustpilot" → live widget (or its built-in fallback image from
// global settings). tp_mode "image" → the per-section trustpilot_image.
$tp_widget   = $tp_mode === 'trustpilot';
$tp_fallback = $tp_mode === 'image' && $tp_image !== '';
$has_tp      = $tp_widget || $tp_fallback;
$has_social  = $has_tp || $has_users;

$classes     = workernu_section_classes($data, 'hero');
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="hero">
    <div class="section--hero__inner container">

        <div class="section--hero__content">

            <?php if ($has_eyebrow): ?>
                <div class="section--hero__eyebrow" data-animate-item="eyebrow">
                    <?php if ($eyebrow_icon !== ''): ?>
                        <span class="section--hero__eyebrow-icon"><?php echo workernu_icon($eyebrow_icon); ?></span>
                    <?php endif; ?>
                    <?php if ($eyebrow_label !== ''): ?>
                        <span class="section--hero__eyebrow-label"><?php echo workernu_inline_editable($data, 'eyebrow_label', 'text', wp_kses_post($eyebrow_label), $eyebrow_label); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($heading !== ''): ?>
                <h1 class="section--hero__heading" data-animate-item="heading"><?php echo workernu_inline_editable($data, 'heading', 'text', wp_kses_post($heading), $heading); ?></h1>
            <?php endif; ?>

            <?php $body_html = workernu_text($data['body'] ?? null, 'section--hero__body'); ?>
            <?php $body_raw  = workernu_t($data['body']['value'] ?? ''); ?>
            <?php if ($body_html !== ''): ?>
                <div class="section--hero__body-wrap" data-animate-item="body"><?php echo workernu_inline_editable($data, 'body', 'rich_text', $body_html, $body_raw, 'div'); ?></div>
            <?php endif; ?>

            <?php if ($ctas): ?>
                <div class="section--hero__ctas" data-animate-item="ctas">
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

            <?php if ($has_social): ?>
                <div class="section--hero__social" data-animate-item="social">

                    <?php if ($tp_widget): ?>
                        <?php \WorkerNu\Settings\Trustpilot\render(
                            'section--hero__trustpilot trustpilot-widget',
                            'section--hero__trustpilot section--hero__trustpilot--image'
                        ); ?>
                    <?php elseif ($tp_fallback): ?>
                        <?php $tp_attrs = workernu_image_attrs($tp_image_id, 'medium', ['alt' => $tp_image_alt !== '' ? $tp_image_alt : 'Trustpilot']); ?>
                        <?php if ($tp_review !== ''): ?>
                            <a class="section--hero__trustpilot section--hero__trustpilot--image" href="<?php echo esc_url($tp_review); ?>" target="_blank" rel="noopener">
                                <img <?php echo $tp_attrs; ?>>
                            </a>
                        <?php else: ?>
                            <span class="section--hero__trustpilot section--hero__trustpilot--image">
                                <img <?php echo $tp_attrs; ?>>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($has_users): ?>
                        <div class="section--hero__users">
                            <?php if ($has_stack): ?>
                                <div class="section--hero__avatars">
                                    <?php foreach ($avatar_urls as $a): ?>
                                        <span class="section--hero__avatar">
                                            <img <?php echo workernu_image_attrs((int) $a['id'], 'thumbnail', ['alt' => $a['alt']]); ?>>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if ($badge_5th !== ''): ?>
                                        <span class="section--hero__avatar section--hero__avatar--badge"><?php echo workernu_inline_editable($data, 'users_badge_label', 'text', wp_kses_post($badge_5th), $badge_5th); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($has_caption): ?>
                                <div class="section--hero__users-caption">
                                    <?php if ($users_num !== ''): ?>
                                        <span class="section--hero__users-number"><?php echo workernu_inline_editable($data, 'users_count_number', 'text', wp_kses_post($users_num), $users_num); ?></span>
                                    <?php endif; ?>
                                    <?php if ($users_lbl !== ''): ?>
                                        <span class="section--hero__users-label"><?php echo workernu_inline_editable($data, 'users_count_label', 'text', wp_kses_post($users_lbl), $users_lbl); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

        </div>

        <?php if ($image_url !== ''): ?>
            <div class="section--hero__media" data-animate-item="media">
                <img class="section--hero__image" <?php echo workernu_image_attrs($image_value, 'full', ['alt' => $image_alt, 'loading' => 'eager', 'fetchpriority' => 'high']); ?>>
            </div>
        <?php endif; ?>

    </div>
</section>
