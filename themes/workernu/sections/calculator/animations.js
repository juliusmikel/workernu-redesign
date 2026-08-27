/* Calculator — live savings maths. Vanilla JS, multi-instance safe.
 *
 * Reads rates from the section's data-* attributes and recomputes the three
 * results whenever a slider moves. Mirrors the server-side model in template.php:
 *   monthlySpend   = employees * costEmployee + projects * costProject
 *   monthlySavings = monthlySpend * (savingsRate / 100)
 *   yearlySavings  = monthlySavings * 12
 */
(function () {
    function initCalc(root) {
        var costEmployee = parseFloat(root.getAttribute('data-cost-employee')) || 0;
        var costProject  = parseFloat(root.getAttribute('data-cost-project'))  || 0;
        var savingsRate  = parseFloat(root.getAttribute('data-savings-rate'))  || 0;
        var currency     = root.getAttribute('data-currency') || '';

        var sliders = Array.prototype.slice.call(root.querySelectorAll('[data-calc-input]'));
        if (!sliders.length) return;

        function format(n) {
            return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + currency;
        }

        function readValue(key) {
            var el = root.querySelector('[data-calc-input="' + key + '"]');
            return el ? (parseFloat(el.value) || 0) : 0;
        }

        function setResult(key, value) {
            var el = root.querySelector('[data-calc-result="' + key + '"]');
            if (el) el.textContent = format(value);
        }

        function recompute() {
            var employees = readValue('employees');
            var projects  = readValue('projects');
            var spend   = employees * costEmployee + projects * costProject;
            var savings = spend * (savingsRate / 100);
            var yearly  = savings * 12;
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

        sliders.forEach(function (slider) {
            var outId = slider.getAttribute('data-calc-output');
            var out = outId ? root.querySelector('#' + (window.CSS && CSS.escape ? CSS.escape(outId) : outId)) : null;
            updateFill(slider);  // initial position
            slider.addEventListener('input', function () {
                if (out) out.textContent = slider.value;
                updateFill(slider);
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
