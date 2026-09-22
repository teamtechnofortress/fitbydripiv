<?php

namespace App\Models;

use App\enums\ProductImageType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductImage extends Model
{
    use HasUuids;

    protected $table = 'product_images';

    public const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'image_url',
        'image_type',
        'sort_order',
        'duration_ms',
        'is_enabled',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'duration_ms' => 'integer',
        'is_enabled' => 'boolean',
        'image_type' => ProductImageType::class,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function coverForProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'cover_image_id');
    }

    public function scopeOfType(Builder $query, ProductImageType|string $type): Builder
    {
        $type = $type instanceof ProductImageType ? $type->value : $type;

        return $query->where('image_type', $type);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function shouldDisplay(): bool
    {
        $type = $this->image_type instanceof ProductImageType
            ? $this->image_type
            : ProductImageType::tryFrom($this->image_type);

        return $type === ProductImageType::COVER || $this->is_enabled;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
