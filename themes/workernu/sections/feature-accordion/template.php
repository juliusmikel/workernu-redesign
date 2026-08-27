<?php
/**
 * Feature Accordion — expandable rows; companion image swaps per row.
 *
 * Receives $data — all field + modifier values for this section instance.
 * Schema lives in section.php (owned by Julius). Fields used here:
 *   heading, subheading, items[] (title, text (rich_text), image, image_alt,
 *   cta1_*, cta2_*).
 * Modifiers: align (left|center), media_position (right|left).
 *
 * Rows are native <details> grouped by the `name` attribute — a no-JS
 * fallback only. animations.js takes over exclusivity + the slide animation
 * and strips that `name` attribute on init: the browser's native "close
 * siblings in this name group" behavior fires on ANY open-attribute change,
 * including ones made by our own script, which fights a JS-driven close
 * animation (interrupts it mid-transition, orphaning the transitionend
 * listener) — so once JS is running, it alone owns open/close.
 *
 * Each row's image lives in a shared media pane (all images stacked via
 * position: absolute). The image shown isn't "whichever row is currently
 * open" — it's whichever row was opened most recently (animations.js tracks
 * this via an .is-active class, so the image doesn't go blank when a row is
 * closed back down to zero-open). The media pane is aria-hidden: its images
 * are a visual echo of whatever row's text is already open and announced,
 * not new information.
 */

$heading    = workernu_t($data['heading']    ?? '');
$subheading = workernu_t($data['subheading'] ?? '');
$items_raw  = is_array($data['items'] ?? null) ? $data['items'] : [];
$classes    = workernu_section_classes($data, 'feature-accordion');

$uid = sanitize_html_class((string) ($data['_id'] ?? uniqid('feature-accordion-')));

// Resolve + drop incomplete items up front so the list and the media stack
// share the exact same (reindexed) order — style.css matches an image to its
// row by position, so the two must stay in lockstep.
$items = [];
foreach (array_values($items_raw) as $item) {
    $title     = workernu_t($item['title'] ?? '');
    $body_html = workernu_text($item['text'] ?? null, 'section--feature-accordion__body');

    $image_value = $item['image'] ?? 0;
    $image_url   = workernu_image_url($image_value, 'large');
    if ($title === '' || $body_html === '' || $image_url === '') continue;

    $image_alt = workernu_t($item['image_alt'] ?? '');
    if ($image_alt === '') $image_alt = workernu_image_alt($image_value);

    $ctas = [];
    foreach ([1, 2] as $n) {
        $cta_label = workernu_t($item["cta{$n}_label"] ?? '');
        $cta_url   = (string) ($item["cta{$n}_url"] ?? '');
        if ($cta_label === '' || $cta_url === '') continue;
        $ctas[] = [
            'label'   => $cta_label,
            'url'     => $cta_url,
            'variant' => (string) ($item["cta{$n}_variant"] ?? 'primary'),
            'target'  => (string) ($item["cta{$n}_target"]  ?? '_self'),
        ];
    }

    $items[] = [
        'title'       => $title,
        'body_html'   => $body_html,
        'image_value' => $image_value,
        'image_alt'   => $image_alt,
        'ctas'        => $ctas,
    ];
}
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="feature-accordion">
    <div class="section--feature-accordion__inner container">

        <?php if ($heading !== '' || $subheading !== ''): ?>
            <header class="section--feature-accordion__header" data-animate-item="header">
                <?php if ($heading !== ''): ?>
                    <h2 class="section--feature-accordion__heading"><?php echo wp_kses_post($heading); ?></h2>
                <?php endif; ?>
                <?php if ($subheading !== ''): ?>
                    <p class="section--feature-accordion__sub"><?php echo nl2br(wp_kses_post($subheading)); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php if ($items): ?>
            <div class="section--feature-accordion__grid" data-animate-item="grid">

                <ul class="section--feature-accordion__list">
                    <?php foreach ($items as $i => $item): ?>
                        <li class="section--feature-accordion__item">
                            <details class="section--feature-accordion__details" name="fa-<?php echo esc_attr($uid); ?>"<?php echo $i === 0 ? ' open' : ''; ?>>
                                <summary class="section--feature-accordion__title">
                                    <span class="section--feature-accordion__title-text"><?php echo wp_kses_post($item['title']); ?></span>
                                    <span class="section--feature-accordion__icon" aria-hidden="true">
                                        <i class="fa-solid fa-plus section--feature-accordion__icon-plus"></i>
                                        <i class="fa-solid fa-minus section--feature-accordion__icon-minus"></i>
                                    </span>
                                </summary>
                                <div class="section--feature-accordion__body-wrap">
                                    <div class="section--feature-accordion__body-inner">
                                        <?php echo $item['body_html']; ?>
                                        <?php if ($item['ctas']): ?>
                                            <div class="section--feature-accordion__ctas">
                                                <?php foreach ($item['ctas'] as $cta): ?>
                                                    <a class="btn btn--<?php echo esc_attr($cta['variant']); ?>"
                                                       href="<?php echo esc_url(workernu_localize_url($cta['url'])); ?>"
                                                       target="<?php echo esc_attr($cta['target']); ?>"
                                                       <?php echo $cta['target'] === '_blank' ? 'rel="noopener"' : ''; ?>>
                                                        <?php echo wp_kses_post($cta['label']); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </details>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="section--feature-accordion__media" aria-hidden="true">
                    <?php foreach ($items as $item): ?>
                        <img class="section--feature-accordion__media-img" <?php echo workernu_image_attrs($item['image_value'], 'large', ['alt' => $item['image_alt']]); ?>>
                    <?php endforeach; ?>
                </div>

            </div>
        <?php endif; ?>

    </div>
</section>
