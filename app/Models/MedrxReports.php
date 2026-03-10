<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedrxReports extends Model
{
    use HasFactory;
    protected $table = 'medrx_reports';

    protected $fillable = [
        'frequency',
        'range_sdate',
        'range_edate',        
        'email',
        'reported_date',
        'iv_solutions',
        'injectable',
        'peptides',
        'consumables',
        'on_hand',
        'sold_invoiced',
        'deleted',
    ];
}
