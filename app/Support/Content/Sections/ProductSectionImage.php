<?php

namespace App\Support\Content\Sections;

use App\Enums\ProductImageType;
use App\enums\SectionType;
use App\Models\Product;
use App\Models\ProductImage;

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
        ];
    }
}
