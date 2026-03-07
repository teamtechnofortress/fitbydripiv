<?php

namespace App\Models;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChartHistory extends Model
{
    use HasFactory;

    protected $table = 'chart_history';
    
    protected $fillable = [
        'patient_id',
        'frequency',
        'isEncounters',
        'isProducts',
        'range_sdate',
        'range_edate',
        'hasNotes',
        'email',
        'reported_date',
        'deleted',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id')->where('deleted', 0);
    }
}
