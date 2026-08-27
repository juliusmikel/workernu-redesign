/* workernu — main.js
 * Site-wide JS. GSAP/ScrollTrigger to be added in a later phase.
 */
(function () {
    'use strict';

    /* ─── Header: mobile nav toggle ───
     * Desktop dropdowns are pure CSS (hover + focus-within). On mobile the
     * hamburger toggles the whole nav panel; submenus render inline. */
    function initHeaderNav() {
        var header = document.querySelector('[data-site-header]');
        if (!header) return;
        var toggle = header.querySelector('[data-nav-toggle]');
        var nav = header.querySelector('#site-nav');
        if (!toggle || !nav) return;

        function setOpen(open) {
            header.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        toggle.addEventListener('click', function () {
            setOpen(!header.classList.contains('is-open'));
        });

        // Close on Escape.
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && header.classList.contains('is-open')) {
                setOpen(false);
                toggle.focus();
            }
        });

        // Close when clicking outside the header.
        document.addEventListener('click', function (e) {
            if (header.classList.contains('is-open') && !header.contains(e.target)) {
                setOpen(false);
            }
        });

        // Reset when resizing up to desktop.
        var mq = window.matchMedia('(min-width: 901px)');
        (mq.addEventListener ? mq.addEventListener.bind(mq, 'change') : mq.addListener.bind(mq))(function () {
            if (mq.matches) setOpen(false);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeaderNav);
    } else {
        initHeaderNav();
    }
})();
