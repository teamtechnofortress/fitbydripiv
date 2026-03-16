<?php

namespace App\Services;

use App\Models\CmsProduct;
use App\Models\CmsSubscriptionDiscount;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

class CheckoutService
{
    public function __construct(
        protected PricingService $pricingService
    ) {
    }

    public function createCheckout(array $data): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));

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
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);
        });

        $params = [
            'mode' => $purchaseType === 'subscription' ? 'subscription' : 'payment',
            'success_url' => config('services.stripe.success_url'),
            'cancel_url' => config('services.stripe.cancel_url'),
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($product->currency ?? 'usd'),
                    'product_data' => [
                        'name' => $product->name,
                    ],
                    'unit_amount' => (int) round($finalPrice * 100),
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'order_id' => (string) $order->id,
                'patient_id' => (string) $order->patient_id,
                'purchase_type' => $purchaseType,
                'product_id' => (string) $product->id,
            ],
        ];

        if ($purchaseType === 'subscription') {
            $params['subscription_data'] = [
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'patient_id' => (string) $order->patient_id,
                    'product_id' => (string) $product->id,
                ],
            ];
        } else {
            $params['payment_intent_data'] = [
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'patient_id' => (string) $order->patient_id,
                ],
            ];
        }

        $session = StripeCheckoutSession::create($params);

        $order->update([
            'stripe_checkout_id' => $session->id,
        ]);

        return [
            'order_id' => $order->id,
            'checkout_id' => $session->id,
            'checkout_url' => $session->url,
        ];
    }
}
