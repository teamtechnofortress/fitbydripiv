<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffPayroll extends Model
{
    use HasFactory;
    protected $table = 'staff_payroll';

    protected $fillable = [
        'staff_id',
        'frequency',
        'withholding',
        'payrate',
        'others',
        'deleted'  
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id')->where('deleted', 0);
    }
}
