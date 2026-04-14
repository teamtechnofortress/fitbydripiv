<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingOption extends Model
{
    use HasUuids;

    protected $table = 'pricing_options';

    protected $fillable = [
        'pricing_id',
        'billing_interval',
        'interval_count',
        'price',
        'discount_percent',
        'final_price',
        'label',
        'sort_order',
        'is_default',
        'metadata',
    ];

    protected $casts = [
        'interval_count' => 'integer',
        'price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'final_price' => 'decimal:2',
        'sort_order' => 'integer',
        'is_default' => 'boolean',
        'metadata' => 'array',
    ];

    public function pricing(): BelongsTo
    {
        return $this->belongsTo(ProductPricing::class, 'pricing_id');
    }
}
