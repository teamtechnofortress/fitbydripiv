<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCheckoutRequest;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {
    }

    public function create(CreateCheckoutRequest $request): JsonResponse
    {
        $result = $this->checkoutService->createCheckout($request->validated());

        return response()->json($result);
    }
}
