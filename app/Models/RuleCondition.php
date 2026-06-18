<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuleCondition extends Model
{
    use HasFactory;

    public const LOGIC_AND = 'AND';

    public const LOGIC_OR = 'OR';

    protected $fillable = [
        'configurable_rule_id',
        'condition_type',
        'operator',
        'values',
        'priority',
        'logic_operator',
        'negate',
        'metadata',
    ];

    protected $casts = [
        'values' => 'array',
        'priority' => 'integer',
        'negate' => 'boolean',
        'metadata' => 'array',
    ];

    public function configurableRule(): BelongsTo
    {
        return $this->belongsTo(ConfigurableRule::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority')->orderBy('id');
    }
}
