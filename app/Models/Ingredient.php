<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    use HasUuids;

    protected $table = 'ingredients';

    protected $fillable = [
        'name',
        'description',
    ];

    public function productMappings(): HasMany
    {
        return $this->hasMany(ProductIngredientMap::class, 'ingredient_id')->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_ingredient_map', 'ingredient_id', 'product_id')
            ->using(ProductIngredientMap::class)
            ->withPivot(['id', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
}
