<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrNetworkWebhookEvent extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    protected $table = 'dr_network_webhook_events';

    protected $fillable = [
        'dr_network_id',
        'adapter_key',
        'event_type',
        'external_event_id',
        'external_case_id',
        'external_order_id',
        'idempotency_hash',
        'status',
        'headers',
        'payload',
        'normalized_payload',
        'raw_body',
        'occurred_at',
        'processed_at',
        'failure_reason',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'normalized_payload' => 'array',
        'occurred_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function drNetwork(): BelongsTo
    {
        return $this->belongsTo(DrNetwork::class, 'dr_network_id');
    }
}
