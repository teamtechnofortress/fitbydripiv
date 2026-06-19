<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkDocumentRule extends Model
{
    use HasFactory;

    protected $table = 'network_document_rules';

    public const OPERATOR_ANY = 'any';

    public const OPERATOR_ALL = 'all';

    public const OPERATOR_EXACT = 'exact';

    public const OPERATORS = [
        self::OPERATOR_ANY,
        self::OPERATOR_ALL,
        self::OPERATOR_EXACT,
    ];

    public const REQUIREMENT_IDENTITY = 'identity';

    public const REQUIREMENT_VERIFICATION = 'verification';

    public const REQUIREMENT_MEDICAL = 'medical';

    public const REQUIREMENT_CONDITION_SPECIFIC = 'condition_specific';

    public const REQUIREMENT_INSURANCE = 'insurance';

    public const REQUIREMENT_CONSENT = 'consent';

    public const REQUIREMENT_PRESCRIPTION = 'prescription';

    public const REQUIREMENT_TYPES = [
        self::REQUIREMENT_IDENTITY,
        self::REQUIREMENT_VERIFICATION,
        self::REQUIREMENT_MEDICAL,
        self::REQUIREMENT_CONDITION_SPECIFIC,
        self::REQUIREMENT_INSURANCE,
        self::REQUIREMENT_CONSENT,
        self::REQUIREMENT_PRESCRIPTION,
    ];

    protected $fillable = [
        'dr_network_id',
        'flow_key',
        'state_code',
        'product_code',
        'rule_key',
        'rule_name',
        'priority',
        'requirement_type',
        'operator',
        'document_ids',
        'is_required',
        'conditions',
        'error_message',
        'help_text',
        'is_active',
    ];

    protected $casts = [
        'priority' => 'integer',
        'document_ids' => 'array',
        'is_required' => 'boolean',
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function drNetwork(): BelongsTo
    {
        return $this->belongsTo(DrNetwork::class, 'dr_network_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForNetwork(Builder $query, int $drNetworkId): Builder
    {
        return $query->where('dr_network_id', $drNetworkId);
    }

    public function scopeForFlow(Builder $query, string $flowKey): Builder
    {
        return $query->where('flow_key', $flowKey);
    }

    public function scopeForState(Builder $query, ?string $stateCode): Builder
    {
        return $query->where(function (Builder $query) use ($stateCode): void {
            $query->whereNull('state_code');

            if ($stateCode !== null) {
                $query->orWhere('state_code', strtoupper($stateCode));
            }
        });
    }

    public function scopeForProduct(Builder $query, ?string $productCode): Builder
    {
        return $query->where(function (Builder $query) use ($productCode): void {
            $query->whereNull('product_code');

            if ($productCode !== null) {
                $query->orWhere('product_code', $productCode);
            }
        });
    }

    public function scopeForRequirementType(Builder $query, string $requirementType): Builder
    {
        return $query->where('requirement_type', $requirementType);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority')->orderBy('id');
    }
}
