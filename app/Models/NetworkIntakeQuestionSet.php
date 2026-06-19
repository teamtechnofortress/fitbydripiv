<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class NetworkIntakeQuestionSet extends Model
{
    use HasFactory;

    public const ALL_SCOPE = '*';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    protected $table = 'network_intake_question_sets';

    protected $fillable = [
        'dr_network_id',
        'flow_id',
        'product_code',
        'state_code',
        'set_key',
        'set_name',
        'version',
        'status',
        'metadata',
    ];

    protected $casts = [
        'dr_network_id' => 'integer',
        'flow_id' => 'integer',
        'flow_scope_id' => 'integer',
        'version' => 'integer',
        'metadata' => 'array',
    ];

    public function drNetwork(): BelongsTo
    {
        return $this->belongsTo(DrNetwork::class, 'dr_network_id');
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(NetworkFlowDefinition::class, 'flow_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(NetworkIntakeQuestion::class, 'question_set_id')
            ->active()
            ->ordered();
    }

    public function allQuestions(): HasMany
    {
        return $this->hasMany(NetworkIntakeQuestion::class, 'question_set_id')
            ->ordered();
    }

    public function setProductCodeAttribute(?string $value): void
    {
        $this->attributes['product_code'] = $this->normalizeScopeCode($value);
    }

    public function setStateCodeAttribute(?string $value): void
    {
        $value = $this->normalizeScopeCode($value);

        $this->attributes['state_code'] = $value === self::ALL_SCOPE ? $value : strtoupper($value);
    }

    public function setStatusAttribute(string $value): void
    {
        if (! in_array($value, self::STATUSES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid status "%s". Allowed values: %s.',
                $value,
                implode(', ', self::STATUSES)
            ));
        }

        $this->attributes['status'] = $value;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeForNetwork(Builder $query, int $drNetworkId): Builder
    {
        return $query->where('dr_network_id', $drNetworkId);
    }

    public function scopeApplicableTo(
        Builder $query,
        ?int $flowId,
        ?string $productCode,
        ?string $stateCode
    ): Builder {
        $productCode = $this->normalizeScopeCode($productCode);
        $stateCode = $this->normalizeScopeCode($stateCode);

        return $query
            ->where(function (Builder $query) use ($flowId): void {
                $query->whereNull('flow_id');

                if ($flowId !== null) {
                    $query->orWhere('flow_id', $flowId);
                }
            })
            ->whereIn('product_code', array_unique([$productCode, self::ALL_SCOPE]))
            ->whereIn('state_code', array_unique([$stateCode, self::ALL_SCOPE]));
    }

    public function scopeMostSpecific(
        Builder $query,
        ?int $flowId,
        ?string $productCode,
        ?string $stateCode
    ): Builder {
        $productCode = $this->normalizeScopeCode($productCode);
        $stateCode = $this->normalizeScopeCode($stateCode);

        return $query
            ->orderByRaw(
                '(
                    CASE WHEN flow_id = ? THEN 4 ELSE 0 END +
                    CASE WHEN product_code = ? THEN 2 ELSE 0 END +
                    CASE WHEN state_code = ? THEN 1 ELSE 0 END
                ) DESC',
                [$flowId, $productCode, $stateCode]
            )
            ->orderByDesc('version')
            ->orderByDesc('id');
    }

    public static function resolveFor(
        int $drNetworkId,
        ?int $flowId = null,
        ?string $productCode = null,
        ?string $stateCode = null
    ): ?self {
        return self::query()
            ->forNetwork($drNetworkId)
            ->published()
            ->applicableTo($flowId, $productCode, $stateCode)
            ->mostSpecific($flowId, $productCode, $stateCode)
            ->first();
    }

    private function normalizeScopeCode(?string $value): string
    {
        $value = trim((string) $value);

        return $value === '' ? self::ALL_SCOPE : $value;
    }
}
