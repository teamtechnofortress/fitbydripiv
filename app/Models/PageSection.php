<?php

namespace App\Models;

use App\enums\SectionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PageSection extends Model
{
    use HasUuids;

    protected $table = 'page_sections';

    protected $fillable = [
        'page_id',
        'section_key',
        'type',
        'title',
        'subtitle',
        'content',
        'image',
        'sort_order',
    ];

    protected $casts = [
        'content' => 'array',
        'type' => SectionType::class,
        'sort_order' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SectionItem::class, 'section_id')->orderBy('sort_order');
    }

    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'scope')->orderBy('sort_order');
    }
}
