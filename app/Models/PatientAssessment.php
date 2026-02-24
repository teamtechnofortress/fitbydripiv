<?php

namespace App\Models;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientAssessment extends Model
{
    use HasFactory; 
    protected $table = 'patient_assessment';
    
    protected $fillable = [
        'patient_id',
        'staff_id',
        'date',
        'diag_description',
        'diag_problem',
        'diag_comment',
        'notes',
        'treatment_type',
        'deleted'  
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id')->where('deleted', 0);
    }
}
