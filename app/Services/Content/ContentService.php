<?php

namespace App\Services\Content;

use App\Models\CmsCategory;
use App\Models\Page;
use App\Models\Product;
use App\Support\Content\Sections\SectionResolver;

class ContentService
{
    public function resolveCmsPage(Page $page, array $context = []): array
    {
        $sections = $page->relationLoaded('sections')
            ? $page->sections
            : $page->sections()->with(['items', 'faqs'])->orderBy('sort_order')->get();

        return [
            'page' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'status' => $page->status,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
            ],
            'context' => $this->serializeContext($context),
            'sections' => SectionResolver::resolveCollection($sections, $context),
        ];
    }

    protected function serializeContext(array $context): array
    {
        $serialized = [];

        if (isset($context['type'])) {
            $serialized['type'] = $context['type'];
        }

        if (isset($context['preview_mode'])) {
            $serialized['preview_mode'] = (bool) $context['preview_mode'];
        }

        if (($context['category'] ?? null) instanceof CmsCategory) {
            $category = $context['category'];

            $serialized['category'] = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'banner_image' => $category->banner_image,
                'landscape_banner' => $category->landscape_banner,
                'background_video' => $category->background_video,
            ];
        }

        if (($context['product'] ?? null) instanceof Product) {
            $product = $context['product'];

            $serialized['product'] = ($context['preview_mode'] ?? false)
                ? $this->serializePreviewProduct($product)
                : [
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
                ];
        }

        return $serialized;
    }

    protected function serializePreviewProduct(Product $product): array
    {
        return [
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
            'research_description' => $product->research_description,
            'clinical_research_description' => $product->clinical_research_description,
            'is_featured' => $product->is_featured,
            'is_published' => $product->is_published,
            'completion_status' => $product->completion_status,
            'completion_percentage' => $product->completion_percentage,
            'completion_step' => $product->completion_step,
            'cover_image_id' => $product->cover_image_id,
            'cover_image' => $product->relationLoaded('coverImage') && $product->coverImage ? [
                'id' => $product->coverImage->id,
                'image_url' => $product->coverImage->image_url,
                'image_type' => $product->coverImage->image_type,
                'sort_order' => $product->coverImage->sort_order,
            ] : null,
            'images' => $product->relationLoaded('images')
                ? $product->images->map(fn ($image) => [
                    'id' => $image->id,
                    'image_url' => $image->image_url,
                    'image_type' => $image->image_type,
                    'sort_order' => $image->sort_order,
                ])->values()->all()
                : [],
            'benefits' => $product->relationLoaded('benefits')
                ? $product->benefits->map(fn ($benefit) => [
                    'id' => $benefit->id,
                    'title' => $benefit->title,
                    'description' => $benefit->description,
                    'sort_order' => $benefit->sort_order,
                ])->values()->all()
                : [],
            'faqs' => $product->relationLoaded('faqs')
                ? $product->faqs->map(fn ($faq) => [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'sort_order' => $faq->sort_order,
                    'is_active' => $faq->is_active,
                ])->values()->all()
                : [],
            'research_links' => $product->relationLoaded('researchLinks')
                ? $product->researchLinks->map(fn ($link) => [
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
                ])->values()->all()
                : [],
            'pricing' => $product->relationLoaded('pricing')
                ? $product->pricing->map(fn ($pricing) => [
                    'id' => $pricing->id,
                    'pricing_type' => $pricing->pricing_type,
                    'title' => $pricing->title,
                    'description' => $pricing->description,
                    'is_active' => $pricing->is_active,
                    'options' => $pricing->relationLoaded('options')
                        ? $pricing->options->map(fn ($option) => [
                            'id' => $option->id,
                            'billing_interval' => $option->billing_interval,
                            'interval_count' => $option->interval_count,
                            'price' => $option->price,
                            'discount_percent' => $option->discount_percent,
                            'final_price' => $option->final_price,
                            'label' => $option->label,
                            'sort_order' => $option->sort_order,
                            'is_default' => $option->is_default,
                            'metadata' => $option->metadata,
                        ])->values()->all()
                        : [],
                ])->values()->all()
                : [],
            'ingredients' => $product->relationLoaded('ingredients')
                ? $product->ingredients->map(fn ($ingredient) => [
                    'id' => $ingredient->id,
                    'name' => $ingredient->name,
                    'description' => $ingredient->description,
                    'sort_order' => $ingredient->pivot?->sort_order,
                ])->values()->all()
                : [],
        ];
    }
}
