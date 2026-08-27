<?php
/**
 * Calculator — savings calculator with sliders.
 *
 * Homepage "Kiek galite sutaupyti su Worker?" block: two sliders (team size,
 * projects/month) drive three live results (current spend, monthly savings,
 * yearly savings). All rates and labels are editor-configurable; the maths runs
 * client-side in animations.js.
 *
 * `content_defaults: true` — this section carries a `content_source` toggle
 * (custom | default, injected automatically by Registry\discover()). When set
 * to "Site default", its content comes from Settings → WorkerNu → Savings
 * Calculator instead of this instance's own fields — see
 * WorkerNu\Sections\Defaults\resolve(). Display modifiers (align, spacing)
 * always stay per-instance regardless of content_source.
 *
 * Computation model (in animations.js):
 *   monthlySpend   = employees * cost_per_employee + projects * cost_per_project
 *   monthlySavings = monthlySpend * (savings_rate / 100)
 *   yearlySavings  = monthlySavings * 12
 *
 * Field map for the frontend dev:
 *   heading              — text (translatable)
 *   subheading           — textarea (translatable)
 *   employees_label      — text (translatable; slider 1 caption)
 *   employees_min/max/default — number (slider 1 range)
 *   projects_label       — text (translatable; slider 2 caption)
 *   projects_min/max/default  — number (slider 2 range)
 *   cost_per_employee    — number (monthly cost per employee, pre-Worker)
 *   cost_per_project     — number (monthly cost per project, pre-Worker)
 *   savings_rate         — number (percent saved with Worker, e.g. 30)
 *   currency             — text (symbol, e.g. "€")
 *   result_spend_label   — text (translatable)
 *   result_savings_label — text (translatable)
 *   result_yearly_label  — text (translatable)
 *   cta_label / cta_url  — text (optional button)
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   spacing  — vertical padding: tight | normal (default) | loose
 *   align    — header alignment: left | center (default)
 */

return [
    'label'            => 'Calculator',
    'description'      => 'Savings calculator: two sliders drive live spend/savings results. Rates configurable.',
    'content_defaults' => true,

    'fields' => [
        ['name' => 'heading',    'type' => 'text',     'label' => 'Heading',    'translatable' => true],
        ['name' => 'subheading', 'type' => 'textarea', 'label' => 'Subheading', 'translatable' => true, 'rows' => 2],

        ['name' => 'employees_label',   'type' => 'text',   'label' => 'Slider 1 label (team size)', 'translatable' => true],
        ['name' => 'employees_min',     'type' => 'number', 'label' => 'Team size: min',     'min' => 1,  'width' => 'half'],
        ['name' => 'employees_max',     'type' => 'number', 'label' => 'Team size: max',     'min' => 1,  'width' => 'half'],
        ['name' => 'employees_default', 'type' => 'number', 'label' => 'Team size: default', 'min' => 1,  'width' => 'half'],

        ['name' => 'projects_label',    'type' => 'text',   'label' => 'Slider 2 label (projects/month)', 'translatable' => true],
        ['name' => 'projects_min',      'type' => 'number', 'label' => 'Projects: min',     'min' => 0,  'width' => 'half'],
        ['name' => 'projects_max',      'type' => 'number', 'label' => 'Projects: max',     'min' => 0,  'width' => 'half'],
        ['name' => 'projects_default',  'type' => 'number', 'label' => 'Projects: default', 'min' => 0,  'width' => 'half'],

        ['name' => 'cost_per_employee', 'type' => 'number', 'label' => 'Cost per employee / month', 'min' => 0, 'step' => 0.01, 'width' => 'half'],
        ['name' => 'cost_per_project',  'type' => 'number', 'label' => 'Cost per project / month',  'min' => 0, 'step' => 0.01, 'width' => 'half'],
        ['name' => 'savings_rate',      'type' => 'number', 'label' => 'Savings rate (%)', 'min' => 0, 'max' => 100, 'step' => 0.1, 'width' => 'half'],
        ['name' => 'currency',          'type' => 'text',   'label' => 'Currency symbol', 'width' => 'half', 'hint' => 'e.g. "€". Defaults to "€".'],

        ['name' => 'result_spend_label',   'type' => 'text', 'label' => 'Result label: current spend',   'translatable' => true],
        ['name' => 'result_savings_label', 'type' => 'text', 'label' => 'Result label: monthly savings', 'translatable' => true],
        ['name' => 'result_yearly_label',  'type' => 'text', 'label' => 'Result label: yearly savings',  'translatable' => true],

        ['name' => 'cta_label', 'type' => 'text', 'label' => 'CTA label', 'translatable' => true, 'width' => 'half'],
        ['name' => 'cta_url',   'type' => 'text', 'label' => 'CTA URL', 'width' => 'half'],
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
