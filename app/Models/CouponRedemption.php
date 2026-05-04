<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponRedemption extends Model
{
    use HasUuids;

    protected $table = 'coupon_redemptions';

    protected $fillable = [
        'coupon_id',
        'order_id',
        'patient_id',
        'coupon_code',
        'discount_type',
        'discount_value',
        'discount_amount',
        'original_amount',
        'final_amount',
        'redeemed_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'patient_id' => 'integer',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'redeemed_at' => 'datetime',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
