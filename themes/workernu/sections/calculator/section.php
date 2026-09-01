<?php
/**
 * Savings Calculator — savings calculator with sliders.
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
 * Computation model (in animations.js — mirrored server-side in template.php):
 *   spend          = employees * spend_rate_employee
 *   monthlySavings = employees * savings_rate_employee_monthly + projects * savings_rate_project_monthly
 *   yearlySavings  = employees * savings_rate_employee_yearly  + projects * savings_rate_project_yearly
 *
 * yearlySavings is NOT monthlySavings * 12 — it's an independently fit linear
 * function (2026-09 correction: the old model computed spend from BOTH
 * employees and projects, then derived monthly savings as a flat percentage
 * of that spend, then derived yearly as monthly * 12. None of those three
 * assumptions held against real reference data: spend depends only on
 * employees, monthly/yearly savings both depend on employees AND projects
 * with their own independent per-unit rates, and yearly isn't a fixed
 * multiple of monthly. Default rates below are fit to that reference data —
 * (250·E + 284·P)/15 monthly and (608·E + 672·P)/3 yearly).
 *
 * Field map for the frontend dev:
 *   heading              — text (translatable)
 *   subheading           — textarea (translatable)
 *   employees_label      — text (translatable; slider 1 caption)
 *   employees_min/max/default — number (slider 1 range)
 *   projects_label       — text (translatable; slider 2 caption)
 *   projects_min/max/default  — number (slider 2 range)
 *   spend_rate_employee            — number (€ spend per employee / month)
 *   savings_rate_employee_monthly  — number (€ monthly savings per employee)
 *   savings_rate_project_monthly   — number (€ monthly savings per project)
 *   savings_rate_employee_yearly   — number (€ yearly savings per employee)
 *   savings_rate_project_yearly    — number (€ yearly savings per project)
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
    'label'            => 'Savings Calculator',
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

        ['name' => 'spend_rate_employee', 'type' => 'number', 'label' => 'Spend: € per employee / month', 'min' => 0, 'step' => 0.01, 'default' => 5, 'width' => 'half',
         'hint' => 'Current spend depends only on team size. Default 5.'],
        ['name' => 'currency',            'type' => 'text',   'label' => 'Currency symbol', 'width' => 'half', 'hint' => 'e.g. "€". Defaults to "€".'],

        ['name' => 'savings_rate_employee_monthly', 'type' => 'number', 'label' => 'Monthly savings: € per employee', 'min' => 0, 'step' => 0.0000001, 'default' => 16.6666667, 'width' => 'half'],
        ['name' => 'savings_rate_project_monthly',  'type' => 'number', 'label' => 'Monthly savings: € per project',  'min' => 0, 'step' => 0.0000001, 'default' => 18.9333333, 'width' => 'half'],
        ['name' => 'savings_rate_employee_yearly',  'type' => 'number', 'label' => 'Yearly savings: € per employee',  'min' => 0, 'step' => 0.0000001, 'default' => 202.6666667, 'width' => 'half'],
        ['name' => 'savings_rate_project_yearly',   'type' => 'number', 'label' => 'Yearly savings: € per project',   'min' => 0, 'step' => 0.0000001, 'default' => 224, 'width' => 'half',
         'hint' => 'Yearly savings is fit independently from monthly, not monthly × 12.'],

        ['name' => 'result_spend_label',   'type' => 'text', 'label' => 'Result label: current spend',   'translatable' => true],
        ['name' => 'result_savings_label', 'type' => 'text', 'label' => 'Result label: monthly savings', 'translatable' => true],
        ['name' => 'result_yearly_label',  'type' => 'text', 'label' => 'Result label: yearly savings',  'translatable' => true],

        ['name' => 'cta_label', 'type' => 'text', 'label' => 'CTA label', 'translatable' => true, 'width' => 'half'],
        ['name' => 'cta_url',   'type' => 'text', 'label' => 'CTA URL', 'width' => 'half'],
        ['name' => 'cta_icon',  'type' => 'boolean', 'label' => 'Play icon',
         'hint' => 'Adds a play-circle icon before the label, same color as the button text.'],
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
