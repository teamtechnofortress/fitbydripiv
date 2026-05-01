<?php

namespace Database\Seeders;

use App\enums\SectionType;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class AboutPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'About FitByShot',
                'status' => 'published',
                'meta_title' => 'FitByShot | About Us',
                'meta_description' => 'Learn about FitByShot, our mission, our approach, and the research-backed treatments we provide for weight loss, wellness, and longevity.',
            ]
        );

        $this->upsertSection($page, [
            'section_key' => 'about_header',
            'type' => SectionType::SECTION_HEADER->value,
            'title' => 'About Header',
            'subtitle' => 'Top banner for about page',
            'content' => [
                'headline' => 'About FitByShot',
                'description' => 'Your trusted partner in health and wellness',
                'alignment' => 'center',
                'spacing' => 'comfortable',
            ],
            'sort_order' => 1,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'about_top_spacer',
            'type' => SectionType::SPACER->value,
            'title' => 'About Top Spacer',
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
            'section_key' => 'about_mission',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Our Mission',
            'subtitle' => '',
            'content' => [
                'headline' => 'Our Mission',
                'paragraphs' => [
                    "At FitByShot, we're dedicated to making prescription weight loss, longevity, and wellness solutions accessible and specific to your goals. We believe that health should be simplified and reliably delivered right to your door, with support for body, mind, and cellular health customized for your unique needs.",
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 3,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'about_approach',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Our Approach',
            'subtitle' => '',
            'content' => [
                'headline' => 'Our Approach',
                'paragraphs' => [
                    'We combine cutting-edge medical treatments with personalized telehealth consultations to create treatment plans tailored to your health and wellness goals. Our process is simple: complete our Telehealth form about your health and goals, our medical professionals review your eligibility, and your customized prescription is delivered directly to you.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 4,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'about_treatments',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Our Treatments',
            'subtitle' => '',
            'content' => [
                'headline' => 'Our Treatments',
                'intro' => 'We offer a comprehensive range of evidence-based treatments across three key categories:',
                'bullets' => [
                    'Weight Loss: Semaglutide and Tirzepatide for sustainable weight loss and improved metabolic health',
                    'Wellness: B12 for energy and metabolism, Glutathione for immunity and detoxification',
                    'Longevity: NAD+ therapy for cellular energy and healthy aging',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 5,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'about_why_choose',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Why Choose FitByShot',
            'subtitle' => '',
            'content' => [
                'headline' => 'Why Choose FitByShot',
                'bullets' => [
                    'Personalized telehealth consultations and medical oversight',
                    'Treatment plans specific to your health and wellness goals',
                    'Convenient delivery reliably right to your door',
                    'Support for body, mind, and cellular health',
                    'Ongoing medical support throughout your wellness journey',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 6,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'about_research_backed',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Research-Backed Approach',
            'subtitle' => '',
            'content' => [
                'headline' => 'Research-Backed Approach',
                'paragraphs' => [
                    'At FitByShot, we believe in evidence-based medicine. All of our treatments are supported by peer-reviewed clinical research from leading medical journals and institutions. We stay current with the latest scientific developments to ensure our patients receive therapies with proven safety and efficacy profiles.',
                    'Each product page includes citations to clinical studies and research articles, allowing you to review the scientific evidence behind our treatments. We are committed to transparency and helping you make informed decisions about your health and wellness journey.',
                ],
                'alignment' => 'left',
                'max_width' => 'full',
            ],
            'sort_order' => 7,
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
