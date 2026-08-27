/* workernu-sections — admin builder
 *
 * Vanilla JS. Depends on:
 *   - wp.media (loaded via wp_enqueue_media() in PHP)
 *
 * Responsibilities:
 *   - Add section from the dropdown (clone template)
 *   - Remove section
 *   - Move section up/down via chevron buttons (no drag-and-drop)
 *   - Collapse/expand card body
 *   - Global language tabs (one toggle switches every translatable input in the form)
 *   - Image picker via the WP media library
 *   - Repeater field type: add/remove/move/renumber items in scope
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

        initLangTabs(builder);
        applyLangTo(builder, builder.dataset.wsLang);
        bindToolbar(builder);
        bindCardEvents(builder);
        bindImagePickers(builder);
        bindGalleryPickers(builder);
        bindRepeaterEvents(builder);
        bindConditionalFields(builder);
        bindCopyPaste(builder);
        bindSubmit(builder);
        updateMoveStates(builder);
        restoreCollapsedState(builder);
    }

    /* ─── Conditional fields (show_if)
     * Fields declaring `show_if` in their schema get the data attributes
     * `data-ws-show-if-field` (the source field's name) and
     * `data-ws-show-if-equals` (a JSON array of allowed values). When the
     * source's value isn't in the allowed list, the dependent is hidden via
     * the `is-disabled-by-condition` class (CSS sets `display: none`). The
     * inputs are deliberately NOT disabled so their values survive a save
     * round-trip — flipping the source back restores the original input data.
     * Source and dependent must live at the same card level for now (no
     * nested repeater lookup). */
    function bindConditionalFields(builder) {
        applyAllConditional(builder);
        // Delegated listeners — works across newly-added cards too.
        builder.addEventListener('change', function (e) { handleConditionalChange(e.target); });
        builder.addEventListener('input',  function (e) { handleConditionalChange(e.target); });
    }
    function applyAllConditional(builder) {
        $$('[data-ws-show-if-field], [data-ws-show-if-not-empty-field]', builder).forEach(applyConditionalField);
    }
    function handleConditionalChange(input) {
        if (!input || !input.name) return;
        // Field name can appear either as the last bracketed segment
        // (`...[icon_image]`) or one above it for image fields that store a
        // language-keyed map (`...[icon_image][lt]`).
        var name = String(input.name);
        var m = name.match(/\[([^\[\]]+)\]\[[a-z]{2}\]$/) || name.match(/\[([^\[\]]+)\]$/);
        if (!m) return;
        var fieldName = m[1];
        var scope = input.closest('[data-ws-repeater-item]') || input.closest('[data-ws-card]');
        if (!scope) return;
        // A change to `fieldName` can affect either flavour of dependent, so
        // re-evaluate both.
        var deps = $$('[data-ws-show-if-field="' + fieldName + '"], [data-ws-show-if-not-empty-field="' + fieldName + '"]', scope);
        deps.forEach(applyConditionalField);
    }
    function applyConditionalField(dep) {
        var scope = dep.closest('[data-ws-repeater-item]') || dep.closest('[data-ws-card]');
        if (!scope) return;

        var matches = true;

        // Equality check (data-ws-show-if-field + data-ws-show-if-equals).
        if (dep.dataset.wsShowIfField) {
            var allowed;
            try { allowed = JSON.parse(dep.dataset.wsShowIfEquals || '[]'); }
            catch (e) { allowed = []; }
            var value = getCardFieldValue(scope, dep.dataset.wsShowIfField);
            if (allowed.indexOf(String(value)) === -1) matches = false;
        }

        // Non-empty check (data-ws-show-if-not-empty-field).
        if (matches && dep.dataset.wsShowIfNotEmptyField) {
            if (!hasAnyNonEmpty(scope, dep.dataset.wsShowIfNotEmptyField)) matches = false;
        }

        dep.classList.toggle('is-disabled-by-condition', !matches);
    }
    function getCardFieldValue(scope, fieldName) {
        var suffix = '[' + fieldName + ']';
        var sel = scope.querySelector('select[name$="' + suffix + '"]');
        if (sel) return sel.value;
        var radio = scope.querySelector('input[type="radio"][name$="' + suffix + '"]:checked');
        if (radio) return radio.value;
        var txt = scope.querySelector('input[name$="' + suffix + '"]:not([type="radio"])');
        if (txt) return txt.value;
        return '';
    }
    // True if any input named `...[fieldName]` OR `...[fieldName][<lang>]`
    // inside `scope` carries a non-empty value. Used by `show_if_not_empty`.
    function hasAnyNonEmpty(scope, fieldName) {
        var direct = $$('input[name$="[' + fieldName + ']"]', scope);
        for (var i = 0; i < direct.length; i++) {
            if (direct[i].type === 'radio' && !direct[i].checked) continue;
            if (String(direct[i].value).trim() !== '') return true;
        }
        // Translatable image fields: `...[fieldName][lt]`, `...[fieldName][en]`.
        var langInputs = $$('input[name*="[' + fieldName + ']["]', scope);
        for (var j = 0; j < langInputs.length; j++) {
            if (String(langInputs[j].value).trim() !== '') return true;
        }
        return false;
    }

    /* ─── Collapsed-state persistence (per post, per section _id) ─── */

    function postId() {
        var el = document.getElementById('post_ID');
        return el && el.value ? el.value : '0';
    }
    function cardStorageKey(card) {
        var idInput = card.querySelector('input[name$="[_id]"]');
        if (!idInput || !idInput.value || idInput.value === '__ID__') return null;
        return 'workernu_collapsed:' + postId() + ':' + idInput.value;
    }
    function restoreCollapsedState(builder) {
        try {
            $$('[data-ws-card]', builder).forEach(function (card) {
                var key = cardStorageKey(card);
                if (key && localStorage.getItem(key) === '1') {
                    card.classList.add('is-collapsed');
                }
            });
        } catch (e) { /* localStorage may be unavailable; fail silently */ }
    }
    function writeCollapsedState(card) {
        try {
            var key = cardStorageKey(card);
            if (!key) return;
            if (card.classList.contains('is-collapsed')) {
                localStorage.setItem(key, '1');
            } else {
                localStorage.removeItem(key);
            }
        } catch (e) { /* same — silent fail */ }
    }
    function clearCollapsedState(card) {
        try {
            var key = cardStorageKey(card);
            if (key) localStorage.removeItem(key);
        } catch (e) { /* silent */ }
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
        applyLangTo(builder, builder.dataset.wsLang);
        applyAllConditional(builder);
        updateMoveStates(builder);
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

    /* ─── Card events (toggle, remove, move) ─── */

    function bindCardEvents(builder) {
        builder.addEventListener('click', function (e) {
            var toggle = e.target.closest('[data-ws-toggle]');
            if (toggle && toggle.closest('[data-ws-builder]')) {
                var tcard = toggle.closest('[data-ws-card]');
                tcard.classList.toggle('is-collapsed');
                writeCollapsedState(tcard);
                return;
            }

            var move = e.target.closest('[data-ws-move]');
            if (move) {
                var card = move.closest('[data-ws-card]');
                var dir  = move.dataset.wsMove;
                moveSibling(card, dir);
                reindex(builder);
                updateMoveStates(builder);
                return;
            }

            var dup = e.target.closest('[data-ws-duplicate]');
            if (dup && !e.target.closest('[data-ws-repeater-duplicate]')) {
                duplicateCard(builder, dup.closest('[data-ws-card]'));
                return;
            }

            var remove = e.target.closest('[data-ws-remove]');
            if (remove && !e.target.closest('[data-ws-repeater-remove]')) {
                if (!window.confirm('Remove this section?')) return;
                var rcard = remove.closest('[data-ws-card]');
                clearCollapsedState(rcard);
                rcard.remove();
                reindex(builder);
                updateMoveStates(builder);
                return;
            }
        });
    }

    function moveSibling(el, dir) {
        var parent = el.parentElement;
        if (!parent) return;
        if (dir === 'up' && el.previousElementSibling) {
            parent.insertBefore(el, el.previousElementSibling);
        } else if (dir === 'down' && el.nextElementSibling) {
            parent.insertBefore(el.nextElementSibling, el);
        }
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
            var idInput = scope.querySelector('[data-ws-image-id]');
            idInput.value = sel.id;
            // Setting `.value` doesn't fire change events natively — dispatch one
            // so `show_if_not_empty` dependents re-evaluate.
            idInput.dispatchEvent(new Event('input', { bubbles: true }));
            var preview = scope.querySelector('.ws-image__preview');
            preview.innerHTML = '<img src="' + url + '" alt="">';
            preview.hidden = false;
            scope.querySelector('[data-ws-image-clear]').hidden = false;
        });
        frame.open();
    }

    function clearMedia(scope) {
        var idInput = scope.querySelector('[data-ws-image-id]');
        idInput.value = '';
        idInput.dispatchEvent(new Event('input', { bubbles: true }));
        var preview = scope.querySelector('.ws-image__preview');
        preview.innerHTML = '';
        preview.hidden = true;
        scope.querySelector('[data-ws-image-clear]').hidden = true;
    }

    /* ─── Gallery picker (wp.media multiple) ─── */

    function bindGalleryPickers(builder) {
        builder.addEventListener('click', function (e) {
            var add = e.target.closest('[data-ws-gallery-add]');
            if (add) {
                openGallery(add.closest('[data-ws-gallery]'));
                return;
            }
            var remove = e.target.closest('[data-ws-gallery-remove]');
            if (remove) {
                remove.closest('[data-ws-gallery-item]').remove();
                reindexGallery(remove.closest('[data-ws-gallery]'));
                return;
            }
        });
    }

    function openGallery(scope) {
        if (typeof wp === 'undefined' || !wp.media) return;
        var frame = wp.media({
            title: 'Select images',
            multiple: true,
            library: { type: 'image' },
            button: { text: 'Add selected' }
        });
        frame.on('select', function () {
            var selection = frame.state().get('selection');
            var grid = scope.querySelector('[data-ws-gallery-grid]');
            selection.each(function (attachment) {
                var att = attachment.toJSON();
                var url = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
                var item = document.createElement('div');
                item.className = 'ws-gallery__thumb';
                item.setAttribute('data-ws-gallery-item', '');
                item.innerHTML =
                    '<img src="' + url + '" alt="">' +
                    '<button type="button" class="ws-gallery__remove" data-ws-gallery-remove aria-label="Remove">×</button>' +
                    '<input type="hidden" value="' + att.id + '">';
                grid.appendChild(item);
            });
            reindexGallery(scope);
        });
        frame.open();
    }

    function reindexGallery(scope) {
        var fieldName = scope.dataset.wsGalleryName;
        if (!fieldName) return;
        scope.querySelectorAll('[data-ws-gallery-item]').forEach(function (item, i) {
            var input = item.querySelector('input[type="hidden"]');
            if (input) input.name = fieldName + '[' + i + ']';
        });
    }

    /* ─── Repeater events (add/remove/move items) ─── */

    function bindRepeaterEvents(builder) {
        builder.addEventListener('click', function (e) {
            var add = e.target.closest('[data-ws-repeater-add]');
            if (add) {
                var repeater = add.closest('[data-ws-repeater]');
                if (!repeater) return;
                addRepeaterItem(repeater);
                applyLangTo(builder, builder.dataset.wsLang);
                updateMoveStates(builder);
                return;
            }

            var move = e.target.closest('[data-ws-repeater-move]');
            if (move) {
                var item = move.closest('[data-ws-repeater-item]');
                if (!item) return;
                var rep = item.closest('[data-ws-repeater]');
                moveSibling(item, move.dataset.wsRepeaterMove);
                if (rep) renumberRepeater(rep);
                updateMoveStates(builder);
                return;
            }

            var rdup = e.target.closest('[data-ws-repeater-duplicate]');
            if (rdup) {
                var ditem = rdup.closest('[data-ws-repeater-item]');
                var drep  = ditem.closest('[data-ws-repeater]');
                duplicateRepeaterItem(drep, ditem);
                applyLangTo(builder, builder.dataset.wsLang);
                updateMoveStates(builder);
                return;
            }

            var remove = e.target.closest('[data-ws-repeater-remove]');
            if (remove) {
                var ritem = remove.closest('[data-ws-repeater-item]');
                if (!ritem) return;
                var repx = ritem.closest('[data-ws-repeater]');
                ritem.remove();
                if (repx) renumberRepeater(repx);
                updateMoveStates(builder);
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

    /* ─── Duplicate (sections + repeater items) ─── */

    /**
     * cloneNode(true) preserves attributes but NOT live form values:
     *   - <input>.value, <input>.checked, <textarea>.value, <select>.selectedIndex
     *   reset to defaults on clone. Walk both trees and copy current state across.
     */
    function transferValues(source, clone) {
        var sel = 'input, textarea, select';
        var srcNodes = source.querySelectorAll(sel);
        var dstNodes = clone.querySelectorAll(sel);
        for (var i = 0; i < srcNodes.length; i++) {
            var s = srcNodes[i];
            var d = dstNodes[i];
            if (!d) continue;
            if (s.type === 'checkbox' || s.type === 'radio') {
                d.checked = s.checked;
            } else if (s.tagName === 'SELECT') {
                d.value = s.value;
            } else {
                d.value = s.value;
            }
        }
    }

    function duplicateCard(builder, source) {
        if (!source) return;
        var clone = source.cloneNode(true);
        transferValues(source, clone);

        // Fresh _id so collapsed-state localStorage doesn't carry over.
        var idInput = clone.querySelector('input[name$="[_id]"]');
        if (idInput) {
            var type = clone.dataset.wsType || 'section';
            idInput.value = type + '-' + Math.random().toString(36).slice(2, 10);
        }
        clone.classList.remove('is-collapsed');

        source.parentElement.insertBefore(clone, source.nextSibling);
        reindex(builder);
        updateMoveStates(builder);
        applyLangTo(builder, builder.dataset.wsLang);
        applyAllConditional(builder);
    }

    function duplicateRepeaterItem(repeater, source) {
        if (!repeater || !source) return;
        var clone = source.cloneNode(true);
        transferValues(source, clone);
        source.parentElement.insertBefore(clone, source.nextSibling);
        renumberRepeater(repeater);
    }

    /* ─── Reindex (top-level section cards) ─── */

    function reindex(builder) {
        $$('[data-ws-list] > [data-ws-card]', builder).forEach(function (card, idx) {
            var prefix = 'workernu_sections[' + idx + ']';
            var re = /^workernu_sections\[[^\]]+\]/;
            $$('[name^="workernu_sections["]', card).forEach(function (input) {
                input.name = input.name.replace(re, prefix);
            });
            // Keep gallery field-name data attributes in sync with the card index
            // so reindexGallery() always has a correct base name to work from.
            $$('[data-ws-gallery-name]', card).forEach(function (el) {
                el.dataset.wsGalleryName = el.dataset.wsGalleryName.replace(re, prefix);
            });
        });
    }

    /* ─── Move-button enabled/disabled state ─── */

    function updateMoveStates(builder) {
        // Section cards
        $$('[data-ws-list]', builder).forEach(function (list) {
            var cards = $$(':scope > [data-ws-card]', list);
            cards.forEach(function (card, i) {
                var up   = card.querySelector(':scope > .ws-card__header [data-ws-move="up"]');
                var down = card.querySelector(':scope > .ws-card__header [data-ws-move="down"]');
                if (up)   up.disabled   = (i === 0);
                if (down) down.disabled = (i === cards.length - 1);
            });
        });
        // Repeater items
        $$('[data-ws-repeater-list]', builder).forEach(function (list) {
            var items = $$(':scope > [data-ws-repeater-item]', list);
            items.forEach(function (item, i) {
                var up   = item.querySelector('[data-ws-repeater-move="up"]');
                var down = item.querySelector('[data-ws-repeater-move="down"]');
                if (up)   up.disabled   = (i === 0);
                if (down) down.disabled = (i === items.length - 1);
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

    /* ─── Copy / paste sections via clipboard
     *
     * Copy: walk the card's form inputs, serialise to JSON shaped
     *   { kind: 'workernu-section', version: 1, type: '<slug>', data: { _type, ...fields } }
     * and write to the system clipboard.
     *
     * Paste: read clipboard, validate shape, addCard(type) to insert an empty
     * card template, then populate inputs by mapping bracket-path names to
     * positions in the data tree. Repeater items are added programmatically
     * before their inputs are populated.
     */
    var CLIPBOARD_KIND    = 'workernu-section';
    var CLIPBOARD_VERSION = 1;

    function bindCopyPaste(builder) {
        builder.addEventListener('click', function (e) {
            var copyBtn = e.target.closest('[data-ws-copy]');
            if (copyBtn) {
                var card = copyBtn.closest('[data-ws-card]');
                if (card) copyCardToClipboard(card, copyBtn);
                return;
            }
            var pasteBtn = e.target.closest('[data-ws-paste]');
            if (pasteBtn) {
                pasteFromClipboard(builder, pasteBtn);
                return;
            }
        });
    }

    function clipboardSupported(direction) {
        return navigator.clipboard && typeof navigator.clipboard[direction] === 'function';
    }

    function copyCardToClipboard(card, btn) {
        if (!clipboardSupported('writeText')) {
            window.alert('Clipboard not available — needs HTTPS or a modern browser.');
            return;
        }
        var type = card.dataset.wsType || '';
        if (!type) { window.alert('Could not detect section type to copy.'); return; }
        var data = extractCardData(card);
        // Drop the section's stable id — each paste creates a fresh one.
        delete data._id;
        var payload = { kind: CLIPBOARD_KIND, version: CLIPBOARD_VERSION, type: type, data: data };
        navigator.clipboard.writeText(JSON.stringify(payload)).then(
            function () { flashButtonOK(btn); },
            function (err) { window.alert('Copy failed: ' + (err && err.message)); }
        );
    }

    function pasteFromClipboard(builder, btn) {
        if (!clipboardSupported('readText')) {
            window.alert('Clipboard not available — needs HTTPS or a modern browser.');
            return;
        }
        navigator.clipboard.readText().then(function (text) {
            var payload;
            try { payload = JSON.parse(text); }
            catch (err) { window.alert('Clipboard contents are not valid section JSON.'); return; }
            if (!payload || payload.kind !== CLIPBOARD_KIND || !payload.type) {
                window.alert('Clipboard contents are not a copied workernu section.');
                return;
            }
            var tmpl = builder.querySelector('[data-ws-template="' + payload.type + '"]');
            if (!tmpl) {
                window.alert('Unknown section type "' + payload.type + '" — make sure that section exists on this site.');
                return;
            }
            // Insert an empty card of the right type, then fill it in.
            addCard(builder, payload.type);
            var list = $('[data-ws-list]', builder);
            var newCard = list && list.lastElementChild;
            if (!newCard) { window.alert('Could not insert the pasted card.'); return; }

            populateCardData(newCard, payload.data || {});

            // Refresh derived state for the populated card.
            reindex(builder);
            applyLangTo(builder, builder.dataset.wsLang);
            applyAllConditional(builder);
            updateMoveStates(builder);
            flashButtonOK(btn);
        }, function (err) {
            window.alert('Paste failed: ' + (err && err.message));
        });
    }

    /* ─── Data extraction / population ─── */

    // Pull the bracket prefix off the first input so the rest of the path can
    // be mapped against the in-memory data tree. Card index varies and must
    // not leak into the serialised data.
    function cardInputPrefix(card) {
        var first = card.querySelector('[name^="workernu_sections["]');
        if (!first) return null;
        var m = first.name.match(/^(workernu_sections\[[^\]]+\])/);
        return m ? m[1] : null;
    }

    function extractCardData(card) {
        var prefix = cardInputPrefix(card);
        if (!prefix) return {};
        var data = {};
        $$('[name^="workernu_sections["]', card).forEach(function (input) {
            if (input.disabled) return;
            if (input.type === 'submit' || input.type === 'button') return;
            var name = input.name;
            if (name.indexOf(prefix) !== 0) return;
            var rest = name.slice(prefix.length);
            var path = parseBracketPath(rest);
            if (!path.length) return;

            if (input.type === 'checkbox') {
                // Always record checkbox state so paste can explicitly uncheck
                // fields whose template default is checked.
                setByPath(data, path, input.checked ? (input.value === '' ? '1' : input.value) : '0');
            } else if (input.type === 'radio') {
                if (!input.checked) return;
                setByPath(data, path, input.value);
            } else {
                setByPath(data, path, input.value);
            }
        });
        return data;
    }

    function populateCardData(card, data) {
        // Phase 1: repeater items must exist before their inputs can be set.
        // For each repeater in the new card, click "Add item" until the count
        // matches the data array.
        $$('[data-ws-repeater]', card).forEach(function (repeater) {
            var fieldName = repeater.dataset.wsRepeater;
            if (!fieldName) return;
            var itemsData = data[fieldName];
            if (!Array.isArray(itemsData)) return;
            var listEl = repeater.querySelector('[data-ws-repeater-list]');
            if (!listEl) return;
            var existing = listEl.children.length;
            for (var i = existing; i < itemsData.length; i++) addRepeaterItem(repeater);
        });

        // Phase 1b: gallery fields — build thumb elements so their hidden inputs
        // exist before Phase 2 tries to set values.
        var prefix = cardInputPrefix(card);
        $$('[data-ws-gallery]', card).forEach(function (gallery) {
            if (!prefix) return;
            var galName = gallery.dataset.wsGalleryName;
            if (!galName) return;
            var galPath = parseBracketPath(galName.slice(prefix.length));
            if (!galPath.length) return;
            var idsData = getByPath(data, galPath);
            if (!Array.isArray(idsData) || !idsData.length) return;
            var grid = gallery.querySelector('[data-ws-gallery-grid]');
            if (!grid) return;
            idsData.forEach(function (rawId, i) {
                var id = parseInt(rawId, 10) || 0;
                if (!id) return;
                var item = document.createElement('div');
                item.className = 'ws-gallery__thumb';
                item.setAttribute('data-ws-gallery-item', '');
                item.innerHTML =
                    '<img src="" alt="">' +
                    '<button type="button" class="ws-gallery__remove" data-ws-gallery-remove aria-label="Remove">×</button>' +
                    '<input type="hidden" name="' + galName + '[' + i + ']" value="' + id + '">';
                grid.appendChild(item);
                // Async: fetch thumbnail URL from WordPress media attachment cache.
                if (typeof wp !== 'undefined' && wp.media && wp.media.attachment) {
                    (function (el, attId) {
                        try {
                            wp.media.attachment(attId).fetch().done(function () {
                                var sizes = this.get('sizes');
                                var url = (sizes && sizes.thumbnail) ? sizes.thumbnail.url : this.get('url');
                                var img = el.querySelector('img');
                                if (img && url) img.src = url;
                            });
                        } catch (e) {}
                    })(item, id);
                }
            });
        });

        // Phase 1c: re-normalise ALL input names to the current card prefix.
        // addRepeaterItem clones from the section <template> whose input names
        // still carry the original UID string (e.g. "workernu_sections[hero-x1y2]").
        // reindex() can't touch <template> content, so newly-added items keep the
        // stale prefix until here. Without this step Phase 2's prefix guard would
        // skip every repeater item that was just created, leaving them blank.
        if (prefix) {
            var normRe = /^workernu_sections\[[^\]]+\]/;
            $$('[name^="workernu_sections["]', card).forEach(function (inp) {
                inp.name = inp.name.replace(normRe, prefix);
            });
        }

        // Phase 2: walk inputs, map each to a path in the data tree.
        if (!prefix) return;
        $$('[name^="workernu_sections["]', card).forEach(function (input) {
            if (input.disabled) return;
            var name = input.name;
            if (name.indexOf(prefix) !== 0) return;
            var rest = name.slice(prefix.length);
            var path = parseBracketPath(rest);
            if (!path.length) return;
            var value = getByPath(data, path);
            if (value === undefined) return;
            applyInputValue(input, value);
        });
    }

    function parseBracketPath(str) {
        // '[heading]'             → ['heading']
        // '[items][0][title][lt]' → ['items','0','title','lt']
        var path = [];
        var re = /\[([^\]]*)\]/g;
        var m;
        while ((m = re.exec(str))) path.push(m[1]);
        return path;
    }

    function setByPath(obj, path, value) {
        var current = obj;
        for (var i = 0; i < path.length; i++) {
            var key = path[i];
            if (i === path.length - 1) {
                // Coerce numeric key on a leaf when the parent is an array.
                if (Array.isArray(current) && /^\d+$/.test(key)) current[parseInt(key, 10)] = value;
                else current[key] = value;
            } else {
                var nextKey = path[i + 1];
                var nextIsNumeric = /^\d+$/.test(nextKey);
                if (current[key] === undefined || current[key] === null) {
                    current[key] = nextIsNumeric ? [] : {};
                }
                current = current[key];
            }
        }
    }

    function getByPath(obj, path) {
        var current = obj;
        for (var i = 0; i < path.length; i++) {
            if (current == null) return undefined;
            var key = path[i];
            if (Array.isArray(current) && /^\d+$/.test(key)) current = current[parseInt(key, 10)];
            else current = current[key];
        }
        return current;
    }

    function applyInputValue(input, value) {
        if (input.type === 'checkbox') {
            // Checkbox in the saved data is a string (the input's value, e.g. "1");
            // treat any non-empty/non-"0"/non-"false" as checked.
            var v = value == null ? '' : String(value);
            input.checked = v !== '' && v !== '0' && v !== 'false';
        } else if (input.type === 'radio') {
            input.checked = String(input.value) === String(value);
        } else if (input.tagName === 'SELECT') {
            input.value = value == null ? '' : String(value);
        } else if (input.hasAttribute('data-ws-image-id')) {
            var idStr = value == null ? '' : String(value);
            input.value = idStr;
            var scope = input.closest('[data-ws-image]');
            if (scope) updateImagePreviewFromId(scope, parseInt(idStr, 10) || 0);
            // Trigger show_if_not_empty re-evaluation.
            input.dispatchEvent(new Event('input', { bubbles: true }));
        } else {
            input.value = value == null ? '' : String(value);
        }
    }

    function updateImagePreviewFromId(scope, id) {
        var preview = scope.querySelector('.ws-image__preview');
        var clearBtn = scope.querySelector('[data-ws-image-clear]');
        if (!preview) return;
        if (id <= 0) {
            preview.innerHTML = '';
            preview.hidden = true;
            if (clearBtn) clearBtn.hidden = true;
            return;
        }
        // Optimistically show the preview slot; fill the URL once wp.media
        // resolves the attachment. If the attachment doesn't exist (e.g.
        // pasted across sites), the preview stays empty but the ID is still
        // set so the user can re-pick a replacement.
        preview.innerHTML = '';
        preview.hidden = false;
        if (clearBtn) clearBtn.hidden = false;
        if (typeof wp !== 'undefined' && wp.media && wp.media.attachment) {
            try {
                var att = wp.media.attachment(id);
                att.fetch().done(function () {
                    var sizes = att.get('sizes');
                    var url = (sizes && sizes.medium && sizes.medium.url) || att.get('url');
                    if (url) preview.innerHTML = '<img src="' + url + '" alt="">';
                });
            } catch (e) { /* fail silently — id is saved either way */ }
        }
    }

    function flashButtonOK(btn) {
        if (!btn) return;
        btn.classList.add('is-success');
        setTimeout(function () { btn.classList.remove('is-success'); }, 1000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
