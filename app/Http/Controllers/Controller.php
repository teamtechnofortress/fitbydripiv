<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    static public function getPatientLevel($fromDate, $toDate){
                
        return DB::table('invoice')
            ->join('patient', 'invoice.patient_id', '=', 'patient.id')
            ->select(
                DB::raw('invoice.patient_id as id'), 
                DB::raw('SUM(invoice.totalPrice) as totalPrice'),                 
                'patient.first_name', 
                'patient.middle_name', 
                'patient.last_name',
                )
            ->whereBetween('invoice.created_at', [$fromDate, $toDate])
            ->groupBy('invoice.patient_id', 'patient.first_name', 'patient.middle_name', 'patient.last_name')            
            ->get();        
    }
}
