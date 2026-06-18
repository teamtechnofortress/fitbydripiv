<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class State extends Model
{
    protected $table = 'states';

    protected $fillable = [
        'country_code',
        'state_code',
        'state_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function mappings(): HasMany
    {
        return $this->hasMany(NetworkStateMapping::class, 'state_id');
    }

    public function configurableRules(): MorphMany
    {
        return $this->morphMany(ConfigurableRule::class, 'ruleable')->orderBy('execution_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCode(Builder $query, string $stateCode, string $countryCode = 'US'): Builder
    {
        return $query
            ->where('country_code', strtoupper($countryCode))
            ->where('state_code', strtoupper($stateCode));
    }

    public function scopeMatchingState(Builder $query, string $state, string $countryCode = 'US'): Builder
    {
        $normalizedState = strtoupper(trim($state));

        return $query
            ->where('country_code', strtoupper(trim($countryCode)))
            ->where(function (Builder $query) use ($normalizedState): void {
                $query
                    ->where('state_code', $normalizedState)
                    ->orWhereRaw('UPPER(state_name) = ?', [$normalizedState]);
            });
    }
}
