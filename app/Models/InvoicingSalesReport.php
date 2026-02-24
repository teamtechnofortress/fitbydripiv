<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoicingSalesReport extends Model
{
    use HasFactory;
    protected $table = 'invoicing_sales_report';

    protected $fillable = [
        'frequency',
        'range_sdate',
        'range_edate',
        'credit_card',
        'cash',
        'transfer',
        'paypal',
        'venmo',
        'cashapp',
        'crypto',
        'sales_totals',
        'sales_detail',
        'profit',
        'margin',
        'sales_tax',        
        'email',
        'reported_date',
        'deleted',
    ];
}
