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
            'section_key' => 'privacy_information_collect',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Information We Collect',
            'subtitle' => '',
            'content' => [
                'headline' => 'Information We Collect',
                'paragraphs' => [
                    'We collect personal information that you provide directly to us when you complete forms, contact our team, create an account, or use our telehealth services. This may include your name, email address, phone number, shipping details, and other information needed to support your care.',
                    'We may also collect health-related information, medical history, wellness goals, and treatment preferences that are necessary for our licensed medical professionals to review your eligibility and support your treatment plan.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 3,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'privacy_how_we_use',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'How We Use Your Information',
            'subtitle' => '',
            'content' => [
                'headline' => 'How We Use Your Information',
                'paragraphs' => [
                    'We use your information to provide telehealth services, review eligibility, fulfill prescriptions, coordinate delivery, respond to questions, and improve the overall patient experience.',
                    'Your information may also be used to communicate important updates about your treatment, account activity, support requests, and service-related notifications.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 4,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'privacy_information_sharing',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'How We Share Information',
            'subtitle' => '',
            'content' => [
                'headline' => 'How We Share Information',
                'paragraphs' => [
                    'We share information only when necessary to operate our services, support your treatment, fulfill orders, comply with legal requirements, or protect our users and business.',
                    'This may include sharing relevant information with licensed medical professionals, pharmacies, delivery partners, payment processors, technology providers, and service vendors that help us deliver FitByShot services.',
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
                    'We use administrative, technical, and operational safeguards designed to protect your personal and health-related information from unauthorized access, misuse, alteration, or disclosure.',
                    'While no platform can guarantee absolute security, we take privacy seriously and continuously work to maintain secure systems and responsible data practices.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 6,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'privacy_choices',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Your Choices and Rights',
            'subtitle' => '',
            'content' => [
                'headline' => 'Your Choices and Rights',
                'paragraphs' => [
                    'You may contact us to request updates to your account information, ask questions about your data, or seek assistance regarding privacy-related concerns.',
                    'Depending on your location and applicable laws, you may also have additional rights related to access, correction, deletion, or restriction of certain personal information.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 7,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'privacy_contact',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Contact Us About Privacy',
            'subtitle' => '',
            'content' => [
                'headline' => 'Contact Us',
                'paragraphs' => [
                    'If you have questions about this Privacy Policy or how your information is handled, please contact our support team through the channels listed on our website.',
                    'We may update this Privacy Policy from time to time to reflect operational, legal, or regulatory changes. Continued use of FitByShot services after updates constitutes acceptance of the revised policy.',
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
