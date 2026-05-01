<?php

namespace App\Support\Content\Globals;

use App\Models\CmsCategory;
use App\Models\CmsResearchLink;
use App\Models\Page;
use App\Services\SettingService;
use Illuminate\Support\Str;

class GlobalSourceResolver
{
    public function resolveItems(array $definition, array $context = []): array
    {
        return match ($definition['source'] ?? null) {
            'categories' => $this->categoryItems($definition),
            'static_pages' => $this->staticPageItems($definition),
            'research_links' => $this->researchLinkItems($definition),
            'social_links' => $this->socialItems($definition),
            default => $this->manualItems($definition),
        };
    }

    public function resolveBrand(array $definition = []): array
    {
        $settings = app(SettingService::class);

        return [
            'name' => $definition['name'] ?? $settings->value('app_name', 'FitByShot'),
            'logo' => $definition['logo'] ?? $settings->value('logo'),
            'description' => $definition['description'] ?? $settings->value(
                'brand_description',
                'Prescription Weight Loss, Longevity, and Wellness specific to your goals.'
            ),
            'home_url' => $definition['home_url'] ?? '/',
        ];
    }

    public function resolveLink(array $definition, array $context = []): ?array
    {
        if (filled($definition['href'] ?? null) || filled($definition['url'] ?? null)) {
            return [
                'type' => 'link',
                'label' => $definition['label'] ?? $definition['title'] ?? null,
                'slug' => $definition['slug'] ?? null,
                'href' => $definition['href'] ?? $definition['url'],
                'icon' => $definition['icon'] ?? null,
                'external' => (bool) ($definition['external'] ?? false),
            ];
        }

        if (filled($definition['slug'] ?? null)) {
            return $this->resolveStaticPageLink($definition['slug'], $definition['label'] ?? null);
        }

        return null;
    }

    protected function categoryItems(array $definition): array
    {
        $requestedSlugs = array_values($definition['items'] ?? []);

        if ($requestedSlugs !== []) {
            $categories = CmsCategory::query()
                ->whereIn('slug', $requestedSlugs)
                ->get(['id', 'name', 'slug'])
                ->keyBy('slug');

            return collect($requestedSlugs)
                ->map(function (string $slug) use ($categories) {
                    $category = $categories->get($slug);

                    if (! $category) {
                        return null;
                    }

                    return [
                        'label' => $category->name,
                        'slug' => $category->slug,
                        'href' => '/' . ltrim($category->slug, '/'),
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        return CmsCategory::query()
            ->orderBy('display_order')
            ->get(['id', 'name', 'slug'])
            ->map(fn (CmsCategory $category) => [
                'label' => $category->name,
                'slug' => $category->slug,
                'href' => '/' . ltrim($category->slug, '/'),
            ])
            ->values()
            ->all();
    }

    protected function staticPageItems(array $definition): array
    {
        $requestedItems = collect($definition['items'] ?? [])
            ->map(function ($item) {
                if (is_string($item)) {
                    return [
                        'slug' => $item,
                        'label' => null,
                    ];
                }

                if (is_array($item) && filled($item['slug'] ?? null)) {
                    return [
                        'slug' => $item['slug'],
                        'label' => $item['label'] ?? null,
                    ];
                }

                return null;
            })
            ->filter()
            ->values();

        $requestedSlugs = $requestedItems
            ->pluck('slug')
            ->values()
            ->all();

        $pages = Page::published()
            ->whereIn('slug', $requestedSlugs)
            ->get(['slug', 'title'])
            ->keyBy('slug');

        return $requestedItems
            ->map(function (array $item) use ($pages) {
                $slug = $item['slug'];
                $page = $pages->get($slug);

                if ($page) {
                    return [
                        'label' => $item['label'] ?? $page->title,
                        'slug' => $page->slug,
                        'href' => '/' . ltrim($page->slug, '/'),
                    ];
                }

                $fallback = $this->fallbackStaticItem($slug);

                if ($fallback === null) {
                    return null;
                }

                return [
                    'label' => $item['label'] ?? $fallback['label'],
                    'slug' => $fallback['slug'] ?? $slug,
                    'href' => $fallback['href'] ?? '/' . ltrim($slug, '/'),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function researchLinkItems(array $definition): array
    {
        $supplementalItems = collect($definition['items'] ?? [])
            ->filter(fn ($item) => is_array($item) && blank($item['article_url'] ?? null))
            ->map(fn ($item) => $this->normalizeManualItem($item))
            ->filter()
            ->values();

        $items = CmsResearchLink::query()
            ->select(['title', 'article_url', 'display_order'])
            ->whereNull('product_id')
            ->whereNotNull('article_url')
            ->orderBy('display_order')
            ->orderBy('title')
            ->get()
            ->map(fn (CmsResearchLink $link) => [
                'label' => $link->title,
                'href' => $link->article_url,
                'external' => true,
            ])
            ->unique('href')
            ->values()
            ->all();

        if ($supplementalItems->isNotEmpty() || $items !== []) {
            return $supplementalItems
                ->concat($items)
                ->values()
                ->all();
        }

        return $this->manualItems($definition);
    }

    protected function socialItems(array $definition): array
    {
        $manualItems = collect($definition['items'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(fn ($item) => $this->normalizeManualItem($item))
            ->filter()
            ->values();

        if ($manualItems->isNotEmpty()) {
            return $manualItems->all();
        }

        $settings = app(SettingService::class);

        $definitions = [
            'facebook' => ['key' => 'facebook_url', 'icon' => 'facebook'],
            'instagram' => ['key' => 'instagram_url', 'icon' => 'instagram'],
            'twitter' => ['key' => 'twitter_url', 'icon' => 'twitter'],
            'email' => ['key' => 'contact_email', 'icon' => 'mail'],
        ];

        return collect($definition['items'] ?? array_keys($definitions))
            ->map(function (string $item) use ($definitions, $settings) {
                if (! isset($definitions[$item])) {
                    return null;
                }

                $value = $settings->value($definitions[$item]['key']);

                if (blank($value)) {
                    return null;
                }

                return [
                    'label' => Str::headline($item),
                    'href' => $item === 'email' ? 'mailto:' . $value : $value,
                    'icon' => $definitions[$item]['icon'],
                    'external' => $item !== 'email',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function manualItems(array $definition): array
    {
        return collect($definition['items'] ?? [])
            ->map(fn ($item) => $this->normalizeManualItem($item))
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeManualItem(mixed $item): ?array
    {
        if (is_string($item)) {
            return [
                'label' => $item,
                'href' => null,
                'external' => false,
            ];
        }

        if (! is_array($item)) {
            return null;
        }

        return [
            'label' => $item['label'] ?? $item['title'] ?? null,
            'title' => $item['title'] ?? $item['label'] ?? null,
            'href' => $item['href'] ?? $item['url'] ?? null,
            'article_url' => $item['article_url'] ?? $item['url'] ?? $item['href'] ?? null,
            'slug' => $item['slug'] ?? null,
            'icon' => $item['icon'] ?? null,
            'authors' => $item['authors'] ?? null,
            'journal' => $item['journal'] ?? null,
            'publication_year' => $item['publication_year'] ?? null,
            'pubmed_id' => $item['pubmed_id'] ?? null,
            'doi' => $item['doi'] ?? null,
            'display_order' => $item['display_order'] ?? null,
            'external' => (bool) ($item['external'] ?? false),
        ];
    }

    protected function fallbackStaticItem(string $slug): ?array
    {
        return match ($slug) {
            'contact' => ['label' => 'Contact', 'slug' => 'contact', 'href' => '/contact'],
            'login' => ['label' => 'Login', 'slug' => 'login', 'href' => '/login'],
            'instructions' => ['label' => 'Instructions', 'slug' => 'instructions', 'href' => '/instructions'],
            'legal' => ['label' => 'Legal', 'slug' => 'legal', 'href' => '/legal'],
            default => null,
        };
    }

    protected function resolveStaticPageLink(string $slug, ?string $label = null): ?array
    {
        $page = Page::published()
            ->where('slug', $slug)
            ->first(['slug', 'title']);

        if ($page) {
            return [
                'type' => 'link',
                'label' => $label ?? $page->title,
                'slug' => $page->slug,
                'href' => '/' . ltrim($page->slug, '/'),
                'external' => false,
            ];
        }

        $fallback = $this->fallbackStaticItem($slug);

        if ($fallback === null) {
            return null;
        }

        return [
            'type' => 'link',
            'label' => $label ?? $fallback['label'],
            'slug' => $fallback['slug'] ?? $slug,
            'href' => $fallback['href'] ?? '/' . ltrim($slug, '/'),
            'external' => false,
        ];
    }
}
