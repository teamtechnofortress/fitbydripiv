<?php

namespace App\Support\Content\Sections\Handlers;

use App\Models\CmsCategory;
use App\Models\PageSection;
use App\Models\Product;
use App\Support\Content\Sections\ProductSectionImage;
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
            ->with(['coverImage', 'images'])
            ->live()
            ->orderByDesc('updated_at');

        static::applySourceFilter($query, $source, $context, $config);
        static::applyManualFilter($query, $source, $config);

        $paginator = $query
            ->paginate($limit, ['*'], $pageName)
            ->withQueryString();

        $items = $paginator->getCollection()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category' => $product->category,
                'description' => $product->description,
                'is_featured' => $product->is_featured,
                'is_published' => $product->is_published,
                'completion_status' => $product->completion_status,
                'completion_percentage' => $product->completion_percentage,
                'completion_step' => $product->completion_step,
                'cover_image_id' => $product->cover_image_id,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'cover_image' => ProductSectionImage::serialize(
                    ProductSectionImage::resolveForSection($product, $section->type ?? $section->getRawOriginal('type'))
                ),
            ])
            ->values()
            ->all();

        $products = $paginator->toArray();
        unset($products['data']);

        return [
            'id' => $section->id,
            'section_key' => $section->section_key,
            'type' => $section->type?->value ?? $section->getRawOriginal('type'),
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'content' => $config,
            'image' => $section->image,
            'sort_order' => $section->sort_order,
            'source' => $source,
            'products' => $items,
            'pagination' => $products,
            'data' => [
                'source' => $source,
                'products' => [
                    'items' => $items,
                    'pagination' => $products,
                ],
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
}
