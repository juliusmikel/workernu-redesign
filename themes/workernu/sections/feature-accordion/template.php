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
 *
 * That pane only works as a second column. Below 900px style.css hides it and
 * shows __row-img instead — the same image, rendered a second time inside each
 * row's own panel, so it sits next to the text it illustrates rather than below
 * every row. Same src, so it is one network fetch, not two; and since
 * `display: none` drops an element from the accessibility tree, only one of the
 * two copies is ever exposed. That is why __row-img gets a real alt while the
 * pane stays aria-hidden.
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
foreach (array_values($items_raw) as $orig_i => $item) {
    $title     = workernu_t($item['title'] ?? '');
    $body_html = workernu_text($item['text'] ?? null, 'section--feature-accordion__body');

    $image_value = $item['image'] ?? 0;
    $image_url   = workernu_image_url($image_value, 'large');
    if ($title === '' || $body_html === '' || $image_url === '') continue;

    $image_alt = workernu_t($item['image_alt'] ?? '');
    if ($image_alt === '') $image_alt = workernu_image_alt($image_value);

    // field_path indices below use $orig_i (this item's index in the raw,
    // unfiltered $data['items'] — what Draft\save_field() actually reads),
    // not the position in this filtered/reindexed $items array, which
    // diverges from it as soon as an earlier item gets skipped above.
    $ctas = [];
    foreach ([1, 2] as $n) {
        $cta_label = workernu_t($item["cta{$n}_label"] ?? '');
        $cta_url   = (string) ($item["cta{$n}_url"] ?? '');
        if ($cta_label === '' || $cta_url === '') continue;
        $ctas[] = [
            'field'   => "cta{$n}_label",
            'label'   => $cta_label,
            'url'     => $cta_url,
            'variant' => (string) ($item["cta{$n}_variant"] ?? 'primary'),
            'target'  => (string) ($item["cta{$n}_target"]  ?? '_self'),
            'icon'    => !empty($item["cta{$n}_icon"]),
        ];
    }

    $items[] = [
        'orig_i'      => $orig_i,
        'title'       => $title,
        'body_html'   => $body_html,
        'body_raw'    => workernu_t($item['text']['value'] ?? ''),
        'image_value' => $image_value,
        'image_alt'   => $image_alt,
        'image_fit'   => ($item['image_fit'] ?? 'overflow') === 'centered' ? 'centered' : 'overflow',
        'ctas'        => $ctas,
    ];
}
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="feature-accordion">
    <div class="section--feature-accordion__inner container">

        <?php if ($heading !== '' || $subheading !== ''): ?>
            <header class="section--feature-accordion__header" data-animate-item="header">
                <?php if ($heading !== ''): ?>
                    <h2 class="section--feature-accordion__heading"><?php echo workernu_inline_editable($data, 'heading', 'text', wp_kses_post($heading), $heading); ?></h2>
                <?php endif; ?>
                <?php if ($subheading !== ''): ?>
                    <p class="section--feature-accordion__sub"><?php echo workernu_inline_editable($data, 'subheading', 'textarea', nl2br(wp_kses_post($subheading)), $subheading); ?></p>
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
                                    <span class="section--feature-accordion__title-text"><?php echo workernu_inline_editable($data, "items.{$item['orig_i']}.title", 'text', wp_kses_post($item['title']), $item['title']); ?></span>
                                    <span class="section--feature-accordion__icon" aria-hidden="true">
                                        <i class="fa-solid fa-plus section--feature-accordion__icon-plus"></i>
                                        <i class="fa-solid fa-minus section--feature-accordion__icon-minus"></i>
                                    </span>
                                </summary>
                                <div class="section--feature-accordion__body-wrap">
                                    <div class="section--feature-accordion__body-inner">
                                        <?php echo workernu_inline_editable($data, "items.{$item['orig_i']}.text", 'rich_text', $item['body_html'], $item['body_raw'], 'div'); ?>
                                        <?php /* Mobile copy of this row's image. Below 900px the shared
                                                 pane below is hidden and this one shows instead — see the
                                                 header comment. It carries a real alt: unlike the pane, it
                                                 is not inside an aria-hidden container, and `display: none`
                                                 keeps it out of the a11y tree on desktop, so only ever one
                                                 of the two copies is exposed. */ ?>
                                        <img class="section--feature-accordion__row-img" <?php echo workernu_image_attrs($item['image_value'], 'large', ['alt' => $item['image_alt']]); ?>>
                                        <?php if ($item['ctas']): ?>
                                            <div class="section--feature-accordion__ctas">
                                                <?php foreach ($item['ctas'] as $cta): ?>
                                                    <a class="btn btn--<?php echo esc_attr($cta['variant']); ?>"
                                                       href="<?php echo esc_url(workernu_localize_url($cta['url'])); ?>"
                                                       target="<?php echo esc_attr($cta['target']); ?>"
                                                       <?php echo $cta['target'] === '_blank' ? 'rel="noopener"' : ''; ?>>
                                                        <?php if ($cta['icon']): ?><i class="fa-solid fa-circle-play" aria-hidden="true"></i><?php endif; ?>
                                                        <?php echo workernu_inline_editable($data, "items.{$item['orig_i']}.{$cta['field']}", 'text', wp_kses_post($cta['label']), $cta['label']); ?>
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
                        <img class="section--feature-accordion__media-img section--feature-accordion__media-img--<?php echo esc_attr($item['image_fit']); ?>" <?php echo workernu_image_attrs($item['image_value'], 'large', ['alt' => $item['image_alt']]); ?>>
                    <?php endforeach; ?>
                </div>

            </div>
        <?php endif; ?>

    </div>
</section>
