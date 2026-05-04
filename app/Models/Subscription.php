<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'patient_id',
        'product_id',
        'pricing_option_id',
        'coupon_id',
        'coupon_code',
        'current_cycle_number',
        'total_cycles',
        'stripe_subscription_id',
        'stripe_customer_id',
        'billing_frequency_months',
        'discount_percentage',
        'base_recurring_amount',
        'discounted_recurring_amount',
        'discount_duration_type',
        'discount_remaining_cycles',
        'start_date',
        'next_billing_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'pricing_option_id' => 'string',
        'coupon_id' => 'string',
        'current_cycle_number' => 'integer',
        'total_cycles' => 'integer',
        'billing_frequency_months' => 'integer',
        'discount_percentage' => 'decimal:2',
        'base_recurring_amount' => 'decimal:2',
        'discounted_recurring_amount' => 'decimal:2',
        'discount_remaining_cycles' => 'integer',
        'start_date' => 'date',
        'next_billing_date' => 'date',
        'end_date' => 'date',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
