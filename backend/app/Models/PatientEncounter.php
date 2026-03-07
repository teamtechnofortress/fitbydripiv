<?php

namespace App\Models;

use App\Models\Patient;
use App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientEncounter extends Model
{
    use HasFactory;

    protected $table = 'patient_encounter';

    protected $fillable = [
        "patient_id",
        "type",        
        "name",        
        "dosage",    
        "ingredients",
        "quantity",   
        "paid",
        "inventory_id",
        "is_add_on",
        "unit",
    ];

    public function patient(){
        return $this->belongsTo(Patient::class, 'patient_id', 'id');
    }

    public function inventory(){
        return $this->belongsTo(Inventory::class, 'inventory_id', 'id');
    }
}
