<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsProduct extends Model
{
    use HasUuids;

    protected $table = 'cms_products';

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'short_description',
        'full_description',
        'benefits',
        'treatment_details',
        'featured_image',
        'portrait_image',
        'landscape_image',
        'image_gallery',
        'base_price',
        'micro_dose_price',
        'sample_price',
        'currency',
        'display_order',
        'is_featured',
    ];

    protected $casts = [
        'benefits' => 'array',
        'treatment_details' => 'array',
        'image_gallery' => 'array',
        'base_price' => 'float',
        'micro_dose_price' => 'float',
        'sample_price' => 'float',
        'display_order' => 'integer',
        'is_featured' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CmsCategory::class, 'category_id');
    }

    public function researchLinks(): HasMany
    {
        return $this->hasMany(CmsResearchLink::class, 'product_id')->orderBy('display_order');
    }

    public function pricingOptions(): HasMany
    {
        return $this->hasMany(CmsPricingOption::class, 'product_id')->orderBy('display_order');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(CmsProductFaq::class, 'product_id')->orderBy('display_order');
    }

    public function subscriptionDiscounts(): HasMany
    {
        return $this->hasMany(CmsSubscriptionDiscount::class, 'product_id')->orderBy('frequency_months');
    }
}
