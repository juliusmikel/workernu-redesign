/* Feature Accordion — smooth slide open/close; 0 or 1 row open at a time.
 *
 * First row starts open (set by PHP). After that: opening a row closes
 * whichever other row was open; closing the open row (clicking its own
 * title again) is allowed, down to zero rows open. The right-hand image
 * always shows whichever row was opened MOST RECENTLY — via .is-active on
 * the image — even after that row is later closed, so the pane never goes
 * blank once something's been opened.
 *
 * The <details name="…"> attribute in the markup is a no-JS fallback only.
 * The browser's native "close other same-name details" behavior fires on
 * ANY open-attribute change — including ones made by this script — which
 * fights a JS-driven close animation (the browser can slam a sibling closed
 * mid-slide, orphaning the transition). So on init this script strips the
 * `name` attribute and takes over exclusivity itself.
 *
 * Vanilla JS, multi-instance safe via [data-animate="feature-accordion"]. */
(function () {
    function bodyOf(details) {
        return details.querySelector('.section--feature-accordion__body-wrap');
    }

    // Rapid clicking can start a new animation before the previous one's
    // transitionend fires — drop any pending handler first so listeners
    // never stack/orphan.
    function clearPending(body) {
        if (body._faTransitionEnd) {
            body.removeEventListener('transitionend', body._faTransitionEnd);
            body._faTransitionEnd = null;
        }
    }

    function slideTo(body, targetPx, onDone) {
        clearPending(body);
        // Snap the starting point to whatever height is ACTUALLY rendered
        // right now (not assumed 0/scrollHeight) so retargeting mid-slide
        // animates smoothly instead of jumping.
        body.style.height = body.getBoundingClientRect().height + 'px';
        requestAnimationFrame(function () {
            body.style.height = targetPx;
        });
        var handler = function (e) {
            if (e.target !== body || e.propertyName !== 'height') return;
            clearPending(body);
            if (onDone) onDone();
        };
        body._faTransitionEnd = handler;
        body.addEventListener('transitionend', handler);
    }

    function openRow(details) {
        var body = bodyOf(details);
        details.setAttribute('open', '');
        slideTo(body, body.scrollHeight + 'px', function () {
            body.style.height = 'auto';
        });
    }

    function closeRow(details) {
        var body = bodyOf(details);
        slideTo(body, '0px', function () {
            details.removeAttribute('open');
        });
    }

    function setActiveImage(root, index) {
        var imgs = root.querySelectorAll('.section--feature-accordion__media-img');
        for (var i = 0; i < imgs.length; i++) {
            imgs[i].classList.toggle('is-active', i === index);
        }
    }

    function initAccordion(root) {
        var items = Array.prototype.slice.call(root.querySelectorAll('.section--feature-accordion__item'));
        if (!items.length) return;

        var detailsList = items.map(function (item) {
            return item.querySelector('.section--feature-accordion__details');
        });

        detailsList.forEach(function (d) { d.removeAttribute('name'); });

        var openIndex = 0;
        detailsList.forEach(function (d, i) {
            var body = bodyOf(d);
            var isOpen = d.hasAttribute('open');
            body.style.height = isOpen ? 'auto' : '0px';
            if (isOpen) openIndex = i;
        });
        setActiveImage(root, openIndex);

        detailsList.forEach(function (details, i) {
            var summary = details.querySelector('.section--feature-accordion__title');
            summary.addEventListener('click', function (e) {
                e.preventDefault();

                if (details.hasAttribute('open')) {
                    closeRow(details);
                    return;
                }

                detailsList.forEach(function (d) {
                    if (d !== details && d.hasAttribute('open')) closeRow(d);
                });
                openRow(details);
                setActiveImage(root, i);
            });
        });
    }

    function init() {
        document.querySelectorAll('[data-animate="feature-accordion"]').forEach(initAccordion);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
