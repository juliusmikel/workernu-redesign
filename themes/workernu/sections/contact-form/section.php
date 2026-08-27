<?php
/**
 * Contact Form — two-column: contact info left, form right.
 */

return [
    'label'       => 'Contact Form',
    'description' => 'Two-column layout: contact info on the left, enquiry form on the right.',

    'fields' => [
        ['name' => 'heading',    'type' => 'textarea', 'label' => 'Heading',    'translatable' => true, 'rows' => 2,
         'hint' => 'Multi-line. Each line break becomes a new line in the big blue heading.'],
        ['name' => 'subheading', 'type' => 'text',     'label' => 'Subheading', 'translatable' => true],

        [
            'name'      => 'contacts',
            'type'      => 'repeater',
            'label'     => 'Contact blocks',
            'add_label' => 'Add block',
            'fields'    => [
                ['name' => 'label',     'type' => 'text', 'label' => 'Label (small, e.g. El. paštas)',  'translatable' => true],
                ['name' => 'value',     'type' => 'text', 'label' => 'Value (bold)',                    'translatable' => true],
                ['name' => 'value_url', 'type' => 'url',  'label' => 'Link URL (optional)'],
                ['name' => 'note',      'type' => 'text', 'label' => 'Note (smaller text below value)', 'translatable' => true],
            ],
        ],

        ['name' => 'response_time', 'type' => 'text', 'label' => 'Response time note', 'translatable' => true,
         'hint' => 'Shown with a green dot at the bottom of the left column.'],

        ['name' => 'reason_options', 'type' => 'textarea', 'label' => 'Reason for message — options', 'translatable' => true, 'rows' => 3,
         'hint' => 'One option per line (e.g. "Prezentacija", "Kainodara", "Bendri klausimai"). Blank hides the reason select from the form.'],

        ['name' => 'notify_email', 'type' => 'text', 'label' => 'Notify email',
         'hint' => 'Form submissions go here. Defaults to the WP admin email.'],

        ['name' => 'recaptcha_site_key',   'type' => 'text', 'label' => 'reCAPTCHA site key',   'width' => 'half',
         'hint' => 'Google reCAPTCHA v3 (invisible — no checkbox, scores the submission in the background). Blank disables the captcha.'],
        ['name' => 'recaptcha_secret_key', 'type' => 'text', 'label' => 'reCAPTCHA secret key', 'width' => 'half',
         'hint' => 'The matching v3 secret key — used server-side to verify the token and its score.'],

        ['name' => 'privacy_label', 'type' => 'text', 'label' => 'Consent checkbox label', 'translatable' => true,
         'hint' => 'HTML allowed. e.g. Sutinku su <a href="/privatumo-politika">privatumo politika</a>'],

        ['name' => 'submit_label', 'type' => 'text', 'label' => 'Submit button label', 'translatable' => true],

        ['name' => 'success_message', 'type' => 'text', 'label' => 'Success message', 'translatable' => true,
         'hint' => 'Shown after successful submission.'],
    ],

    'modifiers' => [
        [
            'name'    => 'spacing',
            'type'    => 'select',
            'label'   => 'Vertical spacing',
            'options' => ['tight' => 'Tight', 'normal' => 'Normal', 'loose' => 'Loose'],
            'default' => 'normal',
        ],
    ],
];
