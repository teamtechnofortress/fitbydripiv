<?php

namespace App\Support\Content\Sections;

use App\Enums\SectionType;
use App\Models\PageSection;
use App\Support\Content\Sections\Handlers\CategoryCardsSection;
use App\Support\Content\Sections\Handlers\ContentBlockSection;
use App\Support\Content\Sections\Handlers\FaqSection;
use App\Support\Content\Sections\Handlers\FeaturedProductsSection;
use App\Support\Content\Sections\Handlers\HeroSection;
use App\Support\Content\Sections\Handlers\ProcessSection;
use App\Support\Content\Sections\Handlers\ProductGridSection;
use App\Support\Content\Sections\Handlers\ProductDetailsSection;
use App\Support\Content\Sections\Handlers\SectionHeaderSection;
use App\Support\Content\Sections\Handlers\SpacerSection;
use App\Support\Content\Sections\Handlers\TelehealthCtaSection;
use Illuminate\Support\Collection;

class SectionResolver
{
    public static function resolveCollection(Collection $sections, array $context = []): array
    {
        return $sections
            ->map(fn (PageSection $section) => static::resolve($section, $context))
            ->filter()
            ->values()
            ->all();
    }

    public static function resolve(PageSection $section, array $context = []): ?array
    {
        return match ($section->type) {
            SectionType::CONTENT_BLOCK => ContentBlockSection::handle($section),
            SectionType::SPACER => SpacerSection::handle($section),
            SectionType::PRODUCT_GRID => ProductGridSection::handle($section, $context),
            SectionType::PRODUCT_DETAILS => ProductDetailsSection::handle($section, $context),
            SectionType::HERO => HeroSection::handle($section),
            SectionType::FAQ => FaqSection::handle($section),
            SectionType::SECTION_HEADER => SectionHeaderSection::handle($section),
            SectionType::FEATURED_PRODUCTS => FeaturedProductsSection::handle($section),
            SectionType::CATEGORY_CARDS => CategoryCardsSection::handle($section),
            SectionType::PROCESS => ProcessSection::handle($section),
            SectionType::TELEHEALTH_CTA => TelehealthCtaSection::handle($section),
            default => static::default($section),
        };
    }

    protected static function default(PageSection $section): array
    {
        return [
            'id' => $section->id,
            'section_key' => $section->section_key,
            'type' => $section->type?->value ?? $section->getRawOriginal('type'),
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'content' => $section->content,
            'image' => $section->image,
            'sort_order' => $section->sort_order,
            'items' => $section->items->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'icon' => $item->icon,
                'image' => $item->image,
                'sort_order' => $item->sort_order,
            ])->values()->all(),
            'faqs' => $section->faqs->map(fn ($faq) => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'sort_order' => $faq->sort_order,
                'is_active' => $faq->is_active,
            ])->values()->all(),
        ];
    }
}
