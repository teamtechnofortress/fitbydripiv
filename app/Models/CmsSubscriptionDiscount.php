<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsSubscriptionDiscount extends Model
{
    use HasUuids;

    protected $table = 'cms_subscription_discounts';

    protected $fillable = [
        'product_id',
        'frequency_months',
        'discount_percentage',
    ];

    protected $casts = [
        'id' => 'string',
        'frequency_months' => 'integer',
        'discount_percentage' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(CmsProduct::class, 'product_id');
    }
}
