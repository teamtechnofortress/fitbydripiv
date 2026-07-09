<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrNetworkPayout extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_WIRE = 'wire';

    public const METHOD_CHECK = 'check';

    public const METHOD_OTHER = 'other';

    public const METHODS = [
        self::METHOD_BANK_TRANSFER,
        self::METHOD_WIRE,
        self::METHOD_CHECK,
        self::METHOD_OTHER,
    ];

    protected $fillable = [
        'dr_network_id',
        'amount',
        'currency',
        'method',
        'reference_number',
        'note',
        'status',
        'paid_at',
        'initiated_by',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function drNetwork(): BelongsTo
    {
        return $this->belongsTo(DrNetwork::class, 'dr_network_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCountsTowardPaidOut(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForNetwork(Builder $query, int $drNetworkId): Builder
    {
        return $query->where('dr_network_id', $drNetworkId);
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $query) => $query->where('paid_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('paid_at', '<=', $to));
    }
}
