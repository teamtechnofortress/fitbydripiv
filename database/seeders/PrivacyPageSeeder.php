<?php

namespace Database\Seeders;

use App\enums\SectionType;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class PrivacyPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'privacy'],
            [
                'title' => 'Privacy Policy',
                'status' => 'published',
                'meta_title' => 'FitByShot | Privacy Policy',
                'meta_description' => 'Learn how FitByShot collects, uses, stores, and protects your personal and health-related information.',
            ]
        );

        $this->upsertSection($page, [
            'section_key' => 'privacy_header',
            'type' => SectionType::SECTION_HEADER->value,
            'title' => 'Privacy Header',
            'subtitle' => 'Top banner for privacy page',
            'content' => [
                'headline' => 'Privacy Policy',
                'description' => 'How we collect, use, and protect your information',
                'alignment' => 'center',
                'spacing' => 'comfortable',
            ],
            'sort_order' => 1,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'privacy_top_spacer',
            'type' => SectionType::SPACER->value,
            'title' => 'Privacy Top Spacer',
            'subtitle' => 'Spacing between header and content',
            'content' => [
                'height' => 56,
                'desktop' => 56,
                'tablet' => 48,
                'mobile' => 32,
            ],
            'sort_order' => 2,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'privacy_information_sharing',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Introduction',
            'subtitle' => '',
            'content' => [
                'headline' => 'Introduction',
                'paragraphs' => [
                    'At FitByShot, we take your privacy seriously. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our services.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 3,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'privacy_information_collect',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Information We Collect',
            'subtitle' => '',
            'content' => [
                'headline' => 'Information We Collect',
                'intro' => 'We collect information that you provide directly to us, including:',
                'bullets' => [
                    'Personal information (name, email, phone number)',
                    'Health information relevant to your treatment',
                    'Payment and billing information',
                    'Communication preferences',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 4,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'privacy_how_we_use',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'How We Use Your Information',
            'subtitle' => '',
            'content' => [
                'headline' => 'How We Use Your Information',
                'intro' => 'We use your information to:',
                'bullets' => [
                    'Provide and improve our services',
                    'Process your orders and payments',
                    'Communicate with you about your treatment',
                    'Comply with legal obligations',
                    'Ensure the security of our services',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 5,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'privacy_security',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Data Security',
            'subtitle' => '',
            'content' => [
                'headline' => 'Data Security',
                'paragraphs' => [
                    'We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 6,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'privacy_choices',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Your Rights',
            'subtitle' => '',
            'content' => [
                'headline' => 'Your Rights',
                'paragraphs' => [
                    'You have the right to access, update, or delete your personal information. Contact us to exercise these rights.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 7,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'privacy_contact',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Contact Us',
            'subtitle' => '',
            'content' => [
                'headline' => 'Contact Us',
                'paragraphs' => [
                    'If you have questions about this Privacy Policy, please contact us at Support@FitbyShot.com.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 8,
        ]);
    }

    protected function upsertSection(Page $page, array $attributes): PageSection
    {
        return PageSection::updateOrCreate(
            [
                'page_id' => $page->id,
                'section_key' => $attributes['section_key'],
            ],
            [
                'type' => $attributes['type'],
                'title' => $attributes['title'] ?? null,
                'subtitle' => $attributes['subtitle'] ?? null,
                'content' => $attributes['content'] ?? null,
                'image' => $attributes['image'] ?? null,
                'sort_order' => $attributes['sort_order'] ?? 0,
            ]
        );
    }
}
