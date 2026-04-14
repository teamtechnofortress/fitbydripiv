<?php

namespace App\Models;

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
        'slot_position',
        'image_url',
        'image_type',
        'sort_order',
    ];

    protected $casts = [
        'slot_position' => 'integer',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function coverForProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'cover_image_id');
    }
}
