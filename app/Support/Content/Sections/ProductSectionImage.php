<?php

namespace App\Support\Content\Sections;

use App\enums\ProductImageType;
use App\enums\SectionType;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Collection;

class ProductSectionImage
{
    public static function imageTypeForSection(SectionType|string|null $sectionType): ProductImageType
    {
        if (! $sectionType instanceof SectionType) {
            $sectionType = is_string($sectionType) ? SectionType::tryFrom($sectionType) : null;
        }

        return match ($sectionType) {
            SectionType::PRODUCT_DETAILS => ProductImageType::PRODUCT_DETAIL_MAIN,
            SectionType::FEATURED_PRODUCTS => ProductImageType::FEATURED_CARD,
            SectionType::PRODUCT_GRID => ProductImageType::PRODUCT_SELECT_CARD,
            default => ProductImageType::COVER,
        };
    }

    public static function resolveForSection(Product $product, SectionType|string|null $sectionType): ?ProductImage
    {
        $product->loadMissing(['coverImage', 'images']);

        return $product->getImageByTypeOrCover(static::imageTypeForSection($sectionType));
    }

    public static function resolveGalleryForSection(Product $product, SectionType|string|null $sectionType): array
    {
        $product->loadMissing(['coverImage', 'images']);

        $type = static::imageTypeForSection($sectionType);
        $images = static::enabledImagesOfType($product->images, $type)
            ->map(fn (ProductImage $image) => static::serialize($image))
            ->filter()
            ->values()
            ->all();

        if ($images !== []) {
            return $images;
        }

        $fallback = static::serialize($product->coverImage);

        return $fallback ? [$fallback] : [];
    }

    protected static function enabledImagesOfType(Collection $images, ProductImageType $type): Collection
    {
        return $images
            ->filter(function (ProductImage $image) use ($type) {
                $imageType = $image->image_type instanceof ProductImageType
                    ? $image->image_type
                    : ProductImageType::tryFrom($image->image_type);

                if ($imageType !== $type) {
                    return false;
                }

                return $image->shouldDisplay();
            })
            ->sortBy('sort_order')
            ->values();
    }

    public static function serialize(?ProductImage $image): ?array
    {
        if (! $image) {
            return null;
        }

        return [
            'id' => $image->id,
            'image_url' => $image->image_url,
            'image_type' => $image->image_type instanceof ProductImageType
                ? $image->image_type->value
                : $image->image_type,
            'sort_order' => $image->sort_order,
            'duration_ms' => $image->duration_ms,
            'is_enabled' => $image->is_enabled,
        ];
    }
}
