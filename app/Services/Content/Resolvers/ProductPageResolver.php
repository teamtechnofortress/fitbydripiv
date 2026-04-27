<?php

namespace App\Services\Content\Resolvers;

use App\Models\Page;
use App\Models\Product;
use App\Services\Content\ContentService;
use Illuminate\Support\Facades\Log;

class ProductPageResolver
{
    public function __construct(
        protected ContentService $contentService
    ) {
    }

    public function handle(string $slug): ?array
    {
        Log::info('Product page resolver: public handle requested', [
            'slug' => $slug,
        ]);

        $product = Product::query()
            ->with([
                'coverImage',
                'images',
                'benefits',
                'faqs',
                'researchLinks',
                'pricing.options',
                'ingredients',
            ])
            ->live()
            ->where('slug', $slug)
            ->first();

        if (! $product) {
            Log::warning('Product page resolver: public product not found', [
                'slug' => $slug,
            ]);

            return null;
        }

        Log::info('Product page resolver: public product loaded', [
            'product_id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
        ]);

        return $this->resolveForProduct($product, false);
    }

    public function previewById(string $productId): ?array
    {
        Log::info('Product preview resolver: previewById requested', [
            'product_id' => $productId,
        ]);

        $product = Product::query()
            ->with([
                'coverImage',
                'images',
                'benefits',
                'faqs',
                'researchLinks',
                'pricing.options',
                'ingredients',
            ])
            ->find($productId);

        if (! $product) {
            Log::warning('Product preview resolver: product lookup returned null', [
                'product_id' => $productId,
                'table' => 'products',
                'model' => Product::class,
            ]);

            return null;
        }

        Log::info('Product preview resolver: product loaded', [
            'product_id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'is_published' => $product->is_published,
            'completion_status' => $product->completion_status,
            'completion_percentage' => $product->completion_percentage,
            'completion_step' => $product->completion_step,
            'relation_counts' => [
                'images' => $product->images->count(),
                'benefits' => $product->benefits->count(),
                'faqs' => $product->faqs->count(),
                'research_links' => $product->researchLinks->count(),
                'pricing_groups' => $product->pricing->count(),
                'ingredients' => $product->ingredients->count(),
            ],
            'cover_image_id' => $product->cover_image_id,
        ]);

        Log::info('Product preview resolver: forwarding product to template resolver', [
            'product_id' => $product->id,
            'preview_mode' => true,
        ]);

        return $this->resolveForProduct($product, true);
    }

    protected function resolveForProduct(Product $product, bool $isPreview): ?array
    {
        Log::info('Product page resolver: resolveForProduct started', [
            'product_id' => $product->id,
            'product_slug' => $product->slug,
            'preview_mode' => $isPreview,
        ]);

        $page = Page::published()
            ->where('slug', 'product-template')
            ->with(['sections.items', 'sections.faqs'])
            ->first();

        if (! $page) {
            Log::warning('Product page resolver: template page not found', [
                'product_id' => $product->id,
                'product_slug' => $product->slug,
                'page_slug' => 'product-template',
                'preview_mode' => $isPreview,
            ]);

            return null;
        }

        Log::info('Product page resolver: template page loaded', [
            'product_id' => $product->id,
            'product_slug' => $product->slug,
            'page_id' => $page->id,
            'page_slug' => $page->slug,
            'page_status' => $page->status,
            'sections_count' => $page->sections->count(),
            'preview_mode' => $isPreview,
        ]);

        $resolved = $this->contentService->resolveCmsPage($page, [
            'type' => 'product',
            'product' => $product,
            'preview_mode' => $isPreview,
        ]);

        Log::info('Product page resolver: content service returned', [
            'product_id' => $product->id,
            'product_slug' => $product->slug,
            'page_id' => $page->id,
            'preview_mode' => $isPreview,
            'has_resolved_page' => $resolved !== null,
            'resolved_keys' => is_array($resolved) ? array_keys($resolved) : [],
        ]);

        return $resolved;
    }
}
