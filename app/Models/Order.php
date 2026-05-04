<?php

namespace App\Models;

use App\Models\PricingOption;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const PRICING_TYPE_ONE_TIME = 'one_time';
    public const PRICING_TYPE_SUBSCRIPTION = 'subscription';

    public const PRICING_TYPES = [
        self::PRICING_TYPE_ONE_TIME,
        self::PRICING_TYPE_SUBSCRIPTION,
    ];

    protected $fillable = [
        'order_uuid',
        'patient_id',
        'product_id',
        'price',
        'currency',
        'subscription_id',
        'billing_cycle_number',
        'purchase_type',
        'pricing_type',
        'pricing_option_id',
        'coupon_id',
        'coupon_code',
        'base_amount',
        'coupon_discount_amount',
        'final_amount',
        'frequency_months',
        'status',
        'payment_status',
        'stripe_checkout_id',
        'stripe_payment_intent_id',
        'stripe_invoice_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'coupon_discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'billing_cycle_number' => 'integer',
        'frequency_months' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (empty($order->order_uuid)) {
                $order->order_uuid = (string) Str::uuid();
            }
        });
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function pricingOption(): BelongsTo
    {
        return $this->belongsTo(PricingOption::class, 'pricing_option_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function webhooks(): MorphMany
    {
        return $this->morphMany(StripeWebhookEvent::class, 'webhookable');
    }
}
