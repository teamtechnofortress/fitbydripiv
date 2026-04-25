<?php

namespace App\Support\Content\Globals\Handlers;

use App\Models\GlobalSection;
use App\Support\Content\Globals\GlobalSourceResolver;

class FooterSection
{
    public static function handle(GlobalSection $section): array
    {
        $config = is_array($section->config) ? $section->config : [];
        $sourceResolver = app(GlobalSourceResolver::class);
        $context = $config['context'] ?? [];
        $columns = collect($config['columns'] ?? [])
            ->map(fn (array $column) => static::resolveColumn($column, $sourceResolver, $context))
            ->values()
            ->all();

        return [
            'id' => $section->id,
            'key' => $section->key,
            'type' => $section->type,
            'data' => [
                'columns' => $columns,
                'bottom' => $config['bottom'] ?? [],
            ],
        ];
    }

    protected static function resolveColumn(array $column, GlobalSourceResolver $sourceResolver, array $context = []): array
    {
        $source = $column['source'] ?? null;

        return match ($source) {
            'brand' => static::brandColumn($column, $sourceResolver),
            'categories', 'static_pages', 'research_links', 'social_links' => static::linksColumn($column, $sourceResolver, $context),
            default => static::manualColumn($column),
        };
    }

    protected static function brandColumn(array $column, GlobalSourceResolver $sourceResolver): array
    {
        $brand = $sourceResolver->resolveBrand($column);

        return [
            'type' => 'brand',
            'title' => $column['title'] ?? $brand['name'],
            'logo' => $brand['logo'],
            'content' => $brand['description'],
            'home_url' => $brand['home_url'],
        ];
    }

    protected static function linksColumn(array $column, GlobalSourceResolver $sourceResolver, array $context = []): array
    {
        return [
            'type' => ($column['source'] ?? null) === 'social_links' ? 'social_links' : 'links',
            'source' => $column['source'] ?? 'manual',
            'title' => $column['title'] ?? 'Links',
            'items' => $sourceResolver->resolveItems($column, $context),
        ];
    }

    protected static function manualColumn(array $column): array
    {
        return [
            'type' => $column['type'] ?? 'links',
            'title' => $column['title'] ?? null,
            'items' => array_values($column['items'] ?? []),
            'content' => $column['content'] ?? null,
        ];
    }
}
