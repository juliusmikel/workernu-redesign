(function () {
    'use strict';

    if (typeof wnInlineEditor === 'undefined') return;

    var cfg = wnInlineEditor;

    // A .wn-editable can sit inside a container with its OWN click handler
    // (a CTA's <a>, a FAQ <summary> that toggles its <details>, a submit
    // <button>, a consent <label> that toggles its checkbox, ...), attached
    // directly to that ancestor. Starting the edit uses capture phase +
    // stopPropagation() so the click never reaches that ancestor's handler
    // at all — safe here because nothing else needs this particular click
    // to keep propagating.
    document.addEventListener('click', function (e) {
        var pencil = e.target.closest('.wn-editable__pencil');
        if (!pencil) return;
        e.preventDefault();
        e.stopPropagation();
        startEdit(pencil.closest('.wn-editable'));
    }, true);

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        var pencil = e.target.closest('.wn-editable__pencil');
        if (!pencil) return;
        e.preventDefault();
        e.stopPropagation();
        startEdit(pencil.closest('.wn-editable'));
    }, true);

    function startEdit(wrapper) {
        if (!wrapper || wrapper.classList.contains('is-editing')) return;

        var content = wrapper.querySelector('.wn-editable__content');
        var raw     = wrapper.dataset.wnRaw || '';

        // Always a <textarea>, even for single-line "text" fields — a plain
        // <input> clips a long heading/label to one scrolling line, which
        // makes editing it awkward. A short textarea still reads as "one
        // field" but lets the text wrap into view.
        var input = document.createElement('textarea');
        input.className = 'wn-editable__input';
        input.value = raw;

        var toolbar = document.createElement('div');
        toolbar.className = 'wn-editable__toolbar';
        toolbar.innerHTML =
            '<button type="button" data-wn-action="save">' + cfg.i18n.save + '</button>' +
            '<button type="button" data-wn-action="cancel">' + cfg.i18n.cancel + '</button>' +
            '<span class="wn-editable__status"></span>';

        // Stops a click on the input or a toolbar button from continuing on
        // to whatever ancestor .wn-editable happens to sit inside (a CTA's
        // <a>, a FAQ <summary>, a submit <button>, ...) — added here as a
        // plain bubble-phase listener, so it runs AFTER toolbar's own click
        // handler below (a descendant) has already done its job, and only
        // stops the event from going any further up than this point.
        wrapper._wnStopBubble = function (e) { e.stopPropagation(); };
        wrapper.addEventListener('click', wrapper._wnStopBubble);

        wrapper.classList.add('is-editing');
        content.style.display = 'none';
        wrapper.appendChild(input);
        wrapper.appendChild(toolbar);
        input.focus();

        toolbar.addEventListener('click', function (e) {
            var btn = e.target.closest('button[data-wn-action]');
            if (!btn) return;
            var action = btn.dataset.wnAction;

            if (action === 'cancel') {
                endEdit(wrapper, input, toolbar, content);
                return;
            }
            if (action === 'save') {
                save(wrapper, input.value, toolbar.querySelector('.wn-editable__status'), function () {
                    endEdit(wrapper, input, toolbar, content);
                });
            }
        });
    }

    function endEdit(wrapper, input, toolbar, content) {
        wrapper.removeEventListener('click', wrapper._wnStopBubble);
        delete wrapper._wnStopBubble;
        wrapper.classList.remove('is-editing');
        input.remove();
        toolbar.remove();
        content.style.display = '';
    }

    function save(wrapper, value, statusEl, onDone) {
        var field = wrapper.dataset.wnField; // "<section_id>::<field_path>"
        var sep   = field.indexOf('::');
        var sectionId = field.slice(0, sep);
        var fieldPath = field.slice(sep + 2);

        if (statusEl) statusEl.textContent = cfg.i18n.saving;

        var body = new URLSearchParams();
        body.set('action', 'workernu_inline_save');
        body.set('nonce', cfg.nonce);
        body.set('post_id', cfg.postId);
        body.set('section_id', sectionId);
        body.set('field_path', fieldPath);
        body.set('value', value);

        fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) {
                    if (statusEl) statusEl.textContent = (res.data && res.data.message) || cfg.i18n.error;
                    return;
                }
                wrapper.dataset.wnRaw = value;
                wrapper.querySelector('.wn-editable__content').textContent = value;
                if (onDone) onDone();
            })
            .catch(function () {
                if (statusEl) statusEl.textContent = cfg.i18n.error;
            });
    }
})();
