<?php

namespace App\Models;

use App\Support\Money\DecimalMoney;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrNetworkTransaction extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_VOID = 'void';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_VOID,
        self::STATUS_REFUNDED,
    ];

    protected $fillable = [
        'dr_network_id',
        'order_id',
        'consultation_record_id',
        'product_id',
        'flow_id',
        'patient_paid_amount',
        'network_owed_amount',
        'currency',
        'status',
        'void_reason',
        'voided_by',
        'voided_at',
        'occurred_at',
        'metadata',
    ];

    protected $casts = [
        'patient_paid_amount' => 'decimal:2',
        'network_owed_amount' => 'decimal:2',
        'occurred_at' => 'datetime',
        'voided_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'profit_amount',
    ];

    public function getProfitAmountAttribute(): string
    {
        return DecimalMoney::subtract($this->patient_paid_amount, $this->network_owed_amount);
    }

    public function drNetwork(): BelongsTo
    {
        return $this->belongsTo(DrNetwork::class, 'dr_network_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function consultationRecord(): BelongsTo
    {
        return $this->belongsTo(ConsultationRecord::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(NetworkFlowDefinition::class, 'flow_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCountsTowardPatientRevenue(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCountsTowardNetworkObligation(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForNetwork(Builder $query, int $drNetworkId): Builder
    {
        return $query->where('dr_network_id', $drNetworkId);
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $query) => $query->where('occurred_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('occurred_at', '<=', $to));
    }
}
