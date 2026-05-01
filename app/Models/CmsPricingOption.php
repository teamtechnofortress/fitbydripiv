<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsPricingOption extends Model
{
    use HasUuids;

    protected $table = 'cms_pricing_options';

    protected $fillable = [
        'product_id',
        'plan_name',
        'price',
        'billing_cycle',
        'supply_duration',
        'description',
        'features',
        'is_popular',
        'display_order',
    ];

    protected $casts = [
        'price' => 'float',
        'features' => 'array',
        'is_popular' => 'boolean',
        'display_order' => 'integer',
    ];

    // public function product(): BelongsTo
    // {
    //     return $this->belongsTo(CmsProduct::class, 'product_id');
    // }
}
