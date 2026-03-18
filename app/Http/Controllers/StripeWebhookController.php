<?php

namespace App\Http\Controllers;

use App\Services\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Throwable;

class StripeWebhookController extends Controller
{
    public function __construct(
        protected StripeWebhookService $stripeWebhookService
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = app()->bound('stripe.raw_body') ? app('stripe.raw_body') : null;
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if (empty($secret)) {
            Log::error('Stripe webhook secret missing. Set STRIPE_WEBHOOK_SECRET.');
            return response()->json(['message' => 'Webhook secret not configured'], 500);
        }

        if (empty($signature)) {
            Log::warning('Stripe webhook received without Stripe-Signature header.');
            return response()->json(['message' => 'Missing Stripe-Signature header'], 400);
        }

        if ($payload === null || $payload === '') {
            Log::warning('Stripe webhook received without payload body.');
            return response()->json(['message' => 'Missing webhook payload'], 400);
        }

        Log::info('Stripe webhook raw payload', ['payload' => $payload]);

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Invalid webhook signature'], 400);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook payload invalid', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Invalid webhook payload'], 400);
        }

        try {
            $this->stripeWebhookService->processEvent($event);
        } catch (Throwable $e) {
            Log::error('Stripe webhook processing failed', [
                'event_id' => $event->id,
                'type' => $event->type,
                'message' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Webhook processing failed'], 500);
        }

        Log::info('Stripe webhook processed successfully', [
            'event_id' => $event->id,
            'type' => $event->type,
        ]);

        return response()->json(['message' => 'OK']);
    }
}
