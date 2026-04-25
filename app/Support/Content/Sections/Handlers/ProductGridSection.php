<?php

namespace App\Support\Content\Sections\Handlers;

use App\Models\CmsCategory;
use App\Models\PageSection;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductGridSection
{
    public static function handle(PageSection $section, array $context = []): array
    {
        $config = is_array($section->content) ? $section->content : [];
        $source = $config['source'] ?? static::inferSourceFromType($section);
        $limit = max(1, (int) ($config['limit'] ?? 20));
        $pageName = $config['page_param'] ?? ($section->section_key . '_page');

        $query = Product::query()
            ->with('coverImage')
            ->live()
            ->orderByDesc('updated_at');

        static::applySourceFilter($query, $source, $context, $config);
        static::applyManualFilter($query, $source, $config);

        $products = $query
            ->paginate($limit, ['*'], $pageName)
            ->withQueryString()
            ->through(fn (Product $product) => static::serializeProductCard($product))
            ->toArray();

        return [
            'id' => $section->id,
            'section_key' => $section->section_key,
            'type' => $section->type?->value ?? $section->getRawOriginal('type'),
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'content' => $config,
            'image' => $section->image,
            'sort_order' => $section->sort_order,
            'data' => [
                'source' => $source,
                'products' => $products,
            ],
        ];
    }

    protected static function inferSourceFromType(PageSection $section): string
    {
        return match ($section->type?->value ?? $section->getRawOriginal('type')) {
            'category_products' => 'category',
            default => 'all',
        };
    }

    protected static function applySourceFilter(Builder $query, string $source, array $context, array $config): void
    {
        if ($source === 'category') {
            $categoryKey = null;

            if (! empty($config['category'])) {
                $categoryKey = static::normalizeCategoryKey($config['category']);
            } elseif (($context['category'] ?? null) instanceof CmsCategory) {
                $categoryKey = static::normalizeCategoryKey($context['category']->slug ?: $context['category']->name);
            }

            if ($categoryKey !== null) {
                $query->where('category', $categoryKey);
            }
        }
    }

    protected static function applyManualFilter(Builder $query, string $source, array $config): void
    {
        if ($source !== 'manual' || empty($config['product_ids']) || ! is_array($config['product_ids'])) {
            return;
        }

        $productIds = array_values(array_filter($config['product_ids'], fn ($id) => is_string($id) && $id !== ''));

        if ($productIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('id', $productIds);
    }

    protected static function normalizeCategoryKey(string $value): string
    {
        return str_replace('-', '_', Str::slug($value, '-'));
    }

    protected static function serializeProductCard(Product $product): array
    {
        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'category' => $product->category,
            'description' => $product->description,
            'cover_image' => $product->coverImage ? [
                'id' => $product->coverImage->id,
                'image_url' => $product->coverImage->image_url,
                'image_type' => $product->coverImage->image_type,
            ] : null,
        ];
    }
}
