<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ConfigurableRule extends Model
{
    use HasFactory;

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_CRITICAL = 'critical';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'ruleable_type',
        'ruleable_id',
        'rule_type',
        'rule_key',
        'rule_name',
        'description',
        'priority',
        'execution_order',
        'config',
        'status',
        'version',
        'is_active',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'execution_order' => 'integer',
        'config' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function ruleable(): MorphTo
    {
        return $this->morphTo();
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(RuleCondition::class)->orderBy('priority')->orderBy('id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(RuleAction::class)->orderBy('execution_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where('is_active', true);
    }

    public function scopeForType(Builder $query, string $ruleType): Builder
    {
        return $query->where('rule_type', $ruleType);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('execution_order')->orderBy('id');
    }
}
