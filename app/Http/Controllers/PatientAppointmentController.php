<?php

namespace App\Http\Controllers;

use App\Models\PatientAppointment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;
use GrahamCampbell\ResultType\Success;
use Illuminate\Support\Facades\Mail;

class PatientAppointmentController extends BaseController
{
    /**
     * getAppointments
     */
    public function getAppointments(Request $request){
        $validator = Validator::make($request->all(), [
            'start' => 'required',
            'end' => 'required',
        ]);
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }
        //$patients = array_map('intval', explode(',', $request->patients));
        $appointments = PatientAppointment::where('deleted', 0)
                                    // ->where('staff_id', $request->staff_id)
                                    ->where('start', '<=', $request->end)
                                    ->where('end', '>=', $request->start)
                                    //->whereIn('patient_id', $patients)
                                    ->get();

        $success['appointments'] = $appointments;
        return $this->sendResponse($success, 'Successfully get Patient appointments');
    }

    /**
     * getAllAppointments
     */
    public function getAllAppointments(Request $request){

        $appointments = PatientAppointment::where('deleted', 0)->get();

        $success['appointments'] = $appointments;
        return $this->sendResponse($success, 'Successfully get All Patient appointments');
    }

    /**
     * getAppointment
     */
    public function getAppointment(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id'   => 'required|exists:patient_appointments,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        /*
        * ########### Appointment data remove ################
        */
        $appointment = PatientAppointment::where('id', $id)->get();
        $success['appointment'] = $appointment;
        return $this->sendResponse($success, "Successfully get an appointment.");
    }

    /**
     * Add Appointment Data
     */
    public function addAppointment(Request $request){

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|min:1',
            'start'         => 'required',
            'end'           => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        //Create the Appointment
        PatientAppointment::create([            
            'patient_id'   => $request->patient_id,
            'name'         => $request->name,
            'start'        => $request->start,
            'end'          => $request->end,
            'goal'         => $request->goal,
            'therapy'      => $request->therapy,
            'inventory_id' => $request->inventory_id,
            'appointed_type'=> $request->appointed_type,
            'deleted'       => 0,
        ]);

        $success = "ok";

        return $this->sendResponse($success, 'PatientAppointment created successfully.');
    }

    /**
     * Update Appointment Data
     */
    public function updateAppointment(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id'   => 'required|exists:patient_appointments,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $appointmentInfo = [
            'patient_id' =>$request->patient_id,
            'name'       => $request->name,
            'start'      => $request->start,
            'end'        => $request->end,
            'goal'    => $request->goal,
            'therapy' => $request->therapy,
            "appointed_type" => $request->appointed_type,
            'inventory_id' => $request->inventory_id,
            'deleted' => 0,
        ];

        /*
        * ########### Update the Appointment ################
        */
        $appointment = PatientAppointment::updateOrCreate(['id' => $id], $appointmentInfo);

        $success['appointment'] = $appointment;

        return $this->sendResponse($success, "Successfully Updated Patient Appointment Info.");
    }

    /**
    * Remove Appointments
    */
    public function removeAppointment(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id'   => 'required|exists:patient_appointments,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        /*
        * ########### Appointment data remove ################
        */
        PatientAppointment::where('id', $id)->delete();

        return $this->sendResponse(true, "Successfully Deleted Patient Appointment.");
    }

}
