<?php

namespace App\Models;

use App\Models\User;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientPhysicalExam extends Model
{
    use HasFactory;
    protected $table = 'patient_physical_exam';
    
    protected $fillable = [
        'patient_id',        
        'staff_id',        
        'date',        
        'notes',  
        'BP',
        'HR',
        'Temp',
        'WT',
        'deleted'   
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id')->where('deleted', 0);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id')->where('deleted', 0);
    }
}
