<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessHours extends Model
{
    use HasFactory;

    protected $table = 'business_hours';

    protected $fillable = [
        'start_time',
        'end_time',
        'twillio_sid',
        'twillio_auth_token',
        'twillio_phone_number',
        'company_name',
        'invoice_intro_text'
    ];

    protected $casts = [

    ];
}
