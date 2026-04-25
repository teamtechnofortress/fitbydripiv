<?php

namespace App\Services\Content\Resolvers;

use App\Models\Page;
use App\Services\Content\ContentService;

class CmsPageResolver
{
    public function __construct(
        protected ContentService $contentService
    ) {
    }

    public function handle(string $slug): ?array
    {
        $page = Page::published()
            ->where('slug', $slug)
            ->with(['sections.items', 'sections.faqs'])
            ->first();

        if (! $page) {
            return null;
        }

        return $this->contentService->resolveCmsPage($page, [
            'type' => 'cms',
        ]);
    }
}
