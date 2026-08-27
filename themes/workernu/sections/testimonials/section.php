<?php
/**
 * Testimonials — social-proof grid.
 *
 * Homepage "Ką sako mūsų klientai" block: testimonial cards with avatar, star
 * rating, name, country flag, and quote, plus an optional footer badge
 * (e.g. a Trustpilot widget).
 *
 * `content_defaults: true` — this section carries a `content_source` toggle
 * (custom | default, injected automatically by Registry\discover()). When set
 * to "Site default", testimonials come from Settings → WorkerNu → Reviews
 * instead of this instance's own fields — see WorkerNu\Sections\Defaults\resolve().
 * Display modifiers (layout, columns, card_gap, align) always stay per-instance.
 *
 * Field map for the frontend dev:
 *   heading           — text (translatable, required)
 *   subheading        — textarea (translatable; optional)
 *   testimonials[]    — repeater
 *       └─ avatar         — image (round headshot)
 *       └─ country_flag   — image (small flag PNG/SVG; appears top-right)
 *       └─ name           — text (not translatable — names are universal)
 *       └─ country_label  — text (optional; used as flag alt text)
 *       └─ rating         — number 1–5 (defaults to 5 if blank)
 *       └─ quote          — textarea (translatable, required)
 *       └─ highlight      — boolean (emphasised card border)
 *   footer_image       — image (translatable; optional badge under the grid)
 *   footer_url         — text (optional link wrapping the footer image)
 *   footer_image_alt   — text (translatable; only shown when footer_image set
 *                          AND footer_type=image)
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   spacing      — vertical padding: tight | normal (default) | loose
 *   align        — heading alignment: left | center (default)
 *   layout       — static grid | sliding marquee (default)
 *   columns      — grid columns (grid layout only): 2 | 3 (default) | 4
 *   card_gap     — gap between cards: none | tight | normal (default) | loose
 *   footer_type  — what to show below the testimonials grid:
 *                  none (default) | image (uses footer_image + footer_url)
 *                  | trustpilot (uses the global TrustBox widget — see Settings → WorkerNu)
 */

return [
    'label'            => 'Testimonials',
    'description'      => 'Testimonials grid with star ratings, names, country flags, and optional footer badge.',
    'content_defaults' => true,

    'fields' => [
        ['name' => 'heading',    'type' => 'text',     'label' => 'Heading',    'translatable' => true, 'required' => true],
        ['name' => 'subheading', 'type' => 'textarea', 'label' => 'Subheading', 'translatable' => true, 'rows' => 2],

        [
            'name'      => 'testimonials',
            'type'      => 'repeater',
            'label'     => 'Testimonials',
            'add_label' => 'Add testimonial',
            'fields'    => [
                ['name' => 'avatar',        'type' => 'image',    'label' => 'Avatar',           'width' => 'half'],
                ['name' => 'country_flag',  'type' => 'image',    'label' => 'Country flag',     'width' => 'half',
                 'hint' => 'Small flag image (PNG/SVG). Shows in the top-right corner of the card.'],
                ['name' => 'name',          'type' => 'text',     'label' => 'Name',             'required' => true, 'width' => 'half'],
                ['name' => 'country_label', 'type' => 'text',     'label' => 'Country (a11y)',   'width' => 'half',
                 'hint' => 'Used as alt text for the flag (e.g. "Lithuania", "Denmark").'],
                ['name' => 'role',          'type' => 'text',     'label' => 'Role',             'translatable' => true, 'width' => 'half',
                 'hint' => 'e.g. "Owner", "Project manager". Optional.'],
                ['name' => 'company',       'type' => 'text',     'label' => 'Company',          'translatable' => true, 'width' => 'half',
                 'hint' => 'e.g. "Statybos UAB". Optional.'],
                ['name' => 'rating',        'type' => 'number',   'label' => 'Star rating (1-5)', 'min' => 1, 'max' => 5, 'width' => 'half',
                 'hint' => 'Defaults to 5 if blank.'],
                ['name' => 'date',          'type' => 'text',     'label' => 'Date',             'width' => 'half',
                 'hint' => 'ISO format YYYY-MM-DD (e.g. 2025-11-14). Emitted as Review.datePublished.'],
                ['name' => 'quote',         'type' => 'textarea', 'label' => 'Quote',            'translatable' => true, 'required' => true, 'rows' => 4],
                ['name' => 'highlight',     'type' => 'boolean',  'label' => 'Highlight this card',
                 'hint' => 'Adds an accent border. Use sparingly.'],
            ],
        ],

        [
            'name'      => 'footer_type',
            'type'      => 'select',
            'render_as' => 'buttons',
            'label'     => 'Footer',
            'hint'      => 'What to show below the testimonials. Trustpilot uses the global account at Settings → WorkerNu.',
            'options'   => [
                'none'       => 'None',
                'image'      => 'Image',
                'trustpilot' => 'Trustpilot',
            ],
            'default'   => 'none',
        ],
        ['name' => 'footer_image', 'type' => 'image', 'label' => 'Footer badge image', 'translatable' => true, 'width' => 'half',
         'show_if' => ['footer_type' => 'image'],
         'hint'    => 'Pick a separate image per language if the badge contains locale-specific copy.'],
        ['name' => 'footer_url',   'type' => 'text',  'label' => 'Footer badge link', 'width' => 'half',
         'show_if' => ['footer_type' => 'image'],
         'hint'    => 'Optional. If set, the badge becomes a link.'],
        ['name' => 'footer_image_alt', 'type' => 'text', 'label' => 'Footer badge alt text', 'translatable' => true,
         'show_if'           => ['footer_type' => 'image'],
         'show_if_not_empty' => 'footer_image',
         'hint' => 'Describes the badge for screen readers and search engines. Falls back to the attachment\'s alt if blank.'],
    ],

    'modifiers' => [
        [
            'name'    => 'align',
            'type'    => 'select',
            'label'   => 'Heading alignment',
            'options' => ['left' => 'Left', 'center' => 'Center'],
            'default' => 'center',
        ],
        [
            'name'    => 'layout',
            'type'    => 'select',
            'label'   => 'Layout',
            'options' => ['grid' => 'Static grid', 'marquee' => 'Sliding marquee'],
            'default' => 'marquee',
        ],
        [
            'name'    => 'columns',
            'type'    => 'select',
            'label'   => 'Grid columns (grid layout only)',
            'options' => ['2' => '2 columns', '3' => '3 columns', '4' => '4 columns'],
            'default' => '3',
        ],
        [
            'name'    => 'card_gap',
            'type'    => 'select',
            'label'   => 'Gap between cards',
            'options' => ['none' => 'None', 'tight' => 'Tight', 'normal' => 'Normal', 'loose' => 'Loose'],
            'default' => 'loose',
        ],
    ],

    /**
     * Schema.org — each testimonial becomes a Review with itemReviewed → the
     * SoftwareApplication declared by the Hero. When the Trustpilot global
     * settings are configured, an AggregateRating entity is also emitted with
     * the same itemReviewed reference.
     */
    'schema' => function (array $data): ?array {
        $testimonials = is_array($data['testimonials'] ?? null) ? $data['testimonials'] : [];
        $entries = [];
        $sa_id   = home_url('/#software-application');
        $org_id  = home_url('/#organization');

        foreach ($testimonials as $t) {
            $name    = (string) ($t['name']  ?? '');
            $quote   = workernu_t($t['quote'] ?? '');
            $rating  = max(1, min(5, (int) ($t['rating'] ?? 5) ?: 5));
            $role    = workernu_t($t['role']    ?? '');
            $company = workernu_t($t['company'] ?? '');
            $date    = trim((string) ($t['date'] ?? ''));
            if ($quote === '') continue;

            $review = [
                '@type'        => 'Review',
                'itemReviewed' => ['@id' => $sa_id],
                'reviewBody'   => (string) $quote,
                'reviewRating' => [
                    '@type'       => 'Rating',
                    'ratingValue' => (string) $rating,
                    'bestRating'  => '5',
                    'worstRating' => '1',
                ],
                // Publisher = our org. We host the testimonial on this site, so
                // attributing it back to the Organization is the truthful chain.
                'publisher'    => ['@id' => $org_id],
            ];

            // datePublished — accept YYYY-MM-DD only; silently drop anything else
            // so we never emit a garbage date that fails validators.
            if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $review['datePublished'] = $date;
            }

            if ($name !== '') {
                $author = ['@type' => 'Person', 'name' => $name];
                if ($role !== '')    $author['jobTitle'] = $role;
                if ($company !== '') $author['worksFor'] = ['@type' => 'Organization', 'name' => $company];
                $review['author'] = $author;
            }
            $entries[] = $review;
        }

        // AggregateRating from the Trustpilot global settings (if any).
        if (function_exists('\\WorkerNu\\Settings\\Trustpilot\\get')) {
            $tp = \WorkerNu\Settings\Trustpilot\get();
            $rating_value = trim((string) ($tp['aggregate_rating'] ?? ''));
            $rating_count = trim((string) ($tp['aggregate_count']  ?? ''));
            if ($rating_value !== '' && $rating_count !== '') {
                $entries[] = [
                    '@type'        => 'AggregateRating',
                    // Stable @id so SoftwareApplication can back-reference this
                    // entity via its `aggregateRating` property in the second pass.
                    '@id'          => home_url('/#aggregate-rating'),
                    'name'         => 'Customer reviews on Trustpilot',
                    'itemReviewed' => ['@id' => $sa_id],
                    'ratingValue'  => $rating_value,
                    // ratingCount + reviewCount are technically distinct properties
                    // (raw star ratings vs. written reviews), but Trustpilot
                    // requires a written review for every rating, so the two
                    // numbers are always identical for our data. Emit both so
                    // validators looking for either are satisfied.
                    'ratingCount'  => $rating_count,
                    'reviewCount'  => $rating_count,
                    'bestRating'   => '5',
                    'worstRating'  => '1',
                ];
            }
        }

        return $entries ?: null;
    },
];
