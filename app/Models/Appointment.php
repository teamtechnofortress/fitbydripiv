<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointment';

    protected $fillable = [
        'staff_id',
        'patient_name'  ,
        'label',
        'start',
        'end',
        'isAllDay',
        'url',
        'guests',
        'location',
        'description',
        'goal',
        'treatment'
    ];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
        'isAllDay' => 'boolean',
    ];
}
