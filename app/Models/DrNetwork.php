<?php

namespace App\Models;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrNetwork extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_PAUSED = 'paused';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_PAUSED,
    ];

    public const INTEGRATION_MODE_API = 'api';

    public const INTEGRATION_MODE_MANUAL = 'manual';

    public const INTEGRATION_MODES = [
        self::INTEGRATION_MODE_API,
        self::INTEGRATION_MODE_MANUAL,
    ];

    protected $table = 'dr_networks';

    protected $fillable = [
        'name',
        'slug',
        'adapter_key',
        'integration_mode',
        'status',
        'is_default',
        'settings',
        'metadata',
        'feature_flags',
        'config_version',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'settings' => 'array',
        'metadata' => 'array',
        'feature_flags' => 'array',
        'config_version' => 'integer',
    ];

    public function mappings(): HasMany
    {
        return $this->hasMany(NetworkStateMapping::class, 'dr_network_id');
    }

    public function flowDefinitions(): BelongsToMany
    {
        return $this->belongsToMany(
            NetworkFlowDefinition::class,
            'network_state_mappings',
            'dr_network_id',
            'flow_id'
        )->withPivot([
            'state_id',
            'priority',
            'is_active',
        ])->withTimestamps();
    }

    public function setStatusAttribute(string $value): void
    {
        $this->attributes['status'] = $this->validatedValue($value, self::STATUSES, 'status');
    }

    public function setIntegrationModeAttribute(string $value): void
    {
        $this->attributes['integration_mode'] = $this->validatedValue(
            $value,
            self::INTEGRATION_MODES,
            'integration_mode'
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    private function validatedValue(string $value, array $allowedValues, string $attribute): string
    {
        if (! in_array($value, $allowedValues, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid %s "%s". Allowed values: %s.',
                $attribute,
                $value,
                implode(', ', $allowedValues)
            ));
        }

        return $value;
    }
}
