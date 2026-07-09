<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NetworkConfigAuditLog extends Model
{
    protected $table = 'network_config_audit_logs';

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'action',
        'before',
        'after',
        'actor_id',
        'actor_role',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
