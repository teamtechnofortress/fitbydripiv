<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasUuids;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'category',
        'description',
        'about_treatment',
        'how_it_works',
        'key_ingredients',
        'treatment_duration',
        'usage_instructions',
        'research_description',
        'clinical_research_description',
        'is_featured',
        'is_published',
        'completion_status',
        'completion_percentage',
        'completion_step',
        'cover_image_id',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'completion_percentage' => 'integer',
        'completion_step' => 'integer',
    ];

    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'cover_image_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order');
    }

    public function benefits(): HasMany
    {
        return $this->hasMany(ProductBenefit::class, 'product_id')->orderBy('sort_order');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(ProductFaq::class, 'product_id')->orderBy('sort_order');
    }

    public function researchLinks(): HasMany
    {
        return $this->hasMany(ProductResearchLink::class, 'product_id')->orderBy('sort_order');
    }

    public function pricing(): HasMany
    {
        return $this->hasMany(ProductPricing::class, 'product_id');
    }

    public function ingredientMappings(): HasMany
    {
        return $this->hasMany(ProductIngredientMap::class, 'product_id')->orderBy('sort_order');
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredient_map', 'product_id', 'ingredient_id')
            ->using(ProductIngredientMap::class)
            ->withPivot(['id', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
}
