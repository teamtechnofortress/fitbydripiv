<?php

namespace App\Services\Content;

use App\Services\Content\Resolvers\CategoryPageResolver;
use App\Services\Content\Resolvers\CmsPageResolver;
use App\Services\Content\Resolvers\ProductPageResolver;

class PageResolver
{
    public function __construct(
        protected CmsPageResolver $cmsPageResolver,
        protected ProductPageResolver $productPageResolver,
        protected CategoryPageResolver $categoryPageResolver
    ) {
    }

    public function resolve(string $slug): ?array
    {
        return $this->cmsPageResolver->handle($slug)
            ?? $this->productPageResolver->handle($slug)
            ?? $this->categoryPageResolver->handle($slug);
    }
}
