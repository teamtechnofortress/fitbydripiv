<?php

namespace App\Http\Controllers;

use App\Models\Banking;
use App\Models\BusinessHours;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;
use GrahamCampbell\ResultType\Success;
use Illuminate\Support\Facades\Mail;

class SettingsController extends BaseController
{
    /**
     * Save Banking Data
     */
    public function saveBankingData(Request $request){

        $validator = Validator::make($request->all(), [

        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $bankingData = Banking::first();
        if($bankingData){
            $bankingData->update($request->all());
        } else{
            $bankingData = Banking::create([
                ...$request->all(),
            ]);
        }        

        $success['bankingData'] = $bankingData;

        return $this->sendResponse($success, 'BankingData Saved successfully.');
    }   

    public function getBankingData(Request $request){
        $validator = Validator::make($request->all(), [

        ]);
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $bankingData = Banking::first();

        $success['bankingData'] = $bankingData;
        return $this->sendResponse($success, 'Successfully get bankingData');
    } 

        /**
     * Save Banking Data
     */
    public function saveBusinessHours(Request $request){

        $validator = Validator::make($request->all(), [
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $businessHours = BusinessHours::first();
        if($businessHours){
            $businessHours->update($request->all());
        } else{
            $businessHours = BusinessHours::create([
                ...$request->all(),
            ]);
        }        

        $success['businessHours'] = $businessHours;

        return $this->sendResponse($success, 'BusinessHours Saved successfully.');
    }   

    public function getBusinessHours(Request $request){
        $validator = Validator::make($request->all(), [

        ]);
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $businessHours = BusinessHours::first();

        $success['businessHours'] = $businessHours;
        return $this->sendResponse($success, 'Successfully get businessHours');
    } 
}
