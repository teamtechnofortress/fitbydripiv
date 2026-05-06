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
            'certification' => static::certificationColumn($column),
            'categories', 'static_pages', 'research_links', 'social_links' => static::linksColumn($column, $sourceResolver, $context),
            default => static::manualColumn($column),
        };
    }

    protected static function brandColumn(array $column, GlobalSourceResolver $sourceResolver): array
    {
        $brand = $sourceResolver->resolveBrand($column);

        return array_merge(static::columnMetadata($column), [
            'type' => 'brand',
            'title' => $column['title'] ?? $brand['name'],
            'logo' => $brand['logo'],
            'content' => $brand['description'],
            'home_url' => $brand['home_url'],
        ]);
    }

    protected static function linksColumn(array $column, GlobalSourceResolver $sourceResolver, array $context = []): array
    {
        $definition = $column;

        if (($column['source'] ?? null) === 'categories') {
            // Footer category columns should always reflect the current table data.
            unset($definition['items']);
        }

        return array_merge(static::columnMetadata($column), [
            'type' => ($column['source'] ?? null) === 'social_links' ? 'social_links' : 'links',
            'source' => $column['source'] ?? 'manual',
            'title' => $column['title'] ?? 'Links',
            'items' => $sourceResolver->resolveItems($definition, $context),
        ]);
    }

    protected static function certificationColumn(array $column): array
    {
        $item = collect($column['items'] ?? [])
            ->first(fn ($item) => is_array($item)) ?? [];

        return array_merge(static::columnMetadata($column), [
            'type' => 'certification',
            'source' => 'certification',
            'title' => $column['title'] ?? 'Certification',
            'items' => [[
                'image' => $item['image'] ?? null,
                'description' => $item['description'] ?? null,
            ]],
        ]);
    }

    protected static function manualColumn(array $column): array
    {
        return array_merge(static::columnMetadata($column), [
            'type' => $column['type'] ?? 'links',
            'title' => $column['title'] ?? null,
            'items' => array_values($column['items'] ?? []),
            'content' => $column['content'] ?? null,
        ]);
    }

    protected static function columnMetadata(array $column): array
    {
        return [
            'id' => $column['id'] ?? null,
            'sort_order' => isset($column['sort_order']) ? (int) $column['sort_order'] : 0,
            'start_new_row' => (bool) ($column['start_new_row'] ?? false),
        ];
    }
}
