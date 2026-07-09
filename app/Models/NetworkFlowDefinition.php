<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class NetworkFlowDefinition extends Model
{
    protected $table = 'network_flow_definitions';

    protected $fillable = [
        'dr_network_id',
        'flow_key',
        'name',
        'description',
        'steps',
        'network_fee_amount',
        'patient_fee_amount',
        'is_active',
    ];

    protected $casts = [
        'dr_network_id' => 'integer',
        'steps' => 'array',
        'network_fee_amount' => 'decimal:2',
        'patient_fee_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function drNetwork(): BelongsTo
    {
        return $this->belongsTo(DrNetwork::class, 'dr_network_id');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(NetworkStateMapping::class, 'flow_id');
    }

    public function productMappings(): HasMany
    {
        return $this->hasMany(NetworkProductMapping::class, 'flow_id');
    }

    public function networks(): BelongsToMany
    {
        return $this->belongsToMany(
            DrNetwork::class,
            'network_state_mappings',
            'flow_id',
            'dr_network_id'
        )->withPivot([
            'state_id',
            'priority',
            'is_active',
        ])->withTimestamps();
    }

    public function intakeQuestionSets(): HasMany
    {
        return $this->hasMany(NetworkIntakeQuestionSet::class, 'flow_id');
    }

    public function flowRuns(): HasMany
    {
        return $this->hasMany(DrNetworkFlowRun::class, 'flow_id');
    }

    public function configurableRules(): MorphMany
    {
        return $this->morphMany(ConfigurableRule::class, 'ruleable')->orderBy('execution_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForNetwork(Builder $query, int $drNetworkId): Builder
    {
        return $query->where('dr_network_id', $drNetworkId);
    }

    public function scopeForKey(Builder $query, string $flowKey): Builder
    {
        return $query->where('flow_key', $flowKey);
    }
}
