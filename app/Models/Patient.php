<?php

namespace App\Models;

use App\Models\Intake1;
use App\Models\PatientPlan;
use App\Models\ChiefComplaint;
use App\Models\PatientEncounter;
use App\Models\PatientProcedure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $table = 'patient';

    protected $fillable = [
        "first_name",
        "middle_name",
        "last_name",
        "email",
        "birthday",
        "cell",
        "phone",
        "home",
        "emergency",
        "contact",
        "referred",
        "address",
        "city",
        "state",
        "zip",
        "age",
        "gender",
        "ethnicity",
        "current_conditions",
        "current_allergies",
        "allergy_reactions",
        "current_medications",
        "pregnant",
        "tobacco",
        "drug_use",
        "alcohol",
        "signature",        
    ];

    public function intake(){
        return $this->hasMany(Intake1::class, 'patient_id', 'id');
    }

    public function encounter(){
        return $this->hasMany(PatientEncounter::class, 'patient_id', 'id')->where('paid', false);
    }
    
    public function encounterAll(){
        return $this->hasMany(PatientEncounter::class, 'patient_id', 'id')->where('deleted', 0);
    }

    public function complaint(){
        return $this->hasMany(ChiefComplaint::class, 'patient_id', 'id')->where('deleted', 0);
    }

    public function assessment(){
        return $this->hasMany(PatientAssessment::class, 'patient_id', 'id')->where('deleted', 0);
    }

    public function physicalExam(){
        return $this->hasMany(PatientPhysicalExam::class, 'patient_id', 'id')->where('deleted', 0);
    }

    public function patientPlan(){
        return $this->hasMany(PatientPlan::class, 'patient_id', 'id')->where('deleted', 0);
    }

    public function patientProcedure(){
        return $this->hasMany(PatientProcedure::class, 'patient_id', 'id')->where('deleted', 0);
    }

    public function invoice(){
        return $this->hasMany(Invoice::class, 'patient_id', 'id')->where('deleted', 0);
    }    

    public function intakes(){
        return $this->hasMany(PatientIntake::class, 'patient_id');
    }

    public function latestIntake(){
        return $this->hasOne(PatientIntake::class, 'patient_id')->latestOfMany();
    }
}
