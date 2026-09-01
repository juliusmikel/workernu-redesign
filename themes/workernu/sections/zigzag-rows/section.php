<?php
/**
 * Zigzag Rows — alternating media + text feature rows.
 *
 * Homepage "Viskas, ko reikia jūsų darbų valdymui" block: a stack of rows, each
 * pairing a screenshot with an eyebrow, title, body, and optional CTA. Rows can
 * alternate the image side automatically.
 *
 * Field map for the frontend dev:
 *   heading      — text (translatable; optional section heading)
 *   subheading   — textarea (translatable; optional)
 *   rows[]       — repeater (one entry per feature row)
 *       └─ image          — image (translatable; the screenshot)
 *       └─ image_alt      — text (translatable; only shown when image set)
 *       └─ eyebrow        — text (translatable; small label above title)
 *       └─ eyebrow_style  — select (plain | pill) — pill renders the eyebrow as a rounded badge
 *       └─ title          — text (translatable, required)
 *       └─ body           — rich_text (translatable; paragraph | bullets | numbered | checks)
 *       └─ cta_label      — text (translatable; optional)
 *       └─ cta_url        — text
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   spacing     — vertical padding: tight | normal (default) | loose
 *   first_side  — first row's image side: right (default) | left;
 *                 subsequent rows automatically alternate from there.
 */

return [
    'label'       => 'Zigzag Rows',
    'description' => 'Alternating media + text rows; each screenshot bleeds to the page edge.',

    'fields' => [
        ['name' => 'heading',    'type' => 'text',     'label' => 'Section heading', 'translatable' => true],
        ['name' => 'subheading', 'type' => 'textarea', 'label' => 'Section subheading', 'translatable' => true, 'rows' => 2],

        [
            'name'      => 'rows',
            'type'      => 'repeater',
            'label'     => 'Feature rows',
            'add_label' => 'Add row',
            'fields'    => [
                ['name' => 'image',         'type' => 'image',     'label' => 'Image', 'translatable' => true,
                 'hint' => 'Pick a separate image per language if the screenshot contains translatable UI text.'],
                ['name' => 'image_alt',     'type' => 'text',      'label' => 'Image alt text', 'translatable' => true,
                 'show_if_not_empty' => 'image',
                 'hint' => 'Describes the screenshot for screen readers and search engines. Falls back to the attachment\'s alt if blank.'],
                ['name' => 'eyebrow',       'type' => 'text',      'label' => 'Eyebrow',       'translatable' => true, 'width' => 'half'],
                ['name' => 'eyebrow_style', 'type' => 'select',    'label' => 'Eyebrow style', 'render_as' => 'buttons', 'width' => 'half',
                 'options' => ['plain' => 'Plain', 'pill' => 'Pill'], 'default' => 'plain'],
                ['name' => 'title',         'type' => 'text',      'label' => 'Title',     'translatable' => true, 'required' => true],
                ['name' => 'body',          'type' => 'rich_text', 'label' => 'Body',      'translatable' => true, 'rows' => 3,
                 'hint' => 'For list displays, put each item on its own line.'],
                ['name' => 'cta_label',     'type' => 'text',      'label' => 'CTA label', 'translatable' => true, 'width' => 'half'],
                ['name' => 'cta_url',       'type' => 'text',      'label' => 'CTA URL',   'width' => 'half'],
                ['name' => 'cta_icon',      'type' => 'boolean',   'label' => 'CTA play icon',
                 'hint' => 'Adds a play-circle icon before the label, same color as the button text.'],
            ],
        ],
    ],

    'modifiers' => [
        [
            'name'    => 'first_side',
            'type'    => 'select',
            'label'   => 'First row image side',
            'hint'    => 'Subsequent rows automatically alternate from this starting side.',
            'options' => ['right' => 'Right', 'left' => 'Left'],
            'default' => 'right',
        ],
    ],
];
