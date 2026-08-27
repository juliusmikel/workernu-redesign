<?php
/**
 * Trustpilot — site-wide TrustBox account settings.
 *
 * Stored as individual `wp_options` entries (one per field) under the keys
 * declared in TRUSTPILOT_FIELDS. The public `get()` helper returns the whole
 * set as an associative array for use in section templates.
 */
namespace WorkerNu\Settings\Trustpilot;

if (!defined('ABSPATH')) exit;

const OPTION_GROUP = 'workernu_trustpilot';

const TRUSTPILOT_FIELDS = [
    'businessunit_id' => [
        'option' => 'workernu_tp_businessunit_id',
        'label'  => 'Business unit ID',
        'type'   => 'string',
        'sanitize' => 'sanitize_text_field',
        'default' => '',
        'description' => 'From your TrustBox embed (data-businessunit-id).',
    ],
    'template_id' => [
        'option' => 'workernu_tp_template_id',
        'label'  => 'Template ID',
        'type'   => 'string',
        'sanitize' => 'sanitize_text_field',
        'default' => '',
        'description' => 'From your TrustBox embed (data-template-id) — selects which widget style renders.',
    ],
    'locale' => [
        'option' => 'workernu_tp_locale',
        'label'  => 'Locale',
        'type'   => 'string',
        'sanitize' => 'sanitize_text_field',
        'default' => 'lt-LT',
        'description' => 'e.g. "lt-LT" or "en-US".',
    ],
    'height' => [
        'option' => 'workernu_tp_height',
        'label'  => 'Widget height',
        'type'   => 'string',
        'sanitize' => 'sanitize_text_field',
        'default' => '20px',
        'description' => 'e.g. "20px" — the data-style-height of the embedded widget.',
    ],
    'theme' => [
        'option' => 'workernu_tp_theme',
        'label'  => 'Widget theme',
        'type'   => 'string',
        'sanitize' => __NAMESPACE__ . '\\sanitize_theme',
        'default' => 'light',
        'description' => 'light | dark — the data-theme of the embedded widget.',
    ],
    'review_url' => [
        'option' => 'workernu_tp_review_url',
        'label'  => 'Reviews URL',
        'type'   => 'string',
        'sanitize' => 'esc_url_raw',
        'default' => '',
        'description' => 'e.g. https://www.trustpilot.com/review/yourdomain.com — the link inside the widget and the fallback image.',
    ],
    'image_id' => [
        'option' => 'workernu_tp_image_id',
        'label'  => 'Fallback image',
        'type'   => 'integer',
        'sanitize' => 'absint',
        'default' => 0,
        'description' => 'Optional. Static badge shown when the widget is not available (e.g. before JS loads).',
    ],
    'aggregate_rating' => [
        'option' => 'workernu_tp_aggregate_rating',
        'label'  => 'Aggregate rating value',
        'type'   => 'string',
        'sanitize' => 'sanitize_text_field',
        'default' => '',
        'description' => 'e.g. "4.5" — used in the AggregateRating structured data on pages with a testimonials section. Mirror the current value from your Trustpilot dashboard.',
    ],
    'aggregate_count' => [
        'option' => 'workernu_tp_aggregate_count',
        'label'  => 'Aggregate review count',
        'type'   => 'string',
        'sanitize' => 'sanitize_text_field',
        'default' => '',
        'description' => 'e.g. "26" — total number of reviews backing the aggregate rating above.',
    ],
];

function sanitize_theme($v): string {
    return in_array((string) $v, ['light', 'dark'], true) ? (string) $v : 'light';
}

/**
 * Register all Trustpilot settings with the WP Settings API so they save via
 * the standard options.php form handler on the admin page.
 */
function register_settings(): void {
    foreach (TRUSTPILOT_FIELDS as $key => $field) {
        register_setting(OPTION_GROUP, $field['option'], [
            'type'              => $field['type'],
            'sanitize_callback' => $field['sanitize'],
            'default'           => $field['default'],
        ]);
    }
}

/**
 * Public helper: read every Trustpilot setting as an associative array.
 * Returns the canonical short keys (businessunit_id, template_id, etc.), not
 * the underlying wp_option names, so templates stay clean.
 */
function get(): array {
    $out = [];
    foreach (TRUSTPILOT_FIELDS as $key => $field) {
        $out[$key] = get_option($field['option'], $field['default']);
    }
    return $out;
}

/**
 * Quick check: is enough of the account configured for the live widget to
 * actually render? (Both business-unit ID and template ID are required by
 * Trustpilot's bootstrap loader.)
 */
function is_configured(): bool {
    $s = get();
    return !empty($s['businessunit_id']) && !empty($s['template_id']);
}

/**
 * Render the live TrustBox widget if the account is configured, otherwise fall
 * back to the static fallback image (if one is uploaded), otherwise render
 * nothing. Templates call this when their `footer_type` / mode is "trustpilot".
 */
function render(string $widget_class = 'workernu-trustpilot trustpilot-widget', string $image_class = 'workernu-trustpilot workernu-trustpilot--image'): void {
    if (is_configured()) {
        render_widget($widget_class);
    } else {
        render_fallback_image($image_class);
    }
}

function render_widget(string $class = 'workernu-trustpilot trustpilot-widget'): void {
    $s = get();
    $review = !empty($s['review_url']) ? $s['review_url'] : 'https://www.trustpilot.com/';
    ?>
    <div class="<?php echo esc_attr($class); ?>"
         data-locale="<?php echo esc_attr($s['locale']); ?>"
         data-template-id="<?php echo esc_attr($s['template_id']); ?>"
         data-businessunit-id="<?php echo esc_attr($s['businessunit_id']); ?>"
         data-style-height="<?php echo esc_attr($s['height']); ?>"
         data-style-width="100%"
         data-theme="<?php echo esc_attr($s['theme'] === 'dark' ? 'dark' : 'light'); ?>">
        <a href="<?php echo esc_url($review); ?>" target="_blank" rel="noopener">Trustpilot</a>
    </div>
    <?php
    enqueue_bootstrap();
}

function render_fallback_image(string $class = 'workernu-trustpilot workernu-trustpilot--image'): void {
    $s = get();
    $img_id = (int) ($s['image_id'] ?? 0);
    if (!$img_id) return;
    $url = wp_get_attachment_image_url($img_id, 'medium');
    if (!$url) return;
    $alt = get_post_meta($img_id, '_wp_attachment_image_alt', true) ?: 'Trustpilot';
    $review = (string) ($s['review_url'] ?? '');
    if ($review !== '') {
        ?>
        <a class="<?php echo esc_attr($class); ?>" href="<?php echo esc_url($review); ?>" target="_blank" rel="noopener">
            <img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy">
        </a>
        <?php
    } else {
        ?>
        <span class="<?php echo esc_attr($class); ?>">
            <img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy">
        </span>
        <?php
    }
}

/**
 * Trustpilot's bootstrap loader needs to be on the page once. Guarded via a
 * request-scoped global so multiple calls in the same render don't dupe it.
 */
function enqueue_bootstrap(): void {
    if (!empty($GLOBALS['workernu_trustpilot_loaded'])) return;
    $GLOBALS['workernu_trustpilot_loaded'] = true;
    echo '<script type="text/javascript" src="https://widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js" async></script>';
}
