<?php

namespace App\Services;

use App\Models\Order;

class CheckoutResponseService
{
    public function buildResponse(string $message, Order $order, bool $success = true): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $this->buildOrderContext($order),
        ];
    }

    public function buildOrderContext(Order $order): array
    {
        $order->loadMissing([
            'patient',
            'product.coverImage',
            'pricingOption',
            'coupon',
        ]);

        return [
            'patient' => $order->patient ? [
                'id' => $order->patient->id,
                'first_name' => $order->patient->first_name,
                'last_name' => $order->patient->last_name,
                'full_name' => trim(($order->patient->first_name ?? '') . ' ' . ($order->patient->last_name ?? '')),
                'email' => $order->patient->email,
                'phone' => $order->patient->phone,
                'birthday' => $order->patient->birthday,
                'age' => $order->patient->age,
                'gender' => $order->patient->gender,
            ] : null,
            'product' => $order->product ? [
                'id' => $order->product->id,
                'name' => $order->product->name,
                'slug' => $order->product->slug,
                'category' => $order->product->category,
                'description' => $order->product->description,
                'cover_image' => $order->product->coverImage ? [
                    'id' => $order->product->coverImage->id,
                    'image_url' => $order->product->coverImage->image_url,
                    'image_type' => $order->product->coverImage->image_type,
                ] : null,
            ] : null,
            'order' => [
                'order_id' => $order->id,
                'order_uuid' => $order->order_uuid,
                'purchase_type' => $order->purchase_type,
                'pricing_type' => $order->pricing_type,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'currency' => $order->currency,
                'base_amount' => $order->base_amount,
                'coupon_discount_amount' => $order->coupon_discount_amount,
                'final_amount' => $order->final_amount,
                'price' => $order->price,
                'frequency_months' => $order->frequency_months,
                'pricing_option' => $order->pricingOption ? [
                    'id' => $order->pricingOption->id,
                    'label' => $order->pricingOption->label,
                    'billing_interval' => $order->pricingOption->billing_interval,
                    'interval_count' => $order->pricingOption->interval_count,
                    'price' => $order->pricingOption->price,
                    'discount_percent' => $order->pricingOption->discount_percent,
                    'final_price' => $order->pricingOption->final_price,
                ] : null,
                'coupon' => $order->coupon ? [
                    'id' => $order->coupon->id,
                    'code' => $order->coupon->code,
                    'name' => $order->coupon->name,
                    'type' => $order->coupon->type,
                    'value' => $order->coupon->value,
                    'scope' => $order->coupon->scope,
                    'applies_to' => $order->coupon->applies_to,
                ] : null,
            ],
        ];
    }
}
