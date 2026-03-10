<?php

namespace App\Models;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Intake3 extends Model
{
    use HasFactory;
    protected $table = 'intake_3';

    protected $fillable = [
        'patient_id',  
        'intake1_id',
        'agreedTxt'      
    ];

    public function patient(){
        return $this->belongsTo(Patient::class, 'patient_id', 'id');
    }
}
