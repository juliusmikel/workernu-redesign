<?php
/**
 * Global modifiers — appended to every section's modifier list via the
 * `workernu_section_definition` filter, so they live on the section element
 * without each section.php having to re-declare them.
 *
 * Each entry uses the `'global' => true` flag, which workernu_section_classes
 * picks up and emits as `section--<modifier>-<value>` (no slug prefix) — so a
 * single CSS rule in the theme styles every section using the modifier.
 *
 * Current globals:
 *   margin_top    — 0 | 2 | 4 | 6 (rem) — outer top margin between sections
 *   margin_bottom — same, for the bottom
 */
namespace WorkerNu\Sections\GlobalModifiers;

if (!defined('ABSPATH')) exit;

const MARGIN_OPTIONS = [
    '0' => 'None',
    '2' => 'Little',
    '4' => 'Medium',
    '6' => 'A lot',
];

function definitions(): array {
    return [
        [
            'name'      => 'margin_top',
            'type'      => 'select',
            'label'     => 'Margin top',
            'render_as' => 'buttons',
            'options'   => MARGIN_OPTIONS,
            'default'   => '4',
            'global'    => true,
        ],
        [
            'name'      => 'margin_bottom',
            'type'      => 'select',
            'label'     => 'Margin bottom',
            'render_as' => 'buttons',
            'options'   => MARGIN_OPTIONS,
            'default'   => '4',
            'global'    => true,
        ],
        [
            'name'      => 'tone',
            'type'      => 'select',
            'label'     => 'Tone',
            'hint'      => 'Inverted swaps the page palette (dark bg / light text). Margins convert to padding so the dark fill extends through the spacing.',
            'render_as' => 'buttons',
            'options'   => [
                'default'  => 'Default',
                'inverted' => 'Inverted',
            ],
            'default'   => 'default',
            'global'    => true,
        ],
    ];
}

function inject(array $config, string $slug): array {
    $globals = definitions();

    // Place global modifiers AFTER any section-specific ones so the
    // section's own controls stay visually at the top of the modifier list.
    $config['modifiers'] = array_merge(
        $config['modifiers'] ?? [],
        $globals
    );

    return $config;
}

add_filter('workernu_section_definition', __NAMESPACE__ . '\\inject', 10, 2);
