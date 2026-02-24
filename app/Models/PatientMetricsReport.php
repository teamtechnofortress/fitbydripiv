<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientMetricsReport extends Model
{
    use HasFactory;
    protected $table = 'patient_metrics_report';

    protected $fillable = [
        'frequency',
        'range_sdate',
        'range_edate',
        'type',                
        'add_on_purchase',
        'reward_usage',
        'utilized_discount',
        'email',
        'reported_date',
        'deleted',
    ];
}
