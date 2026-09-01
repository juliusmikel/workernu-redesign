<?php
/**
 * CTA Band — closing call-to-action band.
 *
 * Homepage footer band ("Valdykite darbus paprasčiau jau šiandien"): a heading,
 * optional body, and CTA button(s), centered on a full-width band.
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
 *   align    — content alignment: left | center (default)
 *
 * `tone` is a GLOBAL modifier, not declared here (see
 * global-modifiers.php + .section--tone-inverted in main.css) — this
 * section used to declare its own local `tone` modifier too, which
 * duplicated the globally-injected one under the same field name. That
 * caused two "tone" radio groups sharing one form field name (the
 * browser silently collapsed them into a single group, so the visible
 * selection jumped to whichever rendered last on reload), and the
 * section's own now-removed `.section--cta-band--tone-inverted` CSS rule
 * (a crude fg/bg swap, no real dark palette) won the cascade over the
 * global rule's actual dark-palette token redefinition since it loaded
 * after main.css — so even the "working" control never applied real
 * inverted colors. Same call as the one already made on feature-highlight
 * and the other content_defaults sections — just belatedly applied here.
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
            'name'    => 'align',
            'type'    => 'select',
            'label'   => 'Content alignment',
            'options' => ['left' => 'Left', 'center' => 'Center'],
            'default' => 'center',
        ],
    ],
];
