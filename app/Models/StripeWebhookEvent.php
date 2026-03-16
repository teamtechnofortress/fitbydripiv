<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StripeWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'stripe_event_id',
        'event_type',
        'payload_json',
        'webhookable_id',
        'webhookable_type',
        'processed',
        'processed_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function webhookable(): MorphTo
    {
        return $this->morphTo();
    }
}
