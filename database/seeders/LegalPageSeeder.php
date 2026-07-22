<?php

namespace Database\Seeders;

use App\enums\SectionType;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'legal'],
            [
                'title' => 'Legal Information',
                'status' => 'published',
                'meta_title' => 'FitByShot | Legal Information',
                'meta_description' => 'Important legal information and compliance details for FitByShot services.',
            ]
        );

        $this->upsertSection($page, [
            'section_key' => 'legal_header',
            'type' => SectionType::SECTION_HEADER->value,
            'title' => 'Legal Header',
            'subtitle' => 'Top banner for legal page',
            'content' => [
                'headline' => 'Legal Information',
                'description' => 'Important legal information and compliance details',
                'alignment' => 'center',
                'spacing' => 'comfortable',
            ],
            'sort_order' => 1,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'legal_content',
            'type' => SectionType::RICH_TEXT->value,
            'title' => 'Legal Content',
            'subtitle' => null,
            'content' => [
                'html' => implode('', [
                    '<h4>Medical Disclaimer</h4>',
                    '<p>The information provided on FitByShot is for educational and informational purposes only. It is not intended as medical advice and should not be used as a substitute for consultation with qualified healthcare professionals. All treatments require prescription and medical oversight.</p>',
                    '<h4>Prescription Requirements</h4>',
                    '<p>All products offered through FitByShot require a valid prescription from a licensed healthcare provider. Our telehealth process includes medical review and eligibility determination by qualified medical professionals before any prescription is issued.</p>',
                    '<h4>Regulatory Compliance</h4>',
                    '<p>FitByShot operates in compliance with all applicable federal and state regulations regarding telehealth services and prescription medication dispensing. All medications are compounded by licensed pharmacies following FDA guidelines.</p>',
                    '<h4>Treatment Safety</h4>',
                    '<p>While our treatments are administered under medical supervision, all medications carry potential risks and side effects. Patients should discuss their complete medical history, current medications, and any concerns with their healthcare provider before starting any treatment.</p>',
                    '<p>While our treatments are administered under medical supervision, all medications carry potential risks and side effects. Patients should discuss their complete medical history, current medications, and any concerns with their healthcare provider before starting any treatment.</p>',
                    '<h4>Terms and Policies</h4>',
                    '<p>Please review our Terms of Service and Privacy Policy for complete information about your rights, responsibilities, and how we protect your information.</p>',
                ]),
                'source' => 'manual',
                'source_filename' => null,
                'alignment' => 'left',
                'max_width' => 'content',
                'background_style' => null,
                'warnings' => [],
            ],
            'sort_order' => 3,
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
