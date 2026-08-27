<?php
/**
 * People — contact / team cards.
 *
 * Homepage "Turite klausimų?" block: a heading and body over a row of person
 * cards (avatar, name, role, contact links) plus an optional CTA.
 *
 * `content_defaults: true` — this section carries a `content_source` toggle
 * (custom | default, injected automatically by Registry\discover()). When set
 * to "Site default", people come from Settings → WorkerNu → People instead
 * of this instance's own fields — see WorkerNu\Sections\Defaults\resolve().
 * Display modifiers (align, layout) always stay per-instance.
 *
 * Field map for the frontend dev:
 *   heading      — text (translatable; required)
 *   body         — textarea (translatable; optional)
 *   people[]     — repeater
 *       └─ avatar     — image (headshot; not translatable — photos are universal)
 *       └─ avatar_alt — text (translatable; only shown when avatar set; falls
 *                          back to name if blank)
 *       └─ name       — text (not translatable)
 *       └─ role       — text (translatable; optional)
 *       └─ email  — text (optional; renders a mailto link)
 *       └─ phone  — text (optional; renders a tel link)
 *   cta_label    — text (translatable; optional)
 *   cta_url      — text
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   spacing  — vertical padding: tight | normal (default) | loose
 *   align    — header alignment: left | center (default)
 *   columns  — person card columns: 2 (default) | 3
 */

return [
    'label'            => 'People',
    'description'      => 'Contact / team cards with avatar, name, role, and contact links.',
    'content_defaults' => true,

    'fields' => [
        ['name' => 'heading', 'type' => 'text',     'label' => 'Heading', 'translatable' => true, 'required' => true],
        ['name' => 'body',    'type' => 'textarea', 'label' => 'Body',    'translatable' => true, 'rows' => 2],

        [
            'name'      => 'people',
            'type'      => 'repeater',
            'label'     => 'People',
            'add_label' => 'Add person',
            'fields'    => [
                ['name' => 'avatar',     'type' => 'image', 'label' => 'Avatar', 'width' => 'half'],
                ['name' => 'avatar_alt', 'type' => 'text',  'label' => 'Avatar alt text', 'translatable' => true,
                 'show_if_not_empty' => 'avatar',
                 'hint' => 'Describes the avatar for screen readers and search engines. Falls back to the person\'s name if blank.'],
                ['name' => 'name',       'type' => 'text',  'label' => 'Name', 'required' => true, 'width' => 'half'],
                ['name' => 'role',   'type' => 'text',  'label' => 'Role', 'translatable' => true],
                ['name' => 'email',  'type' => 'text',  'label' => 'Email', 'width' => 'half'],
                ['name' => 'phone',  'type' => 'text',  'label' => 'Phone', 'width' => 'half'],
            ],
        ],

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
        [
            'name'    => 'layout',
            'type'    => 'select',
            'label'   => 'Layout',
            'options' => [
                'row' => 'Single row (horizontal scroll)',
                '2'   => '2 columns',
                '3'   => '3 columns',
                '4'   => '4 columns',
            ],
            'default' => '3',
        ],
    ],

    /**
     * Schema.org — each person becomes a Person with `worksFor` → Organization
     * (the Organization is declared globally by workernu-seo).
     */
    'schema' => function (array $data): ?array {
        $people = is_array($data['people'] ?? null) ? $data['people'] : [];
        if (!$people) return null;
        $org_id = home_url('/#organization');
        $entries = [];

        foreach ($people as $person) {
            $name  = (string) ($person['name']  ?? '');
            if ($name === '') continue;
            $role  = workernu_t($person['role']  ?? '');
            $email = trim((string) ($person['email'] ?? ''));
            $phone = trim((string) ($person['phone'] ?? ''));
            $avatar_id = (int) ($person['avatar'] ?? 0);

            $entry = [
                '@type'    => 'Person',
                'name'     => $name,
                'worksFor' => ['@id' => $org_id],
            ];
            if ($role !== '')  $entry['jobTitle']  = $role;
            if ($email !== '') $entry['email']     = $email;
            if ($phone !== '') $entry['telephone'] = $phone;
            if ($avatar_id) {
                $url = wp_get_attachment_image_url($avatar_id, 'medium');
                if ($url) $entry['image'] = $url;
            }
            $entries[] = $entry;
        }

        return $entries ?: null;
    },
];
