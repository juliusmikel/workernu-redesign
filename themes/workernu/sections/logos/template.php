<?php
/**
 * Logos — trusted-by / partners strip.
 * Receives $data — all field + modifier values for this section instance.
 *
 * The logo set is rendered TWICE. Below 768px style.css turns the row into a
 * marquee, and a seamless loop needs the sequence duplicated so that
 * translateX(-50%) lands on a frame identical to the start. The second half is
 * aria-hidden and carries no links — see the clone loop below. Above the
 * breakpoint the clones are `display: none` and the wrapper is
 * `display: contents`, so desktop renders exactly as it did before.
 */

$heading = workernu_t($data['heading'] ?? '');
$logos   = is_array($data['logos'] ?? null) ? $data['logos'] : [];
$classes = workernu_section_classes($data, 'logos');

// Resolve every logo up front: both halves of the marquee need the same data,
// and the clone half needs its own attribute string (empty alt).
$items = [];
foreach ($logos as $logo) {
    $logo_id = (int) ($logo['image'] ?? 0);
    if ($logo_id <= 0) continue;

    $logo_alt = workernu_t($logo['alt'] ?? '');
    if ($logo_alt === '') $logo_alt = workernu_image_alt($logo_id);

    // loading="eager": the marquee lays logos out past the right edge of the
    // viewport, where the lazy-loading intersection test never fires until they
    // animate in — the first pass would scroll blank slots into view. Logo
    // marks are a few KB, and this section sits directly under the hero.
    $attrs = workernu_image_attrs($logo_id, 'medium', ['alt' => $logo_alt, 'loading' => 'eager']);
    if ($attrs === '') continue;

    $link = (string) ($logo['url'] ?? '');

    // When the logo is wrapped in a link but no alt was filled, the <a> would
    // have no accessible name (a11y violation). Fall back to "Visit <hostname>"
    // derived from the URL so the link always announces something meaningful to
    // screen readers.
    $link_aria = '';
    if ($link !== '' && $logo_alt === '') {
        $host = (string) (parse_url($link, PHP_URL_HOST) ?: '');
        $host = preg_replace('/^www\./', '', $host);
        if ($host !== '') {
            $link_aria = sprintf(__('Visit %s', 'workernu'), $host);
        }
    }

    $items[] = [
        'attrs'       => $attrs,
        'clone_attrs' => workernu_image_attrs($logo_id, 'medium', ['alt' => '', 'loading' => 'eager']),
        'link'        => $link,
        'link_aria'   => $link_aria,
    ];
}
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="logos">
    <div class="section--logos__inner container">

        <?php if ($heading !== ''): ?>
            <p class="section--logos__heading" data-animate-item="heading">
                <?php echo workernu_inline_editable($data, 'heading', 'textarea', nl2br(wp_kses_post($heading)), $heading); ?>
            </p>
        <?php endif; ?>

        <?php if ($items): ?>
            <div class="section--logos__viewport">
                <ul class="section--logos__list" data-animate-item="logos">
                    <?php foreach ($items as $item): ?>
                        <li class="section--logos__item" data-animate-item="logo">
                            <?php if ($item['link'] !== ''): ?>
                                <a class="section--logos__link" href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener"<?php echo $item['link_aria'] !== '' ? ' aria-label="' . esc_attr($item['link_aria']) . '"' : ''; ?>>
                                    <img class="section--logos__img" <?php echo $item['attrs']; ?>>
                                </a>
                            <?php else: ?>
                                <img class="section--logos__img" <?php echo $item['attrs']; ?>>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>

                    <?php /* Marquee clone half. Bare <img>, never the <a>: a duplicated
                             link would put every logo in the tab order twice, and
                             aria-hidden on a focusable element is itself a violation. */ ?>
                    <?php foreach ($items as $item): ?>
                        <li class="section--logos__item section--logos__item--clone" aria-hidden="true">
                            <img class="section--logos__img" <?php echo $item['clone_attrs']; ?>>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

    </div>
</section>
