<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsResearchLink extends Model
{
    use HasUuids;

    protected $table = 'cms_research_links';

    protected $fillable = [
        'product_id',
        'title',
        'authors',
        'journal',
        'publication_year',
        'pubmed_id',
        'doi',
        'article_url',
        'display_order',
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'display_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(CmsProduct::class, 'product_id');
    }
}
