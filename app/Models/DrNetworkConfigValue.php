<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;

class DrNetworkConfigValue extends Model
{
    public const TYPE_STRING = 'string';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_JSON = 'json';

    public const TYPES = [
        self::TYPE_STRING,
        self::TYPE_INTEGER,
        self::TYPE_BOOLEAN,
        self::TYPE_JSON,
    ];

    protected $table = 'dr_network_config_values';

    protected $fillable = [
        'dr_network_id',
        'key',
        'value',
        'lookup_hash',
        'value_type',
        'is_secret',
        'display_name',
        'description',
    ];

    protected $casts = [
        'is_secret' => 'boolean',
    ];

    public function drNetwork(): BelongsTo
    {
        return $this->belongsTo(DrNetwork::class, 'dr_network_id');
    }

    public static function normalizeKey(string $key): string
    {
        return Str::snake(trim($key));
    }

    public static function lookupHash(string $value): string
    {
        return hash('sha256', trim($value));
    }

    public function typedValue(): mixed
    {
        return match ($this->value_type) {
            self::TYPE_INTEGER => $this->value === null ? null : (int) $this->value,
            self::TYPE_BOOLEAN => $this->value === null ? null : filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_JSON => $this->jsonValue(),
            default => $this->value,
        };
    }

    protected function key(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => self::normalizeKey($value)
        );
    }

    protected function valueType(): Attribute
    {
        return Attribute::make(
            set: function (string $value): string {
                if (! in_array($value, self::TYPES, true)) {
                    throw new InvalidArgumentException(sprintf(
                        'Invalid network config value_type "%s". Allowed values: %s.',
                        $value,
                        implode(', ', self::TYPES)
                    ));
                }

                return $value;
            }
        );
    }

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value === null ? null : Crypt::decryptString($value),
            set: fn (mixed $value): ?string => $value === null ? null : Crypt::encryptString(
                is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : (string) $value
            )
        );
    }

    private function jsonValue(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        try {
            return json_decode($this->value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }
}
