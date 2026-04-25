<?php

namespace App\Services\Content\Resolvers;

use App\Models\CmsCategory;
use App\Models\Page;
use App\Services\Content\ContentService;

class CategoryPageResolver
{
    public function __construct(
        protected ContentService $contentService
    ) {
    }

    public function handle(string $slug): ?array
    {
        $category = CmsCategory::query()
            ->where('slug', $slug)
            ->first();

        if (! $category) {
            return null;
        }

        $page = Page::published()
            ->where('slug', 'category-template')
            ->with(['sections.items', 'sections.faqs'])
            ->first();

        if (! $page) {
            return null;
        }

        return $this->contentService->resolveCmsPage($page, [
            'type' => 'category',
            'category' => $category,
        ]);
    }
}
