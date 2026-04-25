<?php

namespace App\Support\Content\Sections\Handlers;

use App\Models\PageSection;
use App\Models\Product;

class ProductDetailsSection
{
    public static function handle(PageSection $section, array $context = []): ?array
    {
        $product = $context['product'] ?? null;

        if (! $product instanceof Product) {
            return null;
        }

        return [
            'id' => $section->id,
            'section_key' => $section->section_key,
            'type' => $section->type?->value ?? $section->getRawOriginal('type'),
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'content' => $section->content,
            'image' => $section->image,
            'sort_order' => $section->sort_order,
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'slug' => $product->slug,
                    'name' => $product->name,
                    'category' => $product->category,
                    'description' => $product->description,
                    'about_treatment' => $product->about_treatment,
                    'how_it_works' => $product->how_it_works,
                    'key_ingredients' => $product->key_ingredients,
                    'treatment_duration' => $product->treatment_duration,
                    'usage_instructions' => $product->usage_instructions,
                    'clinical_research_description' => $product->clinical_research_description,
                    'cover_image' => $product->coverImage ? [
                        'id' => $product->coverImage->id,
                        'image_url' => $product->coverImage->image_url,
                        'image_type' => $product->coverImage->image_type,
                    ] : null,
                    'images' => $product->images->map(fn ($image) => [
                        'id' => $image->id,
                        'image_url' => $image->image_url,
                        'image_type' => $image->image_type,
                        'sort_order' => $image->sort_order,
                    ])->values()->all(),
                    'benefits' => $product->benefits->map(fn ($benefit) => [
                        'id' => $benefit->id,
                        'benefit_text' => $benefit->benefit_text,
                        'sort_order' => $benefit->sort_order,
                    ])->values()->all(),
                    'faqs' => $product->faqs->map(fn ($faq) => [
                        'id' => $faq->id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                        'sort_order' => $faq->sort_order,
                        'is_active' => $faq->is_active,
                    ])->values()->all(),
                    'research_links' => $product->researchLinks->map(fn ($link) => [
                        'id' => $link->id,
                        'title' => $link->title,
                        'article_url' => $link->article_url,
                        'authors' => $link->authors,
                        'journal' => $link->journal,
                        'publication_year' => $link->publication_year,
                        'pubmed_id' => $link->pubmed_id,
                        'doi' => $link->doi,
                        'description' => $link->description,
                        'sort_order' => $link->sort_order,
                    ])->values()->all(),
                    'ingredients' => $product->ingredients->map(fn ($ingredient) => [
                        'id' => $ingredient->id,
                        'name' => $ingredient->name,
                        'description' => $ingredient->description,
                        'sort_order' => $ingredient->pivot?->sort_order,
                    ])->values()->all(),
                    'pricing' => $product->pricing->map(fn ($pricing) => [
                        'id' => $pricing->id,
                        'pricing_type' => $pricing->pricing_type,
                        'title' => $pricing->title,
                        'description' => $pricing->description,
                        'is_active' => $pricing->is_active,
                        'options' => $pricing->options->map(fn ($option) => [
                            'id' => $option->id,
                            'label' => $option->label,
                            'billing_interval' => $option->billing_interval,
                            'interval_count' => $option->interval_count,
                            'price' => $option->price,
                            'discount_percent' => $option->discount_percent,
                            'final_price' => $option->final_price,
                            'sort_order' => $option->sort_order,
                            'is_default' => $option->is_default,
                            'metadata' => $option->metadata,
                        ])->values()->all(),
                    ])->values()->all(),
                ],
            ],
        ];
    }
}
