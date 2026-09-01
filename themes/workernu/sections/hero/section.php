<?php
/**
 * Hero — the homepage hero section.
 *
 * Field map for the frontend dev (the $data array your template.php receives):
 *   eyebrow_icon          — icon       (FA class or raw <i>/<svg> HTML; blank hides icon)
 *   eyebrow_label         — text       (translatable; blank hides whole eyebrow if icon also blank)
 *   heading               — text       (translatable, required)
 *   body                  — rich_text  (translatable, required; editor picks paragraph | bullets | numbered)
 *   ctas[]                — repeater of buttons
 *       └─ label          — text       (translatable)
 *       └─ url            — text
 *       └─ variant        — select     (primary | outline | subtle | ghost — global .btn styles)
 *       └─ target         — select     (_self | _blank)
 *   Trustpilot:
 *     tp_mode               — modifier (none | trustpilot | image) — picks which
 *                              social-proof element renders in the hero's badge row.
 *     trustpilot_image      — image (only used when tp_mode=image)
 *     trustpilot_review_url — text  (only used when tp_mode=image; link wrap)
 *
 *   The live TrustBox widget (tp_mode=trustpilot) is configured globally at
 *   Settings → WorkerNu via the workernu-settings plugin — business-unit ID,
 *   template ID, locale, height, theme, and review URL all live there.
 *   users_avatars[]       — repeater of images (the overlapping avatar stack; ~4 photos)
 *       └─ image          — image      (attachment id, required)
 *   users_badge_label     — text       (translatable; text in the dark 5th circle, e.g. "2k+"; blank hides it)
 *   users_count_number    — text       (translatable; blank hides the count caption)
 *   users_count_label     — text       (translatable)
 *   image                 — image      (attachment id, required)
 *
 * Modifiers (rendered as BEM classes via workernu_section_classes()):
 *   layout    — image position: right (default) | left
 *   spacing   — vertical padding: tight | normal | loose
 *   tp_mode   — social-proof element: none | trustpilot (live widget; default)
 *               | image (static badge)
 */

return [
    'label'       => 'Hero',
    'description' => 'Homepage hero — badge, heading, body, CTAs, social proof, image.',

    'fields' => [
        ['name' => 'eyebrow_icon',       'type' => 'icon',     'label' => 'Eyebrow icon', 'width' => 'half',
         'hint' => 'Font Awesome class (e.g. "fa-solid fa-bolt") or full <i>/<svg> HTML. Blank hides the icon.'],
        ['name' => 'eyebrow_label',      'type' => 'text',     'label' => 'Eyebrow label', 'translatable' => true, 'width' => 'half',
         'hint' => 'Short tag above the heading. Blank hides the whole eyebrow when no icon either.'],

        ['name' => 'heading',            'type' => 'text',      'label' => 'Heading', 'translatable' => true, 'required' => true],
        ['name' => 'body',               'type' => 'rich_text', 'label' => 'Body',    'translatable' => true, 'required' => true, 'rows' => 3,
         'hint' => 'For bullet/numbered display, put each item on its own line.'],

        [
            'name'      => 'ctas',
            'type'      => 'repeater',
            'label'     => 'CTA buttons',
            'hint'      => 'Add up to 2 buttons.',
            'add_label' => 'Add CTA',
            'fields'    => [
                ['name' => 'label',   'type' => 'text',   'label' => 'Label',  'translatable' => true],
                ['name' => 'url',     'type' => 'text',   'label' => 'URL'],
                ['name' => 'variant', 'type' => 'select', 'label' => 'Style', 'render_as' => 'buttons',
                 'options' => ['primary' => 'Primary', 'outline' => 'Outline', 'subtle' => 'Subtle', 'ghost' => 'Ghost']],
                ['name' => 'target',  'type' => 'select', 'label' => 'Opens', 'render_as' => 'buttons',
                 'options' => ['_self' => 'Same tab', '_blank' => 'New tab']],
                ['name' => 'icon', 'type' => 'boolean', 'label' => 'Play icon',
                 'hint' => 'Adds a play-circle icon before the label, same color as the button text.'],
            ],
        ],

        // Social proof — the live Trustpilot widget config lives globally at
        // Settings → WorkerNu; per-section only the mode + (when in image
        // mode) the badge image and its link.
        [
            'name'      => 'tp_mode',
            'type'      => 'select',
            'render_as' => 'buttons',
            'label'     => 'Social proof',
            'hint'      => 'Trustpilot uses the global account at Settings → WorkerNu.',
            'options'   => [
                'none'       => 'None',
                'trustpilot' => 'Trustpilot',
                'image'      => 'Image',
            ],
            'default'   => 'trustpilot',
        ],
        ['name' => 'trustpilot_image',      'type' => 'image', 'label' => 'Social-proof image', 'width' => 'half',
         'show_if' => ['tp_mode' => 'image']],
        ['name' => 'trustpilot_review_url', 'type' => 'text',  'label' => 'Social-proof image link', 'width' => 'half',
         'show_if' => ['tp_mode' => 'image'],
         'hint'    => 'Optional. If set, the image becomes a link.'],

        // Social proof — overlapping avatar stack + editable dark badge + caption.
        [
            'name'      => 'users_avatars',
            'type'      => 'gallery',
            'label'     => 'Avatar stack',
            'add_label' => '+ Add photos',
            'hint'      => 'Pick all avatar photos in one go. They overlap left-to-right; the dark badge sits on the right.',
        ],
        ['name' => 'users_count_number', 'type' => 'text',  'label' => 'Users count: number',  'translatable' => true, 'width' => 'half',
         'hint' => 'e.g. "3,400+". Blank hides the count caption.'],
        ['name' => 'users_count_label',  'type' => 'text',  'label' => 'Users count: caption', 'translatable' => true, 'width' => 'half',
         'hint' => 'e.g. "Vartotojų" / "businesses already using workernu"'],

        ['name' => 'users_badge_label',  'type' => 'text',  'label' => 'Stack badge label', 'translatable' => true, 'width' => 'half',
         'hint' => 'Text in the dark 5th circle, e.g. "2k+". Blank hides the badge entirely.'],

        ['name' => 'image',              'type' => 'image', 'label' => 'Image', 'translatable' => true, 'required' => true,
         'hint' => 'Pick a separate image per language if the hero screenshot contains translatable UI text.'],
        ['name' => 'image_alt',          'type' => 'text',  'label' => 'Image alt text', 'translatable' => true,
         'hint' => 'Describes the hero image for screen readers and search engines. Falls back to the attachment\'s alt if blank.'],
    ],

    'modifiers' => [
        [
            'name'    => 'layout',
            'type'    => 'select',
            'label'   => 'Image position',
            'options' => ['right' => 'Right (default)', 'left' => 'Left (reversed)'],
            'default' => 'right',
        ],
    ],

    // No schema contribution — Hero alone doesn't mean "this is a product page."
    // SoftwareApplication is emitted by the Pricing section, which is what
    // actually makes a page a real product offering. See sections/pricing/section.php.
];
