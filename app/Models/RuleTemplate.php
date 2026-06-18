<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RuleTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_key',
        'template_name',
        'description',
        'rule_type',
        'config_schema',
        'condition_schema',
        'action_schema',
        'example_config',
        'example_conditions',
        'example_actions',
        'is_active',
    ];

    protected $casts = [
        'config_schema' => 'array',
        'condition_schema' => 'array',
        'action_schema' => 'array',
        'example_config' => 'array',
        'example_conditions' => 'array',
        'example_actions' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForType(Builder $query, string $ruleType): Builder
    {
        return $query->where('rule_type', $ruleType);
    }
}
