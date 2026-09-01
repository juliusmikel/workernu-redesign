<?php
/**
 * Stats — 2–3 stat items (big number + label + caption), in one of two
 * layouts (see section.php's header comment for the full field map):
 *   card  (default) — title above; heading + stats share a card, asymmetric
 *                      width split driven by stat count (--count-N).
 *   split            — title + subheading centered above, as their own
 *                      header block; stats alone fill the card as N equal
 *                      columns, styled identically, no card heading.
 */

$title      = workernu_t($data['title']      ?? '');
$subheading = workernu_t($data['subheading'] ?? '');
$heading    = workernu_t($data['heading']    ?? '');
$stats      = is_array($data['stats'] ?? null) ? $data['stats'] : [];
$layout     = (string) ($data['layout'] ?? 'card');
$is_split   = $layout === 'split';

// orig_i (this stat's index in the raw $data['stats'] — what
// Draft\save_field() reads) is preserved through the filter/reindex below,
// since it diverges from the filtered array's own position as soon as an
// earlier stat is dropped for having no number/label.
$stats = array_values(array_filter(
    array_map(function ($s, $orig_i) {
        if (!is_array($s)) return null;
        $s['orig_i'] = $orig_i;
        return $s;
    }, $stats, array_keys($stats)),
    function ($s) {
        if ($s === null) return false;
        $num = workernu_t($s['number'] ?? '');
        $lbl = workernu_t($s['label']  ?? '');
        return $num !== '' || $lbl !== '';
    }
));
$stats = array_slice($stats, 0, 3);
$count = count($stats);

$classes = workernu_section_classes($data, 'stats');
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="stats">
    <div class="section--stats__inner container">

        <?php if ($title !== '' || ($is_split && $subheading !== '')): ?>
            <div class="section--stats__header">
                <?php if ($title !== ''): ?>
                    <h2 class="section--stats__title" data-animate-item="title">
                        <?php echo workernu_inline_editable($data, 'title', 'text', wp_kses_post($title), $title); ?>
                    </h2>
                <?php endif; ?>
                <?php if ($is_split && $subheading !== ''): ?>
                    <p class="section--stats__subheading"><?php echo workernu_inline_editable($data, 'subheading', 'textarea', nl2br(wp_kses_post($subheading)), $subheading); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="section--stats__card section--stats__card--count-<?php echo (int) $count; ?><?php echo $is_split ? ' section--stats__card--split' : ''; ?>" data-animate-item="card">

            <?php if (!$is_split && $heading !== ''): ?>
                <div class="section--stats__heading-wrap">
                    <p class="section--stats__heading"><?php echo workernu_inline_editable($data, 'heading', 'text', wp_kses_post($heading), $heading); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($stats): ?>
                <ul class="section--stats__stats">
                    <?php foreach ($stats as $stat):
                        $num = workernu_t($stat['number']  ?? '');
                        $lbl = workernu_t($stat['label']   ?? '');
                        $cap = workernu_t($stat['caption'] ?? '');
                        $si  = $stat['orig_i'];
                        ?>
                        <li class="section--stats__stat">
                            <?php if ($num !== ''): ?>
                                <span class="section--stats__number"><?php echo workernu_inline_editable($data, "stats.$si.number", 'text', wp_kses_post($num), $num); ?></span>
                            <?php endif; ?>
                            <?php if ($lbl !== ''): ?>
                                <p class="section--stats__label"><?php echo workernu_inline_editable($data, "stats.$si.label", 'text', wp_kses_post($lbl), $lbl); ?></p>
                            <?php endif; ?>
                            <?php if ($cap !== ''): ?>
                                <p class="section--stats__caption"><?php echo workernu_inline_editable($data, "stats.$si.caption", 'text', wp_kses_post($cap), $cap); ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        </div>

    </div>
</section>
