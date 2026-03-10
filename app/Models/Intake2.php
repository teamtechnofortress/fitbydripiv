<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intake2 extends Model
{
    use HasFactory;
    protected $table = 'intake_2';

    protected $fillable = [
        'patient_id',
        'constitutional'  ,
        'head',
        'eyes',
        'nose',
        'mouth',
        'ears',
        'throat_neck',
        'respiratory',
        'cardiovascular',
        'gastrointestinal',
        'musculoskeletal',
        'skin',
        'endocrine',
        'urinary',
        'male_genitalia',
        'neurological',
        'intake1_id',
    ];
}
