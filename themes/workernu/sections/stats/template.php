<?php
/**
 * Stats — optional section title above + a wide card with a heading on the
 * left and 2–3 stat items on the right (big number + label + caption).
 *
 * Stat count (2 or 3) is auto-detected and emitted as `--count-N` on the
 * card; CSS uses it for the width split. Anything past the third is dropped.
 */

$title   = workernu_t($data['title']   ?? '');
$heading = workernu_t($data['heading'] ?? '');
$stats   = is_array($data['stats'] ?? null) ? $data['stats'] : [];

$stats = array_values(array_filter($stats, function ($s) {
    if (!is_array($s)) return false;
    $num = workernu_t($s['number'] ?? '');
    $lbl = workernu_t($s['label']  ?? '');
    return $num !== '' || $lbl !== '';
}));
$stats = array_slice($stats, 0, 3);
$count = count($stats);

$classes = workernu_section_classes($data, 'stats');
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="stats">
    <div class="section--stats__inner container">

        <?php if ($title !== ''): ?>
            <h2 class="section--stats__title" data-animate-item="title">
                <?php echo wp_kses_post($title); ?>
            </h2>
        <?php endif; ?>

        <div class="section--stats__card section--stats__card--count-<?php echo (int) $count; ?>" data-animate-item="card">

            <?php if ($heading !== ''): ?>
                <div class="section--stats__heading-wrap">
                    <p class="section--stats__heading"><?php echo wp_kses_post($heading); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($stats): ?>
                <ul class="section--stats__stats">
                    <?php foreach ($stats as $stat):
                        $num = workernu_t($stat['number']  ?? '');
                        $lbl = workernu_t($stat['label']   ?? '');
                        $cap = workernu_t($stat['caption'] ?? '');
                        ?>
                        <li class="section--stats__stat">
                            <?php if ($num !== ''): ?>
                                <span class="section--stats__number"><?php echo wp_kses_post($num); ?></span>
                            <?php endif; ?>
                            <?php if ($lbl !== ''): ?>
                                <p class="section--stats__label"><?php echo wp_kses_post($lbl); ?></p>
                            <?php endif; ?>
                            <?php if ($cap !== ''): ?>
                                <p class="section--stats__caption"><?php echo wp_kses_post($cap); ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        </div>

    </div>
</section>
