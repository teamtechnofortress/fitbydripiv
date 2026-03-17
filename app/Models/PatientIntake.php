<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientIntake extends Model
{
    use HasFactory;

    protected $table = 'patient_intakes';

    protected $fillable = [
        'patient_id',
        'patient_type',
        'diabetes',
        'blood_thinners',
        'alcohol',
        'glp_history',
        'pancreatitis',
        'thyroid_cancer',
        'renal_impairment',
        'current_conditions',
        'additional_conditions',
        'goals',
        'medical_history',
        'medications',
        'current_conditions_notes',
        'allergies',
        'allergy_reactions',
        'submission_status',
        'reviewed_by',
        'reviewed_at',
        'notes',
    ];

    protected $casts = [
        'current_conditions' => 'array',
        'additional_conditions' => 'array',
        'goals' => 'array',
        'medical_history' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
