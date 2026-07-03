<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CheckoutResponseService;
use App\Services\OrderJourneyService;
use Illuminate\Http\JsonResponse;

class OrderJourneyController extends Controller
{
    public function __construct(
        private OrderJourneyService $journeyService,
        private CheckoutResponseService $checkoutResponseService,
    ) {}

    public function show(Order $order): JsonResponse
    {
        $journey = $this->journeyService->build($order);
        $context = $this->checkoutResponseService->buildOrderContext($order);

        return response()->json(array_merge($journey, $context));
    }
}
