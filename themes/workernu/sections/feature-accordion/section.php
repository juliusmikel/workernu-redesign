<?php
/**
 * Feature Accordion — expandable rows on the left; a companion image on the
 * right that swaps to match whichever row is open.
 *
 * Field map for the frontend dev:
 *   heading      — text (translatable; optional)
 *   subheading   — textarea (translatable; optional)
 *   items[]      — repeater (one entry per row, unlimited)
 *       └─ title       — text      (translatable, required; the clickable row title)
 *       └─ text        — rich_text (translatable, required; editor picks paragraph | bullets | numbered)
 *       └─ image       — image     (translatable, required; shown while this row is open)
 *       └─ image_alt   — text      (translatable; falls back to the attachment's alt if blank)
 *       └─ cta1_*      — optional CTA button #1: label (translatable) / url / variant / target
 *       └─ cta2_*      — optional CTA button #2: same shape
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   align            — header alignment: left | center (default)
 *   media_position   — image side: right (default) | left
 *
 * No overflow modifier — images are never scaled/cropped to fill their
 * column, each renders at its own natural size (style.css). A large image
 * overflows past the column edge automatically when it's wider than the
 * column; a small one just sits at its own width. That's inherent per image,
 * not a per-section on/off setting.
 *
 * No first-open / all-open modifier like the plain `faq` section has — the
 * first row is always open on load; from then on it's 0 or 1 rows open
 * (closing the open one is fine). The image pane isn't tied to "currently
 * open" — it shows whichever row was opened most recently, even after that
 * row is later closed, so it never goes blank. animations.js owns all of
 * this (exclusivity, the slide animation, and the "sticky" image); the
 * underlying <details name="…"> markup is a no-JS fallback only.
 */

return [
    'label'       => 'Feature Accordion',
    'description' => 'Expandable rows with a companion image that swaps per row — a mini FAQ with visuals.',

    'fields' => [
        ['name' => 'heading',    'type' => 'text',     'label' => 'Heading',    'translatable' => true],
        ['name' => 'subheading', 'type' => 'textarea', 'label' => 'Subheading', 'translatable' => true, 'rows' => 2],

        [
            'name'      => 'items',
            'type'      => 'repeater',
            'label'     => 'Items',
            'add_label' => 'Add item',
            'hint'      => 'Each entry becomes an expandable row. Opening one shows its image on the other side and closes the rest.',
            'fields'    => [
                ['name' => 'title', 'type' => 'text',      'label' => 'Title', 'translatable' => true, 'required' => true],
                ['name' => 'text',  'type' => 'rich_text', 'label' => 'Text',  'translatable' => true, 'required' => true, 'rows' => 3,
                 'hint' => 'For bullet/numbered display, put each item on its own line.'],

                ['name' => 'image',     'type' => 'image', 'label' => 'Image', 'translatable' => true, 'required' => true,
                 'hint' => 'Shown on the other side while this row is open. Pick a separate image per language if it contains translatable UI text.'],
                ['name' => 'image_alt', 'type' => 'text',  'label' => 'Image alt text', 'translatable' => true,
                 'show_if_not_empty' => 'image',
                 'hint' => 'Describes the image for screen readers and search engines. Falls back to the attachment\'s alt if blank.'],

                ['name' => 'cta1_label',   'type' => 'text',   'label' => 'CTA 1 label',  'translatable' => true],
                ['name' => 'cta1_url',     'type' => 'text',   'label' => 'CTA 1 URL'],
                ['name' => 'cta1_variant', 'type' => 'select', 'label' => 'CTA 1 style', 'render_as' => 'buttons',
                 'options' => ['primary' => 'Primary', 'outline' => 'Outline', 'subtle' => 'Subtle', 'ghost' => 'Ghost']],
                ['name' => 'cta1_target',  'type' => 'select', 'label' => 'CTA 1 opens', 'render_as' => 'buttons',
                 'options' => ['_self' => 'Same tab', '_blank' => 'New tab']],

                ['name' => 'cta2_label',   'type' => 'text',   'label' => 'CTA 2 label',  'translatable' => true],
                ['name' => 'cta2_url',     'type' => 'text',   'label' => 'CTA 2 URL'],
                ['name' => 'cta2_variant', 'type' => 'select', 'label' => 'CTA 2 style', 'render_as' => 'buttons',
                 'options' => ['primary' => 'Primary', 'outline' => 'Outline', 'subtle' => 'Subtle', 'ghost' => 'Ghost']],
                ['name' => 'cta2_target',  'type' => 'select', 'label' => 'CTA 2 opens', 'render_as' => 'buttons',
                 'options' => ['_self' => 'Same tab', '_blank' => 'New tab']],
            ],
        ],
    ],

    'modifiers' => [
        [
            'name'    => 'align',
            'type'    => 'select',
            'label'   => 'Header alignment',
            'options' => ['left' => 'Left', 'center' => 'Center'],
            'default' => 'center',
        ],
        [
            'name'    => 'media_position',
            'type'    => 'select',
            'label'   => 'Image side',
            'options' => ['right' => 'Right', 'left' => 'Left'],
            'default' => 'right',
        ],
    ],

    /**
     * Schema.org — same FAQPage pattern as the plain `faq` section (title =
     * Question, text = Answer). If a page uses both `faq` and this section,
     * you'll get two FAQPage entities in the graph — fine per spec, but Google
     * only ever surfaces one page's worth of FAQ rich results, so avoid
     * stacking both on the same page for the cleanest result.
     */
    'schema' => function (array $data): ?array {
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        if (!$items) return null;

        $questions = [];
        foreach ($items as $item) {
            $q = workernu_t($item['title'] ?? '');
            $text_field = $item['text'] ?? null;
            $a = is_array($text_field) ? workernu_t($text_field['value'] ?? '') : '';
            if ($q === '' || $a === '') continue;
            $questions[] = [
                '@type'          => 'Question',
                'name'           => (string) $q,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => (string) $a,
                ],
            ];
        }

        if (!$questions) return null;

        return [
            '@type'      => 'FAQPage',
            '@id'        => home_url('/#feature-accordion'),
            'mainEntity' => $questions,
        ];
    },
];
