<?php
/**
 * Map — geographic coverage band.
 *
 * Homepage "Naudojamas įmonių visoje Europoje ir už jos ribų" block: a heading
 * and body over a map image, with optional positioned pins and a CTA.
 *
 * Field map for the frontend dev:
 *   heading      — text (translatable; required)
 *   body         — textarea (translatable; optional)
 *   image        — image (the map graphic; not translatable — geography is universal)
 *   image_alt    — text (translatable; only shown when image set)
 *   pins[]       — repeater (optional dots overlaid on the map)
 *       └─ label — text (translatable; tooltip / a11y label)
 *       └─ x     — number (0–100, % from left)
 *       └─ y     — number (0–100, % from top)
 *   cta_label    — text (translatable; optional)
 *   cta_url      — text
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   spacing  — vertical padding: tight | normal (default) | loose
 *   align    — header alignment: left | center (default)
 */

return [
    'label'       => 'Map',
    'description' => 'Geographic coverage band: heading + body over a map image with optional pins.',

    'fields' => [
        ['name' => 'heading', 'type' => 'text',     'label' => 'Heading', 'translatable' => true, 'required' => true],
        ['name' => 'body',    'type' => 'textarea', 'label' => 'Body',    'translatable' => true, 'rows' => 2],
        ['name' => 'image',     'type' => 'image', 'label' => 'Map image'],
        ['name' => 'image_alt', 'type' => 'text',  'label' => 'Map image alt text', 'translatable' => true,
         'show_if_not_empty' => 'image',
         'hint' => 'Describes the map for screen readers and search engines, e.g. "Map of Europe highlighting countries we serve". Falls back to the attachment\'s alt if blank.'],

        [
            'name'      => 'pins',
            'type'      => 'repeater',
            'label'     => 'Map pins',
            'add_label' => 'Add pin',
            'hint'      => 'Optional dots overlaid on the map. Position is a percentage of the image.',
            'fields'    => [
                ['name' => 'label', 'type' => 'text',   'label' => 'Label', 'translatable' => true, 'width' => 'half'],
                ['name' => 'x',     'type' => 'number', 'label' => 'X (% from left)', 'min' => 0, 'max' => 100, 'step' => 0.1, 'width' => 'half'],
                ['name' => 'y',     'type' => 'number', 'label' => 'Y (% from top)',  'min' => 0, 'max' => 100, 'step' => 0.1, 'width' => 'half'],
            ],
        ],

        ['name' => 'cta_label', 'type' => 'text', 'label' => 'CTA label', 'translatable' => true, 'width' => 'half'],
        ['name' => 'cta_url',   'type' => 'text', 'label' => 'CTA URL', 'width' => 'half'],
        ['name' => 'cta_icon',  'type' => 'boolean', 'label' => 'Play icon',
         'hint' => 'Adds a play-circle icon before the label, same color as the button text.'],
    ],

    'modifiers' => [
        [
            'name'    => 'align',
            'type'    => 'select',
            'label'   => 'Header alignment',
            'options' => ['left' => 'Left', 'center' => 'Center'],
            'default' => 'center',
        ],
    ],
];
