<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function applyCouponToOrder(string $orderUuid, string $couponCode): Order
    {
        $order = Order::query()
            ->with([
                'product:id,name,slug',
                'pricingOption:id,price,final_price,label',
                'coupon:id,code,name,type,value,scope,applies_to',
            ])
            ->where('order_uuid', $orderUuid)
            ->first();

        if (! $order) {
            abort(404, 'Order not found.');
        }

        if (! $order->patient_id) {
            throw ValidationException::withMessages([
                'order_uuid' => 'Order must be linked to a patient before applying a coupon.',
            ]);
        }

        if ($order->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'order_uuid' => 'Coupon cannot be applied to a paid order.',
            ]);
        }

        return DB::transaction(function () use ($order, $couponCode) {
            $coupon = Coupon::query()
                ->with('products:id')
                ->where('code', strtoupper(trim($couponCode)))
                ->first();

            if (! $coupon) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'Coupon code is invalid.',
                ]);
            }

            $originalAmount = $this->resolveOriginalAmount($order);
            $this->assertCouponCanBeApplied($coupon, $order, $originalAmount);

            $discountAmount = $this->calculateDiscountAmount($coupon, $originalAmount);
            $finalAmount = max(0, round($originalAmount - $discountAmount, 2));

            $order->coupon()->associate($coupon);
            $order->coupon_code = $coupon->code;
            $order->coupon_discount_amount = $discountAmount;
            $order->final_amount = $finalAmount;
            $order->price = $finalAmount;
            $order->save();

            return $order->fresh([
                'product:id,name,slug',
                'pricingOption:id,price,final_price,label',
                'coupon:id,code,name,type,value,scope,applies_to',
            ]);
        });
    }

    protected function assertCouponCanBeApplied(Coupon $coupon, Order $order, float $originalAmount): void
    {
        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Coupon is inactive.',
            ]);
        }

        $now = now();

        if ($coupon->starts_at && $coupon->starts_at->gt($now)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Coupon is not active yet.',
            ]);
        }

        if ($coupon->expires_at && $coupon->expires_at->lt($now)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Coupon has expired.',
            ]);
        }

        if ($coupon->applies_to !== 'all' && $coupon->applies_to !== $order->purchase_type) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Coupon is not valid for this purchase type.',
            ]);
        }

        if ($coupon->scope === 'product_specific' && ! $coupon->products->contains('id', $order->product_id)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Coupon is not valid for this product.',
            ]);
        }

        if ($coupon->usage_limit_total !== null && $coupon->redemptions()->count() >= $coupon->usage_limit_total) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Coupon usage limit has been reached.',
            ]);
        }

        if (
            $coupon->usage_limit_per_user !== null
            && $coupon->redemptions()->where('patient_id', $order->patient_id)->count() >= $coupon->usage_limit_per_user
        ) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Coupon usage limit has been reached for this patient.',
            ]);
        }

        if ($coupon->first_order_only) {
            $hasPaidOrders = Order::query()
                ->where('patient_id', $order->patient_id)
                ->where('id', '!=', $order->id)
                ->where('payment_status', 'paid')
                ->exists();

            if ($hasPaidOrders) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'Coupon is only valid for a first order.',
                ]);
            }
        }

        if ($coupon->min_order_amount !== null && $originalAmount < (float) $coupon->min_order_amount) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Order does not meet the minimum amount for this coupon.',
            ]);
        }
    }

    protected function calculateDiscountAmount(Coupon $coupon, float $originalAmount): float
    {
        $discountAmount = $coupon->type === 'percent'
            ? round($originalAmount * ((float) $coupon->value / 100), 2)
            : round((float) $coupon->value, 2);

        if ($coupon->max_discount_amount !== null) {
            $discountAmount = min($discountAmount, round((float) $coupon->max_discount_amount, 2));
        }

        return min($discountAmount, $originalAmount);
    }

    protected function resolveOriginalAmount(Order $order): float
    {
        if ($order->pricingOption) {
            return round((float) $order->pricingOption->final_price, 2);
        }

        $currentFinalAmount = round((float) ($order->final_amount ?? $order->price), 2);
        $currentCouponDiscount = round((float) ($order->coupon_discount_amount ?? 0), 2);

        return round($currentFinalAmount + $currentCouponDiscount, 2);
    }
}
