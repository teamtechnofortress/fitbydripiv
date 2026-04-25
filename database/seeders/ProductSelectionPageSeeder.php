<?php

namespace Database\Seeders;

use App\Enums\SectionType;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class ProductSelectionPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'productselection'],
            [
                'title' => 'Select a Product',
                'status' => 'published',
                'meta_title' => 'FitByShot | Select a Product',
                'meta_description' => 'Choose the treatment you would like to learn more about and view pricing options.',
            ]
        );

        $this->upsertSection($page, [
            'section_key' => 'product_selection_header',
            'type' => SectionType::SECTION_HEADER->value,
            'title' => 'Product Selection Header',
            'subtitle' => 'Intro block for product selection page',
            'content' => [
                'headline' => 'Select a Product',
                'description' => "Choose the treatment you'd like to learn more about and view pricing options.",
                'alignment' => 'center',
                'spacing' => 'comfortable',
            ],
            'sort_order' => 1,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'product_selection_grid',
            'type' => SectionType::PRODUCT_GRID->value,
            'title' => 'Product Selection Grid',
            'subtitle' => 'All published products shown as a selectable catalog',
            'content' => [
                'source' => 'all',
                'limit' => 12,
                'variant' => 'selection_cards',
                'cta_label' => 'View Pricing',
                'cta_link_mode' => 'product_page',
                'columns' => [
                    'desktop' => 3,
                    'tablet' => 2,
                    'mobile' => 1,
                ],
                'card_style' => 'soft_product_card',
                'image_ratio' => 'portrait',
            ],
            'sort_order' => 2,
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
