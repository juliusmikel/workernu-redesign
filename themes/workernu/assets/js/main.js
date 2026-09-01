/* workernu — main.js
 * Site-wide JS. GSAP/ScrollTrigger to be added in a later phase.
 */
(function () {
    'use strict';

    /* ─── Header: mobile nav toggle ───
     * Desktop dropdowns are pure CSS (hover + focus-within). On mobile the
     * hamburger opens .mobile-nav, a separate full-screen multi-view panel
     * (see workernu_mobile_nav_menu() in functions.php) — a "main" view plus
     * one view per top-level item that has children, switched by toggling
     * which [data-mobile-nav-view] carries .is-active. */
    function initHeaderNav() {
        var header = document.querySelector('[data-site-header]');
        if (!header) return;
        var toggle = header.querySelector('[data-nav-toggle]');
        var panel = header.querySelector('[data-mobile-nav]');
        if (!toggle || !panel) return;

        var views = Array.prototype.slice.call(panel.querySelectorAll('[data-mobile-nav-view]'));

        function showView(key) {
            views.forEach(function (v) {
                v.classList.toggle('is-active', v.getAttribute('data-mobile-nav-view') === key);
            });
        }

        function setOpen(open) {
            header.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.classList.toggle('has-mobile-nav-open', open);
            // Always land back on the main view next time the panel opens.
            if (!open) showView('main');
        }

        toggle.addEventListener('click', function () {
            setOpen(!header.classList.contains('is-open'));
        });

        panel.addEventListener('click', function (e) {
            var openBtn = e.target.closest('[data-mobile-nav-open]');
            if (openBtn) { showView(openBtn.getAttribute('data-mobile-nav-open')); return; }
            var backBtn = e.target.closest('[data-mobile-nav-back]');
            if (backBtn) { showView('main'); return; }
            var closeBtn = e.target.closest('[data-mobile-nav-close]');
            if (closeBtn) { setOpen(false); return; }
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

        // Close on any width change while open — not just crossing up to
        // desktop. A resized/rotated viewport mid-open otherwise leaves the
        // fixed-position panel and its .mobile-nav__view offsets stale
        // against the new dimensions. Compared against innerWIDTH
        // specifically (not a generic resize listener) so mobile Safari's
        // address-bar show/hide — which fires resize on scroll and changes
        // innerHeight only — doesn't close the menu out from under a user
        // who's just scrolling the panel's own list.
        var lastWidth = window.innerWidth;
        window.addEventListener('resize', function () {
            if (window.innerWidth === lastWidth) return;
            lastWidth = window.innerWidth;
            if (header.classList.contains('is-open')) setOpen(false);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeaderNav);
    } else {
        initHeaderNav();
    }
})();
