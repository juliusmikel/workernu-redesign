(function () {
    'use strict';

    if (typeof wnInlineEditor === 'undefined') return;

    var cfg = wnInlineEditor;

    document.addEventListener('click', function (e) {
        var pencil = e.target.closest('.wn-editable__pencil');
        if (pencil) {
            e.preventDefault();
            startEdit(pencil.closest('.wn-editable'));
            return;
        }

        var publishNode = e.target.closest('.wn-admin-bar-publish');
        if (publishNode && !publishNode.classList.contains('is-disabled')) {
            e.preventDefault();
            publishAll();
        }
    });

    function startEdit(wrapper) {
        if (!wrapper || wrapper.classList.contains('is-editing')) return;

        var content = wrapper.querySelector('.wn-editable__content');
        var type    = wrapper.dataset.wnType;
        var raw     = wrapper.dataset.wnRaw || '';
        var isMulti = type !== 'text';

        var input = document.createElement(isMulti ? 'textarea' : 'input');
        input.className = 'wn-editable__input';
        if (!isMulti) input.type = 'text';
        input.value = raw;

        var toolbar = document.createElement('div');
        toolbar.className = 'wn-editable__toolbar';
        toolbar.innerHTML =
            '<button type="button" data-wn-action="save">' + cfg.i18n.save + '</button>' +
            '<button type="button" data-wn-action="publish">' + cfg.i18n.publish + '</button>' +
            '<button type="button" data-wn-action="cancel">' + cfg.i18n.cancel + '</button>' +
            '<span class="wn-editable__status"></span>';

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
                saveDraft(wrapper, input.value, toolbar.querySelector('.wn-editable__status'));
                return;
            }
            if (action === 'publish') {
                saveDraft(wrapper, input.value, toolbar.querySelector('.wn-editable__status'), function () {
                    publishAll();
                });
            }
        });
    }

    function endEdit(wrapper, input, toolbar, content) {
        wrapper.classList.remove('is-editing');
        input.remove();
        toolbar.remove();
        content.style.display = '';
    }

    function saveDraft(wrapper, value, statusEl, onDone) {
        var field = wrapper.dataset.wnField; // "<section_id>::<field_path>"
        var sep   = field.indexOf('::');
        var sectionId = field.slice(0, sep);
        var fieldPath = field.slice(sep + 2);

        if (statusEl) statusEl.textContent = cfg.i18n.saving;

        var body = new URLSearchParams();
        body.set('action', 'workernu_inline_save_draft');
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
                if (statusEl) statusEl.textContent = cfg.i18n.saved;
                refreshPublishNode(res.data.has_pending);
                if (onDone) onDone();
            })
            .catch(function () {
                if (statusEl) statusEl.textContent = cfg.i18n.error;
            });
    }

    function publishAll() {
        var body = new URLSearchParams();
        body.set('action', 'workernu_inline_publish');
        body.set('nonce', cfg.nonce);
        body.set('post_id', cfg.postId);

        fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) refreshPublishNode(false);
            });
    }

    function refreshPublishNode(hasPending) {
        var node = document.querySelector('.wn-admin-bar-publish');
        if (!node) return;
        node.classList.toggle('is-disabled', !hasPending);
        node.textContent = hasPending ? cfg.i18n.publish : cfg.i18n.saved;
    }
})();
