<?php
/**
 * Feature Highlight — "why choose us" value-prop band.
 *
 * Homepage "Kodėl įmonės renkasi Workernu" block: an intro column (eyebrow,
 * heading, body, CTAs) beside a list of icon + title + description value props.
 * The `tone` modifier inverts the page tokens for a dark band.
 *
 * Field map for the frontend dev:
 *   eyebrow      — text (translatable; small label above heading; optional)
 *   heading      — text (translatable, required)
 *   body         — textarea (translatable; optional)
 *   ctas[]       — repeater of buttons
 *       └─ label    — text (translatable)
 *       └─ url      — text
 *       └─ variant  — select (primary | outline | subtle | ghost — global .btn styles)
 *       └─ target   — select (_self | _blank)
 *   items[]      — repeater of value props
 *       └─ icon            — icon (FA class or raw HTML; ignored if icon_image set)
 *       └─ icon_image      — image (alternative to FA — takes precedence; translatable)
 *       └─ icon_image_alt  — text (translatable; only shown when icon_image set)
 *       └─ title           — text (translatable, required)
 *       └─ description     — textarea (translatable; optional)
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   layout   — split (intro left, items right; default) | stacked
 *
 * `tone` is a GLOBAL modifier, not declared here: .section--tone-inverted in
 * main.css swaps the page palette and converts this section's margins into
 * padding. A `spacing` modifier was documented here too, but it was never
 * declared below and no stylesheet defines
 * .section--feature-highlight--spacing-* — the line was removed rather than
 * given CSS to match. Same call as the one made on the logos section.
 */

return [
    'label'       => 'Feature Highlight',
    'description' => 'Value-prop band: intro column + icon list. Supports an inverted (dark) tone.',

    'fields' => [
        ['name' => 'eyebrow', 'type' => 'text',     'label' => 'Eyebrow',  'translatable' => true,
         'hint' => 'Small label above the heading. Blank hides it.'],
        ['name' => 'heading', 'type' => 'text',     'label' => 'Heading',  'translatable' => true, 'required' => true],
        ['name' => 'body',    'type' => 'textarea', 'label' => 'Body',     'translatable' => true, 'rows' => 3],

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
                ['name' => 'icon', 'type' => 'boolean', 'label' => 'Play icon',
                 'hint' => 'Adds a play-circle icon before the label, same color as the button text.'],
            ],
        ],

        [
            'name'      => 'items',
            'type'      => 'repeater',
            'label'     => 'Value props',
            'add_label' => 'Add value prop',
            'fields'    => [
                ['name' => 'icon',        'type' => 'icon',  'label' => 'Icon (Font Awesome)', 'width' => 'half',
                 'hint' => 'FA class like "fa-solid fa-clock" or full <i>/<svg> HTML. Ignored if Icon image is set.'],
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
            'name'    => 'layout',
            'type'    => 'select',
            'label'   => 'Layout',
            'options' => ['split' => 'Split (intro + items)', 'stacked' => 'Stacked'],
            'default' => 'split',
        ],
    ],
];
