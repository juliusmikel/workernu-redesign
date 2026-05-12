/* workernu-sections — admin builder
 *
 * Vanilla JS. Depends on:
 *   - SortableJS (loaded via CDN by the plugin)
 *   - wp.media (loaded via wp_enqueue_media() in PHP)
 *
 * Responsibilities:
 *   - Drag-to-reorder section cards
 *   - Add section from the dropdown (clone template)
 *   - Remove section
 *   - Collapse/expand card body
 *   - Global language tabs (one toggle switches every translatable input in the form)
 *   - Image picker via the WP media library
 *   - Repeater field type: add/remove/reorder items, renumber inputs in scope
 *   - Renumber section input names on every change so the saved POST array is a clean sequence
 */
(function () {
    'use strict';

    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $$(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }
    function uid(prefix) { return prefix + '-' + Math.random().toString(36).slice(2, 10); }

    function init() {
        var builder = $('[data-ws-builder]');
        if (!builder) return;

        initSortable(builder);
        initRepeaters(builder);
        initLangTabs(builder);
        applyLangTo(builder, builder.dataset.wsLang);
        bindToolbar(builder);
        bindCardEvents(builder);
        bindImagePickers(builder);
        bindRepeaterEvents(builder);
        bindSubmit(builder);
    }

    /* ─── Sortable: section cards ─── */

    function initSortable(builder) {
        var list = $('[data-ws-list]', builder);
        if (!list || typeof Sortable === 'undefined') return;
        Sortable.create(list, {
            handle: '.ws-card__handle',
            animation: 150,
            ghostClass: 'ws-card--ghost',
            onEnd: function () { reindex(builder); }
        });
    }

    /* ─── Sortable: repeater items inside a scope ─── */

    function initRepeaters(scope) {
        if (typeof Sortable === 'undefined') return;
        $$('[data-ws-repeater]', scope).forEach(function (repeater) {
            var list = repeater.querySelector('[data-ws-repeater-list]');
            if (!list || list.dataset.wsSortableReady === '1') return;
            list.dataset.wsSortableReady = '1';
            Sortable.create(list, {
                handle: '.ws-repeater__handle',
                animation: 150,
                ghostClass: 'ws-repeater__item--ghost',
                onEnd: function () { renumberRepeater(repeater); }
            });
        });
    }

    /* ─── Toolbar (add + lang tabs) ─── */

    function bindToolbar(builder) {
        var addBtn = $('[data-ws-add]', builder);
        var select = $('[data-ws-add-type]', builder);
        if (!addBtn || !select) return;

        addBtn.addEventListener('click', function () {
            var type = select.value;
            if (!type) return;
            addCard(builder, type);
            select.value = '';
        });
    }

    function addCard(builder, type) {
        var script = builder.querySelector('[data-ws-template="' + type + '"]');
        if (!script) {
            console.warn('[workernu-sections] No template for type:', type);
            return;
        }
        var html = script.innerHTML.replace(/__ID__/g, uid(type)).trim();
        var tpl = document.createElement('template');
        tpl.innerHTML = html;
        var card = tpl.content.firstElementChild;
        if (!card) {
            console.warn('[workernu-sections] Failed to parse template for type:', type);
            return;
        }
        var list = $('[data-ws-list]', builder);
        list.appendChild(card);
        reindex(builder);
        initRepeaters(card);
        applyLangTo(builder, builder.dataset.wsLang);
    }

    function initLangTabs(builder) {
        var tabs = $$('[data-ws-lang-tab]', builder);
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var lang = tab.dataset.wsLangTab;
                builder.dataset.wsLang = lang;
                tabs.forEach(function (t) {
                    var on = t === tab;
                    t.classList.toggle('is-active', on);
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                applyLangTo(builder, lang);
            });
        });
    }

    function applyLangTo(builder, lang) {
        $$('.ws-translatable__panel', builder).forEach(function (panel) {
            panel.classList.toggle('is-active', panel.dataset.lang === lang);
        });
    }

    /* ─── Card events (toggle, remove) ─── */

    function bindCardEvents(builder) {
        builder.addEventListener('click', function (e) {
            var toggle = e.target.closest('[data-ws-toggle]');
            if (toggle && toggle.closest('[data-ws-builder]')) {
                var card = toggle.closest('[data-ws-card]');
                card.classList.toggle('is-collapsed');
                toggle.textContent = card.classList.contains('is-collapsed') ? '▸' : '▾';
                return;
            }

            var remove = e.target.closest('[data-ws-remove]');
            if (remove && !e.target.closest('[data-ws-repeater-remove]')) {
                if (!window.confirm('Remove this section?')) return;
                remove.closest('[data-ws-card]').remove();
                reindex(builder);
                return;
            }
        });
    }

    /* ─── Image picker (wp.media) ─── */

    function bindImagePickers(builder) {
        builder.addEventListener('click', function (e) {
            var pick = e.target.closest('[data-ws-image-pick]');
            if (pick) {
                openMedia(pick.closest('[data-ws-image]'));
                return;
            }
            var clear = e.target.closest('[data-ws-image-clear]');
            if (clear) {
                clearMedia(clear.closest('[data-ws-image]'));
                return;
            }
        });
    }

    function openMedia(scope) {
        if (typeof wp === 'undefined' || !wp.media) return;
        var frame = wp.media({
            title: 'Select image',
            multiple: false,
            library: { type: 'image' },
            button: { text: 'Use image' }
        });
        frame.on('select', function () {
            var sel = frame.state().get('selection').first().toJSON();
            var url = (sel.sizes && sel.sizes.medium) ? sel.sizes.medium.url : sel.url;
            scope.querySelector('[data-ws-image-id]').value = sel.id;
            var preview = scope.querySelector('.ws-image__preview');
            preview.innerHTML = '<img src="' + url + '" alt="">';
            preview.hidden = false;
            scope.querySelector('[data-ws-image-clear]').hidden = false;
        });
        frame.open();
    }

    function clearMedia(scope) {
        scope.querySelector('[data-ws-image-id]').value = '';
        var preview = scope.querySelector('.ws-image__preview');
        preview.innerHTML = '';
        preview.hidden = true;
        scope.querySelector('[data-ws-image-clear]').hidden = true;
    }

    /* ─── Repeater events (add/remove items) ─── */

    function bindRepeaterEvents(builder) {
        builder.addEventListener('click', function (e) {
            var add = e.target.closest('[data-ws-repeater-add]');
            if (add) {
                var repeater = add.closest('[data-ws-repeater]');
                if (!repeater) return;
                addRepeaterItem(repeater);
                applyLangTo(builder, builder.dataset.wsLang);
                return;
            }

            var remove = e.target.closest('[data-ws-repeater-remove]');
            if (remove) {
                var item = remove.closest('[data-ws-repeater-item]');
                if (!item) return;
                var rep = item.closest('[data-ws-repeater]');
                item.remove();
                if (rep) renumberRepeater(rep);
                return;
            }
        });
    }

    function addRepeaterItem(repeater) {
        var src = repeater.querySelector(':scope > template[data-ws-repeater-template]');
        if (!src) return;
        var list = repeater.querySelector('[data-ws-repeater-list]');
        if (!list) return;
        var nextIdx = list.children.length;
        var html = src.innerHTML.replace(/__ITEM__/g, String(nextIdx)).trim();
        var tpl = document.createElement('template');
        tpl.innerHTML = html;
        var item = tpl.content.firstElementChild;
        if (!item) return;
        list.appendChild(item);
        renumberRepeater(repeater);
    }

    function renumberRepeater(repeater) {
        var fieldName = repeater.dataset.wsRepeater;
        if (!fieldName) return;
        // Match the bracket immediately after `[fieldName]` and replace its index.
        var pattern = new RegExp('(\\[' + escapeRegex(fieldName) + '\\])\\[[^\\]]+\\]');
        $$('[data-ws-repeater-list] > [data-ws-repeater-item]', repeater).forEach(function (item, idx) {
            $$('[name]', item).forEach(function (input) {
                input.name = input.name.replace(pattern, '$1[' + idx + ']');
            });
        });
    }

    function escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /* ─── Reindex (top-level section cards) ─── */

    function reindex(builder) {
        $$('[data-ws-list] > [data-ws-card]', builder).forEach(function (card, idx) {
            $$('[name^="workernu_sections["]', card).forEach(function (input) {
                input.name = input.name.replace(/^workernu_sections\[[^\]]+\]/, 'workernu_sections[' + idx + ']');
            });
        });
    }

    function bindSubmit(builder) {
        var form = builder.closest('form');
        if (!form) return;
        form.addEventListener('submit', function () {
            reindex(builder);
            $$('[data-ws-repeater]', builder).forEach(renumberRepeater);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
