<?php
/**
 * FAQ — accordion of question/answer pairs.
 *
 * Field map for the frontend dev:
 *   heading      — text (translatable; optional)
 *   subheading   — textarea (translatable; optional)
 *   items[]      — repeater (one entry per question)
 *       └─ question  — text (translatable, required)
 *       └─ answer    — textarea (translatable, required)
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   align       — header alignment: left | center (default)
 *   layout      — accordion (default) | open (all answers visible at once)
 *   first_open  — whether the first item is expanded on load:
 *                 yes (default) | no
 *   columns     — 1 (default) | 2 (two columns on desktop; below 900px
 *                 always collapses to a single column, same as `columns` on
 *                 the icons/cards sections)
 *
 * Accessibility: built on native <details>/<summary>, so keyboard nav, screen
 * reader announcement, and graceful no-JS fallback all come for free.
 */

return [
    'label'       => 'FAQ',
    'description' => 'Accordion of question/answer pairs. Emits FAQPage schema for Google rich results.',

    'fields' => [
        ['name' => 'heading',    'type' => 'text',     'label' => 'Heading',    'translatable' => true],
        ['name' => 'subheading', 'type' => 'textarea', 'label' => 'Subheading', 'translatable' => true, 'rows' => 2],

        [
            'name'      => 'items',
            'type'      => 'repeater',
            'label'     => 'Questions',
            'add_label' => 'Add question',
            'fields'    => [
                ['name' => 'question', 'type' => 'text',     'label' => 'Question', 'translatable' => true, 'required' => true],
                ['name' => 'answer',   'type' => 'textarea', 'label' => 'Answer',   'translatable' => true, 'required' => true, 'rows' => 4],
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
            'name'    => 'layout',
            'type'    => 'select',
            'label'   => 'Layout',
            'hint'    => 'Accordion = click to expand. Open = all answers always visible.',
            'options' => ['accordion' => 'Accordion', 'open' => 'All open'],
            'default' => 'accordion',
        ],
        [
            'name'    => 'first_open',
            'type'    => 'select',
            'label'   => 'First item expanded',
            'options' => ['yes' => 'Yes', 'no' => 'No'],
            'default' => 'yes',
        ],
        [
            'name'    => 'columns',
            'type'    => 'select',
            'label'   => 'Columns',
            'render_as' => 'buttons',
            'hint'    => 'Two columns only applies at desktop widths; always a single column below 900px.',
            'options' => ['1' => 'One column', '2' => 'Two columns'],
            'default' => '1',
        ],
    ],

    /**
     * Schema.org — emit FAQPage with each item as a Question whose
     * acceptedAnswer is the editor-supplied text. Per Google's policy: the
     * answer text must match what's visible on the page (the template renders
     * the same data), so emission and DOM stay in sync.
     */
    'schema' => function (array $data): ?array {
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        if (!$items) return null;

        $questions = [];
        foreach ($items as $item) {
            $q = workernu_t($item['question'] ?? '');
            $a = workernu_t($item['answer']   ?? '');
            if ($q === '' || $a === '') continue;
            $questions[] = [
                '@type'          => 'Question',
                'name'           => (string) $q,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => (string) $a,
                ],
            ];
        }

        if (!$questions) return null;

        return [
            '@type'      => 'FAQPage',
            '@id'        => home_url('/#faq'),
            'mainEntity' => $questions,
        ];
    },
];
