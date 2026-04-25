<?php

namespace App\Support\Content\Sections\Handlers;

use App\Models\CmsCategory;
use App\Models\PageSection;

class CategoryCardsSection
{
    public static function handle(PageSection $section): array
    {
        $categories = CmsCategory::query()
            ->orderBy('display_order')
            ->get()
            ->keyBy(fn (CmsCategory $category) => mb_strtolower($category->name));

        return [
            'id' => $section->id,
            'section_key' => $section->section_key,
            'type' => $section->type?->value ?? $section->getRawOriginal('type'),
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'items' => $section->items->map(function ($item) use ($categories) {
                $category = $categories->get(mb_strtolower((string) $item->title));
                $slug = $category?->slug ?? strtolower(str_replace(' ', '-', (string) $item->title));

                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'icon' => $item->icon,
                    'image' => $item->image ?: $category?->banner_image,
                    'cta_text' => 'View Products',
                    'cta_link' => '/products?category=' . $slug,
                    'category' => $category ? [
                        'id' => $category->id,
                        'slug' => $category->slug,
                        'banner_image' => $category->banner_image,
                        'landscape_banner' => $category->landscape_banner,
                    ] : null,
                    'sort_order' => $item->sort_order,
                ];
            })->values()->all(),
        ];
    }
}
