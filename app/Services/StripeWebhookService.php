<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\StripeWebhookEvent;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscriptionApi;

class StripeWebhookService
{
    public function processEvent(object $event): void
    {
        $existing = StripeWebhookEvent::where('stripe_event_id', $event->id)->first();

        if ($existing && $existing->processed) {
            return;
        }

        $webhookEvent = $existing ?: StripeWebhookEvent::create([
            'stripe_event_id' => $event->id,
            'event_type' => $event->type,
            'payload_json' => (array) $event,
            'processed' => false,
        ]);

        DB::transaction(function () use ($event, $webhookEvent) {
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event->data->object, $webhookEvent),
                'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded($event->data->object, $webhookEvent),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event->data->object, $webhookEvent),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object, $webhookEvent),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object, $webhookEvent),
                default => null,
            };

            $webhookEvent->update([
                'processed' => true,
                'processed_at' => now(),
            ]);
        });
    }

    protected function handleCheckoutSessionCompleted(object $session, StripeWebhookEvent $webhookEvent): void
    {
        $orderId = $session->metadata->order_id ?? null;

        $order = $orderId
            ? Order::find($orderId)
            : Order::where('stripe_checkout_id', $session->id)->first();

        if (! $order) {
            return;
        }

        $order->update([
            'payment_status' => 'paid',
            'status' => 'completed',
            'stripe_payment_intent_id' => $session->payment_intent ?? $order->stripe_payment_intent_id,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'stripe_payment_intent_id' => $session->payment_intent ?? null,
            'amount' => $order->price,
            'currency' => $order->currency,
            'status' => 'paid',
            'failure_reason' => null,
        ]);

        $webhookEvent->webhookable()->associate($order);
        $webhookEvent->save();

        if ($order->purchase_type === 'subscription' && ! $order->subscription_id) {
            $frequency = $this->resolveFrequencyMonths($order);
            $subscription = Subscription::create([
                'order_id' => $order->id,
                'patient_id' => $order->patient_id,
                'product_id' => $order->product_id,
                'current_cycle_number' => 1,
                'total_cycles' => $this->resolveTotalCycles($order),
                'stripe_subscription_id' => $session->subscription ?? null,
                'stripe_customer_id' => $session->customer ?? null,
                'billing_frequency_months' => $frequency,
                'discount_percentage' => $this->resolveDiscountPercentage($order),
                'start_date' => today(),
                'next_billing_date' => now()->addMonths($frequency)->toDateString(),
                'end_date' => null,
                'status' => 'active',
            ]);

            $order->update([
                'subscription_id' => $subscription->id,
                'billing_cycle_number' => 1,
            ]);
        }
    }

    protected function handleInvoicePaymentSucceeded(object $invoice, StripeWebhookEvent $webhookEvent): void
    {
        if (empty($invoice->subscription)) {
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();

        if (! $subscription) {
            return;
        }

        if (Order::where('stripe_invoice_id', $invoice->id)->exists()) {
            $webhookEvent->webhookable()->associate($subscription);
            $webhookEvent->save();
            return;
        }

        $nextCycle = $subscription->current_cycle_number + 1;

        $order = Order::create([
            'patient_id' => $subscription->patient_id,
            'product_id' => $subscription->product_id,
            'price' => round(((float) ($invoice->amount_paid ?? 0)) / 100, 2),
            'currency' => strtoupper($invoice->currency ?? 'USD'),
            'subscription_id' => $subscription->id,
            'billing_cycle_number' => $nextCycle,
            'purchase_type' => 'subscription',
            'pricing_type' => $subscription->order?->pricing_type ?? 'base',
            'subscription_discount_id' => $subscription->order?->subscription_discount_id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'stripe_checkout_id' => null,
            'stripe_payment_intent_id' => $invoice->payment_intent ?? null,
            'stripe_invoice_id' => $invoice->id,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'stripe_payment_intent_id' => $invoice->payment_intent ?? null,
            'amount' => $order->price,
            'currency' => $order->currency,
            'status' => 'paid',
            'failure_reason' => null,
        ]);

        $subscription->update([
            'current_cycle_number' => $nextCycle,
            'next_billing_date' => Carbon::parse($subscription->next_billing_date ?? now())
                ->addMonths($subscription->billing_frequency_months)
                ->toDateString(),
        ]);

        $webhookEvent->webhookable()->associate($subscription);
        $webhookEvent->save();

        if (
            ! is_null($subscription->total_cycles)
            && $subscription->current_cycle_number >= $subscription->total_cycles
        ) {
            Stripe::setApiKey(config('services.stripe.secret'));

            if (! empty($subscription->stripe_subscription_id)) {
                try {
                    StripeSubscriptionApi::cancel($subscription->stripe_subscription_id, []);
                } catch (\Throwable $e) {
                    // log or ignore
                }
            }

            $subscription->update([
                'status' => 'completed',
                'end_date' => today(),
            ]);
        }
    }

    protected function handleInvoicePaymentFailed(object $invoice, StripeWebhookEvent $webhookEvent): void
    {
        if (empty($invoice->subscription)) {
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();

        if (! $subscription) {
            return;
        }

        $latestOrder = Order::where('subscription_id', $subscription->id)
            ->latest('id')
            ->first();

        if ($latestOrder) {
            $latestOrder->update([
                'payment_status' => 'failed',
            ]);

            Payment::create([
                'order_id' => $latestOrder->id,
                'stripe_payment_intent_id' => $invoice->payment_intent ?? null,
                'amount' => round(((float) ($invoice->amount_due ?? 0)) / 100, 2),
                'currency' => strtoupper($invoice->currency ?? 'USD'),
                'status' => 'failed',
                'failure_reason' => 'Invoice payment failed',
            ]);
        }

        $webhookEvent->webhookable()->associate($subscription);
        $webhookEvent->save();
    }

    protected function handleSubscriptionDeleted(object $stripeSubscription, StripeWebhookEvent $webhookEvent): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => in_array($subscription->status, ['completed']) ? 'completed' : 'cancelled',
            'end_date' => today(),
        ]);

        $webhookEvent->webhookable()->associate($subscription);
        $webhookEvent->save();
    }

    protected function handleSubscriptionUpdated(object $stripeSubscription, StripeWebhookEvent $webhookEvent): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if (! $subscription) {
            return;
        }

        $mappedStatus = match ($stripeSubscription->status) {
            'active' => 'active',
            'paused' => 'paused',
            'canceled' => 'cancelled',
            default => $subscription->status,
        };

        $subscription->update([
            'status' => $mappedStatus,
        ]);

        $webhookEvent->webhookable()->associate($subscription);
        $webhookEvent->save();
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
            return 0;
        }

        $discount = DB::table('cms_subscription_discounts')
            ->where('id', $order->subscription_discount_id)
            ->first();

        return round((float) ($discount?->discount_percentage ?? 0), 2);
    }
}
