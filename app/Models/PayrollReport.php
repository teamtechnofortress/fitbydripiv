<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollReport extends Model
{
    use HasFactory;
    protected $table = 'payroll_reports';

    protected $fillable = [
        'frequency',
        'range_sdate',
        'range_edate',        
        'email',
        'reported_date',
        'hours_worked',
        'salary',
        'calculated_overtime',
        'deleted',
    ];
}
