<?php
/**
 * FAQ — accordion of question/answer pairs.
 * Receives $data — all field + modifier values for this section instance.
 *
 * Built on native <details>/<summary>:
 *   - keyboard nav + screen reader announcement out of the box
 *   - works without JS
 *   - Google indexes the content even when collapsed
 */

$heading    = workernu_t($data['heading']    ?? '');
$subheading = workernu_t($data['subheading'] ?? '');
$items      = is_array($data['items'] ?? null) ? $data['items'] : [];
$layout     = (string) ($data['layout']     ?? 'accordion');
$first_open = (string) ($data['first_open'] ?? 'yes');
$all_open   = $layout === 'open';

$classes = workernu_section_classes($data, 'faq');
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="faq">
    <div class="section--faq__inner container">

        <?php if ($heading !== '' || $subheading !== ''): ?>
            <header class="section--faq__header" data-animate-item="header">
                <?php if ($heading !== ''): ?>
                    <h2 class="section--faq__heading"><?php echo wp_kses_post($heading); ?></h2>
                <?php endif; ?>
                <?php if ($subheading !== ''): ?>
                    <p class="section--faq__sub"><?php echo nl2br(wp_kses_post($subheading)); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php if ($items): ?>
            <ul class="section--faq__list" data-animate-item="list">
                <?php foreach (array_values($items) as $i => $item):
                    $q = workernu_t($item['question'] ?? '');
                    $a = workernu_t($item['answer']   ?? '');
                    if ($q === '' || $a === '') continue;
                    $is_open = $all_open || ($first_open === 'yes' && $i === 0);
                    ?>
                    <li class="section--faq__item" data-animate-item="item">
                        <details class="section--faq__details"<?php echo $is_open ? ' open' : ''; ?>>
                            <summary class="section--faq__question">
                                <span class="section--faq__question-text"><?php echo wp_kses_post($q); ?></span>
                                <span class="section--faq__icon" aria-hidden="true">
                                    <i class="fa-solid fa-plus section--faq__icon-plus"></i>
                                    <i class="fa-solid fa-minus section--faq__icon-minus"></i>
                                </span>
                            </summary>
                            <div class="section--faq__answer">
                                <div class="section--faq__answer-inner">
                                    <p><?php echo nl2br(wp_kses_post($a)); ?></p>
                                </div>
                            </div>
                        </details>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    </div>
</section>
<script>
(function () {
    document.querySelectorAll('.section--faq__details').forEach(function (details) {
        var answer = details.querySelector('.section--faq__answer');
        var summary = details.querySelector('.section--faq__question');

        // Set initial height for items that start open
        if (details.open) {
            answer.style.height = answer.scrollHeight + 'px';
        } else {
            answer.style.height = '0px';
        }

        summary.addEventListener('click', function (e) {
            e.preventDefault();
            if (details.open) {
                // Slide up
                answer.style.height = answer.scrollHeight + 'px';
                requestAnimationFrame(function () {
                    answer.style.height = '0px';
                });
                answer.addEventListener('transitionend', function () {
                    details.removeAttribute('open');
                }, { once: true });
            } else {
                // Slide down
                details.setAttribute('open', '');
                answer.style.height = '0px';
                requestAnimationFrame(function () {
                    answer.style.height = answer.scrollHeight + 'px';
                });
                answer.addEventListener('transitionend', function () {
                    answer.style.height = 'auto';
                }, { once: true });
            }
        });
    });
}());
</script>
