<?php

namespace App\Support\Content\Sections\Handlers;

use App\Models\PageSection;
use App\Models\Product;

class FeaturedProductsSection
{
    public static function handle(PageSection $section): array
    {
        $config = is_array($section->content) ? $section->content : [];
        $limit = max(1, (int) ($config['limit'] ?? 6));

        if (! empty($config['cta_label']) && empty($config['cta_link_mode'])) {
            $config['cta_link_mode'] = 'product_page';
        }

        $products = Product::query()
            ->with('coverImage')
            ->live()
            ->where('is_featured', true)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'category' => $product->category,
                'description' => $product->description,
                'is_featured' => $product->is_featured,
                'is_published' => $product->is_published,
                'completion_status' => $product->completion_status,
                'completion_percentage' => $product->completion_percentage,
                'completion_step' => $product->completion_step,
                'cover_image' => $product->coverImage ? [
                    'id' => $product->coverImage->id,
                    'image_url' => $product->coverImage->image_url,
                    'image_type' => $product->coverImage->image_type,
                ] : null,
            ])
            ->values()
            ->all();

        return [
            'id' => $section->id,
            'section_key' => $section->section_key,
            'type' => $section->type?->value ?? $section->getRawOriginal('type'),
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'content' => $config,
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
            'products' => $products,
        ];
    }
}
