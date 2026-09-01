<?php
/**
 * Cards — grid of image cards with overlay text.
 *
 * Used on the homepage for the industries block ("Aiški platforma skirtingoms
 * industrijoms..."). The grid uses flex so an incomplete final row stretches to
 * fill the width. All cards share a height via the aspect modifier.
 *
 * Field map for the frontend dev:
 *   heading             — text (translatable, required)
 *   subheading          — textarea (translatable)
 *   cards[]             — repeater
 *       └─ image        — image (translatable, required; covers the card)
 *       └─ image_alt    — text (translatable; only shown when image set;
 *                          blank = decorative since title already conveys meaning)
 *       └─ title        — text (translatable, required; overlay)
 *       └─ description  — textarea (translatable; overlay)
 *       └─ url          — text; if set, the whole card becomes a link with an arrow
 *   cta_label           — text (translatable; optional bottom button)
 *   cta_url             — text (button href)
 *   link_label          — text (translatable; label used for the per-card link when
 *                         link_style is "inline-text"; defaults to "Sužinoti daugiau")
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   spacing     — vertical padding: tight | normal (default) | loose
 *   align       — heading + subheading alignment: left | center (default)
 *   size        — minimum card width: sm (≤4/row) | md (≤3/row, default) | lg (≤2/row)
 *   aspect      — card height bucket: portrait | square | landscape (default) | wide
 *   link_style  — per-card link display: corner-arrow (top-right; default)
 *                 | inline-text (blue "label →" below the description)
 */

return [
    'label'       => 'Cards',
    'description' => 'Grid of image cards with overlay text. Use for industries, features, case studies.',

    'fields' => [
        ['name' => 'heading',    'type' => 'text',     'label' => 'Heading',    'translatable' => true, 'required' => true],
        ['name' => 'subheading', 'type' => 'textarea', 'label' => 'Subheading', 'translatable' => true, 'rows' => 2],

        [
            'name'      => 'cards',
            'type'      => 'repeater',
            'label'     => 'Cards',
            'add_label' => 'Add card',
            'hint'      => 'Cards display in a grid. Whatever count you add, the last row stretches to fill the width.',
            'fields'    => [
                ['name' => 'image',       'type' => 'image',    'label' => 'Background image', 'translatable' => true, 'required' => true,
                 'hint' => 'Pick a separate image per language if the photo contains language-specific content.'],
                ['name' => 'image_alt',   'type' => 'text',     'label' => 'Image alt text', 'translatable' => true,
                 'show_if_not_empty' => 'image',
                 'hint' => 'Describes the image for screen readers and search engines. Leave blank if the card title already describes the visual.'],
                ['name' => 'title',       'type' => 'text',     'label' => 'Title',       'translatable' => true, 'required' => true],
                ['name' => 'description', 'type' => 'textarea', 'label' => 'Description', 'translatable' => true, 'rows' => 2],
                ['name' => 'url',         'type' => 'text',     'label' => 'Link URL',
                 'hint'  => 'Optional. If set, the whole card becomes a link and an arrow shows in the top-right corner.'],
            ],
        ],

        ['name' => 'cta_label', 'type' => 'text', 'label' => 'CTA button label', 'translatable' => true, 'width' => 'half',
         'hint' => 'Optional. Blank hides the button.'],
        ['name' => 'cta_url',   'type' => 'text', 'label' => 'CTA button URL', 'width' => 'half'],
        ['name' => 'cta_icon',  'type' => 'boolean', 'label' => 'Play icon',
         'hint' => 'Adds a play-circle icon before the label, same color as the button text.'],

        ['name' => 'link_label', 'type' => 'text', 'label' => 'Per-card link label', 'translatable' => true,
         'hint' => 'Used when Link style = "inline text". Defaults to "Sužinoti daugiau" if blank.'],
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
            'name'    => 'size',
            'type'    => 'select',
            'label'   => 'Card density',
            'options' => ['sm' => 'Small (up to 4/row)', 'md' => 'Medium (up to 3/row)', 'lg' => 'Large (up to 2/row)'],
            'default' => 'md',
        ],
        [
            'name'    => 'aspect',
            'type'    => 'select',
            'label'   => 'Card aspect ratio',
            'options' => ['portrait' => 'Portrait 3/4', 'square' => 'Square 1/1', 'landscape' => 'Landscape 4/3', 'wide' => 'Wide 16/9'],
            'default' => 'landscape',
        ],
        [
            'name'    => 'link_style',
            'type'    => 'select',
            'label'   => 'Per-card link style',
            'hint'    => 'How the link affordance is shown when a card has a Link URL.',
            'options' => [
                'corner-arrow' => 'Top-right corner arrow',
                'inline-text'  => 'Inline blue "label →" below the description',
            ],
            'default' => 'corner-arrow',
        ],
    ],
];
