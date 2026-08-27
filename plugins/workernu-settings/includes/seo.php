<?php
/**
 * SEO — site-wide entity data used by the JSON-LD emitters (currently the
 * `SoftwareApplication` payload contributed by the Hero section). Stores
 * what the SaaS *is*: name, description, app URL, category, OS, screenshot.
 *
 * Storage pattern matches Trustpilot — one wp_options entry per field, with
 * a public `get()` helper that returns the whole set.
 */
namespace WorkerNu\Settings\Seo;

if (!defined('ABSPATH')) exit;

const OPTION_GROUP = 'workernu_seo';

const SEO_FIELDS = [
    'name' => [
        'option' => 'workernu_seo_name',
        'label'  => 'Product name',
        'type'   => 'string',
        'sanitize' => 'sanitize_text_field',
        'default' => 'Workernu',
        'description' => 'Display name of the SaaS — e.g. "Workernu".',
    ],
    'description' => [
        'option' => 'workernu_seo_description',
        'label'  => 'Description',
        'type'   => 'string',
        'sanitize' => 'sanitize_textarea_field',
        'default' => '',
        'description' => 'One or two sentences summarising what the product does. Used in the structured-data `description` field.',
    ],
    'app_url' => [
        'option' => 'workernu_seo_app_url',
        'label'  => 'App URL',
        'type'   => 'string',
        'sanitize' => 'esc_url_raw',
        'default' => '',
        'description' => 'The URL where the actual app lives (e.g. https://app.workernu.com).',
    ],
    'category' => [
        'option' => 'workernu_seo_category',
        'label'  => 'Application category',
        'type'   => 'string',
        'sanitize' => __NAMESPACE__ . '\\sanitize_category',
        'default' => 'BusinessApplication',
        'description' => 'Schema.org applicationCategory. Most SaaS B2B tools use BusinessApplication.',
    ],
    'operating_system' => [
        'option' => 'workernu_seo_operating_system',
        'label'  => 'Operating system',
        'type'   => 'string',
        'sanitize' => 'sanitize_text_field',
        'default' => 'Web',
        'description' => 'Comma-separated list. e.g. "Web", or "Web, iOS, Android".',
    ],
    'screenshot_id' => [
        'option' => 'workernu_seo_screenshot_id',
        'label'  => 'Screenshot',
        'type'   => 'integer',
        'sanitize' => 'absint',
        'default' => 0,
        'description' => 'An image of the product UI. Used as the SoftwareApplication `screenshot` field; may appear in app rich results.',
    ],
];

const CATEGORY_OPTIONS = [
    'BusinessApplication'      => 'Business Application',
    'CommunicationApplication' => 'Communication',
    'DesignApplication'        => 'Design',
    'DeveloperApplication'     => 'Developer Tool',
    'EducationalApplication'   => 'Educational',
    'FinanceApplication'       => 'Finance',
    'HealthApplication'        => 'Health',
    'LifestyleApplication'     => 'Lifestyle',
    'MultimediaApplication'    => 'Multimedia',
    'ReferenceApplication'     => 'Reference',
    'SecurityApplication'      => 'Security',
    'ShoppingApplication'      => 'Shopping',
    'SocialNetworkingApplication' => 'Social Networking',
    'SportsApplication'        => 'Sports',
    'TravelApplication'        => 'Travel',
    'UtilitiesApplication'     => 'Utilities',
];

function sanitize_category($v): string {
    return array_key_exists((string) $v, CATEGORY_OPTIONS) ? (string) $v : 'BusinessApplication';
}

function register_settings(): void {
    foreach (SEO_FIELDS as $key => $field) {
        register_setting(OPTION_GROUP, $field['option'], [
            'type'              => $field['type'],
            'sanitize_callback' => $field['sanitize'],
            'default'           => $field['default'],
        ]);
    }
}

/**
 * Public helper: read every Product setting as an associative array, with the
 * canonical short keys (name, description, app_url, etc.).
 */
function get(): array {
    $out = [];
    foreach (SEO_FIELDS as $key => $field) {
        $out[$key] = get_option($field['option'], $field['default']);
    }
    return $out;
}

/**
 * Is the product entity configured enough to emit a meaningful schema?
 * Minimum bar: name + (description OR app_url).
 */
function is_configured(): bool {
    $p = get();
    return !empty($p['name']) && (!empty($p['description']) || !empty($p['app_url']));
}
