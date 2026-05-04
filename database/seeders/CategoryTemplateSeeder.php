<?php

namespace Database\Seeders;

use App\enums\SectionType;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class CategoryTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'category-template'],
            [
                'title' => 'Category Products Template',
                'status' => 'published',
                'meta_title' => 'FitByShot | Category Treatments',
                'meta_description' => 'Category-based treatment listing page template with a dynamic category hero and product grid.',
            ]
        );

        $this->upsertSection($page, [
            'section_key' => 'category_hero',
            'type' => SectionType::HERO->value,
            'title' => 'Category Hero',
            'subtitle' => 'Dynamic category hero section',
            'content' => [
                'source' => 'category',
                'cta' => null,
                'overlay' => [
                    'style' => 'soft_dark',
                    'opacity' => 0.28,
                ],
                'height' => 'medium',
                'text_align' => 'center',
            ],
            'sort_order' => 1,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'category_product_grid',
            'type' => SectionType::PRODUCT_GRID->value,
            'title' => 'Category Product Grid',
            'subtitle' => 'Products filtered by the current category page context',
            'content' => [
                'source' => 'category',
                'limit' => 12,
                'variant' => 'product_cards',
                'cta_label' => 'View Details',
                'columns' => [
                    'desktop' => 3,
                    'tablet' => 2,
                    'mobile' => 1,
                ],
            ],
            'sort_order' => 2,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'category_support_cta',
            'type' => SectionType::TELEHEALTH_CTA->value,
            'title' => 'Category Support CTA',
            'subtitle' => 'Support CTA shown below category products',
            'content' => [
                'headline' => null,
                'description' => 'Have questions? Our medical team is here to help you find the right solution',
                'button' => [
                    'label' => 'Start my journey',
                    'link' => '/productselection',
                    'style' => 'primary',
                ],
                'layout' => 'centered_cta',
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
