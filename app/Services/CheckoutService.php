<?php

namespace App\Services;

use App\Models\CmsProduct;
use App\Models\CmsSubscriptionDiscount;
use App\Models\Order;
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
            'subscription_discount_id' => $data['subscription_discount_id'] ?? null,
        ]);

        $product = CmsProduct::with('subscriptionDiscounts')
            ->where('slug', $data['product_slug'])
            ->first();

        if (! $product) {
            abort(404, 'Product not found.');
        }

        $basePrice = $this->pricingService->getBasePrice($product, $data['pricing_type']);

        $purchaseType = 'one_time';
        $discount = null;
        $finalPrice = $basePrice;

        if (! empty($data['subscription_discount_id'])) {
            $discount = CmsSubscriptionDiscount::where('id', $data['subscription_discount_id'])
                ->where('product_id', $product->id)
                ->first();

            if (! $discount) {
                throw new InvalidArgumentException('Invalid subscription discount selected.');
            }

            $purchaseType = 'subscription';
            $finalPrice = $this->pricingService->applyDiscount(
                $basePrice,
                (float) $discount->discount_percentage
            );
        }

        $order = DB::transaction(function () use ($data, $product, $purchaseType, $discount, $finalPrice) {
            return Order::create([
                'patient_id' => $data['patient_id'] ?? null,
                'product_id' => $product->id,
                'price' => $finalPrice,
                'currency' => $product->currency ?? 'USD',
                'subscription_id' => null,
                'billing_cycle_number' => $purchaseType === 'subscription' ? 1 : null,
                'purchase_type' => $purchaseType,
                'pricing_type' => $data['pricing_type'],
                'subscription_discount_id' => $discount?->id,
                'frequency_months' => $discount?->frequency_months,
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

        return [
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'purchase_type' => $purchaseType,
            'pricing_type' => $order->pricing_type,
            'price' => $order->price,
            'currency' => $order->currency,
            'subscription_discount' => $discount ? [
                'id' => $discount->id,
                'frequency_months' => $discount->frequency_months,
                'discount_percentage' => $discount->discount_percentage,
            ] : null,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'base_price' => $product->base_price,
                'micro_dose_price' => $product->micro_dose_price,
                'sample_price' => $product->sample_price,
                'currency' => $product->currency ?? 'USD',
                'subscription_discounts' => $product->subscriptionDiscounts
                    ->map(fn (CmsSubscriptionDiscount $item) => [
                        'id' => $item->id,
                        'frequency_months' => $item->frequency_months,
                        'discount_percentage' => $item->discount_percentage,
                    ])
                    ->values()
                    ->all(),
            ],
        ];

        Log::info('Checkout draft response prepared', [
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'payload_keys' => array_keys($response),
        ]);

        return $response;
    }

    public function createCheckout(array $data): array
    {
        Log::info('Direct checkout creation requested', [
            'product_slug' => $data['product_slug'] ?? null,
            'pricing_type' => $data['pricing_type'] ?? null,
            'patient_id' => $data['patient_id'] ?? null,
        ]);

        $product = CmsProduct::where('slug', $data['product_slug'])->first();

        if (! $product) {
            abort(404, 'Product not found.');
        }

        $basePrice = $this->pricingService->getBasePrice($product, $data['pricing_type']);

        $purchaseType = 'one_time';
        $discount = null;
        $finalPrice = $basePrice;

        if (! empty($data['subscription_discount_id'])) {
            $discount = CmsSubscriptionDiscount::where('id', $data['subscription_discount_id'])
                ->where('product_id', $product->id)
                ->first();

            if (! $discount) {
                throw new InvalidArgumentException('Invalid subscription discount selected.');
            }

            $purchaseType = 'subscription';
            $finalPrice = $this->pricingService->applyDiscount(
                $basePrice,
                (float) $discount->discount_percentage
            );
        }

        $order = DB::transaction(function () use ($data, $product, $purchaseType, $discount, $finalPrice) {
            return Order::create([
                'patient_id' => $data['patient_id'],
                'product_id' => $product->id,
                'price' => $finalPrice,
                'currency' => $product->currency ?? 'USD',
                'subscription_id' => null,
                'billing_cycle_number' => $purchaseType === 'subscription' ? 1 : null,
                'purchase_type' => $purchaseType,
                'pricing_type' => $data['pricing_type'],
                'subscription_discount_id' => $discount?->id,
                'frequency_months' => $discount?->frequency_months,
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);
        });

        Log::info('Order created for direct checkout', [
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
        ]);

        return $this->createCheckoutForOrder($order, $product);
    }

    public function createCheckoutForOrder(Order $order, ?CmsProduct $product = null): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $order->loadMissing('subscriptionDiscount');

        $product = $product ?? CmsProduct::find($order->product_id);

        if (! $product) {
            abort(404, 'Product not found for checkout.');
        }

        $purchaseType = $order->purchase_type;
        $discount = $order->subscriptionDiscount;
        $durationMonths = max(1, (int) ($order->frequency_months ?? $discount?->frequency_months ?? 1));
        $finalPrice = $order->price;

        if ($purchaseType === 'subscription') {
            if (! $discount) {
                abort(422, 'Subscription discount is required for subscription purchases.');
            }

            $basePrice = $this->pricingService->getBasePrice($product, $order->pricing_type);
            $finalPrice = $this->pricingService->applyDiscount($basePrice, (float) $discount->discount_percentage);
            $durationMonths = max(1, (int) $discount->frequency_months);

            if ((float) $order->price !== (float) $finalPrice || $order->frequency_months !== $durationMonths) {
                $order->price = $finalPrice;
                $order->frequency_months = $durationMonths;
                $order->save();
            }
        }

        $params = [
            'mode' => $purchaseType === 'subscription' ? 'subscription' : 'payment',
            'success_url' => config('services.stripe.success_url'),
            'cancel_url' => config('services.stripe.cancel_url'),
            'line_items' => [],
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_uuid' => $order->order_uuid,
                'patient_id' => (string) ($order->patient_id ?? ''),
                'purchase_type' => $purchaseType,
                'product_id' => (string) $product->id,
                'subscription_discount_id' => $discount?->id,
                'frequency_months' => $durationMonths,
            ],
        ];

        $lineItem = [
            'price_data' => [
                'currency' => strtolower($order->currency ?? $product->currency ?? 'usd'),
                'product_data' => [
                    'name' => $product->name,
                ],
                'unit_amount' => (int) round((float) $finalPrice * 100),
            ],
            'quantity' => 1,
        ];

        if ($purchaseType === 'subscription') {
            $lineItem['price_data']['recurring'] = [
                'interval' => 'month',
                'interval_count' => 1,
            ];
        }

        $params['line_items'][] = $lineItem;

        if ($purchaseType === 'subscription') {
            $params['subscription_data'] = [
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_uuid' => $order->order_uuid,
                    'patient_id' => (string) ($order->patient_id ?? ''),
                    'product_id' => (string) $product->id,
                    'subscription_discount_id' => $discount?->id,
                    'frequency_months' => $durationMonths,
                ],
            ];
        } else {
            $params['payment_intent_data'] = [
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_uuid' => $order->order_uuid,
                    'patient_id' => (string) ($order->patient_id ?? ''),
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
}
