<?php

namespace App\Models;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Intake1 extends Model
{
    use HasFactory;

    protected $table = 'intake_1';

    protected $fillable = [
        'patient_id',
        'goal_iv'  ,
        'goal_injection',
        'goal_other',
        "weight_loss",
        'hydration',
        'energy',
        'recovery',
        'pain',
        'supplements',
        'fatigue',
        'headache',
        'soreness',
        'current_illness',
        'recent_illness',
        'hangover',
        'low_energy',
        'immunity'
    ];

    public function patient(){
        return $this->belongsTo(Patient::class, 'patient_id', 'id');
    }
}
