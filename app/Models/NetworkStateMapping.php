<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkStateMapping extends Model
{
    protected $table = 'network_state_mappings';

    protected $fillable = [
        'state_id',
        'dr_network_id',
        'flow_id',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function drNetwork(): BelongsTo
    {
        return $this->belongsTo(DrNetwork::class, 'dr_network_id');
    }

    public function flowDefinition(): BelongsTo
    {
        return $this->belongsTo(NetworkFlowDefinition::class, 'flow_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForState(Builder $query, int $stateId): Builder
    {
        return $query->where('state_id', $stateId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority')->orderBy('id');
    }
}
