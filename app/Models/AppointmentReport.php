<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentReport extends Model
{
    use HasFactory;
    protected $table = 'appointment_report';

    protected $fillable = [
        'frequency',
        'range_sdate',
        'range_edate',
        'online',                
        'phoneIn',
        'walkIn',
        'noShow',        
        'email',
        'reported_date',
        'deleted',
    ];
}
 