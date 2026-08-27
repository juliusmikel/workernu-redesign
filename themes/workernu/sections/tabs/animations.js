/* Tabs — tab switching. Vanilla JS, multi-instance safe.
 *
 * Each [data-animate="tabs"] instance wires its own tablist so multiple Tabs
 * sections on one page don't interfere. Markup degrades gracefully without JS:
 * the first panel is visible and the rest are [hidden]. */
(function () {
    function initTabs(root) {
        var tablist = root.querySelector('.section--tabs__tablist');
        if (!tablist) return;

        var tabs = Array.prototype.slice.call(tablist.querySelectorAll('.section--tabs__tab'));
        if (!tabs.length) return;

        function panelFor(tab) {
            var id = tab.getAttribute('aria-controls');
            return id ? root.querySelector('#' + (window.CSS && CSS.escape ? CSS.escape(id) : id)) : null;
        }

        function activate(index, focus) {
            tabs.forEach(function (tab, i) {
                var selected = i === index;
                tab.classList.toggle('is-active', selected);
                tab.setAttribute('aria-selected', selected ? 'true' : 'false');
                tab.tabIndex = selected ? 0 : -1;
                var panel = panelFor(tab);
                if (panel) {
                    panel.classList.toggle('is-active', selected);
                    if (selected) { panel.removeAttribute('hidden'); }
                    else { panel.setAttribute('hidden', ''); }
                }
                if (selected && focus) tab.focus();
            });
        }

        tabs.forEach(function (tab, i) {
            tab.tabIndex = i === 0 ? 0 : -1;
            tab.addEventListener('click', function () { activate(i, false); });
            tab.addEventListener('keydown', function (e) {
                var next = null;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') next = (i + 1) % tabs.length;
                else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') next = (i - 1 + tabs.length) % tabs.length;
                else if (e.key === 'Home') next = 0;
                else if (e.key === 'End') next = tabs.length - 1;
                if (next !== null) { e.preventDefault(); activate(next, true); }
            });
        });
    }

    function init() {
        document.querySelectorAll('[data-animate="tabs"]').forEach(initTabs);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
