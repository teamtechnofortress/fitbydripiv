<?php

namespace Database\Seeders;

use App\enums\SectionType;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SectionItem;
use Illuminate\Database\Seeder;

class HomePageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Homepage',
                'status' => 'published',
                'meta_title' => 'FitByShot | Prescription Weight Loss, Longevity, and Wellness',
                'meta_description' => 'Prescription weight loss, longevity, and wellness programs tailored to your goals with modern telehealth support.',
            ]
        );

        $hero = $this->upsertSection($page, [
            'section_key' => 'home_hero',
            'type' => SectionType::HERO->value,
            'title' => 'Homepage Hero',
            'subtitle' => 'Primary hero section',
            'content' => [
                'headline' => 'Prescription Weight Loss, Longevity, and Wellness Specific to Your Goals',
                'description' => 'Support for Body, Mind & Cellular Health. Health simplified and delivered reliably right to your door.',
                'background' => [
                    'type' => 'video',
                    'url' => '/homepage.mp4',
                ],
                'cta' => [
                    'label' => 'Start my journey',
                    'link' => '/products/select',
                    'style' => 'primary',
                ],
            ],
            'sort_order' => 1,
        ]);
        $this->clearItems($hero);

        $featuredHeader = $this->upsertSection($page, [
            'section_key' => 'featured_products_header',
            'type' => SectionType::SECTION_HEADER->value,
            'title' => 'Featured Products Header',
            'subtitle' => 'Intro block for featured products',
            'content' => [
                'headline' => 'Featured Products',
                'description' => 'Discover our most popular treatments',
                'alignment' => 'center',
            ],
            'sort_order' => 2,
        ]);
        $this->clearItems($featuredHeader);

        $featuredProducts = $this->upsertSection($page, [
            'section_key' => 'featured_products_grid',
            'type' => SectionType::FEATURED_PRODUCTS->value,
            'title' => 'Featured Products Grid',
            'subtitle' => 'Homepage featured products collection',
            'content' => [
                'limit' => 6,
                'variant' => 'carousel_cards',
                'cta_label' => 'View Details',
                'cta_link_mode' => 'product_page',
            ],
            'sort_order' => 3,
        ]);
        $this->clearItems($featuredProducts);

        $categoryHeader = $this->upsertSection($page, [
            'section_key' => 'categories_header',
            'type' => SectionType::SECTION_HEADER->value,
            'title' => 'Treatment Categories Header',
            'subtitle' => 'Intro block for categories',
            'content' => [
                'headline' => 'Our Treatment Categories',
                'alignment' => 'center',
            ],
            'sort_order' => 4,
        ]);
        $this->clearItems($categoryHeader);

        $categoryCards = $this->upsertSection($page, [
            'section_key' => 'category_cards',
            'type' => SectionType::CATEGORY_CARDS->value,
            'title' => 'Treatment Category Cards',
            'subtitle' => 'Primary categories grid',
            'content' => [
                'variant' => 'soft_cards',
                'cta_label' => 'View Products',
            ],
            'sort_order' => 5,
        ]);
        $this->syncItems($categoryCards, [
            [
                'title' => 'Weight Loss',
                'description' => 'Prescription weight loss solutions customized to your goals. Evidence-based treatments delivered to your door.',
                'icon' => 'activity',
                'sort_order' => 1,
            ],
            [
                'title' => 'Wellness',
                'description' => 'Support for body and mind wellness. Personalized treatments for optimal health and vitality.',
                'icon' => 'sparkles',
                'sort_order' => 2,
            ],
            [
                'title' => 'Longevity',
                'description' => 'Cellular health and longevity solutions. Science-backed treatments for healthy aging.',
                'icon' => 'shield-heart',
                'sort_order' => 3,
            ],
        ]);

        $howHeader = $this->upsertSection($page, [
            'section_key' => 'how_it_works_header',
            'type' => SectionType::SECTION_HEADER->value,
            'title' => 'How It Works Header',
            'subtitle' => 'Process intro block',
            'content' => [
                'headline' => 'How It Works',
                'alignment' => 'center',
            ],
            'sort_order' => 6,
        ]);
        $this->clearItems($howHeader);

        $process = $this->upsertSection($page, [
            'section_key' => 'how_it_works_steps',
            'type' => SectionType::PROCESS->value,
            'title' => 'How It Works Steps',
            'subtitle' => 'Three-step patient journey',
            'content' => [
                'variant' => 'icon_steps',
                'background_style' => 'soft_gradient',
            ],
            'sort_order' => 7,
        ]);
        $this->syncItems($process, [
            [
                'title' => 'Step 1: Intake evaluation',
                'description' => 'Complete our secure online health questionnaire about your medical history and wellness goals',
                'icon' => 'clipboard-list',
                'sort_order' => 1,
            ],
            [
                'title' => 'Step 2: Prescription/Approval',
                'description' => 'Our licensed medical providers review your information and determine eligibility',
                'icon' => 'stethoscope',
                'sort_order' => 2,
            ],
            [
                'title' => 'Step 3: Fast Shipping',
                'description' => 'Your prescription is compounded and shipped directly to your door with tracking',
                'icon' => 'truck',
                'sort_order' => 3,
            ],
        ]);

        $telehealthHeader = $this->upsertSection($page, [
            'section_key' => 'telehealth_header',
            'type' => SectionType::SECTION_HEADER->value,
            'title' => 'Telehealth Header',
            'subtitle' => 'Telehealth intro block',
            'content' => [
                'headline' => 'Personalized Telehealth Consultations',
                'description' => 'Complete our Telehealth form about your health and goals. Our medical professionals review your eligibility and your customized prescription is delivered directly to you.',
                'alignment' => 'center',
            ],
            'sort_order' => 8,
        ]);
        $this->clearItems($telehealthHeader);

        $telehealthCta = $this->upsertSection($page, [
            'section_key' => 'telehealth_cta',
            'type' => SectionType::TELEHEALTH_CTA->value,
            'title' => 'Telehealth CTA',
            'subtitle' => 'Homepage telehealth conversion block',
            'content' => [
                'button' => [
                    'label' => 'Learn More',
                    'link' => '/telehealth-faq',
                    'style' => 'outline',
                ],
                'layout' => 'centered_cta',
            ],
            'sort_order' => 9,
        ]);
        $this->clearItems($telehealthCta);
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

    protected function syncItems(PageSection $section, array $items): void
    {
        $section->items()->delete();

        foreach ($items as $item) {
            SectionItem::create([
                'section_id' => $section->id,
                'title' => $item['title'] ?? null,
                'description' => $item['description'] ?? null,
                'icon' => $item['icon'] ?? null,
                'image' => $item['image'] ?? null,
                'sort_order' => $item['sort_order'] ?? 0,
            ]);
        }
    }

    protected function clearItems(PageSection $section): void
    {
        $section->items()->delete();
    }
}
