<?php

namespace App\Support\Content\Sections\Handlers;

use App\Models\CmsCategory;
use App\Models\PageSection;

class CategoryCardsSection
{
    public static function handle(PageSection $section): array
    {
        $items = CmsCategory::query()
            ->orderBy('display_order')
            ->get()
            ->map(fn (CmsCategory $category) => [
                'id' => $category->id,
                'title' => $category->name,
                'description' => $category->description,
                'icon' => null,
                'image' => $category->banner_image,
                'cta_text' => 'View Products',
                'cta_link' => '/' . ltrim($category->slug, '/'),
                'category' => [
                    'id' => $category->id,
                    'slug' => $category->slug,
                    'banner_image' => $category->banner_image,
                    'landscape_banner' => $category->landscape_banner,
                    'background_video' => $category->background_video,
                ],
                'sort_order' => $category->display_order,
            ])
            ->values()
            ->all();

        return [
            'id' => $section->id,
            'section_key' => $section->section_key,
            'type' => $section->type?->value ?? $section->getRawOriginal('type'),
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'content' => [],
            'items' => $items,
        ];
    }
}
