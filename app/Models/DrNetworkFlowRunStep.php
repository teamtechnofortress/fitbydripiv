<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrNetworkFlowRunStep extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'dr_network_flow_run_steps';

    protected $fillable = [
        'flow_run_id',
        'step_key',
        'status',
        'output',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'flow_run_id' => 'integer',
        'output' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function flowRun(): BelongsTo
    {
        return $this->belongsTo(DrNetworkFlowRun::class, 'flow_run_id');
    }
}
