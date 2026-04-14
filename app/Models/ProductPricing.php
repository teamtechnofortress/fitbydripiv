<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPricing extends Model
{
    use HasUuids;

    protected $table = 'product_pricing';

    protected $fillable = [
        'product_id',
        'pricing_type',
        'title',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(PricingOption::class, 'pricing_id')->orderBy('sort_order');
    }
}
