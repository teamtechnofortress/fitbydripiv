<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyCouponRequest;
use App\Http\Requests\CreateCheckoutRequest;
use App\Http\Requests\CreateOrderDraftRequest;
use App\Models\Order;
use App\Services\CheckoutResponseService;
use App\Services\CheckoutService;
use App\Services\CouponService;
use App\Services\IdempotencyService;
use App\Services\OrderJourneyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService,
        protected CheckoutResponseService $checkoutResponseService,
        protected CouponService $couponService,
        protected IdempotencyService $idempotencyService,
        protected OrderJourneyService $orderJourneyService
    ) {}

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

    public function applyCoupon(ApplyCouponRequest $request): JsonResponse
    {
        $order = $this->couponService->applyCouponToOrder(
            $request->validated()['order_uuid'],
            $request->validated()['coupon_code']
        );

        return response()->json(
            $this->checkoutResponseService->buildResponse(
                'Coupon applied successfully.',
                $order
            )
        );
    }

    public function paymentConfirmation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:255'],
        ]);

        $order = $this->findOrderBySession($validated['session_id']);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found for this checkout session.',
            ], 404);
        }

        return response()->json($this->buildPaymentConfirmationResponse($order));
    }

    public function showBySession(string $sessionId): JsonResponse
    {
        $order = $this->findOrderBySession($sessionId);

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
            'journey' => $this->orderJourneyService->build($order),
        ]);
    }

    private function findOrderBySession(string $sessionId): ?Order
    {
        return Order::with([
            'patient',
            'product.coverImage',
            'pricingOption',
            'coupon',
            'flowRun',
        ])->where('stripe_checkout_id', $sessionId)->first();
    }

    private function buildPaymentConfirmationResponse(Order $order): array
    {
        $journey = $this->orderJourneyService->build($order);
        $context = $this->checkoutResponseService->buildOrderContext($order);

        return [
            'success' => true,
            'message' => $this->paymentConfirmationMessage($order->payment_status),
            'journey_ready' => (bool) ($journey['is_ready'] ?? false),
            'data' => array_merge($context, [
                'payment' => [
                    'status' => $order->payment_status,
                    'confirmed' => $order->payment_status === 'paid',
                    'failed' => $order->payment_status === 'failed',
                    'pending' => $order->payment_status !== 'paid' && $order->payment_status !== 'failed',
                    'poll_after_seconds' => $journey['retry_after_seconds'] ?? null,
                ],
                'journey' => $journey,
            ]),
        ];
    }

    private function paymentConfirmationMessage(?string $paymentStatus): string
    {
        return match ($paymentStatus) {
            'paid' => 'Payment confirmed.',
            'failed' => 'Payment failed.',
            default => 'Payment confirmation is pending.',
        };
    }
}
