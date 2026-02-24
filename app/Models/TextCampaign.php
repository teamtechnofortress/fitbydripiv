<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'staff_id',
        'message',
        'company_signature',
        'include_signature',
        'send_date',
        'send_time',
        'texts_per_send',
        'patient_start',
        'patient_end',
        'sent',
        'is_send_birthday',
    ];

    protected $casts = [
        'send_date' => 'date',
        'include_signature' => 'boolean',
    ];
}
