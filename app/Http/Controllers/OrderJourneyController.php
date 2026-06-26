<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderJourneyService;
use Illuminate\Http\JsonResponse;

class OrderJourneyController extends Controller
{
    public function __construct(
        private OrderJourneyService $journeyService,
    ) {}

    public function show(Order $order): JsonResponse
    {
        return response()->json($this->journeyService->build($order));
    }
}
