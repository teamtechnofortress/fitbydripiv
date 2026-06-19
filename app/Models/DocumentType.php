<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $table = 'document_types';

    public const CATEGORY_IDENTITY = 'identity';

    public const CATEGORY_VERIFICATION = 'verification';

    public const CATEGORY_MEDICAL = 'medical';

    public const CATEGORY_INSURANCE = 'insurance';

    public const CATEGORY_CONSENT = 'consent';

    public const CATEGORY_PRESCRIPTION = 'prescription';

    public const CATEGORIES = [
        self::CATEGORY_IDENTITY,
        self::CATEGORY_VERIFICATION,
        self::CATEGORY_MEDICAL,
        self::CATEGORY_INSURANCE,
        self::CATEGORY_CONSENT,
        self::CATEGORY_PRESCRIPTION,
    ];

    protected $fillable = [
        'key',
        'name',
        'category',
        'description',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }
}
