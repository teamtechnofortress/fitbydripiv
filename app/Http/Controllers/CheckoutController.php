<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCheckoutRequest;
use App\Http\Requests\CreateOrderDraftRequest;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService,
        protected IdempotencyService $idempotencyService
    ) {
    }

    public function create(CreateCheckoutRequest $request): JsonResponse
    {
        $result = $this->checkoutService->createCheckout($request->validated());

        return response()->json($result);
    }

    public function createDraft(CreateOrderDraftRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $result = $this->idempotencyService->handle(
            $request->header('Idempotency-Key'),
            'checkout.draft',
            $payload,
            fn () => $this->checkoutService->createDraftOrder($payload)
        );

        return response()->json($result, 201);
    }

    public function showBySession(string $sessionId): JsonResponse
    {
        $order = Order::with(['product.coverImage', 'pricingOption'])->where('stripe_checkout_id', $sessionId)->first();

        if (! $order) {
            return response()->json([
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'purchase_type' => $order->purchase_type,
            'frequency_months' => $order->frequency_months,
            'pricing_type' => $order->pricing_type,
            'product' => $order->product ? [
                'id' => $order->product->id,
                'name' => $order->product->name,
                'slug' => $order->product->slug,
                'category' => $order->product->category,
                'description' => $order->product->description,
                'cover_image' => $order->product->coverImage ? [
                    'id' => $order->product->coverImage->id,
                    'image_url' => $order->product->coverImage->image_url,
                    'image_type' => $order->product->coverImage->image_type,
                ] : null,
            ] : null,
            'pricing_option' => $order->pricingOption ? [
                'id' => $order->pricingOption->id,
                'label' => $order->pricingOption->label,
                'billing_interval' => $order->pricingOption->billing_interval,
                'interval_count' => $order->pricingOption->interval_count,
                'price' => $order->pricingOption->price,
                'discount_percent' => $order->pricingOption->discount_percent,
                'final_price' => $order->pricingOption->final_price,
                'metadata' => $order->pricingOption->metadata,
            ] : null,
        ]);
    }
}
