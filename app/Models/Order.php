<?php

namespace App\Models;

use App\Models\CmsSubscriptionDiscount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const PRICING_TYPE_BASE = 'base';
    public const PRICING_TYPE_MICRO_DOSE = 'micro_dose';
    public const PRICING_TYPE_SAMPLE = 'sample';

    public const PRICING_TYPES = [
        self::PRICING_TYPE_BASE,
        self::PRICING_TYPE_MICRO_DOSE,
        self::PRICING_TYPE_SAMPLE,
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
        'subscription_discount_id',
        'frequency_months',
        'status',
        'payment_status',
        'stripe_checkout_id',
        'stripe_payment_intent_id',
        'stripe_invoice_id',
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
        return $this->belongsTo(CmsProduct::class, 'product_id', 'id');
    }

    public function subscriptionDiscount(): BelongsTo
    {
        return $this->belongsTo(CmsSubscriptionDiscount::class, 'subscription_discount_id');
    }

    public function webhooks(): MorphMany
    {
        return $this->morphMany(StripeWebhookEvent::class, 'webhookable');
    }
}
