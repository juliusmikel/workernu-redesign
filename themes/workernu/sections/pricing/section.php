<?php
/**
 * Pricing — centered header + grid of tier cards + optional add-on pills row.
 *
 * Each tier card has a title, price (number + suffix), unit caption, checkmark
 * feature list, and a CTA. One tier can be highlighted with a badge above it
 * (e.g. "Populiariausias") which also applies an accent border.
 *
 * `content_defaults: true` — this section carries a `content_source` toggle
 * (custom | default, injected automatically by Registry\discover()). When set
 * to "Site default", tiers/addons come from Settings → WorkerNu → Pricing
 * instead of this instance's own fields — see WorkerNu\Sections\Defaults\resolve().
 * Display modifiers (columns, align) always stay per-instance.
 *
 * Field map for the frontend dev:
 *   heading        — text (translatable, required)
 *   subheading     — textarea (translatable)
 *   tiers[]        — repeater
 *       └─ title        — text (translatable, required)
 *       └─ price        — text (translatable; e.g. "€5")
 *       └─ price_suffix — text (translatable; e.g. "/mėn")
 *       └─ unit         — text (translatable; e.g. "Už 1 darbuotoją")
 *       └─ features     — textarea (translatable; one feature per line — rendered as a ✓ list)
 *       └─ cta_label    — text (translatable)
 *       └─ cta_url      — text
 *       └─ badge        — text (translatable; optional; shows badge above + accent border)
 *
 *   addons_heading — text (translatable; optional, e.g. "Papildomos funkcijos")
 *   addons[]       — repeater (optional add-on pills below the tiers)
 *       └─ icon   — icon
 *       └─ label  — text (translatable)
 *       └─ price  — text (translatable; e.g. "+€1 / mėn")
 *
 * Modifiers:
 *   spacing — vertical padding: tight | normal (default) | loose
 *   columns — tier card columns: 2 | 3 (default) | 4
 *   align   — header alignment: left | center (default)
 */

return [
    'label'            => 'Pricing',
    'description'      => 'Pricing tiers grid with check-list features and optional add-on pills.',
    'content_defaults' => true,

    'fields' => [
        ['name' => 'heading',    'type' => 'text',     'label' => 'Heading',    'translatable' => true, 'required' => true],
        ['name' => 'subheading', 'type' => 'textarea', 'label' => 'Subheading', 'translatable' => true, 'rows' => 2],

        [
            'name'      => 'tiers',
            'type'      => 'repeater',
            'label'     => 'Tiers',
            'add_label' => 'Add tier',
            'fields'    => [
                ['name' => 'title',        'type' => 'text',     'label' => 'Title',        'translatable' => true, 'required' => true],
                ['name' => 'price',        'type' => 'text',     'label' => 'Price',        'translatable' => true, 'width' => 'half',
                 'hint' => 'e.g. "€5"'],
                ['name' => 'price_suffix', 'type' => 'text',     'label' => 'Price suffix', 'translatable' => true, 'width' => 'half',
                 'hint' => 'e.g. "/mėn"'],
                ['name' => 'unit',         'type' => 'text',     'label' => 'Unit caption', 'translatable' => true,
                 'hint' => 'e.g. "Už 1 darbuotoją"'],
                ['name' => 'features',     'type' => 'textarea', 'label' => 'Features',     'translatable' => true, 'rows' => 6,
                 'hint' => 'One feature per line. Rendered with check icons.'],
                ['name' => 'cta_label',    'type' => 'text',     'label' => 'CTA label',    'translatable' => true, 'width' => 'half'],
                ['name' => 'cta_url',      'type' => 'text',     'label' => 'CTA URL',      'width' => 'half'],
                ['name' => 'cta_icon',     'type' => 'boolean',  'label' => 'CTA play icon',
                 'hint' => 'Adds a play-circle icon before the label, same color as the button text.'],
                ['name' => 'badge',        'type' => 'text',     'label' => 'Badge',        'translatable' => true,
                 'hint' => 'Optional. Shows a badge above the card and applies the accent border. e.g. "Populiariausias".'],
            ],
        ],

        ['name' => 'addons_heading', 'type' => 'text', 'label' => 'Add-ons heading', 'translatable' => true,
         'hint' => 'Optional. e.g. "Papildomos funkcijos".'],

        [
            'name'      => 'addons',
            'type'      => 'repeater',
            'label'     => 'Add-ons',
            'add_label' => 'Add add-on',
            'hint'      => 'Optional pills shown under the tiers (icon + label + price).',
            'fields'    => [
                ['name' => 'icon',  'type' => 'icon', 'label' => 'Icon', 'width' => 'half'],
                ['name' => 'label', 'type' => 'text', 'label' => 'Label', 'translatable' => true, 'width' => 'half'],
                ['name' => 'price', 'type' => 'text', 'label' => 'Price', 'translatable' => true,
                 'hint' => 'e.g. "+€1 / mėn"'],
            ],
        ],
    ],

    'modifiers' => [
        [
            'name'    => 'columns',
            'type'    => 'select',
            'label'   => 'Tier columns',
            'options' => ['2' => '2 columns', '3' => '3 columns', '4' => '4 columns'],
            'default' => '3',
        ],
        [
            'name'    => 'align',
            'type'    => 'select',
            'label'   => 'Header alignment',
            'options' => ['left' => 'Left', 'center' => 'Center'],
            'default' => 'center',
        ],
    ],

    /**
     * Schema.org — Pricing is the section that makes a page a real product
     * offering, so it owns the SoftwareApplication emission *and* each tier's
     * Offer. Hero is just visual presentation; without pricing there's no
     * product, so SoftwareApplication only emits here.
     *
     * Returns: [SoftwareApplication, Offer, Offer, Offer, ...]
     *
     * Currency is pulled from the price string when possible (€/$/£/¥), else EUR.
     */
    'schema' => function (array $data): ?array {
        $tiers = is_array($data['tiers'] ?? null) ? $data['tiers'] : [];
        if (!$tiers) return null;
        if (!function_exists('\\WorkerNu\\Settings\\Seo\\get')) return null;
        if (!\WorkerNu\Settings\Seo\is_configured())            return null;

        $entries = [];
        $sa_id   = home_url('/#software-application');

        // SoftwareApplication — built from Settings → SEO. Always emitted first
        // so its @id is declared before the orphan filter inspects the Offers.
        $p = \WorkerNu\Settings\Seo\get();
        $sa = [
            '@type'  => 'SoftwareApplication',
            '@id'    => $sa_id,
            'name'   => (string) $p['name'],
            'author' => ['@id' => home_url('/#organization')],
        ];
        if (!empty($p['description'])) $sa['description']         = (string) $p['description'];
        if (!empty($p['app_url']))     $sa['url']                 = (string) $p['app_url'];
        if (!empty($p['category']))    $sa['applicationCategory'] = (string) $p['category'];
        if (!empty($p['operating_system'])) {
            $os = array_values(array_filter(array_map('trim', explode(',', (string) $p['operating_system']))));
            $sa['operatingSystem'] = count($os) > 1 ? $os : ($os[0] ?? '');
        }
        if (!empty($p['screenshot_id'])) {
            $url = wp_get_attachment_image_url((int) $p['screenshot_id'], 'full');
            if ($url) $sa['screenshot'] = $url;
        }
        $entries[] = $sa;

        // Offers — one per tier.
        foreach ($tiers as $i => $tier) {
            $title    = workernu_t($tier['title']    ?? '');
            $price    = workernu_t($tier['price']    ?? '');
            $url      = (string) ($tier['cta_url']   ?? '');
            if ($title === '' && $price === '') continue;

            // Extract a numeric value from "€5", "5,00", "5.50/mo", etc.
            $price_num = '';
            if (preg_match('/[\d]+[\.,]?[\d]*/', (string) $price, $m)) {
                $price_num = str_replace(',', '.', $m[0]);
            }

            // Infer currency from the symbol in the price string.
            $currency = 'EUR';
            if (strpos($price, '$') !== false) $currency = 'USD';
            elseif (strpos($price, '£') !== false) $currency = 'GBP';
            elseif (strpos($price, '¥') !== false) $currency = 'JPY';

            $offer = [
                '@type'         => 'Offer',
                '@id'           => home_url('/#offer-' . sanitize_title($title ?: 'tier-' . $i)),
                'itemOffered'   => ['@id' => $sa_id],
                'name'          => (string) $title,
                'priceCurrency' => $currency,
            ];
            if ($price_num !== '') $offer['price'] = $price_num;
            if ($url !== '')       $offer['url']   = esc_url_raw(workernu_localize_url($url));
            $entries[] = $offer;
        }

        return $entries;
    },
];
