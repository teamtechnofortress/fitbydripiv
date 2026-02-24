<?php

namespace App\Models;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;
    protected $table = 'invoice';

    protected $fillable = [
        "patient_id",
        "data",
        "tip",
        "tax",
        "totalPrice",
        "isEailing",
        "isSendInstructions",
        "isPaid",
        "staff_id",
        "arrival_due", // unit is seconds
        "payment_type",
    ];

    public function patient(){
        return $this->belongsTo(Patient::class, 'patient_id', 'id');
    }
}
