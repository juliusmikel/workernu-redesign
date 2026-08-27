<?php
/**
 * CTA Band — closing call-to-action band.
 *
 * Homepage footer band ("Valdykite darbus paprasčiau jau šiandien"): a heading,
 * optional body, and CTA button(s), centered on a full-width band. The `tone`
 * modifier inverts the page tokens for a dark band.
 *
 * Field map for the frontend dev:
 *   heading   — text (translatable, required)
 *   body      — textarea (translatable; optional)
 *   ctas[]    — repeater of buttons
 *       └─ label    — text (translatable)
 *       └─ url      — text
 *       └─ variant  — select (primary | outline | subtle | ghost — global .btn styles)
 *       └─ target   — select (_self | _blank)
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   tone     — color treatment: default | inverted (dark band)
 *   spacing  — vertical padding: tight | normal (default) | loose
 *   align    — content alignment: left | center (default)
 */

return [
    'label'       => 'CTA Band',
    'description' => 'Closing call-to-action band with heading, body, and buttons. Supports an inverted (dark) tone.',

    'fields' => [
        ['name' => 'heading', 'type' => 'text',     'label' => 'Heading', 'translatable' => true, 'required' => true],
        ['name' => 'body',    'type' => 'textarea', 'label' => 'Body',    'translatable' => true, 'rows' => 2],

        [
            'name'      => 'ctas',
            'type'      => 'repeater',
            'label'     => 'CTA buttons',
            'add_label' => 'Add CTA',
            'fields'    => [
                ['name' => 'label',   'type' => 'text',   'label' => 'Label',  'translatable' => true],
                ['name' => 'url',     'type' => 'text',   'label' => 'URL'],
                ['name' => 'variant', 'type' => 'select', 'label' => 'Style', 'render_as' => 'buttons',
                 'options' => ['primary' => 'Primary', 'outline' => 'Outline', 'subtle' => 'Subtle', 'ghost' => 'Ghost']],
                ['name' => 'target',  'type' => 'select', 'label' => 'Opens', 'render_as' => 'buttons',
                 'options' => ['_self' => 'Same tab', '_blank' => 'New tab']],
            ],
        ],
    ],

    'modifiers' => [
        [
            'name'    => 'tone',
            'type'    => 'select',
            'label'   => 'Color tone',
            'options' => ['default' => 'Default', 'inverted' => 'Inverted (dark)'],
            'default' => 'inverted',
        ],
[
            'name'    => 'align',
            'type'    => 'select',
            'label'   => 'Content alignment',
            'options' => ['left' => 'Left', 'center' => 'Center'],
            'default' => 'center',
        ],
    ],
];
