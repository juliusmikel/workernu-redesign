<?php
/**
 * People — contact / team cards.
 * Receives $data — all field + modifier values for this section instance.
 */

$heading   = workernu_t($data['heading'] ?? '');
$body      = workernu_t($data['body']    ?? '');
$people    = is_array($data['people'] ?? null) ? $data['people'] : [];
$cta_label = workernu_t($data['cta_label'] ?? '');
$cta_url   = (string) ($data['cta_url'] ?? '');
$cta_icon  = !empty($data['cta_icon']);
$layout    = (string) ($data['layout'] ?? '3');
$classes   = workernu_section_classes($data, 'people');
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="people">
    <div class="section--people__inner container">

        <?php if ($heading !== '' || $body !== ''): ?>
            <header class="section--people__header" data-animate-item="header">
                <?php if ($heading !== ''): ?>
                    <h2 class="section--people__heading"><?php echo wp_kses_post($heading); ?></h2>
                <?php endif; ?>
                <?php if ($body !== ''): ?>
                    <p class="section--people__body"><?php echo nl2br(wp_kses_post($body)); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php if ($cta_label !== '' && $cta_url !== ''): ?>
            <div class="section--people__cta-wrap" data-animate-item="cta">
                <a class="btn btn--primary" href="<?php echo esc_url(workernu_localize_url($cta_url)); ?>">
                    <?php if ($cta_icon): ?><i class="fa-solid fa-circle-play" aria-hidden="true"></i><?php endif; ?>
                    <?php echo wp_kses_post($cta_label); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($people): ?>
            <ul class="section--people__grid<?php echo $layout === 'row' ? ' section--people__grid--row' : ''; ?>" data-animate-item="grid"<?php echo $layout === 'row' ? ' tabindex="0" role="region" aria-label="' . esc_attr__('Team members', 'workernu') . '"' : ''; ?>>
                <?php foreach ($people as $person):
                    $avatar_id  = (int) ($person['avatar'] ?? 0);
                    // `medium` baseline (300x300) + explicit sizes matching the
                    // actual CSS slot — clamp(150px, 18vw, 220px) — so the
                    // browser picks the right srcset variant on retina screens
                    // instead of being capped at thumbnail (150px).
                    $avatar_url = workernu_image_url($avatar_id, 'medium');
                    $name       = (string) ($person['name'] ?? '');
                    $role       = workernu_t($person['role'] ?? '');
                    $email      = trim((string) ($person['email'] ?? ''));
                    $phone      = trim((string) ($person['phone'] ?? ''));
                    if ($name === '' && $avatar_url === '') continue;
                    $tel = $phone !== '' ? preg_replace('/[^0-9+]/', '', $phone) : '';
                    // Avatar alt resolution: per-language override → attachment
                    // alt → person's name. Always something useful for SR users.
                    $avatar_alt = workernu_t($person['avatar_alt'] ?? '');
                    if ($avatar_alt === '') $avatar_alt = workernu_image_alt($avatar_id);
                    if ($avatar_alt === '') $avatar_alt = $name;
                    ?>
                    <li class="section--people__card" data-animate-item="person">
                        <?php if ($avatar_url !== ''): ?>
                            <img class="section--people__avatar" <?php echo workernu_image_attrs($avatar_id, 'medium', [
                                'alt'   => $avatar_alt,
                                'sizes' => '(max-width: 600px) 150px, 220px',
                            ]); ?>>
                        <?php else: ?>
                            <span class="section--people__avatar section--people__avatar--placeholder" aria-hidden="true">
                                <i class="fa-solid fa-user"></i>
                            </span>
                        <?php endif; ?>

                        <div class="section--people__info">
                            <?php if ($name !== ''): ?>
                                <p class="section--people__name"><?php echo wp_kses_post($name); ?></p>
                            <?php endif; ?>
                            <?php if ($role !== ''): ?>
                                <p class="section--people__role"><?php echo wp_kses_post($role); ?></p>
                            <?php endif; ?>

                            <?php if ($email !== '' || $tel !== ''): ?>
                                <div class="section--people__contact">
                                    <?php if ($tel !== ''): ?>
                                        <a class="section--people__link" href="<?php echo esc_url('tel:' . $tel); ?>" aria-label="<?php echo esc_attr(sprintf(__('Call %s', 'workernu'), $name)); ?>">
                                            <span class="section--people__link-icon"><i class="fa-solid fa-phone" aria-hidden="true"></i></span>
                                            <span class="section--people__link-text"><?php echo wp_kses_post($phone); ?></span>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($email !== ''): ?>
                                        <a class="section--people__link" href="<?php echo esc_url('mailto:' . $email); ?>" aria-label="<?php echo esc_attr(sprintf(__('Email %s', 'workernu'), $name)); ?>">
                                            <span class="section--people__link-icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
                                            <span class="section--people__link-text"><?php echo wp_kses_post($email); ?></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    </div>
</section>
