<?php
if (!defined('ABSPATH')) exit;

add_action('after_setup_theme', function () {
    // Hardcoded template strings translate via /languages/workernu-<locale>.mo.
    // The locale itself follows the URL language — see workernu-lang, which
    // maps /en/… requests to en_US and everything else to lt_LT.
    load_theme_textdomain('workernu', get_template_directory() . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);

    // Header logo — editable under Appearance → Customize → Site Identity.
    add_theme_support('custom-logo', [
        'height'      => 40,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
        'unlink-homepage-logo' => false,
    ]);

    register_nav_menus([
        'primary'      => __('Primary Navigation', 'workernu'),
        'footer_col_1' => __('Footer Column 1', 'workernu'),
        'footer_col_2' => __('Footer Column 2', 'workernu'),
        'footer_legal' => __('Footer Legal Links', 'workernu'),
    ]);
});

add_action('wp_enqueue_scripts', function () {
    $theme_uri  = get_template_directory_uri();
    $theme_path = get_template_directory();
    $version    = wp_get_theme()->get('Version');

    // Font Awesome — global so any section can use icons.
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
        [],
        '6.7.2'
    );

    wp_enqueue_style(
        'workernu-main',
        $theme_uri . '/assets/css/main.css',
        [],
        file_exists($theme_path . '/assets/css/main.css') ? filemtime($theme_path . '/assets/css/main.css') : $version
    );

    wp_enqueue_script(
        'workernu-main',
        $theme_uri . '/assets/js/main.js',
        [],
        file_exists($theme_path . '/assets/js/main.js') ? filemtime($theme_path . '/assets/js/main.js') : $version,
        true
    );

    if (is_singular() && function_exists('workernu_enqueue_section_assets')) {
        workernu_enqueue_section_assets(get_queried_object_id());
    }
});

/**
 * Register the page-theme palettes. Each entry shows up in the Page Theme
 * meta box as a swatch + label, and applies via the body class `theme--<slug>`.
 * The CSS for each lives in assets/css/main.css.
 */
add_filter('workernu_themes', function (array $themes): array {
    return array_merge($themes, [
        'default'  => ['label' => __('Light',  'workernu'), 'swatch' => '#f0f0f0'],
        'midnight' => ['label' => __('Midnight', 'workernu'), 'swatch' => '#0a0a0b'],
    ]);
});

/**
 * Auto-tag the primary menu's last + penultimate TOP-LEVEL items with `is-cta`
 * and `is-login` respectively. Position-based, so it survives every deploy and
 * works the same in any language — no need to set CSS Classes in the menu
 * editor. Only top-level items count (sub-menu children are ignored).
 */
/**
 * Contact form AJAX handler.
 */
add_action('wp_ajax_workernu_contact',        'workernu_handle_contact_form');
add_action('wp_ajax_nopriv_workernu_contact', 'workernu_handle_contact_form');

function workernu_handle_contact_form(): void {
    // admin-ajax.php carries no /en URL prefix, so the locale filter in
    // workernu-lang can't see the visitor's language — the form sends it
    // explicitly and we switch so response messages match the page.
    if (($_POST['wn_lang'] ?? '') === 'en') {
        switch_to_locale('en_GB'); // en_GB, not en_US — see workernu-lang frontend_locale()
    }

    if (!check_ajax_referer('workernu_contact', 'nonce', false)) {
        wp_send_json_error(['message' => __('Sesija pasibaigė. Atnaujinkite puslapį.', 'workernu')]);
    }

    $name    = sanitize_text_field($_POST['wn_name']    ?? '');
    $phone   = sanitize_text_field($_POST['wn_phone']   ?? '');
    $email   = sanitize_email($_POST['wn_email']        ?? '');
    $company = sanitize_text_field($_POST['wn_company'] ?? '');
    $size    = sanitize_text_field($_POST['wn_size']    ?? '');
    $reason  = sanitize_text_field($_POST['wn_reason']  ?? '');
    $message = sanitize_textarea_field($_POST['wn_message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        wp_send_json_error(['message' => __('Prašome užpildyti vardą, el. paštą ir žinutę.', 'workernu')]);
    }
    if (!is_email($email)) {
        wp_send_json_error(['message' => __('Neteisingas el. pašto adresas.', 'workernu')]);
    }

    // reCAPTCHA — the secret never reaches the browser, so re-read it from the
    // submitting page's contact-form section meta. Verification only runs when
    // a secret is configured there; without one the captcha is simply off.
    $post_id = (int) ($_POST['wn_post_id'] ?? 0);
    $secret  = '';
    if ($post_id) {
        $sections = get_post_meta($post_id, '_page_sections', true);
        foreach (is_array($sections) ? $sections : [] as $s) {
            if (is_array($s) && ($s['_type'] ?? '') === 'contact-form' && trim((string) ($s['recaptcha_secret_key'] ?? '')) !== '') {
                $secret = trim((string) $s['recaptcha_secret_key']);
                break;
            }
        }
    }
    if ($secret !== '') {
        $token = (string) ($_POST['g-recaptcha-response'] ?? '');
        if ($token === '') {
            wp_send_json_error(['message' => __('Roboto patikra nepavyko. Atnaujinkite puslapį ir bandykite dar kartą.', 'workernu')]);
        }
        $verify = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'timeout' => 10,
            'body'    => ['secret' => $secret, 'response' => $token],
        ]);
        $result = is_wp_error($verify) ? null : json_decode(wp_remote_retrieve_body($verify), true);
        // v3 is score-based (0.0 bot … 1.0 human) — 0.5 is Google's suggested
        // default threshold. Also pin the action to 'contact' so a token
        // harvested from some other v3-protected form can't be replayed here.
        $score_ok  = !isset($result['score']) || (float) $result['score'] >= 0.5;
        $action_ok = !isset($result['action']) || $result['action'] === 'contact';
        if (empty($result['success']) || !$score_ok || !$action_ok) {
            wp_send_json_error(['message' => __('Roboto patikra nepavyko. Bandykite dar kartą.', 'workernu')]);
        }
    }

    $to = sanitize_email($_POST['notify_email'] ?? '');
    if ($to === '') $to = get_option('admin_email', '');

    $subject = sprintf(__('Nauja žinutė nuo %s', 'workernu'), $name);
    $body    = implode("\n", array_filter([
        'Vardas: '          . $name,
        'El. paštas: '      . $email,
        $phone   !== '' ? 'Telefonas: '           . $phone   : '',
        $company !== '' ? 'Įmonė: '               . $company : '',
        $size    !== '' ? 'Komandos dydis: '      . $size    : '',
        $reason  !== '' ? 'Kreipimosi priežastis: ' . $reason : '',
        '',
        'Žinutė:',
        $message,
    ]));

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
    ];

    $sent = wp_mail($to, $subject, $body, $headers);

    if ($sent) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => __('Nepavyko išsiųsti. Bandykite vėliau.', 'workernu')]);
    }
}

add_action('customize_register', function (WP_Customize_Manager $wp_customize): void {
    $wp_customize->add_section('workernu_footer', [
        'title'    => __('Footer', 'workernu'),
        'priority' => 130,
    ]);

    $settings = [
        ['footer_description',      'text',  'Atraskite protingesnį darbuotojų valdymą su WorkerNu.'],
        ['footer_col_1_label',      'text',  'Produktas'],
        ['footer_col_2_label',      'text',  'Įmonė'],
        ['footer_col_3_label',      'text',  'Kontaktai'],
        ['footer_contact_email',    'text',  ''],
        ['footer_contact_phone',    'text',  ''],
        ['footer_contact_address',  'text',  ''],
        ['footer_social_linkedin',  'url',   ''],
        ['footer_social_facebook',  'url',   ''],
        ['footer_social_instagram', 'url',   ''],
        ['footer_copyright',        'text',  '© ' . date('Y') . ' WorkerNu. Visos teisės saugomos.'],
    ];

    foreach ($settings as [$id, $type, $default]) {
        $wp_customize->add_setting($id, ['default' => $default, 'sanitize_callback' => $type === 'url' ? 'esc_url_raw' : 'sanitize_text_field']);
        $wp_customize->add_control($id, [
            'label'   => str_replace('_', ' ', ucwords($id, '_')),
            'section' => 'workernu_footer',
            'type'    => $type === 'url' ? 'url' : 'text',
        ]);
    }
});

add_filter('wp_nav_menu_objects', function (array $items, $args): array {
    if (!isset($args->theme_location) || $args->theme_location !== 'primary') return $items;

    $top_level = array_values(array_filter($items, function ($i) {
        return (int) ($i->menu_item_parent ?? 0) === 0;
    }));
    $count = count($top_level);
    if ($count === 0) return $items;

    // Last item → CTA (rightmost, blue full-height rectangle in the design)
    $last = $top_level[$count - 1];
    $last->classes = array_merge((array) ($last->classes ?? []), ['is-cta']);

    // Penultimate → login (stays black, no chrome)
    if ($count >= 2) {
        $penult = $top_level[$count - 2];
        $penult->classes = array_merge((array) ($penult->classes ?? []), ['is-login']);
    }

    return $items;
}, 10, 2);
