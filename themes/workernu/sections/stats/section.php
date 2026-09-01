<?php
/**
 * Stats — 2–3 stat items (big number + label + small caption), in one of
 * two layouts (the `layout` modifier):
 *
 *   card  (default) — a wide surface card with a heading on its left and the
 *                      stats on its right, asymmetric width split. The
 *                      section title, if set, sits above the card.
 *   split            — title + subheading centered above, as their own
 *                      header block (matching the heading/subheading pattern
 *                      most other sections use); below that, the stats
 *                      alone fill a wide surface card as N equal columns —
 *                      no per-card heading, every stat styled identically.
 *
 * Field map for the frontend dev:
 *   title         — text (translatable; both layouts — the large heading)
 *   subheading    — textarea (translatable; split layout's subtext under
 *                   the title; ignored by the card layout)
 *   heading       — text (translatable; card layout's left-side text;
 *                   ignored by the split layout)
 *   stats[]       — repeater (2–3 stat items)
 *       └─ number      — text (translatable; e.g. "30%", "4.5/5")
 *       └─ label       — text (translatable; e.g. "Mažiau administravimo")
 *       └─ caption     — text (translatable; small note, e.g. "26+ atsiliepimai")
 *
 * card layout's width split is driven by the stat count (auto-detected from
 * the data, capped at 3): 2 stats → heading 40% / stats 60%; 3 stats →
 * heading 30% / stats 70%. split layout always divides the stats evenly,
 * regardless of count.
 */

return [
    'label'       => 'Stats',
    'description' => '2–3 stat items (big number, label, small caption) — as a card with a side heading, or a standalone block under its own title.',

    'fields' => [
        ['name' => 'title',      'type' => 'text',     'label' => 'Section title',  'translatable' => true,
         'hint' => 'Large heading. In the split layout this sits above the stats; in the card layout, above the card. Leave blank to hide.'],
        ['name' => 'subheading', 'type' => 'textarea', 'label' => 'Section subheading', 'translatable' => true, 'rows' => 2,
         'hint' => 'Split layout only — short subtext under the title.'],
        ['name' => 'heading',    'type' => 'text',     'label' => 'Card heading',   'translatable' => true,
         'hint' => 'Card layout only — bold text on the card\'s left side, e.g. "Trusted by 300+ companies".'],

        [
            'name'      => 'stats',
            'type'      => 'repeater',
            'label'     => 'Stat items',
            'add_label' => 'Add stat',
            'hint'      => 'Add 2 or 3 stats. Anything past the third is ignored.',
            'fields'    => [
                ['name' => 'number',  'type' => 'text', 'label' => 'Number',  'translatable' => true, 'required' => true, 'width' => 'half',
                 'hint' => 'e.g. "30%", "4.5/5", "300+"'],
                ['name' => 'label',   'type' => 'text', 'label' => 'Label',   'translatable' => true, 'required' => true, 'width' => 'half'],
                ['name' => 'caption', 'type' => 'text', 'label' => 'Caption', 'translatable' => true,
                 'hint' => 'Optional. Smaller note under the label.'],
            ],
        ],
    ],

    'modifiers' => [
        [
            'name'    => 'layout',
            'type'    => 'select',
            'label'   => 'Layout',
            'render_as' => 'buttons',
            'options' => ['card' => 'Heading inside card', 'split' => 'Heading above, stats own block'],
            'default' => 'card',
        ],
    ],
];
