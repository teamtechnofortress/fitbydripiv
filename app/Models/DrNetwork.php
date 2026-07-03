<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use InvalidArgumentException;

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

    public function configValues(): HasMany
    {
        return $this->hasMany(DrNetworkConfigValue::class, 'dr_network_id');
    }

    public function configValue(string $key, mixed $default = null): mixed
    {
        $normalizedKey = DrNetworkConfigValue::normalizeKey($key);

        $configValue = $this->relationLoaded('configValues')
            ? $this->configValues->firstWhere('key', $normalizedKey)
            : $this->configValues()->where('key', $normalizedKey)->first();

        return $configValue?->typedValue() ?? $default;
    }

    public function configValuesArray(bool $includeSecrets = true): array
    {
        $values = $this->relationLoaded('configValues')
            ? $this->configValues
            : $this->configValues()->get();

        return $values
            ->when(! $includeSecrets, fn ($values) => $values->where('is_secret', false))
            ->mapWithKeys(fn (DrNetworkConfigValue $value): array => [
                $value->key => $value->typedValue(),
            ])
            ->all();
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

    public function documentRules(): HasMany
    {
        return $this->hasMany(NetworkDocumentRule::class, 'dr_network_id')->orderBy('priority');
    }

    public function intakeQuestionSets(): HasMany
    {
        return $this->hasMany(NetworkIntakeQuestionSet::class, 'dr_network_id');
    }

    public function productMappings(): HasMany
    {
        return $this->hasMany(NetworkProductMapping::class, 'dr_network_id');
    }

    public function flowRuns(): HasMany
    {
        return $this->hasMany(DrNetworkFlowRun::class, 'dr_network_id');
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(DrNetworkWebhookEvent::class, 'dr_network_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'network_product_mappings',
            'dr_network_id',
            'product_id'
        )->withPivot([
            'flow_id',
            'external_service_id',
            'external_service_key',
            'external_config',
            'is_active',
        ])->withTimestamps();
    }

    public function configurableRules(): MorphMany
    {
        return $this->morphMany(ConfigurableRule::class, 'ruleable')->orderBy('execution_order');
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
