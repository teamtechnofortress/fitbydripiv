<?php

namespace App\Http\Controllers;

use App\Services\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __construct(
        protected StripeWebhookService $stripeWebhookService
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException $e) {
            return response()->json(['message' => 'Invalid webhook signature'], 400);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['message' => 'Invalid webhook payload'], 400);
        }

        $this->stripeWebhookService->processEvent($event);

        return response()->json(['message' => 'Webhook handled successfully']);
    }
}
