<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'stripe_payment_intent_id',
        'amount',
        'currency',
        'status',
        'failure_reason',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
