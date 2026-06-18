<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuleAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'configurable_rule_id',
        'action_type',
        'action_payload',
        'description',
        'execution_order',
        'stop_on_failure',
        'retry_count',
        'metadata',
    ];

    protected $casts = [
        'action_payload' => 'array',
        'execution_order' => 'integer',
        'stop_on_failure' => 'boolean',
        'retry_count' => 'integer',
        'metadata' => 'array',
    ];

    public function configurableRule(): BelongsTo
    {
        return $this->belongsTo(ConfigurableRule::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('execution_order')->orderBy('id');
    }
}
