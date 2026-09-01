/* Savings Calculator — live savings maths. Vanilla JS, multi-instance safe.
 *
 * Reads rates from the section's data-* attributes and recomputes the three
 * results whenever a slider moves. Mirrors the server-side model in
 * template.php — spend depends only on employees; monthly and yearly savings
 * are each an independent linear function of (employees, projects):
 *   spend          = employees * spendRateEmployee
 *   monthlySavings = employees * savingsRateEmployeeMonthly + projects * savingsRateProjectMonthly
 *   yearlySavings  = employees * savingsRateEmployeeYearly  + projects * savingsRateProjectYearly
 * yearlySavings is NOT monthlySavings * 12 — it's fit independently.
 */
(function () {
    function initCalc(root) {
        var spendRateEmployee        = parseFloat(root.getAttribute('data-spend-rate-employee'))          || 0;
        var savingsRateEmployeeMonth = parseFloat(root.getAttribute('data-savings-rate-employee-monthly')) || 0;
        var savingsRateProjectMonth  = parseFloat(root.getAttribute('data-savings-rate-project-monthly'))  || 0;
        var savingsRateEmployeeYear  = parseFloat(root.getAttribute('data-savings-rate-employee-yearly'))  || 0;
        var savingsRateProjectYear   = parseFloat(root.getAttribute('data-savings-rate-project-yearly'))   || 0;
        var currency     = root.getAttribute('data-currency') || '';

        var sliders = Array.prototype.slice.call(root.querySelectorAll('[data-calc-input]'));
        if (!sliders.length) return;

        // "spend" is exact (2 decimals, no leading ~) — it's just today's
        // known cost. Savings figures are estimates, so they're rounded to
        // whole numbers with a leading ~. Neither has a space before the
        // currency symbol.
        function formatExact(n) {
            return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + currency;
        }
        function formatApprox(n) {
            return '~' + Math.round(n).toLocaleString('en-US') + currency;
        }

        function readValue(key) {
            var el = root.querySelector('[data-calc-input="' + key + '"]');
            return el ? (parseFloat(el.value) || 0) : 0;
        }

        function setResult(key, value) {
            var el = root.querySelector('[data-calc-result="' + key + '"]');
            if (!el) return;
            el.textContent = key === 'spend' ? formatExact(value) : formatApprox(value);
        }

        function recompute() {
            var employees = readValue('employees');
            var projects  = readValue('projects');
            var spend   = employees * spendRateEmployee;
            var savings = employees * savingsRateEmployeeMonth + projects * savingsRateProjectMonth;
            var yearly  = employees * savingsRateEmployeeYear  + projects * savingsRateProjectYear;
            setResult('spend', spend);
            setResult('savings', savings);
            setResult('yearly', yearly);
        }

        function updateFill(slider) {
            var min = parseFloat(slider.min) || 0;
            var max = parseFloat(slider.max) || 100;
            var val = parseFloat(slider.value) || 0;
            var pct = max === min ? 0 : Math.max(0, Math.min(100, ((val - min) / (max - min)) * 100));
            slider.style.setProperty('--fill', pct + '%');
        }

        function escId(id) {
            return window.CSS && CSS.escape ? CSS.escape(id) : id;
        }

        // Mobile's stepper buttons are a second control surface for the same
        // range input (style.css shows exactly one of the two per breakpoint);
        // grey a button out once its direction is exhausted.
        function syncStepperState(slider) {
            var min = parseFloat(slider.min);
            var max = parseFloat(slider.max);
            var val = parseFloat(slider.value);
            var btns = root.querySelectorAll('[data-calc-step-target="' + slider.id + '"]');
            Array.prototype.forEach.call(btns, function (btn) {
                var step = parseFloat(btn.getAttribute('data-calc-step')) || 0;
                btn.disabled = (step < 0 && val <= min) || (step > 0 && val >= max);
            });
        }

        sliders.forEach(function (slider) {
            var outId = slider.getAttribute('data-calc-output');
            var out = outId ? root.querySelector('#' + escId(outId)) : null;
            updateFill(slider);  // initial position
            syncStepperState(slider);
            slider.addEventListener('input', function () {
                // out is the editable count <input> (type=number), not a
                // read-only <output> — set .value, not .textContent, and
                // skip it while it's the thing the user is actively typing
                // in (avoids fighting their keystrokes mid-edit).
                if (out && document.activeElement !== out) out.value = slider.value;
                updateFill(slider);
                syncStepperState(slider);
                recompute();
            });
        });

        // Steppers just move the underlying slider and dispatch its own
        // `input` event, so the listener above stays the single place that
        // updates the output, fill and results — no logic duplicated here.
        var stepperBtns = Array.prototype.slice.call(root.querySelectorAll('[data-calc-step-target]'));
        stepperBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-calc-step-target');
                var slider = targetId ? root.querySelector('#' + escId(targetId)) : null;
                if (!slider) return;
                var step = parseFloat(btn.getAttribute('data-calc-step')) || 0;
                var min = parseFloat(slider.min) || 0;
                var max = parseFloat(slider.max) || 100;
                var current = parseFloat(slider.value) || 0;
                var next = Math.max(min, Math.min(max, current + step));
                if (next === current) return;
                slider.value = next;
                slider.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });

        // The count <input> next to each slider is directly editable — typing
        // a number moves the slider live (while still in range), and on
        // blur/Enter the displayed value snaps to a clamped, rounded integer
        // so it can never show something the slider itself couldn't reach.
        var countInputs = Array.prototype.slice.call(root.querySelectorAll('[data-calc-count-target]'));
        countInputs.forEach(function (input) {
            var targetId = input.getAttribute('data-calc-count-target');
            var slider = targetId ? root.querySelector('#' + escId(targetId)) : null;
            if (!slider) return;

            input.addEventListener('input', function () {
                var min = parseFloat(slider.min) || 0;
                var max = parseFloat(slider.max) || 100;
                var val = parseFloat(input.value);
                if (isNaN(val) || val < min || val > max) return;
                slider.value = val;
                updateFill(slider);
                syncStepperState(slider);
                recompute();
            });

            input.addEventListener('change', function () {
                var min = parseFloat(slider.min) || 0;
                var max = parseFloat(slider.max) || 100;
                var val = parseFloat(input.value);
                if (isNaN(val)) val = parseFloat(slider.value) || min;
                val = Math.max(min, Math.min(max, Math.round(val)));
                input.value = val;
                slider.value = val;
                updateFill(slider);
                syncStepperState(slider);
                recompute();
            });
        });

        recompute();
    }

    function init() {
        document.querySelectorAll('[data-animate="calculator"]').forEach(initCalc);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
