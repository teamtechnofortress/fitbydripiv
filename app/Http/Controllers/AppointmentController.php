<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;
use GrahamCampbell\ResultType\Success;
use Illuminate\Support\Facades\Mail;

class AppointmentController extends BaseController
{
    /**
     * getAppointments
     */
    public function getAppointments(Request $request){
        $validator = Validator::make($request->all(), [
            //'staff_id'   => 'required|integer|exists:users,id',
            //'calendars'  => 'required|string',
            'start' => 'required',
            'end' => 'required',
        ]);
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }
        //$calendars = explode(',', $request->calendars);
        $staffs = array_map('intval', explode(',', $request->staffs));
        $appointments = Appointment::where('deleted', 0)
                                    // ->where('staff_id', $request->staff_id)
                                    ->where('start', '<=', $request->end)
                                    ->where('end', '>=', $request->start)
                                    ->whereIn('staff_id', $staffs)
                                    // ->whereIn('label', $calendars)
                                    ->get();

        $success['appointments'] = $appointments;
        return $this->sendResponse($success, 'Successfully get appointments for this staff');
    }

    /**
     * getAppointment
     */
    public function getAppointment(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id'   => 'required|exists:appointment,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        /*
        * ########### Appointment data remove ################
        */
        $appointment = Appointment::where('id', $id)->get();
        $success['appointment'] = $appointment;
        return $this->sendResponse($success, "Successfully get an appointment.");
    }

    /**
     * Add Appointment Data
     */
    public function addAppointment(Request $request){

        $validator = Validator::make($request->all(), [
            'staff_id'      => 'required|integer|exists:users,id',//
            'title'         => 'required|string|min:1',
            'start'         => 'required',
            'end'           => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        //Add Staff
        Appointment::create([
            // ...$request->all(),
            'staff_id'      => $request->staff_id,
            'patient_name'         => $request->title,
            'label'         => 'Business',
            'start'         => $request->start,
            'end'           => $request->end,
            'isAllDay'      => $request->isAllDay,
            'url'           => $request->url,
            'guests'        => $request->guests,
            'location'      => $request->location,
            'description'   => $request->description,
            'goal' => $request->goal,
            'treatment' => $request->treatment,
            'deleted' => 0,
        ]);

        // $appointments = Appointment::where('deleted', 0)
        //                             ->where('staff_id', $request->staff_id)
        //                             ->get();

        // $success['appointments'] = $appointments;
        $success = "ok";

        return $this->sendResponse($success, 'Appointment created successfully.');
    }

    /**
     * Update Appointment Data
     */
    public function updateAppointment(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id'   => 'required|exists:appointment,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $appointmentInfo = [
            "staff_id" => $request->staff_id,
            "patient_name" => $request->title,
            "label" => 'Business',
            "start" => $request->start,
            "end" => $request->end,
            "isAllDay" => $request->isAllDay,
            "url" => $request->url,
            "guests" => $request->guests,
            "location" => $request->location,
            "description" => $request->description,
            'goal' => $request->goal,
            'treatment' => $request->treatment,
        ];

        /*
        * ########### Update the Appointment ################
        */
        $appointment = Appointment::updateOrCreate(['id' => $id], $appointmentInfo);

        $success['appointment'] = $appointment;

        return $this->sendResponse($success, "Successfully Updated Appointment Info.");
    }

    /**
    * Remove Appointments
    */
    public function removeAppointment(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id'   => 'required|exists:appointment,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        /*
        * ########### Appointment data remove ################
        */
        Appointment::where('id', $id)->delete();

        return $this->sendResponse(true, "Successfully Deleted Appointment.");
    }

}
