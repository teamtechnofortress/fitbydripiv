<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Patient;
use App\Models\StaffPayroll;
use Illuminate\Http\Request;
use App\Models\StaffSchedule;
use App\Mail\StaffScheduleMail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class StaffController extends BaseController
{
    /**
     * addStaff
     */
    public function addStaff(Request $request){
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|min:3',
            'lastName'  => 'required|string|min:3',
            'email'     => 'required|email|unique:users,email|max:255',
            'ssn'       => 'required|string|min:3',
            'address'   => 'required|string|min:3',
            'city'      => 'required|string|min:3',
            'state'     => 'required|string|min:3',
            'zip'       => 'required|string|min:3',
            'phone'     => 'required|string|min:3',
            'emergency' => 'required|string|min:3',
            'contact'   => 'required|string|min:3',
            'gender'    => 'required|string|min:3',
            'hourly_rate'=> 'required',
            'hiring_date'=> 'required',
            'title'     => 'required|string|min:3',
            'payment_method'=> 'required|string|min:3',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }
        
        //Add Staff
        User::create([
            ...$request->all(),
            'role' => (strtolower($request->role) == 'admin' ? 'admin' : 'staff'),
            'password' => Hash::make("12345678"),//<-- password setup constantly
        ]);

        $staffList = User::where(['deleted' => 0])->get();
        $success['staffList'] = $staffList;

        return $this->sendResponse($success, 'Staff created successfully.');
    }

    /**
     * updateStaff
     */
    public function updateStaff(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            // 'id' => [ 'required', Rule::exists('users', 'id')->where(function ($query) { $query->where('role', 'staff');})],
            'id' => [ 'required', Rule::exists('users', 'id')->where(function ($query) { $query->where('deleted', 0);})],
            'firstName' => 'required|string|min:2',
            'lastName'  => 'required|string|min:2',
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'ssn'       => 'required|string|min:3',
            'address'   => 'required|string|min:3',
            'city'      => 'required|string|min:3',
            'state'     => 'required|string|min:3',
            'zip'       => 'required|string|min:3',
            'phone'     => 'required|string|min:3',
            'emergency' => 'required|string|min:3',
            'contact'   => 'required|string|min:3',
            'gender'    => 'required|string|min:3',
            'hourly_rate'=> 'required',
            'hiring_date'=> 'required',
            'title'     => 'required|string|min:3',
            'payment_method'=> 'required|string|min:3',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        //Update Staff
        User::where('id', $id)->update([
            ...$request->all(),
            'created_at' => date('Y-m-d H:i:s', strtotime($request->created_at)), // to avoid error if created_at is not fillable 
            'updated_at' => date('Y-m-d H:i:s', strtotime($request->updated_at)), // to avoid error if updated_at is not fillable 
        ]);

        $staffList = User::where(['role' => 'staff', 'deleted' => 0])->get();
        $success['staffList'] = $staffList;        

        return $this->sendResponse($success, 'Staff updated successfully.');
    }

    /**
     * deleteStaff
     */
    public function deleteStaff(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id' => [ 'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'staff');
                }),
            ],
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        User::where(['id' => $id])->update(['deleted' => 1]);

        return $this->sendResponse(true, "Successfully Deleted Staff.");
    }

    /**
     * getAllStaffs
     */
    public function getAllStaffs(Request $request){       

        //repalce with staff later!
        // $success['staffList'] = User::where(['deleted' => 0, 'role' => 'staff'])->get();
        $success['staffList'] = User::where(['deleted' => 0])->get();

        return $this->sendResponse($success, "Staff List");
    }

    /**
     * getAllMembers
     */
    public function getAllMembers(Request $request){       

        $success['membersList'] = User::where(['deleted' => 0])->get();

        return $this->sendResponse($success, "Members List");
    }
        

    /**
     * getStaffByName
     */
    public function getStaffByName(Request $request){
        $validator = Validator::make($request->all(), [
            'fname'   => 'required|string|min:3',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }
        $searchTerm = '%'.$request->fname.'%';

        //repalce with staff later!
        $success['staffList'] = User::where('firstName', 'like', $searchTerm)->where(['deleted' => 0, 'role' => 'staff'])->get();

        return $this->sendResponse($success, "Staff List");
    }

    /**
     * saveStaffSchedule
     */
    public function saveStaffSchedule(Request $request){
        $validator = Validator::make($request->all(), [
            'date'     => 'required|string',
            'schedule' => 'required|string',
            'scheduledDays' => 'required|integer',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }
        
        //Add Staff Schedule        
        $startDate = Carbon::parse($request->date);
        $days = (int) $request->scheduledDays;

        //JSON => email convert
        $scheduleData = json_decode($request->schedule, true);

        $schedules = [];

        for ($i = 0; $i < $days; $i++) {
            $currentDate = $startDate->copy()->addDays($i);

            $schedule = StaffSchedule::updateOrCreate(
                ['date' => $currentDate->toDateString()],
                ['schedule' => $request->schedule]
            );

            $schedules[] = $schedule;
        }

        //Emailing to staffs with scheduled data
        foreach ($scheduleData as $time => $staffArray) {
            foreach ($staffArray as $staffJson) {
                $staff = json_decode($staffJson, true);
                // staff 
                $staffModel = User::find($staff['staffId']);
                if ($staffModel && $staffModel->email) {
                    Mail::to(PHP_OS=='WINNT'?'mvlasau@gmail.com':$staffModel->email)->queue(new StaffScheduleMail($staffModel, $currentDate, $time));
                }
            }
        }

        $success['schedules'] = $schedules;

        return $this->sendResponse($success, 'Staff Schedule created successfully.');
    }

    /**
     * getStaffSchedule
     */
    public function getStaffSchedule(Request $request){
        $validator = Validator::make($request->all(), [
            'date'   => 'required|string',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }
        
        //Staff Schedule
        $schedule = StaffSchedule::where('date', $request->date)->first();
        
        $success['schedule'] = $schedule;

        return $this->sendResponse($success, 'Staff Schedule');
    }

    /**
     * saveStaffPayroll
     */
    public function saveStaffPayroll(Request $request){
        $validator = Validator::make($request->all(), [
            'staff_id'    => 'required|integer|exists:users,id',
            'frequency'   => 'required|string',
            "withholding" => 'required',
            "payrate"     => 'required|string',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }
        
        //Add Staff Payroll
        $payroll = StaffPayroll::updateOrCreate(
            ['staff_id' => $request->staff_id],
            [
                'frequency'  => $request->frequency,
                'withholding' => $request->withholding,
                'payrate'    => $request->payrate,
                'others'     => $request->others ?? "",
            ]
        );

        $success['staff']  = User::where('id', $request->staff_id)->first();
        $success['payroll'] = $payroll;        

        return $this->sendResponse($success, 'Staff payroll created successfully.');
    }

    /**
     * getStaffPayroll
     */
    public function getStaffPayroll(Request $request){
        $validator = Validator::make($request->all(), [
            'staff_id'   => 'required|integer|exists:users,id',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }
        
        //Staff Payroll
        $payroll = StaffPayroll::where('staff_id', $request->staff_id)->first();
        
        $success['staff']  = User::where('id', $request->staff_id)->first();
        $success['payroll'] = $payroll ?? [];

        return $this->sendResponse($success, 'Staff Payroll');
    }
}
