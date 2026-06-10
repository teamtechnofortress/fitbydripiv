<?php

namespace App\enums;

enum ProductImageType: string
{
    case COVER = 'cover';
    case PRODUCT_DETAIL_MAIN = 'product_detail_main';
    case FEATURED_CARD = 'featured_card';
    case PRODUCT_SELECT_CARD = 'product_select_card';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::COVER => 'Cover Image',
            self::PRODUCT_DETAIL_MAIN => 'Product Detail Main Image',
            self::FEATURED_CARD => 'Featured Card Image',
            self::PRODUCT_SELECT_CARD => 'Product Select Card Image',
        };
    }

    public function maxImages(): int
    {
        return match ($this) {
            self::COVER,
            self::FEATURED_CARD=> 1,
            self::PRODUCT_SELECT_CARD => 1,
            self::PRODUCT_DETAIL_MAIN => 1,
        };
    }

    public function isRequired(): bool
    {
        return match ($this) {
            self::COVER => true,
            default => false,
        };
    }

    public function usedFor(): string
    {
        return match ($this) {
            self::COVER => 'Default fallback image',
            self::PRODUCT_DETAIL_MAIN => 'Product detail page',
            self::FEATURED_CARD => 'Featured products section',
            self::PRODUCT_SELECT_CARD => 'Product grid / product selection card',
        };
    }

    public function frontendConfig(): array
    {
        return [
            'type' => $this->value,
            'label' => $this->label(),
            'required' => $this->isRequired(),
            'max_images' => $this->maxImages(),
            'used_for' => $this->usedFor(),
        ];
    }

    public static function frontendConfigs(): array
    {
        return array_map(
            fn (self $type) => $type->frontendConfig(),
            self::cases()
        );
    }
}
