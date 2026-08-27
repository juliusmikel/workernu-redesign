<?php
/**
 * Logos — "trusted by" / partners strip.
 *
 * Field map for the frontend dev (the $data array template.php receives):
 *   heading      — textarea  (translatable; short intro line, blank hides it)
 *   logos[]      — repeater
 *       └─ image — image  (required; the logo mark)
 *       └─ alt   — text   (translatable; optional alt override)
 *       └─ url   — text   (optional click-through)
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   spacing    — vertical padding: tight (default) | normal | loose
 *   align      — heading alignment: left | center (default) | right
 *   treatment  — logo treatment: grayscale (default) | color
 */

return [
    'label'       => 'Logos',
    'description' => 'Logo strip with a short intro line. Use for "trusted by" / partners / customers.',

    'fields' => [
        ['name' => 'heading', 'type' => 'textarea', 'label' => 'Intro line', 'translatable' => true, 'rows' => 2,
         'hint' => 'Short line above the logos. Blank hides it.'],

        [
            'name'      => 'logos',
            'type'      => 'repeater',
            'label'     => 'Logos',
            'add_label' => 'Add logo',
            'hint'      => 'Logos display in a single horizontal row. Wraps on narrow screens.',
            'fields'    => [
                ['name' => 'image', 'type' => 'image', 'label' => 'Logo image', 'required' => true],
                ['name' => 'alt',   'type' => 'text',  'label' => 'Alt text override', 'translatable' => true, 'width' => 'half',
                 'hint' => 'Optional. Falls back to the image\'s WP alt text.'],
                ['name' => 'url',   'type' => 'text',  'label' => 'Link URL (optional)', 'width' => 'half',
                 'hint' => 'Blank renders an <img>, set renders an <a> wrapping the image.'],
            ],
        ],
    ],

    'modifiers' => [
        [
            'name'    => 'spacing',
            'type'    => 'select',
            'label'   => 'Vertical spacing',
            'options' => ['tight' => 'Tight', 'normal' => 'Normal', 'loose' => 'Loose'],
            'default' => 'tight',
        ],
        [
            'name'    => 'align',
            'type'    => 'select',
            'label'   => 'Heading alignment',
            'options' => ['left' => 'Left', 'center' => 'Center', 'right' => 'Right'],
            'default' => 'center',
        ],
        [
            'name'    => 'treatment',
            'type'    => 'select',
            'label'   => 'Logo treatment',
            'options' => ['grayscale' => 'Grayscale', 'color' => 'Color'],
            'default' => 'grayscale',
        ],
    ],
];
