<?php

return [
    'general' => [
        'app_name' => [
            'type' => 'string',
            'default' => 'FitByShot',
        ],
        'brand_description' => [
            'type' => 'string',
            'default' => 'Prescription Weight Loss, Longevity, and Wellness specific to your goals.',
        ],
        'logo' => [
            'type' => 'file',
            'default' => 'defaults/logo.png',
        ],
        'favicon' => [
            'type' => 'file',
            'default' => 'defaults/favicon.png',
        ],
    ],

    'contact' => [
        'contact_email' => [
            'type' => 'string',
            'default' => 'support@fitbyshot.com',
        ],
        'support_phone' => [
            'type' => 'string',
            'default' => '',
        ],
        'contact_address' => [
            'type' => 'string',
            'default' => '',
        ],
    ],

    'social' => [
        'instagram_url' => [
            'type' => 'string',
            'default' => '',
        ],
        'facebook_url' => [
            'type' => 'string',
            'default' => '',
        ],
        'twitter_url' => [
            'type' => 'string',
            'default' => '',
        ],
    ],
];
