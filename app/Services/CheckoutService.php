<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PricingOption;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

class CheckoutService
{
    public function __construct(
        protected PricingService $pricingService
    ) {
    }

    public function createDraftOrder(array $data): array
    {
        Log::info('Checkout draft creation started', [
            'product_slug' => $data['product_slug'] ?? null,
            'pricing_type' => $data['pricing_type'] ?? null,
            'pricing_option_id' => $data['pricing_option_id'] ?? null,
        ]);

        [$product, $pricing, $pricingOption] = $this->resolveCheckoutSelection($data);
        $purchaseType = $pricing->pricing_type;
        $basePrice = $this->pricingService->resolveBasePrice($pricingOption);
        $finalPrice = $this->pricingService->resolvePrice($pricingOption);
        $frequencyMonths = $purchaseType === Order::PRICING_TYPE_SUBSCRIPTION
            ? $this->pricingService->resolveFrequencyMonths($pricingOption)
            : null;

        $order = DB::transaction(function () use ($data, $product, $purchaseType, $pricingOption, $basePrice, $finalPrice, $frequencyMonths) {
            return Order::create([
                'patient_id' => $data['patient_id'] ?? null,
                'product_id' => $product->id,
                'price' => $finalPrice,
                'base_amount' => $basePrice,
                'coupon_discount_amount' => 0,
                'final_amount' => $finalPrice,
                'currency' => $this->pricingService->resolveCurrency($product),
                'subscription_id' => null,
                'billing_cycle_number' => $purchaseType === Order::PRICING_TYPE_SUBSCRIPTION ? 1 : null,
                'purchase_type' => $purchaseType,
                'pricing_type' => $purchaseType,
                'pricing_option_id' => $pricingOption->id,
                'frequency_months' => $frequencyMonths,
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);
        });

        Log::info('Checkout draft created', [
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'purchase_type' => $purchaseType,
            'price' => $order->price,
        ]);

        return $this->buildDraftResponse($order, $product, $pricing, $pricingOption);
    }

    public function createCheckout(array $data): array
    {
        Log::info('Checkout session creation requested for existing draft order', [
            'order_uuid' => $data['order_uuid'] ?? null,
        ]);

        $order = Order::query()
            ->where('order_uuid', $data['order_uuid'])
            ->first();

        if (! $order) {
            abort(404, 'Checkout draft not found.');
        }

        if (! $order->patient_id) {
            abort(422, 'Order is not linked to a patient intake yet.');
        }

        Log::info('Checkout session will be created for existing order', [
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
        ]);

        return $this->createCheckoutForOrder($order);
    }

    public function createCheckoutForOrder(Order $order, ?Product $product = null): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $order->loadMissing('pricingOption');
        $product = $product ?? Product::with('coverImage')->find($order->product_id);
        $pricingOption = $order->pricingOption;

        if (! $product) {
            abort(404, 'Product not found for checkout.');
        }

        if (! $pricingOption) {
            abort(422, 'Pricing option not found for checkout.');
        }

        $purchaseType = $order->purchase_type;
        $basePrice = $this->pricingService->resolveBasePrice($pricingOption);
        $pricingOptionFinalPrice = $this->pricingService->resolvePrice($pricingOption);
        $frequencyMonths = $purchaseType === Order::PRICING_TYPE_SUBSCRIPTION
            ? $this->pricingService->resolveFrequencyMonths($pricingOption)
            : null;
        $recurring = $purchaseType === Order::PRICING_TYPE_SUBSCRIPTION
            ? $this->pricingService->resolveRecurringConfig($pricingOption)
            : null;
        $couponDiscountAmount = round((float) ($order->coupon_discount_amount ?? 0), 2);
        $finalPrice = max(0, round($pricingOptionFinalPrice - $couponDiscountAmount, 2));

        if ($purchaseType === Order::PRICING_TYPE_SUBSCRIPTION && ! $recurring) {
            abort(422, 'Selected subscription option is not configured for recurring checkout.');
        }

        if (
            (float) $order->price !== (float) $finalPrice
            || (float) $order->base_amount !== (float) $basePrice
            || (float) $order->final_amount !== (float) $finalPrice
            || $order->frequency_months !== $frequencyMonths
        ) {
            $order->base_amount = $basePrice;
            $order->final_amount = $finalPrice;
            $order->price = $finalPrice;
            $order->frequency_months = $frequencyMonths;
            $order->save();
        }

        $subscriptionMetadata = $purchaseType === Order::PRICING_TYPE_SUBSCRIPTION
            ? $this->buildSubscriptionMetadata($order, $product, $pricingOption, $finalPrice, $frequencyMonths)
            : [];

        $params = [
            'mode' => $purchaseType === Order::PRICING_TYPE_SUBSCRIPTION ? 'subscription' : 'payment',
            'success_url' => config('services.stripe.success_url'),
            'cancel_url' => config('services.stripe.cancel_url'),
            'line_items' => [],
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_uuid' => $order->order_uuid,
                'patient_id' => (string) ($order->patient_id ?? ''),
                'purchase_type' => $purchaseType,
                'product_id' => (string) $product->id,
                'pricing_option_id' => (string) $pricingOption->id,
                'frequency_months' => $frequencyMonths,
            ],
        ];

        if ($subscriptionMetadata !== []) {
            $params['metadata'] = array_merge($params['metadata'], $subscriptionMetadata);
        }

        $lineItem = [
            'price_data' => [
                'currency' => strtolower($order->currency ?? 'usd'),
                'product_data' => [
                    'name' => $product->name,
                    'description' => $pricingOption->label,
                ],
                'unit_amount' => (int) round($finalPrice * 100),
            ],
            'quantity' => 1,
        ];

        if ($recurring) {
            $lineItem['price_data']['recurring'] = $recurring;
        }

        $params['line_items'][] = $lineItem;

        if ($purchaseType === Order::PRICING_TYPE_SUBSCRIPTION) {
            $params['subscription_data'] = [
                'metadata' => array_merge([
                    'order_id' => (string) $order->id,
                    'order_uuid' => $order->order_uuid,
                    'patient_id' => (string) ($order->patient_id ?? ''),
                    'product_id' => (string) $product->id,
                    'pricing_option_id' => (string) $pricingOption->id,
                    'frequency_months' => $frequencyMonths,
                ], $subscriptionMetadata),
            ];
        } else {
            $params['payment_intent_data'] = [
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_uuid' => $order->order_uuid,
                    'patient_id' => (string) ($order->patient_id ?? ''),
                    'pricing_option_id' => (string) $pricingOption->id,
                ],
            ];
        }

        Log::info('Calling Stripe for checkout session', [
            'order_id' => $order->id,
            'purchase_type' => $purchaseType,
            'stripe_payload' => [
                'mode' => $params['mode'],
                'metadata' => $params['metadata'],
                'subscription_data' => $params['subscription_data'] ?? null,
                'payment_intent_data' => $params['payment_intent_data'] ?? null,
            ],
        ]);

        $session = StripeCheckoutSession::create($params);

        Log::info('Stripe checkout session created', [
            'order_id' => $order->id,
            'checkout_id' => $session->id,
            'purchase_type' => $purchaseType,
        ]);

        $order->update([
            'stripe_checkout_id' => $session->id,
        ]);

        Log::info('Stripe checkout session URL ready', [
            'order_id' => $order->id,
            'checkout_id' => $session->id,
            'checkout_url' => $session->url,
        ]);

        return [
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'checkout_id' => $session->id,
            'checkout_url' => $session->url,
        ];
    }

    protected function resolveCheckoutSelection(array $data): array
    {
        $product = Product::query()
            ->with([
                'coverImage',
                'pricing' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with(['options' => fn ($options) => $options->orderBy('sort_order')]),
            ])
            ->live()
            ->where('slug', $data['product_slug'])
            ->first();

        if (! $product) {
            abort(404, 'Product not found.');
        }

        $selection = $this->pricingService->resolveSelection(
            $product,
            $data['pricing_type'],
            $data['pricing_option_id']
        );

        return [$product, $selection['pricing'], $selection['option']];
    }

    protected function buildDraftResponse(Order $order, Product $product, object $pricing, PricingOption $pricingOption): array
    {
        $frequencyMonths = $pricing->pricing_type === Order::PRICING_TYPE_SUBSCRIPTION
            ? $this->pricingService->resolveFrequencyMonths($pricingOption)
            : null;

        return [
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'purchase_type' => $order->purchase_type,
            'pricing_type' => $order->pricing_type,
            'price' => $order->price,
            'base_amount' => $order->base_amount,
            'final_amount' => $order->final_amount,
            'currency' => $order->currency,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category' => $product->category,
                'description' => $product->description,
                'cover_image' => $product->coverImage ? [
                    'id' => $product->coverImage->id,
                    'image_url' => $product->coverImage->image_url,
                    'image_type' => $product->coverImage->image_type,
                ] : null,
            ],
            'pricing' => [
                'id' => $pricing->id,
                'pricing_type' => $pricing->pricing_type,
                'title' => $pricing->title,
                'description' => $pricing->description,
                'selected_option' => [
                    'id' => $pricingOption->id,
                    'label' => $pricingOption->label,
                    'billing_interval' => $pricingOption->billing_interval,
                    'interval_count' => $pricingOption->interval_count,
                    'price' => $pricingOption->price,
                    'discount_percent' => $pricingOption->discount_percent,
                    'final_price' => $pricingOption->final_price,
                    'sort_order' => $pricingOption->sort_order,
                    'is_default' => $pricingOption->is_default,
                    'metadata' => $pricingOption->metadata,
                ],
                'frequency_months' => $frequencyMonths,
            ],
        ];
    }

    protected function buildSubscriptionMetadata(
        Order $order,
        Product $product,
        PricingOption $pricingOption,
        float $finalPrice,
        ?int $frequencyMonths
    ): array {
        $pricingMetadata = is_array($pricingOption->metadata) ? $pricingOption->metadata : [];
        $discountPercentage = round((float) ($pricingOption->discount_percent ?? 0), 2);
        $baseRecurringAmount = round((float) ($pricingOption->price ?? $finalPrice), 2);
        $discountedRecurringAmount = round($finalPrice, 2);
        $discountDurationType = $pricingMetadata['discount_duration_type']
            ?? ($discountPercentage > 0 ? 'forever' : '');
        $discountRemainingCycles = $pricingMetadata['discount_remaining_cycles'] ?? '';
        $totalCycles = $pricingMetadata['total_cycles'] ?? '';

        return [
            'pricing_option_id' => (string) $pricingOption->id,
            'product_id' => (string) $product->id,
            'frequency_months' => $frequencyMonths === null ? '' : (string) $frequencyMonths,
            'total_cycles' => $totalCycles === '' ? '' : (string) max(1, (int) $totalCycles),
            'base_recurring_amount' => number_format($baseRecurringAmount, 2, '.', ''),
            'discounted_recurring_amount' => number_format($discountedRecurringAmount, 2, '.', ''),
            'discount_percentage' => number_format($discountPercentage, 2, '.', ''),
            'discount_duration_type' => (string) $discountDurationType,
            'discount_remaining_cycles' => $discountRemainingCycles === '' ? '' : (string) max(0, (int) $discountRemainingCycles),
        ];
    }
}
