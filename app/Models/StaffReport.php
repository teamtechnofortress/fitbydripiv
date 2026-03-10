<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffReport extends Model
{
    use HasFactory; 
    protected $table = 'staff_reports';

    protected $fillable = [
        'frequency',
        'range_sdate',
        'range_edate',        
        'reported_date',
        'email',
        'late_checkin',
        'early_checkin',
        'overtime_incident',
        'late_schedule',
        'deleted',
    ];
}
