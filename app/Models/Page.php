<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasUuids;

    protected $table = 'pages';

    protected $fillable = [
        'slug',
        'title',
        'status',
        'meta_title',
        'meta_description',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class, 'page_id')->orderBy('sort_order');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
