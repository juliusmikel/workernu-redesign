<?php
/**
 * Tabs — tabbed feature showcase.
 *
 * Homepage "Sprendimai kiekvienam komandos nariui" block: a row of tab buttons
 * that swap between panels, each panel pairing a screenshot with a heading,
 * body, and optional CTA.
 *
 * Field map for the frontend dev:
 *   heading        — text (translatable; optional)
 *   subheading     — textarea (translatable; optional)
 *   tabs[]         — repeater (one entry per tab)
 *       └─ tab_label     — text (translatable, required; the button text)
 *       └─ panel_heading — text (translatable)
 *       └─ panel_body    — rich_text (translatable; paragraph | bullets | numbered)
 *       └─ cta_label     — text (translatable; optional — first CTA button)
 *       └─ cta_url       — text
 *       └─ cta2_label    — text (translatable; optional — second CTA button)
 *       └─ cta2_url      — text
 *       └─ image         — image (translatable; the panel screenshot)
 *       └─ image_alt     — text (translatable; only shown when image set)
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   spacing         — vertical padding: tight | normal (default) | loose
 *   align           — header alignment: left | center (default)
 *   media_position  — panel image side: right (default) | left
 */

return [
    'label'       => 'Tabs',
    'description' => 'Tabbed feature showcase: tab buttons swap a screenshot + text panel.',

    'fields' => [
        ['name' => 'heading',    'type' => 'text',     'label' => 'Heading',    'translatable' => true],
        ['name' => 'subheading', 'type' => 'textarea', 'label' => 'Subheading', 'translatable' => true, 'rows' => 2],

        [
            'name'      => 'tabs',
            'type'      => 'repeater',
            'label'     => 'Tabs',
            'add_label' => 'Add tab',
            'hint'      => 'Each entry becomes a tab button and its panel. First tab is shown by default.',
            'fields'    => [
                ['name' => 'tab_label',     'type' => 'text',      'label' => 'Tab label',     'translatable' => true, 'required' => true],
                ['name' => 'panel_heading', 'type' => 'text',      'label' => 'Panel heading', 'translatable' => true],
                ['name' => 'panel_body',    'type' => 'rich_text', 'label' => 'Panel body',    'translatable' => true, 'rows' => 3,
                 'hint' => 'For bullet/numbered display, put each item on its own line.'],
                ['name' => 'cta_label',     'type' => 'text',      'label' => 'CTA 1 label',   'translatable' => true, 'width' => 'half'],
                ['name' => 'cta_url',       'type' => 'text',      'label' => 'CTA 1 URL',     'width' => 'half'],
                ['name' => 'cta_icon',      'type' => 'boolean',   'label' => 'CTA 1 play icon',
                 'hint' => 'Adds a play-circle icon before the label, same color as the button text.'],
                ['name' => 'cta2_label',    'type' => 'text',      'label' => 'CTA 2 label',   'translatable' => true, 'width' => 'half'],
                ['name' => 'cta2_url',      'type' => 'text',      'label' => 'CTA 2 URL',     'width' => 'half'],
                ['name' => 'cta2_icon',     'type' => 'boolean',   'label' => 'CTA 2 play icon',
                 'hint' => 'Adds a play-circle icon before the label, same color as the button text.'],
                ['name' => 'image',         'type' => 'image',     'label' => 'Panel image',   'translatable' => true,
                 'hint' => 'Pick a separate image per language if the screenshot contains translatable UI text.'],
                ['name' => 'image_alt',     'type' => 'text',      'label' => 'Panel image alt text', 'translatable' => true,
                 'show_if_not_empty' => 'image',
                 'hint' => 'Describes the screenshot for screen readers and search engines. Falls back to the attachment\'s alt if blank.'],
            ],
        ],
    ],

    'modifiers' => [
        [
            'name'    => 'align',
            'type'    => 'select',
            'label'   => 'Header alignment',
            'options' => ['left' => 'Left', 'center' => 'Center'],
            'default' => 'center',
        ],
        [
            'name'    => 'media_position',
            'type'    => 'select',
            'label'   => 'Panel image side',
            'options' => ['right' => 'Right', 'left' => 'Left'],
            'default' => 'right',
        ],
    ],
];
