<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiefComplaintNotes extends Model
{
    use HasFactory;
    protected $table = 'chief_complaint_notes';
    
    protected $fillable = [ 
        'treatment_type',        
        'notes',        
        'allergies',        
        'medication',        
        'physical_exam',        
        'evaluation',        
        'vital_sign',        
        'assessment',        
        'plan_order',        
        'plan_care',        
        'plan_instruction',        
        'risk',        
        'benefit',        
        'reward',   
        'system_review',   
        'conditions',
        'deleted'  
    ];
}
