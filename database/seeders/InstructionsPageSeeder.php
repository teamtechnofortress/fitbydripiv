<?php

namespace Database\Seeders;

use App\enums\SectionType;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class InstructionsPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'instructions'],
            [
                'title' => 'Treatment Instructions',
                'status' => 'published',
                'meta_title' => 'FitByShot | Treatment Instructions',
                'meta_description' => 'General guidance for using FitByShot treatments safely and effectively.',
            ]
        );

        $this->upsertSection($page, [
            'section_key' => 'instructions_header',
            'type' => SectionType::SECTION_HEADER->value,
            'title' => 'Instructions Header',
            'subtitle' => 'Top banner for instructions page',
            'content' => [
                'headline' => 'Treatment Instructions',
                'description' => 'General guidance for using FitByShot treatments safely and effectively',
                'alignment' => 'center',
                'spacing' => 'comfortable',
            ],
            'sort_order' => 1,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'instructions_top_spacer',
            'type' => SectionType::SPACER->value,
            'title' => 'Instructions Top Spacer',
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
            'section_key' => 'instructions_before_you_begin',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Before You Begin',
            'subtitle' => '',
            'content' => [
                'headline' => 'Before You Begin',
                'bullets' => [
                    'Complete the telehealth intake form thoroughly and accurately',
                    'Wait for medical review and approval (0-48 hours)',
                    'Review all product-specific instructions included with your prescription',
                    'Contact our medical team with any questions before starting treatment',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 3,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'instructions_storage_guidelines',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Storage Guidelines',
            'subtitle' => '',
            'content' => [
                'headline' => 'Storage Guidelines',
                'bullets' => [
                    'Store medications according to product-specific instructions',
                    'Most injectable treatments require refrigeration',
                    'Keep medications out of reach of children and pets',
                    'Check expiration dates and do not use expired medications',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 4,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'instructions_administration',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Administration',
            'subtitle' => '',
            'content' => [
                'headline' => 'Administration',
                'bullets' => [
                    'Follow dosing instructions exactly as prescribed by your medical provider',
                    'For injections, use proper sterile technique and rotate injection sites',
                    'Dispose of needles and syringes safely in a sharps container',
                    'Keep a treatment log to track your progress',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 5,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'instructions_monitoring_support',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Monitoring & Support',
            'subtitle' => '',
            'content' => [
                'headline' => 'Monitoring & Support',
                'bullets' => [
                    'Track your progress and any side effects',
                    'Report any concerning symptoms to our medical team immediately',
                    'Attend follow-up consultations as recommended',
                    'Contact us with any questions about your treatment',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 6,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'instructions_when_to_seek_help',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'When to Seek Help',
            'subtitle' => '',
            'content' => [
                'headline' => 'When to Seek Help',
                'intro' => 'Contact our medical team if you experience:',
                'bullets' => [
                    'Severe or unexpected side effects',
                    'Allergic reactions (rash, difficulty breathing, swelling)',
                    'Concerns about your treatment plan or progress',
                    'Questions about dosing or administration',
                ],
                'paragraphs' => [
                    'For medical emergencies, call 911 or go to the nearest emergency room.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 7,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'instructions_product_specific',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Product-Specific Instructions',
            'subtitle' => '',
            'content' => [
                'headline' => 'Product-Specific Instructions',
                'paragraphs' => [
                    'Each treatment comes with detailed product-specific instructions. Always refer to the instructions included with your prescription for complete information about your specific treatment, including dosing, administration technique, storage, and potential side effects.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
                'background_style' => 'soft_mint_card',
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
