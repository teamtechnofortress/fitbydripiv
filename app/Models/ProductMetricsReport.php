<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductMetricsReport extends Model
{
    use HasFactory;
    protected $table = 'product_metrics_report';

    protected $fillable = [
        'frequency',
        'range_sdate',
        'range_edate',
        'isSemaglutide',
        'isTirzepatide',
        'isIV',
        'isInjections',
        'isPeptides',
        'isOther',
        'email',
        'reported_date',
        'deleted',
    ];
}
