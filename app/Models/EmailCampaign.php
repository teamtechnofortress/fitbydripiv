<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'staff_id',
        'content',
        'company_signature',
        'include_signature',
        'send_date',
        'send_time',
        'texts_per_send',
        'patient_start',
        'patient_end',
        'sent',
        'attachments',
        'contact',
        'archive_after_send'
    ];

    protected $casts = [
        'send_date' => 'date',
        'include_signature' => 'boolean',
        'archive_after_send' => 'boolean'
    ];
}
