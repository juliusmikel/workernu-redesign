<?php
/**
 * Cards — grid of image cards with overlay text.
 * Receives $data — all field + modifier values for this section instance.
 */

$heading    = workernu_t($data['heading']    ?? '');
$subheading = workernu_t($data['subheading'] ?? '');
$cards      = is_array($data['cards'] ?? null) ? $data['cards'] : [];
$cta_label  = workernu_t($data['cta_label']  ?? '');
$cta_url    = (string) ($data['cta_url'] ?? '');
$link_style = (string) ($data['link_style'] ?? 'corner-arrow');
$link_label = workernu_t($data['link_label'] ?? '');
if ($link_label === '') $link_label = 'Sužinoti daugiau';
$classes    = workernu_section_classes($data, 'cards');
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="cards">
    <div class="section--cards__inner container">

        <?php if ($heading !== '' || $subheading !== ''): ?>
            <header class="section--cards__header" data-animate-item="header">
                <?php if ($heading !== ''): ?>
                    <h2 class="section--cards__heading"><?php echo workernu_inline_editable($data, 'heading', 'text', wp_kses_post($heading), $heading); ?></h2>
                <?php endif; ?>
                <?php if ($subheading !== ''): ?>
                    <p class="section--cards__sub"><?php echo workernu_inline_editable($data, 'subheading', 'textarea', nl2br(wp_kses_post($subheading)), $subheading); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php if ($cards): ?>
            <ul class="section--cards__grid" data-animate-item="grid">
                <?php foreach ($cards as $card_i => $card):
                    // image may be an int (legacy) or a { lt: id, en: id } map.
                    $image_value = $card['image'] ?? 0;
                    $image_url   = workernu_image_url($image_value, 'large');
                    if ($image_url === '') continue;
                    // Per-card alt override; blank means the title already
                    // describes the visual → render as decorative (empty alt).
                    $image_alt   = workernu_t($card['image_alt'] ?? '');
                    $title       = workernu_t($card['title']       ?? '');
                    $description = workernu_t($card['description'] ?? '');
                    $url         = (string) ($card['url'] ?? '');
                    $tag         = $url !== '' ? 'a' : 'div';
                    $attrs       = $url !== '' ? ' href="' . esc_url(workernu_localize_url($url)) . '"' : '';
                    ?>
                    <li class="section--cards__item" data-animate-item="card">
                        <<?php echo $tag . $attrs; ?> class="section--cards__card">
                            <img class="section--cards__image" <?php echo workernu_image_attrs($image_value, 'large', ['alt' => $image_alt]); ?>>
                            <span class="section--cards__overlay" aria-hidden="true"></span>

                            <?php if ($url !== '' && $link_style === 'corner-arrow'): ?>
                                <span class="section--cards__arrow" aria-hidden="true">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </span>
                            <?php endif; ?>

                            <div class="section--cards__content">
                                <?php if ($title !== ''): ?>
                                    <h3 class="section--cards__title"><?php echo workernu_inline_editable($data, "cards.$card_i.title", 'text', wp_kses_post($title), $title); ?></h3>
                                <?php endif; ?>
                                <?php if ($description !== ''): ?>
                                    <p class="section--cards__desc"><?php echo workernu_inline_editable($data, "cards.$card_i.description", 'textarea', nl2br(wp_kses_post($description)), $description); ?></p>
                                <?php endif; ?>
                                <?php if ($url !== '' && $link_style === 'inline-text'): ?>
                                    <span class="section--cards__link-text" aria-hidden="true">
                                        <?php echo workernu_inline_editable($data, 'link_label', 'text', wp_kses_post($link_label), $link_label); ?>
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </<?php echo $tag; ?>>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($cta_label !== '' && $cta_url !== ''): ?>
            <div class="section--cards__cta-wrap" data-animate-item="cta">
                <a class="btn btn--primary" href="<?php echo esc_url(workernu_localize_url($cta_url)); ?>">
                    <?php echo workernu_inline_editable($data, 'cta_label', 'text', wp_kses_post($cta_label), $cta_label); ?>
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>
