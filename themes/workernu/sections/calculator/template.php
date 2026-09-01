<?php
/**
 * Savings Calculator — savings calculator with sliders.
 * Receives $data — all field + modifier values for this section instance.
 *
 * Config (rates, ranges, currency) is emitted onto the wrapper as data-*
 * attributes; animations.js reads them and recomputes results on slider input.
 * Server renders sane initial values so it's meaningful before/without JS.
 *
 * spend depends only on employee count; monthly and yearly savings are each
 * an independent linear function of (employees, projects) — yearly is NOT
 * monthly * 12. See section.php's header comment for the fitted constants.
 */

$heading    = workernu_t($data['heading']    ?? '');
$subheading = workernu_t($data['subheading'] ?? '');

$emp_label   = workernu_t($data['employees_label'] ?? '');
$emp_min     = (float) ($data['employees_min']     ?? 1);
$emp_max     = (float) ($data['employees_max']     ?? 100);
$emp_def     = (float) ($data['employees_default'] ?? $emp_min);

$proj_label  = workernu_t($data['projects_label'] ?? '');
$proj_min    = (float) ($data['projects_min']     ?? 0);
$proj_max    = (float) ($data['projects_max']     ?? 50);
$proj_def    = (float) ($data['projects_default'] ?? $proj_min);

// Defaults here are the fitted constants themselves, not 0 — every page
// using this section gets the corrected model immediately, without needing
// an admin to re-enter five new rate fields first. See section.php's
// header comment for the (E, P) -> spend/savings formulas these come from.
$spend_rate_emp      = (float) ($data['spend_rate_employee']           ?? 5);
$savings_emp_month   = (float) ($data['savings_rate_employee_monthly'] ?? 16.6666667);
$savings_proj_month  = (float) ($data['savings_rate_project_monthly']  ?? 18.9333333);
$savings_emp_year    = (float) ($data['savings_rate_employee_yearly']  ?? 202.6666667);
$savings_proj_year   = (float) ($data['savings_rate_project_yearly']   ?? 224);
$currency    = (string) ($data['currency'] ?? '');
if ($currency === '') $currency = '€';

$lbl_spend   = workernu_t($data['result_spend_label']   ?? '');
$lbl_savings = workernu_t($data['result_savings_label'] ?? '');
$lbl_yearly  = workernu_t($data['result_yearly_label']  ?? '');

$cta_label   = workernu_t($data['cta_label'] ?? '');
$cta_url     = (string) ($data['cta_url'] ?? '');

// Clamp defaults into range.
$emp_def  = max($emp_min,  min($emp_max,  $emp_def));
$proj_def = max($proj_min, min($proj_max, $proj_def));

// Server-side initial results (mirrors the JS model). yearlySavings is an
// independently fit linear function, not monthlySavings * 12 — see the
// header comment in section.php.
$spend   = $emp_def * $spend_rate_emp;
$savings = $emp_def * $savings_emp_month + $proj_def * $savings_proj_month;
$yearly  = $emp_def * $savings_emp_year  + $proj_def * $savings_proj_year;
$fmt = function (float $n) use ($currency) {
    return number_format($n, 2, '.', ',') . ' ' . $currency;
};

$classes = workernu_section_classes($data, 'calculator');
$uid     = sanitize_html_class((string) ($data['_id'] ?? uniqid('calc-')));
?>
<section class="<?php echo esc_attr($classes); ?>" data-animate="calculator"
         data-spend-rate-employee="<?php echo esc_attr($spend_rate_emp); ?>"
         data-savings-rate-employee-monthly="<?php echo esc_attr($savings_emp_month); ?>"
         data-savings-rate-project-monthly="<?php echo esc_attr($savings_proj_month); ?>"
         data-savings-rate-employee-yearly="<?php echo esc_attr($savings_emp_year); ?>"
         data-savings-rate-project-yearly="<?php echo esc_attr($savings_proj_year); ?>"
         data-currency="<?php echo esc_attr($currency); ?>">
    <div class="section--calculator__inner container">

        <?php if ($heading !== '' || $subheading !== ''): ?>
            <header class="section--calculator__header" data-animate-item="header">
                <?php if ($heading !== ''): ?>
                    <h2 class="section--calculator__heading"><?php echo wp_kses_post($heading); ?></h2>
                <?php endif; ?>
                <?php if ($subheading !== ''): ?>
                    <p class="section--calculator__sub"><?php echo nl2br(wp_kses_post($subheading)); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php
        // Sliders (wrapper 2 + 3): each slider is its own direct child of
        // __inner — label + live value + range input stacked vertically.
        $sliders = [
            ['key' => 'employees', 'label' => $emp_label,  'min' => $emp_min,  'max' => $emp_max,  'val' => $emp_def],
            ['key' => 'projects',  'label' => $proj_label, 'min' => $proj_min, 'max' => $proj_max, 'val' => $proj_def],
        ];
        foreach ($sliders as $s):
            $sid = $uid . '-' . $s['key'];
            ?>
            <div class="section--calculator__slider-block" data-animate-item="slider">
                <div class="section--calculator__slider-row">
                    <label class="section--calculator__label" for="<?php echo esc_attr($sid); ?>">
                        <?php echo wp_kses_post($s['label']); ?>
                    </label>
                    <output class="section--calculator__value" id="<?php echo esc_attr($sid); ?>-out" for="<?php echo esc_attr($sid); ?>">
                        <?php echo wp_kses_post((string) (int) $s['val']); ?>
                    </output>
                </div>
                <input class="section--calculator__slider"
                       type="range"
                       id="<?php echo esc_attr($sid); ?>"
                       name="<?php echo esc_attr($s['key']); ?>"
                       data-calc-input="<?php echo esc_attr($s['key']); ?>"
                       data-calc-output="<?php echo esc_attr($sid); ?>-out"
                       min="<?php echo esc_attr($s['min']); ?>"
                       max="<?php echo esc_attr($s['max']); ?>"
                       value="<?php echo esc_attr($s['val']); ?>"
                       step="1">
            </div>
        <?php endforeach; ?>

        <?php
        // Result numbers (wrappers 4 + 5 + 6): each is its own direct child of
        // __inner, with the final yearly figure marked --total for emphasis.
        $results = [
            ['key' => 'spend',   'label' => $lbl_spend,   'val' => $spend,   'em' => false],
            ['key' => 'savings', 'label' => $lbl_savings, 'val' => $savings, 'em' => false],
            ['key' => 'yearly',  'label' => $lbl_yearly,  'val' => $yearly,  'em' => true],
        ];
        foreach ($results as $r):
            if ($r['label'] === '') continue;
            $row_class = 'section--calculator__result' . ($r['em'] ? ' section--calculator__result--total' : '');
            ?>
            <div class="<?php echo esc_attr($row_class); ?>" data-animate-item="result">
                <span class="section--calculator__result-label"><?php echo wp_kses_post($r['label']); ?></span>
                <span class="section--calculator__result-value" data-calc-result="<?php echo esc_attr($r['key']); ?>">
                    <?php echo wp_kses_post($fmt($r['val'])); ?>
                </span>
            </div>
        <?php endforeach; ?>

        <?php if ($cta_label !== '' && $cta_url !== ''): ?>
            <a class="btn btn--primary section--calculator__cta" href="<?php echo esc_url(workernu_localize_url($cta_url)); ?>">
                <?php echo wp_kses_post($cta_label); ?>
            </a>
        <?php endif; ?>
    </div>
</section>
