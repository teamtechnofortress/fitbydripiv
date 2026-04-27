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
                    'By accessing or using FitByShot, you agree to these Terms of Service and any related policies referenced within them. If you do not agree to these terms, you should not use our website, telehealth workflows, or related services.',
                    'These terms apply to your use of our content, account tools, treatment selection flows, support interactions, and all services made available through FitByShot.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 3,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'terms_services_scope',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Scope of Services',
            'subtitle' => '',
            'content' => [
                'headline' => 'Scope of Services',
                'paragraphs' => [
                    'FitByShot provides access to wellness-related content, product information, intake workflows, and telehealth coordination designed to help users explore treatment options and receive medically reviewed care when appropriate.',
                    'Use of FitByShot does not guarantee eligibility, approval, prescription, shipment, or treatment outcomes. Any medical decision is subject to provider review and applicable clinical standards.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 4,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'terms_eligibility',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Eligibility and User Responsibilities',
            'subtitle' => '',
            'content' => [
                'headline' => 'Eligibility and User Responsibilities',
                'bullets' => [
                    'You must provide accurate, current, and complete information during intake, account creation, and support interactions.',
                    'You are responsible for reviewing your information before submission and promptly updating us if your medical status, contact details, or relevant health information changes.',
                    'You agree not to misuse the website, interfere with service operation, or submit false, misleading, or unauthorized information.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 5,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'terms_medical_disclaimer',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Medical and Treatment Disclaimer',
            'subtitle' => '',
            'content' => [
                'headline' => 'Medical and Treatment Disclaimer',
                'paragraphs' => [
                    'Information on FitByShot is provided for general informational purposes and does not replace individualized medical advice, diagnosis, or treatment from a licensed professional.',
                    'Treatment plans, prescriptions, and eligibility decisions are made only after review by qualified medical professionals. Results vary, and no outcome is guaranteed.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 6,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'terms_orders_and_fulfillment',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Orders, Fulfillment, and Support',
            'subtitle' => '',
            'content' => [
                'headline' => 'Orders, Fulfillment, and Support',
                'paragraphs' => [
                    'Orders are subject to provider approval, product availability, operational review, and any applicable regulatory limitations. Shipping timelines, delivery availability, and product format may vary by treatment.',
                    'FitByShot may modify, pause, or decline service availability where necessary for operational, compliance, clinical, or legal reasons.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 7,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'terms_intellectual_property',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Content and Intellectual Property',
            'subtitle' => '',
            'content' => [
                'headline' => 'Content and Intellectual Property',
                'paragraphs' => [
                    'All website content, branding, layouts, text, graphics, and related materials are owned by FitByShot or used under applicable rights. You may not copy, distribute, reproduce, or repurpose these materials except as allowed by law or with written permission.',
                    'Use of our website does not transfer ownership or grant a commercial license to any FitByShot intellectual property.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 8,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'terms_changes_contact',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Changes to Terms',
            'subtitle' => '',
            'content' => [
                'headline' => 'Changes to Terms',
                'paragraphs' => [
                    'We may update these Terms of Service from time to time to reflect service changes, legal requirements, or operational updates. Continued use of FitByShot after revisions are posted constitutes acceptance of the updated terms.',
                    'If you have questions about these Terms of Service, please contact our support team through the contact options provided on the website.',
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
