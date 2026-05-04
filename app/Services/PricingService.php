<?php

namespace App\Services;

use App\Models\PricingOption;
use App\Models\Product;
use App\Models\ProductPricing;
use InvalidArgumentException;

class PricingService
{
    /**
     * @return array{pricing: ProductPricing, option: PricingOption}
     */
    public function resolveSelection(Product $product, string $pricingType, string $pricingOptionId): array
    {
        $pricing = $product->pricing
            ->firstWhere('pricing_type', $pricingType);

        if (! $pricing || ! $pricing->is_active) {
            throw new InvalidArgumentException("Selected pricing type '{$pricingType}' is not available.");
        }

        $option = $pricing->options
            ->firstWhere('id', $pricingOptionId);

        if (! $option) {
            throw new InvalidArgumentException('Selected pricing option is not valid for this product.');
        }

        if ($pricingType === 'subscription' && $option->billing_interval === 'one_time') {
            throw new InvalidArgumentException('Subscription pricing option must use a recurring billing interval.');
        }

        if ($pricingType === 'one_time' && $option->billing_interval !== 'one_time') {
            throw new InvalidArgumentException('One-time pricing option must use the one_time billing interval.');
        }

        return [
            'pricing' => $pricing,
            'option' => $option,
        ];
    }

    public function resolveCurrency(Product $product): string
    {
        return 'USD';
    }

    public function resolvePrice(PricingOption $option): float
    {
        return round((float) $option->final_price, 2);
    }

    public function resolveBasePrice(PricingOption $option): float
    {
        return round((float) ($option->price ?? $option->final_price), 2);
    }

    public function resolveFrequencyMonths(PricingOption $option): ?int
    {
        return match ($option->billing_interval) {
            'month' => max(1, (int) $option->interval_count),
            'year' => max(1, (int) $option->interval_count) * 12,
            'week', 'day' => 1,
            default => null,
        };
    }

    public function resolveRecurringConfig(PricingOption $option): ?array
    {
        if ($option->billing_interval === 'one_time') {
            return null;
        }

        return [
            'interval' => $option->billing_interval,
            'interval_count' => max(1, (int) $option->interval_count),
        ];
    }
}
