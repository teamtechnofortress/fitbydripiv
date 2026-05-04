<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasUuids;

    protected $table = 'coupons';

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'scope',
        'is_active',
        'starts_at',
        'expires_at',
        'usage_limit_total',
        'usage_limit_per_user',
        'applies_to',
        'first_order_only',
        'min_order_amount',
        'max_discount_amount',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'usage_limit_total' => 'integer',
        'usage_limit_per_user' => 'integer',
        'first_order_only' => 'boolean',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_products', 'coupon_id', 'product_id')
            ->using(CouponProduct::class)
            ->withTimestamps();
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class, 'coupon_id');
    }
}
