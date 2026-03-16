<?php

namespace App\Services;

use InvalidArgumentException;

class PricingService
{
    public function getBasePrice(object $product, string $pricingType): float
    {
        $price = match ($pricingType) {
            'base' => $product->base_price,
            'micro' => $product->micro_dose_price,
            'sample' => $product->sample_price,
            default => throw new InvalidArgumentException('Invalid pricing type'),
        };

        if ($price === null) {
            throw new InvalidArgumentException("Selected pricing type '{$pricingType}' is not available.");
        }

        return round((float) $price, 2);
    }

    public function applyDiscount(float $amount, float $discountPercentage): float
    {
        return round($amount - ($amount * ($discountPercentage / 100)), 2);
    }
}
