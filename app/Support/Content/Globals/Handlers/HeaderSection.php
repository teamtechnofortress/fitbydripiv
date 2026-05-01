<?php

namespace App\Support\Content\Globals\Handlers;

use App\Models\GlobalSection;
use App\Support\Content\Globals\GlobalSourceResolver;

class HeaderSection
{
    public static function handle(GlobalSection $section): array
    {
        $config = is_array($section->config) ? $section->config : [];
        $sourceResolver = app(GlobalSourceResolver::class);
        $context = $config['context'] ?? [];

        return [
            'id' => $section->id,
            'key' => $section->key,
            'type' => $section->type,
            'data' => [
                'brand' => $sourceResolver->resolveBrand($config['brand'] ?? []),
                'layout' => $config['layout'] ?? [
                    'show_menu_toggle' => true,
                    'show_brand_centered' => true,
                ],
                'menu' => collect($config['menu'] ?? [])
                    ->map(fn (array $item) => static::resolveMenuItem($item, $sourceResolver, $context))
                    ->filter()
                    ->values()
                    ->all(),
            ],
        ];
    }

    protected static function resolveMenuItem(array $item, GlobalSourceResolver $sourceResolver, array $context = []): ?array
    {
        $type = $item['type'] ?? (isset($item['source']) || isset($item['items']) ? 'group' : 'link');

        if ($type === 'group') {
            $source = $item['source'] ?? (isset($item['items']) ? 'manual' : null);
            $definition = isset($item['source']) ? $item : ($item + ['source' => 'manual']);

            if ($source === 'categories') {
                // Header category menus should always reflect the current table data.
                unset($definition['items']);
            }

            return [
                'type' => 'group',
                'label' => $item['label'] ?? $item['title'] ?? null,
                'source' => $source,
                'items' => $sourceResolver->resolveItems($definition, $context),
            ];
        }

        return $sourceResolver->resolveLink($item, $context);
    }
}
