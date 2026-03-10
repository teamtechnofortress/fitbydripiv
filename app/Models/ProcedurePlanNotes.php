<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcedurePlanNotes extends Model
{
    use HasFactory;
    protected $table = 'procedure_plan_notes';
    
    protected $fillable = [
        'treatment_type',        
        'notes',        
        'deleted'  
    ];
}
