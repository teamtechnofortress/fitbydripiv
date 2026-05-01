<?php

namespace Database\Seeders;

use App\enums\SectionType;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class TermsPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'terms'],
            [
                'title' => 'Terms of Service',
                'status' => 'published',
                'meta_title' => 'FitByShot | Terms of Service',
                'meta_description' => 'Review the terms and conditions governing your use of FitByShot services, content, telehealth workflows, and product access.',
            ]
        );

        $this->upsertSection($page, [
            'section_key' => 'terms_header',
            'type' => SectionType::SECTION_HEADER->value,
            'title' => 'Terms Header',
            'subtitle' => 'Top banner for terms page',
            'content' => [
                'headline' => 'Terms of Service',
                'description' => 'The rules, responsibilities, and conditions for using FitByShot services',
                'alignment' => 'center',
                'spacing' => 'comfortable',
            ],
            'sort_order' => 1,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'terms_top_spacer',
            'type' => SectionType::SPACER->value,
            'title' => 'Terms Top Spacer',
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
            'section_key' => 'terms_acceptance',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Acceptance of Terms',
            'subtitle' => '',
            'content' => [
                'headline' => 'Acceptance of Terms',
                'paragraphs' => [
                    "By accessing and using FitByShot's services, you agree to be bound by these Terms of Service and all applicable laws and regulations.",
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 3,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'terms_services_scope',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Medical Disclaimer',
            'subtitle' => '',
            'content' => [
                'headline' => 'Medical Disclaimer',
                'paragraphs' => [
                    'Our services are provided under medical supervision. All treatments require a consultation and medical approval. Results may vary by individual. This service does not replace regular medical care.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 4,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'terms_eligibility',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'User Responsibilities',
            'subtitle' => '',
            'content' => [
                'headline' => 'User Responsibilities',
                'intro' => 'You agree to:',
                'bullets' => [
                    'Provide accurate and complete information',
                    'Follow all treatment instructions',
                    'Report any adverse effects immediately',
                    'Maintain the confidentiality of your account',
                    'Use services only for lawful purposes',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 5,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'terms_medical_disclaimer',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Payment Terms',
            'subtitle' => '',
            'content' => [
                'headline' => 'Payment Terms',
                'paragraphs' => [
                    'Payment is required before treatment begins. Prices are subject to change. Refunds are provided according to our refund policy.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 6,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'terms_orders_and_fulfillment',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Limitation of Liability',
            'subtitle' => '',
            'content' => [
                'headline' => 'Limitation of Liability',
                'paragraphs' => [
                    'FitByShot and its affiliates shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of our services.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 7,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'terms_intellectual_property',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Changes to Terms',
            'subtitle' => '',
            'content' => [
                'headline' => 'Changes to Terms',
                'paragraphs' => [
                    'We reserve the right to modify these terms at any time. Continued use of our services after changes constitutes acceptance of the new terms.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 8,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'terms_changes_contact',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Contact Information',
            'subtitle' => '',
            'content' => [
                'headline' => 'Contact Information',
                'paragraphs' => [
                    'For questions about these Terms of Service, please contact us at Support@FitbyShot.com.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 9,
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
