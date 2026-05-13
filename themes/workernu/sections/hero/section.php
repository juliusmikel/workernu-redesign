<?php
/**
 * Hero — the homepage hero section.
 *
 * Field map for the frontend dev (the $data array your template.php receives):
 *   badge_icon            — icon       (FA class or raw <i>/<svg> HTML; blank hides icon)
 *   badge_label           — text       (translatable; blank hides whole badge if icon also blank)
 *   heading               — text       (translatable, required)
 *   body                  — rich_text  (translatable, required; editor picks paragraph | bullets | numbered)
 *   ctas[]                — repeater of buttons
 *       └─ label          — text       (translatable)
 *       └─ url            — text
 *       └─ variant        — select     (primary | secondary | ghost)
 *       └─ target         — select     (_self | _blank)
 *   users_count_number    — text       (translatable; blank hides the whole social-proof line)
 *   users_count_label     — text       (translatable)
 *   image                 — image      (attachment id, required)
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   layout    — image position: right (default) | left
 *   spacing   — vertical padding: tight | normal | loose
 */

return [
    'label'       => 'Hero',
    'description' => 'Homepage hero — badge, heading, body, CTAs, social proof, image.',

    'fields' => [
        ['name' => 'badge_icon',         'type' => 'icon',     'label' => 'Badge icon', 'width' => 'half',
         'hint' => 'Font Awesome class (e.g. "fa-solid fa-bolt") or full <i>/<svg> HTML. Blank hides the icon.'],
        ['name' => 'badge_label',        'type' => 'text',     'label' => 'Badge label', 'translatable' => true, 'width' => 'half',
         'hint' => 'Short tag above the heading. Blank hides the whole badge when no icon either.'],

        ['name' => 'heading',            'type' => 'text',      'label' => 'Heading', 'translatable' => true, 'required' => true],
        ['name' => 'body',               'type' => 'rich_text', 'label' => 'Body',    'translatable' => true, 'required' => true, 'rows' => 3,
         'hint' => 'For bullet/numbered display, put each item on its own line.'],

        [
            'name'      => 'ctas',
            'type'      => 'repeater',
            'label'     => 'CTA buttons',
            'hint'      => 'Add up to 2 buttons.',
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

        ['name' => 'users_count_number', 'type' => 'text',  'label' => 'Users count: number',  'translatable' => true, 'width' => 'half',
         'hint' => 'e.g. "10,000+". Blank hides the whole social-proof line.'],
        ['name' => 'users_count_label',  'type' => 'text',  'label' => 'Users count: caption', 'translatable' => true, 'width' => 'half',
         'hint' => 'e.g. "businesses already using workernu"'],

        ['name' => 'image',              'type' => 'image', 'label' => 'Image', 'required' => true],
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
    ],
];
