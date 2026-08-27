<?php
/**
 * Icons — icon + title + description grid.
 *
 * Homepage "industries / value props" block. Each item uses a Font Awesome class
 * OR an uploaded image (image wins when both are set).
 *
 * Field map for the frontend dev:
 *   heading             — text (translatable; optional)
 *   subheading          — textarea (translatable; optional)
 *   items[]             — repeater
 *       └─ icon         — icon (FA class or raw HTML; ignored if icon_image set)
 *       └─ icon_image   — image (alternative to FA — takes precedence)
 *       └─ title        — text (translatable, required)
 *       └─ description  — textarea (translatable; optional)
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   spacing        — vertical padding: tight | normal (default) | loose
 *   align          — heading alignment: left | center (default)
 *   columns        — layout: 1 | 2 (default) | 3 | 4 | rail-ltr | rail-rtl | steps
 *                    Rail variants render as a full-bleed continuous marquee
 *                    (single row scrolling left-to-right or right-to-left).
 *                    Steps renders numbered white cards (1, 2, 3…) in a row
 *                    with arrows between them — an onboarding/how-it-works
 *                    flow. Number badges are automatic from item order.
 *   icon_position  — left (default) | top
 *   icon_size      — sm | md (default) | lg
 */

return [
    'label'       => 'Icons',
    'description' => 'Icon + title + description grid. Use for industries, features, value props.',

    'fields' => [
        ['name' => 'heading',    'type' => 'text',     'label' => 'Heading',    'translatable' => true,
         'hint' => 'Optional. Blank hides the header block.'],
        ['name' => 'subheading', 'type' => 'textarea', 'label' => 'Subheading', 'translatable' => true, 'rows' => 2],

        [
            'name'      => 'items',
            'type'      => 'repeater',
            'label'     => 'Items',
            'add_label' => 'Add item',
            'fields'    => [
                ['name' => 'tag',         'type' => 'text',  'label' => 'Tag label (e.g. FIKSUOJA)', 'translatable' => true, 'width' => 'half',
                 'hint' => 'Short uppercase label shown top-left with an auto-number: 01 — TAG. Leave blank to hide.'],
                ['name' => 'icon',        'type' => 'icon',  'label' => 'Icon (Font Awesome)', 'width' => 'half',
                 'hint' => 'FA class like "fa-solid fa-bolt" or full <i>/<svg> HTML. Ignored if Icon image is set.'],
                ['name' => 'icon_image',  'type' => 'image', 'label' => 'Icon image (alternative)', 'translatable' => true, 'width' => 'half',
                 'hint' => 'Upload SVG/PNG. Takes precedence over the FA icon when both are set. Pick a separate image per language if the icon contains translatable text.'],
                ['name' => 'icon_image_alt', 'type' => 'text', 'label' => 'Icon image alt text', 'translatable' => true,
                 'show_if_not_empty' => 'icon_image',
                 'hint' => 'Describes the icon image for screen readers and search engines. Leave blank to treat it as decorative.'],
                ['name' => 'title',       'type' => 'text',     'label' => 'Title',       'translatable' => true, 'required' => true],
                ['name' => 'description', 'type' => 'textarea', 'label' => 'Description', 'translatable' => true, 'rows' => 2],
            ],
        ],
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
            'name'    => 'columns',
            'type'    => 'select',
            'label'   => 'Layout',
            'options' => [
                '1'        => '1 column',
                '2'        => '2 columns',
                '3'        => '3 columns',
                '4'        => '4 columns',
                'rail-ltr' => 'Rail — left to right',
                'rail-rtl' => 'Rail — right to left',
                'steps'    => 'Steps — numbered, arrows between',
            ],
            'default' => '2',
        ],
        [
            'name'    => 'card_style',
            'type'    => 'select',
            'label'   => 'Card style',
            'options' => ['none' => 'None', 'card' => 'White card'],
            'default' => 'none',
        ],
        [
            'name'    => 'icon_position',
            'type'    => 'select',
            'label'   => 'Icon position',
            'options' => ['left' => 'Left of text', 'top' => 'Above text'],
            'default' => 'left',
        ],
        [
            'name'    => 'icon_size',
            'type'    => 'select',
            'label'   => 'Icon size',
            'options' => ['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'],
            'default' => 'md',
        ],
    ],
];
