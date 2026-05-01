<?php

return [
    'header' => [
        'type' => 'header',
        'config' => [
            'brand' => [
                'home_url' => '/',
            ],
            'layout' => [
                'show_menu_toggle' => true,
                'show_brand_centered' => true,
            ],
            'menu' => [
                [
                    'type' => 'link',
                    'label' => 'About',
                    'slug' => 'about',
                ],
                [
                    'type' => 'link',
                    'label' => 'FAQ',
                    'slug' => 'faq',
                ],
                [
                    'type' => 'link',
                    'label' => 'Contact',
                    'slug' => 'contact',
                ],
                [
                    'type' => 'group',
                    'label' => 'Products',
                    'source' => 'categories',
                    'items' => ['weight-loss', 'wellness', 'longevity'],
                ],
            ],
        ],
    ],

    'footer' => [
        'type' => 'footer',
        'config' => [
            'columns' => [
                [
                    'source' => 'brand',
                    'title' => 'FitByShot',
                ],
                [
                    'source' => 'categories',
                    'title' => 'Products',
                    'items' => ['weight-loss', 'wellness', 'longevity'],
                ],
                [
                    'source' => 'static_pages',
                    'title' => 'Company',
                    'items' => [
                        ['slug' => 'about', 'label' => 'About Us'],
                        ['slug' => 'contact', 'label' => 'Contact'],
                        // ['slug' => 'login', 'label' => 'Login'],
                    ],
                ],
                [
                    'source' => 'static_pages',
                    'title' => 'Legal',
                    'items' => [
                        ['slug' => 'privacy', 'label' => 'Privacy Policy'],
                        ['slug' => 'terms', 'label' => 'Terms of Service'],
                        ['slug' => 'legal', 'label' => 'Legal'],
                        ['slug' => 'instructions', 'label' => 'Instructions'],
                    ],
                ],
                [
                    'source' => 'research_links',
                    'title' => 'Research & Resources',
                    'items' => [
                        [
                            'label' => 'Frequently Asked Questions',
                            'href' => '/faq',
                            'external' => false,
                        ],
                        [
                            'title' => 'PubMed Research',
                            'label' => 'PubMed Research',
                            'article_url' => 'https://pubmed.ncbi.nlm.nih.gov/',
                            'href' => 'https://pubmed.ncbi.nlm.nih.gov/',
                            'external' => true,
                            'display_order' => 0,
                        ],
                        [
                            'title' => 'PMC Articles',
                            'label' => 'PMC Articles',
                            'article_url' => 'https://www.ncbi.nlm.nih.gov/pmc/',
                            'href' => 'https://www.ncbi.nlm.nih.gov/pmc/',
                            'external' => true,
                            'display_order' => 1,
                        ],
                        [
                            'title' => 'NEJM Journal',
                            'label' => 'NEJM Journal',
                            'article_url' => 'https://www.nejm.org/',
                            'href' => 'https://www.nejm.org/',
                            'external' => true,
                            'display_order' => 2,
                        ],
                    ],
                ],
                [
                    'source' => 'social_links',
                    'title' => 'Connect',
                    'items' => [
                        [
                            'label' => 'Facebook',
                            'href' => 'https://facebook.com/fitbyshot',
                            'icon' => 'facebook',
                            'external' => true,
                        ],
                        [
                            'label' => 'Instagram',
                            'href' => 'https://instagram.com/fitbyshot',
                            'icon' => 'instagram',
                            'external' => true,
                        ],
                        [
                            'label' => 'Twitter',
                            'href' => 'https://twitter.com/fitbyshot',
                            'icon' => 'twitter',
                            'external' => true,
                        ],
                        [
                            'label' => 'Email',
                            'href' => 'mailto:support@fitbyshot.com',
                            'icon' => 'mail',
                            'external' => false,
                        ],
                    ],
                ],
                [
                    'source' => 'certification',
                    'title' => 'Certification',
                    'items' => [
                        [
                            'image' => null,
                            'description' => 'Licensed and verified by...',
                        ],
                    ],
                ],
            ],
            'bottom' => [
                'copyright' => '© 2025-26 FitByShot. All Rights Reserved Worldwide.',
                'credit' => '',
            ],
        ],
    ],
];
