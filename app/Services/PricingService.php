<?php

namespace App\Services;

use App\Models\Order;
use InvalidArgumentException;

class PricingService
{
    public function getBasePrice(object $product, string $pricingType): float
    {
        $price = match ($pricingType) {
            Order::PRICING_TYPE_BASE => $product->base_price,
            Order::PRICING_TYPE_MICRO_DOSE => $product->micro_dose_price,
            Order::PRICING_TYPE_SAMPLE => $product->sample_price,
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
