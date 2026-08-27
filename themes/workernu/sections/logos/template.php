<?php
/**
 * Logos — trusted-by / partners strip.
 * Receives $data — all field + modifier values for this section instance.
 */

$heading = workernu_t($data['heading'] ?? '');
$logos   = is_array($data['logos'] ?? null) ? $data['logos'] : [];
$classes = workernu_section_classes($data, 'logos');
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="logos">
    <div class="section--logos__inner container">

        <?php if ($heading !== ''): ?>
            <p class="section--logos__heading" data-animate-item="heading">
                <?php echo nl2br(wp_kses_post($heading)); ?>
            </p>
        <?php endif; ?>

        <?php if ($logos): ?>
            <ul class="section--logos__list" data-animate-item="logos">
                <?php foreach ($logos as $logo):
                    $logo_id  = (int) ($logo['image'] ?? 0);
                    if ($logo_id <= 0) continue;
                    $logo_alt = workernu_t($logo['alt'] ?? '');
                    if ($logo_alt === '') $logo_alt = workernu_image_alt($logo_id);
                    $link     = (string) ($logo['url'] ?? '');
                    $attrs    = workernu_image_attrs($logo_id, 'medium', ['alt' => $logo_alt]);
                    if ($attrs === '') continue;

                    // When the logo is wrapped in a link but no alt was filled,
                    // the <a> would have no accessible name (a11y violation).
                    // Fall back to "Visit <hostname>" derived from the URL so the
                    // link always announces something meaningful to screen readers.
                    $link_aria = '';
                    if ($link !== '' && $logo_alt === '') {
                        $host = (string) (parse_url($link, PHP_URL_HOST) ?: '');
                        $host = preg_replace('/^www\./', '', $host);
                        if ($host !== '') {
                            $link_aria = sprintf(__('Visit %s', 'workernu'), $host);
                        }
                    }
                    ?>
                    <li class="section--logos__item" data-animate-item="logo">
                        <?php if ($link !== ''): ?>
                            <a class="section--logos__link" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener"<?php echo $link_aria !== '' ? ' aria-label="' . esc_attr($link_aria) . '"' : ''; ?>>
                                <img class="section--logos__img" <?php echo $attrs; ?>>
                            </a>
                        <?php else: ?>
                            <img class="section--logos__img" <?php echo $attrs; ?>>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    </div>
</section>
