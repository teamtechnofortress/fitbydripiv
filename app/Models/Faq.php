<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Faq extends Model
{
    use HasUuids;

    protected $table = 'faqs';

    public const SCOPE_PRODUCT = 'product';
    public const SCOPE_SECTION = 'section';

    protected $fillable = [
        'scope_type',
        'scope_id',
        'question',
        'answer',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scope(): MorphTo
    {
        return $this->morphTo();
    }
}
