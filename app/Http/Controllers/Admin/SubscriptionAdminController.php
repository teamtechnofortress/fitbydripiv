<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscriptionApi;

class SubscriptionAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 20), 100);

        $query = Subscription::query()
            ->with([
                'order:id,order_uuid,price,currency,status,payment_status',
                'patient:id,first_name,last_name,email',
                'order.product:id,name,slug',
            ])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->boolean('active_only')) {
            $query->whereNull('end_date')->whereNot('status', 'cancelled');
        }

        if ($search = $request->input('search')) {
            $query->where(function ($inner) use ($search) {
                $inner->whereHas('order', function ($orderQuery) use ($search) {
                    $orderQuery->where('order_uuid', 'like', "%{$search}%");
                })->orWhereHas('patient', function ($patientQuery) use ($search) {
                    $patientQuery->where('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            });
        }

        $subscriptions = $query->paginate($perPage);

        $data = $subscriptions->getCollection()->map(function (Subscription $subscription) {
            $patient = $subscription->patient;
            $patientName = $patient ? trim(sprintf('%s %s', $patient->first_name ?? '', $patient->last_name ?? '')) : null;
            $order = $subscription->order;

            return [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'current_cycle_number' => $subscription->current_cycle_number,
                'total_cycles' => $subscription->total_cycles,
                'billing_frequency_months' => $subscription->billing_frequency_months,
                'discount_percentage' => $subscription->discount_percentage,
                'start_date' => $subscription->start_date,
                'next_billing_date' => $subscription->next_billing_date,
                'end_date' => $subscription->end_date,
                'patient' => [
                    'id' => $patient?->id,
                    'name' => $patientName,
                    'email' => $patient?->email,
                ],
                'order' => $order ? [
                    'id' => $order->id,
                    'order_uuid' => $order->order_uuid,
                    'price' => $order->price,
                    'currency' => $order->currency,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'product' => $order->product ? [
                        'id' => $order->product->id,
                        'name' => $order->product->name,
                        'slug' => $order->product->slug,
                    ] : null,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
                'last_page' => $subscriptions->lastPage(),
            ],
        ]);
    }

    public function show(Subscription $subscription): JsonResponse
    {
        $subscription->load([
            'patient:id,first_name,last_name,email,phone',
            'order.product:id,name,slug',
            'order.payments.webhooks',
            'order.webhooks',
            'webhooks',
        ]);

        $patient = $subscription->patient;
        $patientName = $patient ? trim(sprintf('%s %s', $patient->first_name ?? '', $patient->last_name ?? '')) : null;
        $order = $subscription->order;

        $stripeData = null;

        if ($subscription->stripe_subscription_id) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $stripeData = StripeSubscriptionApi::retrieve($subscription->stripe_subscription_id);
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch Stripe subscription details', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $subscription->stripe_subscription_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $payload = [
            'id' => $subscription->id,
            'status' => $subscription->status,
            'current_cycle_number' => $subscription->current_cycle_number,
            'total_cycles' => $subscription->total_cycles,
            'billing_frequency_months' => $subscription->billing_frequency_months,
            'discount_percentage' => $subscription->discount_percentage,
            'start_date' => $subscription->start_date,
            'next_billing_date' => $subscription->next_billing_date,
            'end_date' => $subscription->end_date,
            'stripe_subscription_id' => $subscription->stripe_subscription_id,
            'patient' => $patient ? [
                'id' => $patient->id,
                'name' => $patientName,
                'email' => $patient->email,
                'phone' => $patient->phone,
            ] : null,
            'order' => $order ? [
                'id' => $order->id,
                'order_uuid' => $order->order_uuid,
                'price' => $order->price,
                'currency' => $order->currency,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'product' => $order->product ? [
                    'id' => $order->product->id,
                    'name' => $order->product->name,
                    'slug' => $order->product->slug,
                ] : null,
                'payments' => $order->payments->map(fn ($payment) => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at,
                    'webhooks' => $payment->webhooks->map(fn ($webhook) => [
                        'id' => $webhook->id,
                        'stripe_event_id' => $webhook->stripe_event_id,
                        'event_type' => $webhook->event_type,
                        'processed' => $webhook->processed,
                        'processed_at' => $webhook->processed_at,
                        'created_at' => $webhook->created_at,
                    ])->all(),
                ])->all(),
                'webhooks' => $order->webhooks->map(fn ($webhook) => [
                    'id' => $webhook->id,
                    'stripe_event_id' => $webhook->stripe_event_id,
                    'event_type' => $webhook->event_type,
                    'processed' => $webhook->processed,
                    'processed_at' => $webhook->processed_at,
                    'created_at' => $webhook->created_at,
                ])->all(),
            ] : null,
            'webhooks' => $subscription->webhooks->map(fn ($webhook) => [
                'id' => $webhook->id,
                'stripe_event_id' => $webhook->stripe_event_id,
                'event_type' => $webhook->event_type,
                'processed' => $webhook->processed,
                'processed_at' => $webhook->processed_at,
                'created_at' => $webhook->created_at,
            ])->all(),
            'stripe' => $stripeData ? $stripeData->toArray() : null,
        ];

        return response()->json($payload);
    }
}
