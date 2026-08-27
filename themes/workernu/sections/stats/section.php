<?php
/**
 * Stats — a wide surface card with a heading on the left and 2–3 stat items
 * on the right (big number + label + small caption).
 *
 * Field map for the frontend dev:
 *   heading       — text (translatable, required; left-side title)
 *   stats[]       — repeater (2–3 stat items shown on the right)
 *       └─ number      — text (translatable; e.g. "30%", "4.5/5")
 *       └─ label       — text (translatable; e.g. "Mažiau administravimo")
 *       └─ caption     — text (translatable; small note, e.g. "26+ atsiliepimai")
 *
 * Modifiers:
 *   spacing — vertical padding: tight | normal (default) | loose
 *
 * Layout is driven by the stat count (auto-detected from the data, capped at 3):
 *   2 stats → heading 40% / stats wrapper 60% (each stat 50% of wrapper)
 *   3 stats → heading 30% / stats wrapper 70% (each stat 33.3% of wrapper)
 */

return [
    'label'       => 'Stats',
    'description' => 'White card with a heading + 2–3 stat items (big number, label, small caption).',

    'fields' => [
        ['name' => 'title',   'type' => 'text', 'label' => 'Section title',  'translatable' => true,
         'hint' => 'Large heading displayed above the card. Leave blank to hide.'],
        ['name' => 'heading', 'type' => 'text', 'label' => 'Card heading',   'translatable' => true, 'required' => true,
         'hint' => 'Bold text shown inside the card on the left, e.g. "Trusted by 300+ companies".'],

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

    'modifiers' => [],
];
