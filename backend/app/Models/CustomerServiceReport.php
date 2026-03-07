<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerServiceReport extends Model
{
    use HasFactory;
    protected $table = 'customer_service_report';

    protected $fillable = [
        'frequency',
        'range_sdate',
        'range_edate',
        'arrive_stime',
        'arrive_etime',
        'reward_sales',
        'add_on_beg',
        'add_on_end',
        'email',
        'reported_date',
        'is_arrive_time',
        'deleted',
    ];
}
