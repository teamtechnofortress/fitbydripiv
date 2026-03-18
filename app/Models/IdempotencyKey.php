<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'endpoint',
        'request_hash',
        'response_payload',
        'status',
    ];

    protected $casts = [
        'response_payload' => 'array',
    ];
}
