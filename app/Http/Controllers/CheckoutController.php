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
        $order = Order::with('product')->where('stripe_checkout_id', $sessionId)->first();

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
                'currency' => $order->product->currency,
                'base_price' => $order->product->base_price,
                'micro_dose_price' => $order->product->micro_dose_price,
                'sample_price' => $order->product->sample_price,
            ] : null,
        ]);
    }
}
