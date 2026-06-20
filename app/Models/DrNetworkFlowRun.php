<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrNetworkFlowRun extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_RUNNING,
        self::STATUS_PAUSED,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    protected $table = 'dr_network_flow_runs';

    protected $fillable = [
        'order_id',
        'dr_network_id',
        'flow_id',
        'status',
        'current_step_key',
        'context',
        'pause_reason',
        'failure_reason',
        'started_at',
        'paused_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'dr_network_id' => 'integer',
        'flow_id' => 'integer',
        'context' => 'array',
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function drNetwork(): BelongsTo
    {
        return $this->belongsTo(DrNetwork::class, 'dr_network_id');
    }

    public function flowDefinition(): BelongsTo
    {
        return $this->belongsTo(NetworkFlowDefinition::class, 'flow_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(DrNetworkFlowRunStep::class, 'flow_run_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_RUNNING,
            self::STATUS_PAUSED,
        ]);
    }
}
