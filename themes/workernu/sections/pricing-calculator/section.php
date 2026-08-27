<?php
return [
    'label'       => 'Pricing Calculator',
    'description' => 'Pick a base plan (radio) + optional addons (checkboxes) × worker count = live price.',

    'fields' => [
        ['name' => 'heading',    'type' => 'text',     'label' => 'Heading',    'translatable' => true],
        ['name' => 'subheading', 'type' => 'textarea', 'label' => 'Subheading', 'translatable' => true, 'rows' => 2],

        ['name' => 'plans_label', 'type' => 'text', 'label' => 'Plans step label', 'translatable' => true,
         'hint' => 'e.g. "1. Pasirinkite pagrindinį planą". Shown above the plan cards.'],
        ['name' => 'selected_plan_prefix', 'type' => 'text', 'label' => 'Selected plan prefix text', 'translatable' => true,
         'hint' => 'e.g. "Pagrindinis planas:" — shown before the selected plan name and price.'],

        /* ── Plans ── */
        [
            'name'      => 'plans',
            'type'      => 'repeater',
            'label'     => 'Base plans',
            'add_label' => 'Add plan',
            'fields'    => [
                ['name' => 'name',        'type' => 'text',   'label' => 'Plan name',                         'translatable' => true, 'required' => true],
                ['name' => 'description', 'type' => 'text',   'label' => 'Short description',                 'translatable' => true],
                ['name' => 'icon',        'type' => 'icon',   'label' => 'Icon (FA class)'],
                ['name' => 'price',       'type' => 'number', 'label' => 'Base price per worker / month',     'min' => 0, 'step' => 0.01, 'required' => true],
                ['name' => 'addon_price', 'type' => 'number', 'label' => 'Addon price per worker / month',    'min' => 0, 'step' => 0.01,
                 'hint' => 'Price when added on top of another plan. Leave 0 if this plan cannot be combined.'],
            ],
        ],

        /* ── Addons ── */
        ['name' => 'addons_label', 'type' => 'text', 'label' => 'Addons section heading', 'translatable' => true,
         'hint' => 'e.g. "Papildiniai". Leave blank to hide the heading.'],
        [
            'name'      => 'addons',
            'type'      => 'repeater',
            'label'     => 'Addons',
            'add_label' => 'Add addon',
            'fields'    => [
                ['name' => 'name',        'type' => 'text',   'label' => 'Addon name',               'translatable' => true, 'required' => true],
                ['name' => 'description', 'type' => 'text',   'label' => 'Short description',        'translatable' => true],
                ['name' => 'icon',        'type' => 'icon',   'label' => 'Icon (FA class)'],
                ['name' => 'price',       'type' => 'number', 'label' => 'Price per worker / month', 'min' => 0, 'step' => 0.01],
                ['name' => 'plans',       'type' => 'text',   'label' => 'Show on plans (numbers)',
                 'hint' => 'Comma-separated plan numbers, e.g. "1,2". Leave blank to show on all plans.'],
            ],
        ],

        /* ── Workers ── */
        ['name' => 'workers_label',   'type' => 'text',   'label' => 'Workers label',   'translatable' => true],
        ['name' => 'workers_min',     'type' => 'number', 'label' => 'Workers min',     'min' => 1, 'default' => 1,   'width' => 'third'],
        ['name' => 'workers_max',     'type' => 'number', 'label' => 'Workers max',     'min' => 1, 'default' => 500, 'width' => 'third'],
        ['name' => 'workers_default', 'type' => 'number', 'label' => 'Workers default', 'min' => 1, 'default' => 5,   'width' => 'third'],

        /* ── Display ── */
        ['name' => 'currency',          'type' => 'text', 'label' => 'Currency symbol',       'default' => '€',              'width' => 'half'],
        ['name' => 'period_label',      'type' => 'text', 'label' => 'Period label',           'translatable' => true,        'width' => 'half',
         'hint' => 'e.g. "/ darb. / mėn."'],
        ['name' => 'summary_title',     'type' => 'text', 'label' => 'Summary title',         'translatable' => true,
         'hint' => 'e.g. "Mėnesio mokestis"'],
        ['name' => 'summary_per_label', 'type' => 'text', 'label' => 'Per-worker row label',  'translatable' => true,
         'hint' => 'e.g. "Kaina vienam darbuotojui"'],
        ['name' => 'placeholder_label', 'type' => 'text', 'label' => 'No-plan-selected text', 'translatable' => true,
         'hint' => 'e.g. "Pasirinkite planą"'],

        /* ── CTA ── */
        ['name' => 'cta_label', 'type' => 'text', 'label' => 'CTA label', 'translatable' => true, 'width' => 'half'],
        ['name' => 'cta_url',   'type' => 'url',  'label' => 'CTA URL',                          'width' => 'half'],
        ['name' => 'cta_note',  'type' => 'text', 'label' => 'CTA disclaimer note', 'translatable' => true],
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
