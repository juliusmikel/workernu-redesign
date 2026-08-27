<?php
/**
 * Contact Form section.
 */

$heading         = workernu_t($data['heading']          ?? '');
$subheading      = workernu_t($data['subheading']       ?? '');
$contacts        = is_array($data['contacts'] ?? null) ? $data['contacts'] : [];
$response_time   = workernu_t($data['response_time']    ?? '');
$notify_email    = sanitize_email($data['notify_email'] ?? '');
if ($notify_email === '') $notify_email = get_option('admin_email', '');
$privacy_label   = workernu_t($data['privacy_label']    ?? __('Sutinku su WorkerNU privatumo politika', 'workernu'));
$submit_label    = workernu_t($data['submit_label']     ?? __('SIŲSTI', 'workernu'));
$success_msg     = workernu_t($data['success_message']  ?? __('Ačiū! Jūsų žinutė išsiųsta.', 'workernu'));

// Reason-for-message select — options come from the editor, one per line.
$reason_options = array_values(array_filter(array_map('trim',
    preg_split('/\r?\n/', workernu_t($data['reason_options'] ?? '')) ?: []
)));

// reCAPTCHA v3 (invisible) — no widget; the submit handler fetches a token in
// the background and sends it with the form. Active only when the site key is
// set. The secret stays server-side: the AJAX handler re-reads it from this
// page's section meta (via wn_post_id below), it is never printed into the page.
$recaptcha_site_key = trim((string) ($data['recaptcha_site_key'] ?? ''));

$classes = workernu_section_classes($data, 'contact-form');

$nonce    = wp_create_nonce('workernu_contact');
$ajax_url = esc_url(admin_url('admin-ajax.php'));

wp_enqueue_style('intl-tel-input', 'https://cdn.jsdelivr.net/npm/intl-tel-input@24.7.0/build/css/intlTelInput.css', [], '24.7.0');
wp_enqueue_script('intl-tel-input', 'https://cdn.jsdelivr.net/npm/intl-tel-input@24.7.0/build/js/intlTelInputWithUtils.js', [], '24.7.0', true);
if ($recaptcha_site_key !== '') {
    wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . rawurlencode($recaptcha_site_key), [], null, true);
}
?>
<section class="<?php echo esc_attr($classes); ?>">
    <div class="section--contact-form__inner container">

        <!-- ── Left: contact info ── -->
        <div class="section--contact-form__left">

            <?php if ($heading !== ''): ?>
                <h2 class="section--contact-form__heading"><?php echo nl2br(wp_kses_post($heading)); ?></h2>
            <?php endif; ?>

            <?php if ($subheading !== ''): ?>
                <p class="section--contact-form__sub"><?php echo wp_kses_post($subheading); ?></p>
            <?php endif; ?>

            <?php if ($contacts): ?>
                <ul class="section--contact-form__contacts">
                    <?php foreach ($contacts as $contact):
                        $clabel = workernu_t($contact['label']     ?? '');
                        $cvalue = workernu_t($contact['value']     ?? '');
                        $curl   = (string) ($contact['value_url'] ?? '');
                        $cnote  = workernu_t($contact['note']      ?? '');
                        if ($cvalue === '') continue;
                    ?>
                        <li class="section--contact-form__contact">
                            <?php if ($clabel !== ''): ?>
                                <span class="section--contact-form__contact-label"><?php echo wp_kses_post($clabel); ?></span>
                            <?php endif; ?>
                            <?php if ($curl !== ''): ?>
                                <a class="section--contact-form__contact-value" href="<?php echo esc_url($curl); ?>"><?php echo wp_kses_post($cvalue); ?></a>
                            <?php else: ?>
                                <strong class="section--contact-form__contact-value"><?php echo wp_kses_post($cvalue); ?></strong>
                            <?php endif; ?>
                            <?php if ($cnote !== ''): ?>
                                <span class="section--contact-form__contact-note"><?php echo wp_kses_post($cnote); ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($response_time !== ''): ?>
                <p class="section--contact-form__response">
                    <span class="section--contact-form__dot" aria-hidden="true"></span>
                    <?php echo wp_kses_post($response_time); ?>
                </p>
            <?php endif; ?>

        </div>

        <!-- ── Right: form ── -->
        <div class="section--contact-form__right">
            <form
                class="section--contact-form__form"
                data-ajax="<?php echo $ajax_url; ?>"
                data-nonce="<?php echo esc_attr($nonce); ?>"
                data-notify="<?php echo esc_attr($notify_email); ?>"
                data-success="<?php echo esc_attr($success_msg); ?>"
                data-recaptcha="<?php echo esc_attr($recaptcha_site_key); ?>"
                novalidate
            >
                <!-- Name -->
                <div class="section--contact-form__field">
                    <label class="section--contact-form__label" for="wn-name">
                        <?php esc_html_e('Vardas ir pavardė', 'workernu'); ?>
                    </label>
                    <input
                        class="section--contact-form__input"
                        type="text"
                        id="wn-name"
                        name="wn_name"
                        placeholder="<?php esc_attr_e('Vardenis Pavardenis', 'workernu'); ?>"
                        autocomplete="name"
                        required
                    >
                </div>

                <!-- Phone + Email -->
                <div class="section--contact-form__row">
                    <div class="section--contact-form__field">
                        <label class="section--contact-form__label" for="wn-phone">
                            <?php esc_html_e('Telefonas', 'workernu'); ?>
                        </label>
                        <input
                            class="section--contact-form__input"
                            type="tel"
                            id="wn-phone"
                            name="wn_phone"
                            placeholder="<?php esc_attr_e('XXX XX XXX', 'workernu'); ?>"
                            autocomplete="tel"
                        >
                    </div>
                    <div class="section--contact-form__field">
                        <label class="section--contact-form__label" for="wn-email">
                            <?php esc_html_e('El. paštas', 'workernu'); ?>
                        </label>
                        <div class="section--contact-form__prefixed">
                            <span class="section--contact-form__prefix" aria-hidden="true">@</span>
                            <input
                                class="section--contact-form__input"
                                type="email"
                                id="wn-email"
                                name="wn_email"
                                placeholder="<?php esc_attr_e('vardas@imone.lt', 'workernu'); ?>"
                                autocomplete="email"
                                required
                            >
                        </div>
                    </div>
                </div>

                <!-- Company + Team size -->
                <div class="section--contact-form__row">
                    <div class="section--contact-form__field">
                        <label class="section--contact-form__label" for="wn-company">
                            <?php esc_html_e('Įmonės pavadinimas', 'workernu'); ?>
                        </label>
                        <input
                            class="section--contact-form__input"
                            type="text"
                            id="wn-company"
                            name="wn_company"
                            placeholder="<?php esc_attr_e('UAB Mano Statyba', 'workernu'); ?>"
                            autocomplete="organization"
                        >
                    </div>
                    <div class="section--contact-form__field">
                        <label class="section--contact-form__label" for="wn-size">
                            <?php esc_html_e('Komandos dydis', 'workernu'); ?>
                        </label>
                        <div class="section--contact-form__select-wrap">
                            <select class="section--contact-form__input section--contact-form__select" id="wn-size" name="wn_size">
                                <option value=""><?php esc_html_e('Pasirinkti', 'workernu'); ?></option>
                                <option value="1–5">1–5</option>
                                <option value="6–20">6–20</option>
                                <option value="21–50">21–50</option>
                                <option value="51–200">51–200</option>
                                <option value="200+">200+</option>
                            </select>
                            <i class="fa-solid fa-chevron-down section--contact-form__select-chevron" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                <?php if ($reason_options): ?>
                    <!-- Reason for message -->
                    <div class="section--contact-form__field">
                        <label class="section--contact-form__label" for="wn-reason">
                            <?php esc_html_e('Kreipimosi priežastis', 'workernu'); ?>
                        </label>
                        <div class="section--contact-form__select-wrap">
                            <select class="section--contact-form__input section--contact-form__select" id="wn-reason" name="wn_reason">
                                <option value=""><?php esc_html_e('Pasirinkti', 'workernu'); ?></option>
                                <?php foreach ($reason_options as $reason): ?>
                                    <option value="<?php echo esc_attr($reason); ?>"><?php echo esc_html($reason); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fa-solid fa-chevron-down section--contact-form__select-chevron" aria-hidden="true"></i>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Message -->
                <div class="section--contact-form__field">
                    <label class="section--contact-form__label" for="wn-message">
                        <?php esc_html_e('Žinutė', 'workernu'); ?>
                    </label>
                    <textarea
                        class="section--contact-form__input section--contact-form__textarea"
                        id="wn-message"
                        name="wn_message"
                        placeholder="<?php esc_attr_e('Jūsų žinutė mums', 'workernu'); ?>"
                        rows="4"
                        required
                    ></textarea>
                </div>

                <input type="hidden" name="wn_post_id" value="<?php echo esc_attr((string) get_the_ID()); ?>">
                <input type="hidden" name="wn_lang" value="<?php echo esc_attr(workernu_lang()); ?>">

                <!-- Consent -->
                <label class="section--contact-form__consent">
                    <input class="section--contact-form__checkbox" type="checkbox" name="wn_consent" required>
                    <span class="section--contact-form__consent-text"><?php echo wp_kses_post($privacy_label); ?></span>
                </label>

                <?php if ($recaptcha_site_key !== ''): ?>
                    <!-- Google requires this attribution when the reCAPTCHA badge
                         is hidden (style.css hides .grecaptcha-badge). -->
                    <p class="section--contact-form__recaptcha-note">
                        <?php
                        printf(
                            /* translators: 1: privacy policy link, 2: terms link */
                            esc_html__('Šią svetainę saugo reCAPTCHA. Taikoma Google %1$s ir %2$s.', 'workernu'),
                            '<a href="https://policies.google.com/privacy" target="_blank" rel="noopener">' . esc_html__('privatumo politika', 'workernu') . '</a>',
                            '<a href="https://policies.google.com/terms" target="_blank" rel="noopener">' . esc_html__('paslaugų teikimo sąlygos', 'workernu') . '</a>'
                        );
                        ?>
                    </p>
                <?php endif; ?>

                <!-- Status message -->
                <div class="section--contact-form__status" role="alert" hidden></div>

                <!-- Submit -->
                <button class="section--contact-form__submit" type="submit">
                    <?php echo wp_kses_post($submit_label); ?>
                </button>

            </form>
        </div>

    </div>
</section>

<script>
(function () {
    var section = document.currentScript.previousElementSibling;
    var form    = section.querySelector('.section--contact-form__form');
    if (!form) return;

    /* ── intl-tel-input ── */
    var phoneInput = form.querySelector('#wn-phone');
    var iti = null;
    function initIti() {
        if (!window.intlTelInput || !phoneInput) return;
        iti = window.intlTelInput(phoneInput, {
            initialCountry: 'lt',
            preferredCountries: ['lt', 'lv', 'ee', 'pl', 'de', 'gb'],
            separateDialCode: true,
            countrySearch: false,
        });
    }
    if (window.intlTelInput) {
        initIti();
    } else {
        document.getElementById('intl-tel-input-js') && document.getElementById('intl-tel-input-js').addEventListener('load', initIti);
        window.addEventListener('load', initIti);
    }

    /* ── Prevent consent links from toggling the checkbox ── */
    form.querySelectorAll('.section--contact-form__consent a').forEach(function (a) {
        a.addEventListener('click', function (e) { e.stopPropagation(); });
    });

    /* ── Form submission ── */
    var status = form.querySelector('.section--contact-form__status');
    var btn    = form.querySelector('.section--contact-form__submit');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        btn.disabled = true;
        btn.classList.add('is-loading');
        status.hidden    = true;
        status.className = 'section--contact-form__status';

        /* Write full international number back into the input before serialising */
        if (iti && phoneInput.value.trim() !== '') {
            phoneInput.value = iti.getNumber();
        }

        var body = new FormData(form);
        body.append('action',       'workernu_contact');
        body.append('nonce',        form.dataset.nonce);
        body.append('notify_email', form.dataset.notify);

        function send() {
            fetch(form.dataset.ajax, { method: 'POST', body: body })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        status.classList.add('is-success');
                        status.textContent = form.dataset.success;
                        form.reset();
                    } else {
                        status.classList.add('is-error');
                        status.textContent = res.data && res.data.message
                            ? res.data.message
                            : '<?php echo esc_js(__('Klaida. Bandykite dar kartą.', 'workernu')); ?>';
                    }
                    status.hidden = false;
                })
                .catch(function () {
                    status.classList.add('is-error');
                    status.textContent = '<?php echo esc_js(__('Klaida. Bandykite dar kartą.', 'workernu')); ?>';
                    status.hidden = false;
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.classList.remove('is-loading');
                });
        }

        /* reCAPTCHA v3 (invisible): fetch a fresh token in the background and
           attach it before sending. Every execute() issues a new token, so no
           reset step is needed between submissions. If the key is configured
           but the Google script failed to load, submit anyway — the server
           will reject it, which beats silently swallowing the click. */
        var rcKey = form.dataset.recaptcha;
        if (rcKey && window.grecaptcha && grecaptcha.execute) {
            grecaptcha.ready(function () {
                grecaptcha.execute(rcKey, { action: 'contact' }).then(function (token) {
                    body.append('g-recaptcha-response', token);
                    send();
                }, send);
            });
        } else {
            send();
        }
    });
}());
</script>
