/* workernu-seo — SEO meta box image picker */
(function () {
    'use strict';

    document.addEventListener('click', function (e) {
        var pick = e.target.closest('[data-seo-image-pick]');
        if (pick) {
            openMedia(pick.closest('[data-seo-image]'));
            return;
        }
        var clear = e.target.closest('[data-seo-image-clear]');
        if (clear) {
            clearMedia(clear.closest('[data-seo-image]'));
            return;
        }
    });

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
            scope.querySelector('[data-seo-image-id]').value = sel.id;
            var preview = scope.querySelector('.seo-mb__image-preview');
            preview.innerHTML = '<img src="' + url + '" alt="">';
            preview.hidden = false;
            scope.querySelector('[data-seo-image-clear]').hidden = false;
        });
        frame.open();
    }

    function clearMedia(scope) {
        scope.querySelector('[data-seo-image-id]').value = '';
        var preview = scope.querySelector('.seo-mb__image-preview');
        preview.innerHTML = '';
        preview.hidden = true;
        scope.querySelector('[data-seo-image-clear]').hidden = true;
    }
}());
