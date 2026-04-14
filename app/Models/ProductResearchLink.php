<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductResearchLink extends Model
{
    use HasUuids;

    protected $table = 'product_research_links';

    protected $fillable = [
        'product_id',
        'title',
        'article_url',
        'authors',
        'journal',
        'publication_year',
        'pubmed_id',
        'doi',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
