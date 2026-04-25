<?php

namespace App\Services\Content\Resolvers;

use App\Models\Page;
use App\Models\Product;
use App\Services\Content\ContentService;

class ProductPageResolver
{
    public function __construct(
        protected ContentService $contentService
    ) {
    }

    public function handle(string $slug): ?array
    {
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
            return null;
        }

        return $this->resolveForProduct($product, false);
    }

    public function previewById(string $productId): ?array
    {
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
            return null;
        }

        return $this->resolveForProduct($product, true);
    }

    protected function resolveForProduct(Product $product, bool $isPreview): ?array
    {
        $page = Page::published()
            ->where('slug', 'product-template')
            ->with(['sections.items', 'sections.faqs'])
            ->first();

        if (! $page) {
            return null;
        }

        return $this->contentService->resolveCmsPage($page, [
            'type' => 'product',
            'product' => $product,
            'preview_mode' => $isPreview,
        ]);
    }
}
