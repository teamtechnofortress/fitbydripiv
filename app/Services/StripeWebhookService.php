<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\StripeWebhookEvent;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscriptionApi;

class StripeWebhookService
{
    // ---------------------------------------------------------------------------
    // Entry point
    // ---------------------------------------------------------------------------

    public function processEvent(object $event): void
    {
        // --- Idempotency guard ---
        // Stripe may deliver the same event more than once (network retries, etc.).
        // We store every event and skip it if already processed.
        $existing = StripeWebhookEvent::where('stripe_event_id', $event->id)->first();

        if ($existing && $existing->processed) {
            Log::info('Stripe webhook event already processed — skipping', [
                'event_id' => $event->id,
                'type'     => $event->type,
            ]);
            return;
        }

        // Create a record if this is the first time we've seen this event.
        $webhookEvent = $existing ?? StripeWebhookEvent::create([
            'stripe_event_id' => $event->id,
            'event_type'      => $event->type,
            'payload_json'    => json_decode(json_encode($event), true), // stdClass → array
            'processed'       => false,
            'webhookable_id'   => null,
            'webhookable_type' => null,
        ]);

        DB::transaction(function () use ($event, $webhookEvent) {
            match ($event->type) {
                // Subscription checkout completed (mode = "subscription")
                'checkout.session.completed'      => $this->handleCheckoutSessionCompleted($event->data->object, $webhookEvent),

                // Recurring invoice paid (covers both first cycle and renewals)
                'invoice.payment_succeeded'       => $this->handleInvoicePaymentSucceeded($event->data->object, $webhookEvent),

                // Invoice payment failed (retry or final failure)
                'invoice.payment_failed'          => $this->handleInvoicePaymentFailed($event->data->object, $webhookEvent),

                // Subscription deleted/cancelled in Stripe (manual cancel, final failure, etc.)
                'customer.subscription.deleted'   => $this->handleSubscriptionDeleted($event->data->object, $webhookEvent),

                // Subscription status changed (paused, resumed, trial started, etc.)
                'customer.subscription.updated'   => $this->handleSubscriptionUpdated($event->data->object, $webhookEvent),

                // One-time payment succeeded (non-subscription checkout or direct PaymentIntent)
                'payment_intent.succeeded'        => $this->handlePaymentIntentSucceeded($event->data->object, $webhookEvent),
                'payment_intent.payment_failed'   => $this->handlePaymentIntentFailed($event->data->object, $webhookEvent),

                default => Log::info('Stripe webhook event type not handled', ['type' => $event->type]),
            };

            $webhookEvent->update([
                'processed'    => true,
                'processed_at' => now(),
            ]);
        });
    }

    // ---------------------------------------------------------------------------
    // checkout.session.completed
    // ---------------------------------------------------------------------------
    // Fires when the Stripe Checkout page finishes.
    // For subscription mode this is the canonical "customer has subscribed" signal.
    // ---------------------------------------------------------------------------

    protected function handleCheckoutSessionCompleted(object $session, StripeWebhookEvent $webhookEvent): void
    {
        // Find the order via metadata first, then fall back to checkout session ID.
        $orderId = $session->metadata->order_id ?? null;

        $order = $orderId
            ? Order::find($orderId)
            : Order::where('stripe_checkout_id', $session->id)->first();

        if (! $order) {
            Log::warning('checkout.session.completed: no matching order found', [
                'session_id' => $session->id,
                'order_id'   => $orderId,
            ]);
            return;
        }

        // For subscription mode, payment_intent is null on the session —
        // the PaymentIntent lives on the invoice instead. Guard both cases.
        $paymentIntentId = $session->payment_intent ?? $order->stripe_payment_intent_id ?? null;

        $order->update([
            'payment_status'          => 'paid',
            'status'                  => 'completed',
            'stripe_payment_intent_id' => $paymentIntentId,
        ]);

        // Record the payment only once — avoid duplicates if this event is
        // re-delivered.
        if (! Payment::where('order_id', $order->id)->where('status', 'paid')->exists()) {
            Payment::create([
                'order_id'                 => $order->id,
                'stripe_payment_intent_id' => $paymentIntentId,
                'amount'                   => $order->price,
                'currency'                 => $order->currency,
                'status'                   => 'paid',
                'failure_reason'           => null,
            ]);
        }

        $webhookEvent->webhookable()->associate($order);
        $webhookEvent->save();

        // --- Create the local Subscription record for subscription-mode sessions ---
        // Only run this if we haven't already created one for this order.
        if ($order->purchase_type === 'subscription' && ! $order->subscription_id) {

            $stripeSubscriptionId = $session->subscription ?? null;
            $frequency            = $this->resolveFrequencyMonths($order);

            $subscription = Subscription::create([
                'order_id'                => $order->id,
                'patient_id'              => $order->patient_id,
                'product_id'              => $order->product_id,
                'current_cycle_number'    => 1,
                'total_cycles'            => $this->resolveTotalCycles($order),
                'stripe_subscription_id'  => $stripeSubscriptionId,
                'stripe_customer_id'      => $session->customer ?? null,
                'billing_frequency_months' => $frequency,
                'discount_percentage'     => $this->resolveDiscountPercentage($order),
                'start_date'              => today(),
                'next_billing_date'       => now()->addMonths($frequency)->toDateString(),
                'end_date'                => null,
                'status'                  => 'active',
            ]);

            $order->update([
                'subscription_id'     => $subscription->id,
                'billing_cycle_number' => 1,
            ]);

            if ($stripeSubscriptionId && $frequency > 0) {
                Stripe::setApiKey(config('services.stripe.secret'));
                try {
                    $stripeSubscription = StripeSubscriptionApi::retrieve($stripeSubscriptionId);
                    $periodStart = Carbon::createFromTimestamp(
                        $stripeSubscription->current_period_start ?? time()
                    );
                    $cancelAt = $periodStart->copy()->addMonths($frequency)->timestamp;

                    StripeSubscriptionApi::update($stripeSubscriptionId, [
                        'cancel_at' => $cancelAt,
                    ]);

                    Log::info('Stripe subscription scheduled for auto-cancel', [
                        'stripe_subscription_id' => $stripeSubscriptionId,
                        'cancel_after_months' => $frequency,
                        'cancel_at' => $cancelAt,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to schedule Stripe subscription cancellation', [
                        'stripe_subscription_id' => $stripeSubscriptionId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Subscription created from checkout.session.completed', [
                'subscription_id'        => $subscription->id,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'order_id'               => $order->id,
            ]);
        }
    }

    // ---------------------------------------------------------------------------
    // invoice.payment_succeeded
    // ---------------------------------------------------------------------------
    // Fires for EVERY successful invoice payment, including the very first one
    // (billing_reason = "subscription_create") and all renewals
    // (billing_reason = "subscription_cycle").
    //
    // Strategy:
    //   • billing_reason = "subscription_create"  → first cycle, order already
    //     exists from checkout. Just make sure payment is recorded and skip new order.
    //   • billing_reason = "subscription_cycle"   → renewal, create a new order
    //     and advance the cycle counter.
    // ---------------------------------------------------------------------------

    protected function handleInvoicePaymentSucceeded(object $invoice, StripeWebhookEvent $webhookEvent): void
    {
        if (empty($invoice->subscription)) {
            // Not a subscription invoice (e.g. one-off invoice). Nothing to do.
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();

        if (! $subscription) {
            Log::warning('invoice.payment_succeeded: no matching local subscription found', [
                'stripe_subscription_id' => $invoice->subscription,
                'invoice_id'             => $invoice->id,
            ]);
            return;
        }

        // -----------------------------------------------------------------------
        // Guard: First-cycle invoice (billing_reason = "subscription_create")
        // -----------------------------------------------------------------------
        // The checkout.session.completed handler already recorded the payment and
        // created the order for cycle 1. We must NOT create a duplicate order here.
        //
        // We identify a first-cycle invoice by:
        //   1. billing_reason field (most reliable).
        //   2. Fallback: the original order already has this invoice ID stored, or
        //      the subscription was just created (current_cycle_number === 1 and the
        //      original order's stripe_payment_intent_id matches).
        // -----------------------------------------------------------------------
        $billingReason = $invoice->billing_reason ?? null;

        if ($billingReason === 'subscription_create') {
            // First cycle — ensure the original order has the invoice ID linked
            // and a payment record exists, then bail out without creating a new order.
            $originalOrder = Order::where('subscription_id', $subscription->id)
                ->orderBy('id')
                ->first();

            if ($originalOrder && empty($originalOrder->stripe_invoice_id)) {
                $originalOrder->update(['stripe_invoice_id' => $invoice->id]);
            }

            if ($originalOrder && ! Payment::where('order_id', $originalOrder->id)->where('status', 'paid')->exists()) {
                Payment::create([
                    'order_id'                 => $originalOrder->id,
                    'stripe_payment_intent_id' => $invoice->payment_intent ?? null,
                    'amount'                   => round(((float) ($invoice->amount_paid ?? 0)) / 100, 2),
                    'currency'                 => strtoupper($invoice->currency ?? 'USD'),
                    'status'                   => 'paid',
                    'failure_reason'           => null,
                ]);
            }

            $webhookEvent->webhookable()->associate($subscription);
            $webhookEvent->save();
            return;
        }

        // -----------------------------------------------------------------------
        // Renewal invoice — idempotency check via stripe_invoice_id
        // -----------------------------------------------------------------------
        if (Order::where('stripe_invoice_id', $invoice->id)->exists()) {
            Log::info('invoice.payment_succeeded: renewal order already exists — skipping', [
                'invoice_id' => $invoice->id,
            ]);
            $webhookEvent->webhookable()->associate($subscription);
            $webhookEvent->save();
            return;
        }

        // -----------------------------------------------------------------------
        // Create the renewal order
        // -----------------------------------------------------------------------
        $nextCycle = $subscription->current_cycle_number + 1;

        $order = Order::create([
            'patient_id'               => $subscription->patient_id,
            'product_id'               => $subscription->product_id,
            'price'                    => round(((float) ($invoice->amount_paid ?? 0)) / 100, 2),
            'currency'                 => strtoupper($invoice->currency ?? 'USD'),
            'subscription_id'          => $subscription->id,
            'billing_cycle_number'     => $nextCycle,
            'purchase_type'            => 'subscription',
            'pricing_type'             => $subscription->order?->pricing_type ?? 'base',
            'subscription_discount_id' => $subscription->order?->subscription_discount_id,
            'status'                   => 'completed',
            'payment_status'           => 'paid',
            'stripe_checkout_id'       => null,
            'stripe_payment_intent_id' => $invoice->payment_intent ?? null,
            'stripe_invoice_id'        => $invoice->id,
        ]);

        Payment::create([
            'order_id'                 => $order->id,
            'stripe_payment_intent_id' => $invoice->payment_intent ?? null,
            'amount'                   => $order->price,
            'currency'                 => $order->currency,
            'status'                   => 'paid',
            'failure_reason'           => null,
        ]);

        // Advance the cycle counter and next billing date.
        $subscription->update([
            'current_cycle_number' => $nextCycle,
            'next_billing_date'    => Carbon::parse($subscription->next_billing_date ?? now())
                ->addMonths($subscription->billing_frequency_months)
                ->toDateString(),
        ]);

        $webhookEvent->webhookable()->associate($subscription);
        $webhookEvent->save();

        Log::info('Renewal order created from invoice.payment_succeeded', [
            'order_id'        => $order->id,
            'cycle'           => $nextCycle,
            'subscription_id' => $subscription->id,
        ]);

        // -----------------------------------------------------------------------
        // Auto-cancel if the subscription has reached its total cycle limit
        // -----------------------------------------------------------------------
        if (
            ! is_null($subscription->total_cycles)
            && $subscription->current_cycle_number >= $subscription->total_cycles
        ) {
            $this->cancelStripeSubscription($subscription);
        }
    }

    // ---------------------------------------------------------------------------
    // invoice.payment_failed
    // ---------------------------------------------------------------------------

    protected function handleInvoicePaymentFailed(object $invoice, StripeWebhookEvent $webhookEvent): void
    {
        if (empty($invoice->subscription)) {
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();

        if (! $subscription) {
            Log::warning('invoice.payment_failed: no matching local subscription', [
                'stripe_subscription_id' => $invoice->subscription,
            ]);
            return;
        }

        // Record the failure against the most recent order for this subscription.
        $latestOrder = Order::where('subscription_id', $subscription->id)
            ->latest('id')
            ->first();

        if ($latestOrder) {
            $latestOrder->update(['payment_status' => 'failed']);

            Payment::create([
                'order_id'                 => $latestOrder->id,
                'stripe_payment_intent_id' => $invoice->payment_intent ?? null,
                'amount'                   => round(((float) ($invoice->amount_due ?? 0)) / 100, 2),
                'currency'                 => strtoupper($invoice->currency ?? 'USD'),
                'status'                   => 'failed',
                'failure_reason'           => $invoice->last_finalization_error?->message ?? 'Invoice payment failed',
            ]);
        }

        $webhookEvent->webhookable()->associate($subscription);
        $webhookEvent->save();
    }

    // ---------------------------------------------------------------------------
    // customer.subscription.deleted
    // ---------------------------------------------------------------------------
    // Fires when a subscription is fully cancelled in Stripe (immediately or at
    // period end). Do NOT mark as "cancelled" if we already moved it to "completed"
    // via the cycle-limit logic above.
    // ---------------------------------------------------------------------------

    protected function handleSubscriptionDeleted(object $stripeSubscription, StripeWebhookEvent $webhookEvent): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if (! $subscription) {
            return;
        }

        // Preserve "completed" status if the subscription finished naturally.
        if ($subscription->status !== 'completed') {
            $subscription->update([
                'status'   => 'cancelled',
                'end_date' => today(),
            ]);
        }

        $webhookEvent->webhookable()->associate($subscription);
        $webhookEvent->save();
    }

    // ---------------------------------------------------------------------------
    // customer.subscription.updated
    // ---------------------------------------------------------------------------

    protected function handleSubscriptionUpdated(object $stripeSubscription, StripeWebhookEvent $webhookEvent): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if (! $subscription) {
            return;
        }

        $mappedStatus = match ($stripeSubscription->status) {
            'active'             => 'active',
            'paused'             => 'paused',
            'past_due'           => 'past_due',
            'unpaid'             => 'unpaid',
            'canceled'           => 'cancelled',
            'incomplete'         => 'incomplete',
            'incomplete_expired' => 'incomplete_expired',
            'trialing'           => 'trialing',
            default              => $subscription->status, // preserve unknown statuses
        };

        $updates = ['status' => $mappedStatus];

        if (! empty($stripeSubscription->cancel_at)) {
            $updates['end_date'] = Carbon::createFromTimestamp($stripeSubscription->cancel_at)->toDateString();
            Log::info('Stripe subscription cancel_at updated', [
                'stripe_subscription_id' => $stripeSubscription->id,
                'cancel_at' => $stripeSubscription->cancel_at,
            ]);
        } elseif (array_key_exists('cancel_at', (array) $stripeSubscription) && empty($stripeSubscription->cancel_at)) {
            $updates['end_date'] = null;
        }

        $subscription->update($updates);

        $webhookEvent->webhookable()->associate($subscription);
        $webhookEvent->save();
    }

    // ---------------------------------------------------------------------------
    // payment_intent.succeeded
    // ---------------------------------------------------------------------------
    // Fires for one-time PaymentIntents (non-subscription checkouts / direct charges).
    // For subscription-mode, the authoritative event is invoice.payment_succeeded,
    // so we skip anything that already has an associated invoice.
    // ---------------------------------------------------------------------------

    protected function handlePaymentIntentSucceeded(object $paymentIntent, StripeWebhookEvent $webhookEvent): void
    {
        // If this PaymentIntent is tied to a subscription invoice, it was already
        // handled by invoice.payment_succeeded — skip to avoid duplicate payments.
        if (! empty($paymentIntent->invoice)) {
            return;
        }

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();
        $order = $payment?->order;

        if (! $order) {
            $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();

            if (! $order && isset($paymentIntent->metadata->order_id)) {
                $order = Order::find($paymentIntent->metadata->order_id);
            }

            if (! $order && isset($paymentIntent->metadata->order_uuid)) {
                $order = Order::where('order_uuid', $paymentIntent->metadata->order_uuid)->first();
            }
        }

        if (! $order) {
            Log::info('payment_intent.succeeded: no matching order found (possibly handled elsewhere)', [
                'payment_intent_id' => $paymentIntent->id,
                'metadata' => $paymentIntent->metadata ?? new \stdClass(),
            ]);
            return;
        }

        if (! $payment) {
            if ($order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'status'         => 'completed',
                ]);
            }

            $payment = Payment::create([
                'order_id'                 => $order->id,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'amount'                   => round(((float) ($paymentIntent->amount_received ?? 0)) / 100, 2),
                'currency'                 => strtoupper($paymentIntent->currency ?? 'USD'),
                'status'                   => 'paid',
                'failure_reason'           => null,
            ]);
        }

        $webhookEvent->webhookable()->associate($payment);
        $webhookEvent->save();
    }

    protected function handlePaymentIntentFailed(object $paymentIntent, StripeWebhookEvent $webhookEvent): void
    {
        if (! empty($paymentIntent->invoice)) {
            return;
        }

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();
        $order = $payment?->order;

        if (! $order) {
            $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();
        }

        if (! $order) {
            Log::warning('payment_intent.payment_failed: order not found', [
                'payment_intent_id' => $paymentIntent->id,
                'metadata' => $paymentIntent->metadata ?? new \stdClass(),
            ]);
            return;
        }

        if (! $payment) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'amount' => round(((float) ($paymentIntent->amount ?? 0)) / 100, 2),
                'currency' => strtoupper($paymentIntent->currency ?? $order->currency ?? 'USD'),
                'status' => 'failed',
                'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Payment failed',
            ]);
        } else {
            $payment->update([
                'status' => 'failed',
                'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Payment failed',
            ]);
        }

        $order->update(['payment_status' => 'failed']);

        $webhookEvent->webhookable()->associate($payment);
        $webhookEvent->save();
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    /**
     * Cancel the Stripe subscription remotely and mark it completed locally.
     */
    protected function cancelStripeSubscription(Subscription $subscription): void
    {
        if (! empty($subscription->stripe_subscription_id)) {
            Stripe::setApiKey(config('services.stripe.secret'));
            try {
                StripeSubscriptionApi::cancel($subscription->stripe_subscription_id, []);
                Log::info('Stripe subscription auto-cancelled after reaching total cycles', [
                    'stripe_subscription_id' => $subscription->stripe_subscription_id,
                    'total_cycles'           => $subscription->total_cycles,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to cancel Stripe subscription after cycle limit', [
                    'stripe_subscription_id' => $subscription->stripe_subscription_id,
                    'error'                  => $e->getMessage(),
                ]);
            }
        }

        $subscription->update([
            'status'   => 'completed',
            'end_date' => today(),
        ]);
    }

    protected function resolveTotalCycles(Order $order): ?int
    {
        if (! $order->subscription_discount_id) {
            return null;
        }

        $discount = DB::table('cms_subscription_discounts')
            ->where('id', $order->subscription_discount_id)
            ->first();

        return $discount?->frequency_months ? (int) $discount->frequency_months : null;
    }

    protected function resolveFrequencyMonths(Order $order): int
    {
        if (! $order->subscription_discount_id) {
            return 1;
        }

        $discount = DB::table('cms_subscription_discounts')
            ->where('id', $order->subscription_discount_id)
            ->first();

        return (int) ($discount?->frequency_months ?? 1);
    }

    protected function resolveDiscountPercentage(Order $order): float
    {
        if (! $order->subscription_discount_id) {
            return 0.0;
        }

        $discount = DB::table('cms_subscription_discounts')
            ->where('id', $order->subscription_discount_id)
            ->first();

        return round((float) ($discount?->discount_percentage ?? 0), 2);
    }
}
