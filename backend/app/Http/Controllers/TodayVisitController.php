<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use Illuminate\Http\Request;
use App\Models\ChiefComplaint;
use App\Models\ChiefComplaintNotes;
use App\Models\Intake1;
use App\Models\Patient;
use App\Models\PatientAssessment;
use App\Models\PatientPhysicalExam;
use App\Models\PatientPlan;
use App\Models\PatientProcedure;
use App\Models\ProcedurePlanNotes;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TodayVisitController extends BaseController
{
    /**
    * addAdminSubjectNotes
    */
    public function addAdminSubjectNotes(Request $request){
        $validator = Validator::make($request->all(), [
            'treatment_type' => 'required|string|min:2',
            'notes'          => 'required|string|min:2',
            'allergies'      => 'required|string|min:2',
            'medication'     => 'required|string|min:2',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        if(Auth::user()->role != 'admin'){
            return $this->sendError('Error validation', ['You do not have permission to use this API.']);
        }

        //Add or Update ChiefComplaintNotes
        $notes = ChiefComplaintNotes::updateOrCreate(
            ['treatment_type' => $request->treatment_type],
            [
                ...$request->all(),
                'deleted' => 0,
                'created_at' => now(),
            ]
        );

        $success['notes'] = $notes;
        return $this->sendResponse($success, 'Chief Complaint Note saved successfully.');
    }

    /**
    * addAdminObjectNotes
    */
    public function addAdminObjectNotes(Request $request){
        $validator = Validator::make($request->all(), [
            'treatment_type' => 'required|string|min:2',
            'physical_exam'  => 'required|string|min:2',
            'evaluation'     => 'required|string|min:2',
            'vital_sign'     => 'required|string|min:2',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        if(Auth::user()->role != 'admin'){
            return $this->sendError('Error validation', ['You do not have permission to use this API.']);
        }

        //Add or Update ChiefComplaintNotes
        $notes = ChiefComplaintNotes::updateOrCreate(
            ['treatment_type' => $request->treatment_type],
            [
                ...$request->all(),
                'deleted' => 0,
                'created_at' => now(),
            ]
        );

        $success['notes'] = $notes;
        return $this->sendResponse($success, 'Object Note saved successfully.');
    }

    /**
    * addAdminAssessmentNotes
    */
    public function addAdminAssessmentNotes(Request $request){
        $validator = Validator::make($request->all(), [
            'treatment_type' => 'required|string|min:2',
            'assessment'     => 'required|string|min:2',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        if(Auth::user()->role != 'admin'){
            return $this->sendError('Error validation', ['You do not have permission to use this API.']);
        }

        //Add or Update ChiefComplaintNotes
        $notes = ChiefComplaintNotes::updateOrCreate(
            ['treatment_type' => $request->treatment_type],
            [
                ...$request->all(),
                'deleted' => 0,
                'created_at' => now(),
            ]
        );

        $success['notes'] = $notes;
        return $this->sendResponse($success, 'Assessment Note saved successfully.');
    }


    /**
    * addAdminPlanNotes
    */
    public function addAdminPlanNotes(Request $request){

        $validator = Validator::make($request->all(), [
            'treatment_type'    => 'required|string|min:2',
            'plan_order'        => 'required|string|min:2',
            'plan_care'         => 'required|string|min:2',
            'plan_instruction'  => 'required|string|min:2',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        if(Auth::user()->role != 'admin'){
            return $this->sendError('Error validation', ['You do not have permission to use this API.']);
        }

        //Add or Update ChiefComplaintNotes
        $notes = ChiefComplaintNotes::updateOrCreate(
            ['treatment_type' => $request->treatment_type],
            [
                ...$request->all(),
                'deleted' => 0,
                'created_at' => now(),
            ]
        );

        $success['notes'] = $notes;
        return $this->sendResponse($success, 'Admin Plan Note saved successfully.');
    }

    /**
    * addAdminRiskBenefitReward
    */
    public function addAdminRiskBenefitReward(Request $request){

        $validator = Validator::make($request->all(), [
            'treatment_type' => 'required|string|min:2',
            'risk'           => 'required|string|min:2',
            'benefit'        => 'required|string|min:2',
            'reward'         => 'required|string|min:2',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        if(Auth::user()->role != 'admin'){
            return $this->sendError('Error validation', ['You do not have permission to use this API.']);
        }

        //Add or Update ChiefComplaintNotes
        $notes = ChiefComplaintNotes::updateOrCreate(
            ['treatment_type' => $request->treatment_type],
            [
                ...$request->all(),
                'deleted' => 0,
                'created_at' => now(),
            ]
        );

        $success['notes'] = $notes;
        return $this->sendResponse($success, 'Admin Risk, Benefit, Reward saved successfully.');
    }

    /**
    * getAdminNotes
    */
    public function getAdminNotes(){
        $notes = ChiefComplaintNotes::where('deleted', 0)->get();
        $success['notes'] = $notes;
        return $this->sendResponse($success, 'Chief Complaint Note.');
    }

    /**
    * addProcedurePlanNotes
    */
    public function addProcedurePlanNotes(Request $request){
        $validator = Validator::make($request->all(), [
            'treatment_type' => 'required|string|min:2',
            'notes' => 'required|string|min:2',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        if(Auth::user()->role != 'admin'){
            return $this->sendError('Error validation', ['You do not have permission to use this API.']);
        }

        //Add or Update ProcedurePlanNotes
        $notes = ProcedurePlanNotes::updateOrCreate(
            ['treatment_type' => $request->treatment_type],
            [
                ...$request->all(),
                'deleted' => 0,
                'created_at' => now(),
            ]
        );

        $success['notes'] = $notes;
        return $this->sendResponse($success, 'Procedure Plan Note saved successfully.');
    }

    /**
    * getProcedurePlanNotes
    */
    public function getProcedurePlanNotes(){
        $notes = ProcedurePlanNotes::where('deleted', 0)->get();
        $success['notes'] = $notes;
        return $this->sendResponse($success, 'Procedure Plan Note.');
    }

    //###################################
    //###################################
    /**
    * addPhysicalExam
    */
    public function addPhysicalExam(Request $request){
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patient,id',
            'date'       => 'required|date',
            // 'notes'      => 'required|string|min:2',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        //Add or Update 
        $exams = PatientPhysicalExam::updateOrCreate(
            ['patient_id' => $request->patient_id, 'date' => $request->date],
            [
                ...$request->all(),
                'staff_id'  => Auth::user()->id,
                'deleted'   => 0,
                'created_at'=> now(),
            ]
        );

        $success['exams'] = $exams;
        return $this->sendResponse($success, 'Patient Physical Exam saved successfully.');
    }

    /**
    * getPhysicalExamByDate
    */
    public function getPhysicalExamByDate(Request $request){
        $validator = Validator::make($request->all(), [
            'pid' => 'required|exists:patient,id',
            'date'       => 'required|date',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $exam = PatientPhysicalExam::where('deleted', 0)
            ->where('patient_id', $request->pid)
            ->where('date', $request->date)
            ->with(['patient', 'staff'])
            ->first();

        if(!$exam){
            $exam['patient'] = Patient::find($request->pid);
        }
        
        $exam['chiefComplaint'] = ChiefComplaint::where(['patient_id' => $request->pid, 'date' => $request->date])->first();

        $success['exam'] = $exam;
        return $this->sendResponse($success, 'Patient Physical Exam.');
    }

    /**
    * addPatientPlan
    */
    public function addPatientPlan(Request $request){
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patient,id',
            'date'       => 'required|date',
            'plan'      => 'required|string|min:2',            
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        //Add or Update 
        $plan = PatientPlan::updateOrCreate(
            ['patient_id' => $request->patient_id, 'date' => $request->date],
            [
                ...$request->all(),
                'staff_id'  => Auth::user()->id,
                'deleted'   => 0,
                'created_at'=> now(),
            ]
        );

        $success['plan'] = $plan;
        return $this->sendResponse($success, 'Patient Plan saved successfully.');
    }

    /**
    * getPatientPlan
    */
    public function getPatientPlan(Request $request){

        $validator = Validator::make($request->all(), [
            'pid'   => 'required|exists:patient,id',
            'date'  => 'required|date',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $plan = PatientPlan::where('deleted', 0)
            ->where('patient_id', $request->pid)
            ->where('date', $request->date)
            ->with(['patient', 'staff'])
            ->first();

        if(!$plan){
            $plan['patient'] = Patient::find($request->pid);

            //Get the risk, benefits, alternatives from Admins notes
            $note = ChiefComplaintNotes::where('deleted', 0)->first();
            $plan['risk'] = $note->risk ?? '';
            $plan['plan'] = $note->notes ?? '';
            $plan['administration'] = $note->reward ?? '';
        }

        $plan['chiefComplaint'] = ChiefComplaint::where(['patient_id'  => $request->pid, 'date' => $request->date])->first();
        $success['plan'] = $plan;

        //Get the intake1 for date
        $intake1 = Intake1::where(['patient_id' => $request->pid])->where('created_at', '>=', $request->date)->first();
        $success['intake1'] = $intake1;
        return $this->sendResponse($success, 'Patient Plan.');       
    }

    /**
    * addPatientProcedure
    */
    public function addPatientProcedure(Request $request){
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patient,id',
            'date'       => 'required|date',
            'risk'      => 'required|string|min:2',
            'benefits'      => 'required|string|min:2',
            'alternatives'  => 'required|string|min:2',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        //Add or Update 
        $plan = PatientProcedure::updateOrCreate(
            ['patient_id' => $request->patient_id, 'date' => $request->date],
            [
                ...$request->all(),
                'staff_id'  => Auth::user()->id,
                'deleted'   => 0,
                'created_at'=> now(),
            ]
        );

        $success['plan'] = $plan;
        return $this->sendResponse($success, 'Patient Procedure saved successfully.');
    }

    /**
    * getPatientProcedure
    */
    public function getPatientProcedure(Request $request){
        $validator = Validator::make($request->all(), [
            'pid'   => 'required|exists:patient,id',
            'date'  => 'required|date',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $procedure = PatientProcedure::where('deleted', 0)
            ->where('patient_id', $request->pid)
            ->where('date', $request->date)
            ->with(['patient', 'staff'])
            ->first();

        if(!$procedure){
            $procedure['patient'] = Patient::find($request->pid);
            
            //Get the risk, benefits, alternatives from Admins notes
            $note = ChiefComplaintNotes::where('deleted', 0)->first();
            $procedure['risk'] = $note->risk ?? '';
            $procedure['benefits'] = $note->benefit ?? '';
            $procedure['alternatives'] = $note->reward ?? '';            
            $procedure['notes'] = $note->notes ?? '';            
        }

        //Get the patient physical exam
        $patientPhysicalExam = PatientPhysicalExam::where(['patient_id' => $request->pid, 'date' => $request->date])->first();
        $procedure['patient']['physicalExam'] = $patientPhysicalExam;

        $procedure['chiefComplaint'] = ChiefComplaint::where(['patient_id'  => $request->pid, 'date' => $request->date])->with('staff')->first();
        $success['procedure'] = $procedure;
        
        //Get the intake1 for date
        $intake1 = Intake1::where(['patient_id' => $request->pid])->where('created_at', '>=', $request->date)->first();
        $success['intake1'] = $intake1;
        return $this->sendResponse($success, 'Patient Procedure.');
    }

    /**
    * addChiefComplaint
    */
    public function addChiefComplaint(Request $request){
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patient,id',            
            'date' => 'required|date',
            'treatment_type' => 'required|string|min:2',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        //Add or Update Chief Complaint
        $ChiefComplaint = ChiefComplaint::updateOrCreate(
            ['patient_id' => $request->patient_id, 'date' => $request->date],
            [
                ...$request->all(),
                'staff_id' => Auth::user()->id, // Assuming the staff_id is the authenticated user
                'deleted' => 0,
                'created_at' => now(),
            ]
        );

        $success['complaint'] = $ChiefComplaint;
        return $this->sendResponse($success, 'Chief Complaint created successfully.');
    }

    /**
    * updatePatientPhysicalExamp
    */
    public function updatePatientPhysicalExamp(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id'             => 'required|integer|exists:patient_physical_exam,id',
            'complaint_id'   => 'required|integer|exists:chief_complaint,id',
            'patient_id'     => 'required|exists:patient,id',
            'treatment_type' => 'required|string|min:2',
            'BP'    => 'required',
            'HR'    => 'required',
            'Temp'  => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        //Update PhysicalExam
        $physicalExam = PatientPhysicalExam::updateOrCreate(['id' => $id], [            
            ...$request->all(),
            'updated_at' => now(),
        ]);        
        $success['physicalExam'] = $physicalExam;

        //Update ChiefComplaint
        $ChiefComplaint = ChiefComplaint::updateOrCreate(['id' => $request->complaint_id], [            
            'treatment_type' => $request->treatment_type,
            'updated_at' => now(),
        ]);        
        $success['complaint'] = $ChiefComplaint;
        return $this->sendResponse($success, 'Chief Complaint updated successfully.');        
    }

    /**
    * deleteChiefComplaint
    */
    public function deleteChiefComplaint(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id' => 'required|integer|exists:chief_complaint,id',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        //Delete ChiefComplaint
        ChiefComplaint::where(['id' => $id])->delete();
        return $this->sendResponse(true, 'Chief Complaint deleted successfully.');        
    }

    /**
    * getChiefComplaintByDate
    */
    public function getChiefComplaintByDate(Request $request){
        $validator = Validator::make($request->all(), [
            'pid'  => 'required|integer|exists:patient,id',
            'date' => 'required|date',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        //Get Complaint
        $ChiefComplaint = ChiefComplaint::with(['patient', 'staff'])
            ->where(['patient_id' => $request->pid, 'date' => $request->date])
            ->first();

        if(!$ChiefComplaint){
            $ChiefComplaint['patient'] = Patient::find($request->pid);
        }

        //Get the intake1 for date
        $intake1 = Intake1::where(['patient_id' => $request->pid])->where('created_at', '>=', $request->date)->first();
        $success['intake1'] = $intake1;

        //Get the patient_physical_exam
        $patientPhysicalExam = PatientPhysicalExam::where(['patient_id' => $request->pid, 'date' => $request->date])->with('staff')->first();
        if(empty($patientPhysicalExam)){
            $patientPhysicalExam = PatientPhysicalExam::where(['patient_id' => $request->pid])->orderBy('created_at', 'desc')->with('staff')->first();
        }
        $success['physicalExam'] = $patientPhysicalExam;        

        $success['complaint'] = $ChiefComplaint;    
        return $this->sendResponse($success, 'Chief Complaint Data.');        
    }

    /**
    * addAssessment
    */
    public function addAssessment(Request $request){
        $validator = Validator::make($request->all(), [
            'patient_id'       => 'required|exists:patient,id',            
            'diag_description' => 'required|string|min:2',
            'diag_problem'     => 'required|string|min:2',
            'diag_comment'     => 'required|string|min:2',            
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        //Add or Update Assessment
        $patientAssessment = PatientAssessment::updateOrCreate(
            ['patient_id' => $request->patient_id, 'date' => $request->date],
            [
                ...$request->all(),
                'staff_id' => Auth::user()->id, // Assuming the staff_id is the authenticated user
                'deleted' => 0,
                'created_at' => now(),
            ]
        );

        $success['assessment'] = $patientAssessment;
        return $this->sendResponse($success, 'Daily Assessment created successfully.');
    }

    /**
    * getAssessmentByDate
    */
    public function getAssessmentByDate(Request $request){
        $validator = Validator::make($request->all(), [
            'pid'  => 'required|integer|exists:patient,id',
            'date' => 'required|date',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        //Get Assessment
        $patientAssessment = PatientAssessment::with(['patient'])
            ->where(['patient_id' => $request->pid, 'date' => $request->date])
            ->first();

        //
        $complaint = ChiefComplaint::where(['patient_id'  => $request->pid, 'date' => $request->date])->first();
        $chNote   = ChiefComplaintNotes::where(['deleted' => 0, "treatment_type" => $complaint->treatment_type])->first();

        if(!$patientAssessment){
            $patientAssessment['patient'] = Patient::find($request->pid);
            $patientAssessment['notes'] = $chNote->notes;
        }

        $patientAssessment['chiefComplaint'] = $complaint;
        $success['assessment'] = $patientAssessment;

        //Get the intake1 for date
        $intake1 = Intake1::where(['patient_id' => $request->pid])->where('created_at', '>=', $request->date)->first();
        $success['intake1'] = $intake1;
        return $this->sendResponse($success, 'Patient Assessment Data.');        
    }

}
