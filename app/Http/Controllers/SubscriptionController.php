<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscriptionApi;

class SubscriptionController extends Controller
{
    public function cancel(int $id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);

        if (! $subscription->stripe_subscription_id) {
            return response()->json(['message' => 'Stripe subscription ID missing'], 422);
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        StripeSubscriptionApi::cancel($subscription->stripe_subscription_id, []);

        $subscription->update([
            'status' => 'cancelled',
            'end_date' => today(),
        ]);

        return response()->json([
            'message' => 'Subscription cancelled successfully',
        ]);
    }
}
