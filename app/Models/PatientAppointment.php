<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientAppointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'name'  ,
        'start',
        'end',
        'goal',
        'therapy',
        'inventory_id',
        'appointed_type'
    ];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id')->where('deleted', 0);
    }
}
