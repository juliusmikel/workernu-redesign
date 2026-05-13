<?php
/**
 * Example: Hero
 *
 * Reference section showing how to define content fields, display modifiers,
 * and (if you want) a JSON-LD schema callback. Copy this folder when scaffolding a new section.
 *
 * Field map for the frontend dev:
 *   badge_icon            — FA class string or full <i>/<svg> HTML (icon field type)
 *   badge_label           — text next to the icon
 *   heading               — the H1
 *   body                  — rich_text: paragraph | bullet list | numbered list (editor picks)
 *   ctas[]                — repeater of buttons (label, url, variant, target)
 *   users_count_number    — e.g. "10,000+"
 *   users_count_label     — e.g. "satisfied customers"
 *   image                 — main visual
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   layout    — image position: right (default) | left
 *   spacing   — vertical padding: tight | normal | loose
 *   align     — content alignment: left | center
 */

return [
    'label'       => 'Example: Hero',
    'description' => 'Reference section with badge, heading, subhead, CTAs, social proof, image. Copy this folder when adding new sections.',

    'fields' => [
        ['name' => 'badge_icon',          'type' => 'icon',     'label' => 'Badge icon',  'width' => 'half',
         'hint' => 'Font Awesome class or full <i> HTML. Blank hides icon.'],
        ['name' => 'badge_label',         'type' => 'text',     'label' => 'Badge label', 'translatable' => true, 'width' => 'half',
         'hint' => 'Blank hides the whole badge if no icon either.'],

        ['name' => 'heading',             'type' => 'text',      'label' => 'Heading',     'translatable' => true],
        ['name' => 'body',                'type' => 'rich_text', 'label' => 'Body',        'translatable' => true, 'rows' => 3,
         'hint' => 'For bullet/numbered display, put each item on its own line.'],

        [
            'name'      => 'ctas',
            'type'      => 'repeater',
            'label'     => 'CTA buttons',
            'hint'      => 'Add any number; rendered horizontally with flex.',
            'add_label' => 'Add CTA',
            'fields'    => [
                ['name' => 'label',   'type' => 'text',   'label' => 'Label',  'translatable' => true],
                ['name' => 'url',     'type' => 'text',   'label' => 'URL'],
                ['name' => 'variant', 'type' => 'select', 'label' => 'Style', 'render_as' => 'buttons',
                 'options' => ['primary' => 'Primary', 'secondary' => 'Secondary', 'ghost' => 'Ghost']],
                ['name' => 'target',  'type' => 'select', 'label' => 'Opens', 'render_as' => 'buttons',
                 'options' => ['_self' => 'Same tab', '_blank' => 'New tab']],
            ],
        ],

        ['name' => 'users_count_number',  'type' => 'text',  'label' => 'Users count: number',  'translatable' => true, 'width' => 'half',
         'hint' => 'e.g. "10,000+". Blank hides the block.'],
        ['name' => 'users_count_label',   'type' => 'text',  'label' => 'Users count: caption', 'translatable' => true, 'width' => 'half',
         'hint' => 'e.g. "satisfied customers"'],

        ['name' => 'image',               'type' => 'image', 'label' => 'Image'],
    ],

    'modifiers' => [
        [
            'name'    => 'layout',
            'type'    => 'select',
            'label'   => 'Image position',
            'options' => ['right' => 'Right (default)', 'left' => 'Left (reversed)'],
            'default' => 'right',
        ],
        [
            'name'    => 'spacing',
            'type'    => 'select',
            'label'   => 'Vertical spacing',
            'options' => ['tight' => 'Tight', 'normal' => 'Normal', 'loose' => 'Loose'],
            'default' => 'normal',
        ],
        [
            'name'    => 'align',
            'type'    => 'select',
            'label'   => 'Content alignment',
            'options' => ['left' => 'Left', 'center' => 'Center'],
            'default' => 'left',
        ],
    ],
];
