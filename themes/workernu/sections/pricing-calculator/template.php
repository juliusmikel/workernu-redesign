<?php
$heading               = workernu_t($data['heading']               ?? '');
$subheading            = workernu_t($data['subheading']            ?? '');
$plans_label           = workernu_t($data['plans_label']           ?? '');
$selected_plan_prefix  = workernu_t($data['selected_plan_prefix']  ?? '');
$plans                 = is_array($data['plans']  ?? null) ? $data['plans']  : [];
$addons                = is_array($data['addons'] ?? null) ? $data['addons'] : [];
$addons_label          = workernu_t($data['addons_label']          ?? '');
$workers_label         = workernu_t($data['workers_label']         ?? '');
$workers_min           = max(1, (int) ($data['workers_min']         ?? 1));
$workers_max           = max(1, (int) ($data['workers_max']         ?? 500));
$workers_default       = (int) ($data['workers_default'] ?? 5);
$workers_default       = max($workers_min, min($workers_max, $workers_default));
$currency              = (string) ($data['currency']               ?? '€');
$period_label          = workernu_t($data['period_label']          ?? '');
$summary_title         = workernu_t($data['summary_title']         ?? '');
$summary_per_label     = workernu_t($data['summary_per_label']     ?? '');
$placeholder           = workernu_t($data['placeholder_label']     ?? __('Pasirinkite planą', 'workernu'));
$cta_label             = workernu_t($data['cta_label']             ?? '');
$cta_url               = (string) ($data['cta_url']                ?? '');
$cta_note              = workernu_t($data['cta_note']              ?? '');

$classes = workernu_section_classes($data, 'pricing-calculator');
$pc_uid  = 'wn-pc-' . substr(md5(uniqid()), 0, 8);

$plan_data = [];
foreach ($plans as $i => $plan) {
    $pname = workernu_t($plan['name'] ?? '');
    if ($pname === '') continue;
    $pprice       = (float) ($plan['price']       ?? 0);
    $paddon_price = (float) ($plan['addon_price'] ?? 0);
    $plan_data[$i] = [
        'name'            => $pname,
        'desc'            => workernu_t($plan['description'] ?? ''),
        'icon'            => (string) ($plan['icon'] ?? ''),
        'price'           => $pprice,
        'addon_price'     => $paddon_price,
        'price_str'       => number_format($pprice, $pprice == floor($pprice) ? 0 : 2, '.', '')
                             . ' ' . $currency
                             . ($period_label !== '' ? ' ' . $period_label : ''),
        'addon_price_str' => '+' . number_format($paddon_price, $paddon_price == floor($paddon_price) ? 0 : 2, '.', '')
                             . ' ' . $currency
                             . ($period_label !== '' ? ' ' . $period_label : ''),
    ];
}
?>
<section class="<?php echo esc_attr($classes); ?>">
    <div class="section--pricing-calculator__inner container">

        <?php if ($heading !== '' || $subheading !== ''): ?>
            <header class="section--pricing-calculator__header">
                <?php if ($heading !== ''): ?>
                    <h2 class="section--pricing-calculator__heading"><?php echo wp_kses_post($heading); ?></h2>
                <?php endif; ?>
                <?php if ($subheading !== ''): ?>
                    <p class="section--pricing-calculator__sub"><?php echo nl2br(wp_kses_post($subheading)); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php if ($plan_data): ?>

            <!-- ── Step 1: Plan cards (always visible) ── -->
            <?php if ($plans_label !== ''): ?>
                <p class="section--pricing-calculator__step-label"><?php echo wp_kses_post($plans_label); ?></p>
            <?php endif; ?>

            <div class="section--pricing-calculator__cards" role="radiogroup"
                 aria-label="<?php esc_attr_e('Choose a base plan', 'workernu'); ?>">
                <?php foreach ($plan_data as $i => $p): ?>
                    <button type="button"
                            class="section--pricing-calculator__plan-card"
                            data-pc="plan-card"
                            data-plan-index="<?php echo $i; ?>"
                            data-plan-price="<?php echo esc_attr($p['price']); ?>"
                            data-plan-price-str="<?php echo esc_attr($p['price_str']); ?>"
                            data-plan-addon-price="<?php echo esc_attr($p['addon_price']); ?>"
                            data-plan-addon-price-str="<?php echo esc_attr($p['addon_price_str']); ?>"
                            role="radio"
                            aria-checked="false">
                        <?php if ($p['icon'] !== ''): ?>
                            <span class="section--pricing-calculator__plan-card-icon" aria-hidden="true">
                                <?php echo workernu_icon($p['icon']); ?>
                            </span>
                        <?php endif; ?>
                        <span class="section--pricing-calculator__plan-card-name"><?php echo wp_kses_post($p['name']); ?></span>
                        <?php if ($p['desc'] !== ''): ?>
                            <span class="section--pricing-calculator__plan-card-desc"><?php echo wp_kses_post($p['desc']); ?></span>
                        <?php endif; ?>
                        <span class="section--pricing-calculator__plan-card-price">
                            <?php echo esc_html(number_format($p['price'], $p['price'] == floor($p['price']) ? 0 : 2, '.', '') . ' ' . $currency); ?>
                        </span>
                        <?php if ($period_label !== ''): ?>
                            <span class="section--pricing-calculator__plan-card-period"><?php echo esc_html($period_label); ?></span>
                        <?php endif; ?>
                        <span class="section--pricing-calculator__plan-card-check" aria-hidden="true">
                            <i class="fa-solid fa-check"></i>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- ── Selected plan info line (hidden until plan chosen) ── -->
            <?php if ($selected_plan_prefix !== ''): ?>
            <p class="section--pricing-calculator__selected-info" data-pc="selected-info" hidden>
                <span data-pc="selected-prefix"><?php echo esc_html($selected_plan_prefix); ?></span>
                <strong data-pc="selected-name"></strong>
                <span> — </span>
                <span data-pc="selected-price"></span>
            </p>
            <?php endif; ?>

            <!-- ── Step 2: Addons area (hidden until plan chosen) ── -->
            <div class="section--pricing-calculator__addons-area" data-pc="addons-area" hidden>

                <?php if ($addons_label !== ''): ?>
                    <p class="section--pricing-calculator__step-label"><?php echo wp_kses_post($addons_label); ?></p>
                <?php endif; ?>

                <!-- Plan addon rows (other plans; selected plan hidden by JS) -->
                <div class="section--pricing-calculator__addon-rows" data-pc="plan-rows">
                    <?php foreach ($plan_data as $i => $p): ?>
                        <button type="button"
                                class="section--pricing-calculator__row section--pricing-calculator__row--plan"
                                data-pc="plan-row"
                                data-plan-index="<?php echo $i; ?>"
                                data-plan-addon-price="<?php echo esc_attr($p['addon_price']); ?>"
                                aria-pressed="false">
                            <span class="section--pricing-calculator__toggle" aria-hidden="true">
                                <span class="section--pricing-calculator__toggle-track">
                                    <span class="section--pricing-calculator__toggle-thumb"></span>
                                </span>
                            </span>
                            <?php if ($p['icon'] !== ''): ?>
                                <span class="section--pricing-calculator__row-icon" aria-hidden="true">
                                    <?php echo workernu_icon($p['icon']); ?>
                                </span>
                            <?php endif; ?>
                            <span class="section--pricing-calculator__row-text">
                                <span class="section--pricing-calculator__row-name"><?php echo wp_kses_post($p['name']); ?></span>
                                <?php if ($p['desc'] !== ''): ?>
                                    <span class="section--pricing-calculator__row-desc"><?php echo wp_kses_post($p['desc']); ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="section--pricing-calculator__row-price"><?php echo esc_html($p['addon_price_str']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Regular addon rows -->
                <?php if (!empty($addons)): ?>
                <div class="section--pricing-calculator__addon-rows" data-pc="addon-rows">
                    <?php foreach ($addons as $j => $addon):
                        $aname  = workernu_t($addon['name']        ?? '');
                        $adesc  = workernu_t($addon['description'] ?? '');
                        $aicon  = (string) ($addon['icon']         ?? '');
                        $aprice = (float)  ($addon['price']        ?? 0);
                        $aplans = trim((string) ($addon['plans']   ?? ''));
                        if ($aname === '') continue;
                        $price_str = '+' . number_format($aprice, $aprice == floor($aprice) ? 0 : 2, '.', '')
                                     . ' ' . $currency
                                     . ($period_label !== '' ? ' ' . $period_label : '');
                    ?>
                        <button type="button"
                                class="section--pricing-calculator__row section--pricing-calculator__row--addon"
                                data-pc="addon-row"
                                data-addon-index="<?php echo $j; ?>"
                                data-addon-price="<?php echo esc_attr($aprice); ?>"
                                data-addon-plans="<?php echo esc_attr($aplans); ?>"
                                aria-pressed="false">
                            <span class="section--pricing-calculator__toggle" aria-hidden="true">
                                <span class="section--pricing-calculator__toggle-track">
                                    <span class="section--pricing-calculator__toggle-thumb"></span>
                                </span>
                            </span>
                            <?php if ($aicon !== ''): ?>
                                <span class="section--pricing-calculator__row-icon" aria-hidden="true">
                                    <?php echo workernu_icon($aicon); ?>
                                </span>
                            <?php endif; ?>
                            <span class="section--pricing-calculator__row-text">
                                <span class="section--pricing-calculator__row-name"><?php echo wp_kses_post($aname); ?></span>
                                <?php if ($adesc !== ''): ?>
                                    <span class="section--pricing-calculator__row-desc"><?php echo wp_kses_post($adesc); ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="section--pricing-calculator__row-price"><?php echo esc_html($price_str); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </div><!-- /.addons-area -->

            <!-- ── Worker stepper (always visible) ── -->
            <div class="section--pricing-calculator__stepper-row">
                <?php if ($workers_label !== ''): ?>
                    <label class="section--pricing-calculator__stepper-label" for="<?php echo esc_attr($pc_uid); ?>">
                        <?php echo wp_kses_post($workers_label); ?>
                    </label>
                <?php endif; ?>
                <div class="section--pricing-calculator__stepper">
                    <button type="button" class="section--pricing-calculator__stepper-btn" data-pc="dec"
                            aria-label="<?php esc_attr_e('Decrease', 'workernu'); ?>">
                        <i class="fa-solid fa-minus" aria-hidden="true"></i>
                    </button>
                    <input class="section--pricing-calculator__stepper-input"
                           type="number"
                           id="<?php echo esc_attr($pc_uid); ?>"
                           value="<?php echo esc_attr($workers_default); ?>"
                           min="<?php echo esc_attr($workers_min); ?>"
                           max="<?php echo esc_attr($workers_max); ?>"
                           readonly>
                    <button type="button" class="section--pricing-calculator__stepper-btn" data-pc="inc"
                            aria-label="<?php esc_attr_e('Increase', 'workernu'); ?>">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <!-- ── Summary (always visible, dark bg) ── -->
            <div class="section--pricing-calculator__summary">
                <div class="section--pricing-calculator__summary-top">
                    <div class="section--pricing-calculator__summary-left">
                        <?php if ($summary_title !== ''): ?>
                            <span class="section--pricing-calculator__summary-title">
                                <?php echo wp_kses_post($summary_title); ?>
                            </span>
                        <?php endif; ?>
                        <span class="section--pricing-calculator__summary-plan" data-pc="summary-plan">
                            <?php echo esc_html($placeholder); ?>
                        </span>
                    </div>
                    <div class="section--pricing-calculator__summary-right">
                        <span class="section--pricing-calculator__summary-total" data-pc="summary-total">
                            0 <?php echo esc_html($currency); ?>
                        </span>
                        <span class="section--pricing-calculator__summary-meta" data-pc="summary-meta">
                            <?php printf(esc_html__('už %d darb. / mėn.', 'workernu'), $workers_default); ?>
                        </span>
                    </div>
                </div>
                <hr class="section--pricing-calculator__summary-divider">
                <div class="section--pricing-calculator__summary-per-row">
                    <?php if ($summary_per_label !== ''): ?>
                        <span class="section--pricing-calculator__summary-per-label">
                            <?php echo wp_kses_post($summary_per_label); ?>
                        </span>
                    <?php endif; ?>
                    <span class="section--pricing-calculator__summary-per" data-pc="summary-per">
                        0 <?php echo esc_html($currency); ?> / darb.
                    </span>
                </div>
                <?php if ($cta_label !== ''): ?>
                    <div class="section--pricing-calculator__cta-wrap">
                        <a class="btn btn--primary section--pricing-calculator__cta"
                           href="<?php echo $cta_url !== '' ? esc_url(workernu_localize_url($cta_url)) : '#'; ?>">
                            <?php echo wp_kses_post($cta_label); ?>
                        </a>
                        <?php if ($cta_note !== ''): ?>
                            <p class="section--pricing-calculator__cta-note"><?php echo wp_kses_post($cta_note); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>
</section>
<script>
(function () {
    var section      = document.currentScript.previousElementSibling;
    var addonsArea   = section.querySelector('[data-pc="addons-area"]');
    var selectedInfo = section.querySelector('[data-pc="selected-info"]');
    var planCards    = Array.from(section.querySelectorAll('[data-pc="plan-card"]'));
    var planRows     = Array.from(section.querySelectorAll('[data-pc="plan-row"]'));
    var addonRows    = Array.from(section.querySelectorAll('[data-pc="addon-row"]'));
    var decBtn       = section.querySelector('[data-pc="dec"]');
    var incBtn       = section.querySelector('[data-pc="inc"]');
    var workerInput  = section.querySelector('.section--pricing-calculator__stepper-input');
    var elSelName    = section.querySelector('[data-pc="selected-name"]');
    var elSelPrice   = section.querySelector('[data-pc="selected-price"]');
    var elPlanName   = section.querySelector('[data-pc="summary-plan"]');
    var elTotal      = section.querySelector('[data-pc="summary-total"]');
    var elMeta       = section.querySelector('[data-pc="summary-meta"]');
    var elPer        = section.querySelector('[data-pc="summary-per"]');

    var currency    = <?php echo json_encode($currency); ?>;
    var placeholder = <?php echo json_encode($placeholder); ?>;
    var workersMin  = <?php echo (int) $workers_min; ?>;
    var workersMax  = <?php echo (int) $workers_max; ?>;

    var selectedPlanIndex = null;
    var selectedPlanPrice = 0;
    var selectedPlanName  = '';
    var checkedPlanAddons = {};
    var checkedAddons     = {};
    var workers           = parseInt(workerInput.value, 10);

    function fmt(n) {
        return ((n === Math.floor(n)) ? n.toString() : n.toFixed(2)) + ' ' + currency;
    }

    function updateSummary() {
        decBtn.disabled   = workers <= workersMin;
        incBtn.disabled   = workers >= workersMax;
        workerInput.value = workers;
        if (elMeta) elMeta.textContent = 'už ' + workers + ' darb. / mėn.';

        if (selectedPlanIndex === null) {
            elPlanName.textContent = placeholder;
            elTotal.textContent    = '0 ' + currency;
            elPer.textContent      = '0 ' + currency + ' / darb.';
            return;
        }
        var planAddonSum = Object.values(checkedPlanAddons).reduce(function (a, b) { return a + b; }, 0);
        var addonSum     = Object.values(checkedAddons).reduce(function (a, b) { return a + b; }, 0);
        var perWorker    = selectedPlanPrice + planAddonSum + addonSum;
        var total        = perWorker * workers;
        elPlanName.textContent = selectedPlanName;
        elTotal.textContent    = fmt(total);
        elPer.textContent      = fmt(perWorker) + ' / darb.';
    }

    function updateAddonAvailability(planIndex) {
        addonRows.forEach(function (row) {
            var raw = (row.dataset.addonPlans || '').trim();
            var available = raw === '' || raw.split(',').map(function (s) {
                return parseInt(s.trim(), 10) - 1;
            }).indexOf(planIndex) !== -1;
            row.disabled = !available;
            if (!available && row.getAttribute('aria-pressed') === 'true') {
                row.setAttribute('aria-pressed', 'false');
                delete checkedAddons[row.dataset.addonIndex];
            }
        });
    }

    function enterConfiguredState(planIndex) {
        var card = planCards.filter(function (c) {
            return parseInt(c.dataset.planIndex, 10) === planIndex;
        })[0];
        if (!card) return;

        selectedPlanIndex = planIndex;
        selectedPlanPrice = parseFloat(card.dataset.planPrice);
        selectedPlanName  = card.querySelector('.section--pricing-calculator__plan-card-name').textContent.trim();
        checkedPlanAddons = {};
        checkedAddons     = {};

        planCards.forEach(function (c) {
            var active = parseInt(c.dataset.planIndex, 10) === planIndex;
            c.setAttribute('aria-checked', active ? 'true' : 'false');
            c.classList.toggle('is-active', active);
        });

        if (selectedInfo) {
            selectedInfo.hidden = false;
            if (elSelName)  elSelName.textContent  = selectedPlanName;
            if (elSelPrice) elSelPrice.textContent = card.dataset.planPriceStr;
        }

        if (addonsArea) addonsArea.hidden = false;

        planRows.forEach(function (row) {
            var isBase = parseInt(row.dataset.planIndex, 10) === planIndex;
            row.hidden = isBase;
            if (!isBase) {
                row.setAttribute('aria-pressed', 'false');
                row.disabled = false;
            }
        });

        updateAddonAvailability(planIndex);
        updateSummary();
    }

    function enterInitialState() {
        selectedPlanIndex = null;
        selectedPlanPrice = 0;
        selectedPlanName  = '';
        checkedPlanAddons = {};
        checkedAddons     = {};

        planCards.forEach(function (c) {
            c.setAttribute('aria-checked', 'false');
            c.classList.remove('is-active');
        });
        if (selectedInfo) selectedInfo.hidden = true;
        if (addonsArea)   addonsArea.hidden   = true;

        planRows.forEach(function (row) {
            row.hidden = false;
            row.setAttribute('aria-pressed', 'false');
        });
        addonRows.forEach(function (row) {
            row.setAttribute('aria-pressed', 'false');
            row.disabled = false;
        });
        updateSummary();
    }

    planCards.forEach(function (card) {
        card.addEventListener('click', function () {
            var idx = parseInt(card.dataset.planIndex, 10);
            if (idx === selectedPlanIndex) { enterInitialState(); }
            else                           { enterConfiguredState(idx); }
        });
    });

    planRows.forEach(function (row) {
        row.addEventListener('click', function () {
            var idx = parseInt(row.dataset.planIndex, 10);
            var on  = row.getAttribute('aria-pressed') === 'true';
            row.setAttribute('aria-pressed', on ? 'false' : 'true');
            if (on) { delete checkedPlanAddons[idx]; }
            else    { checkedPlanAddons[idx] = parseFloat(row.dataset.planAddonPrice); }
            updateSummary();
        });
    });

    addonRows.forEach(function (row) {
        row.addEventListener('click', function () {
            var on = row.getAttribute('aria-pressed') === 'true';
            row.setAttribute('aria-pressed', on ? 'false' : 'true');
            if (on) { delete checkedAddons[row.dataset.addonIndex]; }
            else    { checkedAddons[row.dataset.addonIndex] = parseFloat(row.dataset.addonPrice); }
            updateSummary();
        });
    });

    decBtn.addEventListener('click', function () { if (workers > workersMin) { workers--; updateSummary(); } });
    incBtn.addEventListener('click', function () { if (workers < workersMax) { workers++; updateSummary(); } });

    updateSummary();
}());
</script>
