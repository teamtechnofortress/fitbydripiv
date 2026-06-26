<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationRecord extends Model
{
    protected $table = 'consultation_records';

    protected $fillable = [
        'order_id',
        'dr_network_id',
        'network_case_id',
        'network_status',
        'internal_status',
        'network_metadata',
        'payable_amount',
        'currency',
        'submitted_at',
        'resolved_at',
    ];

    protected $casts = [
        'network_metadata' => 'array',
        'payable_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function drNetwork(): BelongsTo
    {
        return $this->belongsTo(DrNetwork::class, 'dr_network_id');
    }
}
