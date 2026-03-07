<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsProductFaq extends Model
{
    use HasUuids;

    protected $table = 'cms_product_faqs';

    protected $fillable = [
        'product_id',
        'category',
        'question',
        'answer',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(CmsProduct::class, 'product_id');
    }
}
