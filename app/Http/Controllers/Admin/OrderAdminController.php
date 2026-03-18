<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 20), 100);

        $query = Order::query()
            ->with([
                'patient:id,first_name,last_name,email',
                'product:id,name,slug',
                'subscription:id,order_id,status,current_cycle_number,total_cycles',
                'payments:id,order_id,amount,currency,status,created_at',
            ])
            ->latest();

        if ($request->filled('purchase_type')) {
            $query->where('purchase_type', $request->input('purchase_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($inner) use ($search) {
                $inner->where('order_uuid', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->paginate($perPage);

        $data = $orders->getCollection()->map(function (Order $order) {
            $patient = $order->patient;
            $patientName = $patient ? trim(sprintf('%s %s', $patient->first_name ?? '', $patient->last_name ?? '')) : null;

            return [
                'id' => $order->id,
                'order_uuid' => $order->order_uuid,
                'purchase_type' => $order->purchase_type,
                'pricing_type' => $order->pricing_type,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'price' => $order->price,
                'currency' => $order->currency,
                'patient' => [
                    'id' => $patient?->id,
                    'name' => $patientName,
                    'email' => $patient?->email,
                ],
                'product' => [
                    'id' => $order->product?->id,
                    'name' => $order->product?->name,
                    'slug' => $order->product?->slug,
                ],
                'subscription' => $order->subscription ? [
                    'id' => $order->subscription->id,
                    'status' => $order->subscription->status,
                    'current_cycle_number' => $order->subscription->current_cycle_number,
                    'total_cycles' => $order->subscription->total_cycles,
                ] : null,
                'payments' => $order->payments->map(fn ($payment) => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at,
                ])->all(),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load([
            'patient:id,first_name,last_name,email,phone',
            'product:id,name,slug',
            'subscription:id,order_id,status,current_cycle_number,total_cycles,billing_frequency_months,next_billing_date,end_date',
            'payments.webhooks',
            'webhooks',
        ]);

        $patient = $order->patient;
        $patientName = $patient ? trim(sprintf('%s %s', $patient->first_name ?? '', $patient->last_name ?? '')) : null;

        $payload = [
            'id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'purchase_type' => $order->purchase_type,
            'pricing_type' => $order->pricing_type,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'price' => $order->price,
            'currency' => $order->currency,
            'patient' => $patient ? [
                'id' => $patient->id,
                'name' => $patientName,
                'email' => $patient->email,
                'phone' => $patient->phone,
            ] : null,
            'product' => $order->product ? [
                'id' => $order->product->id,
                'name' => $order->product->name,
                'slug' => $order->product->slug,
            ] : null,
            'subscription' => $order->subscription ? [
                'id' => $order->subscription->id,
                'status' => $order->subscription->status,
                'current_cycle_number' => $order->subscription->current_cycle_number,
                'total_cycles' => $order->subscription->total_cycles,
                'billing_frequency_months' => $order->subscription->billing_frequency_months,
                'next_billing_date' => $order->subscription->next_billing_date,
                'end_date' => $order->subscription->end_date,
            ] : null,
            'webhooks' => $order->webhooks->map(fn ($webhook) => [
                'id' => $webhook->id,
                'stripe_event_id' => $webhook->stripe_event_id,
                'event_type' => $webhook->event_type,
                'processed' => $webhook->processed,
                'processed_at' => $webhook->processed_at,
                'created_at' => $webhook->created_at,
            ])->all(),
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
        ];

        return response()->json($payload);
    }
}
