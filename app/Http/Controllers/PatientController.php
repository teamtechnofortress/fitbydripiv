<?php

namespace App\Http\Controllers;

use App\Models\Intake1;
use App\Models\Intake2;
use App\Models\Intake3;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Inventory;
use App\Models\PatientPlan;
use Illuminate\Http\Request;
use App\Models\BusinessHours;
use App\Models\ChiefComplaint;
use App\Constants\AppConstants;
use Illuminate\Validation\Rule;
use App\Models\PatientEncounter;
use App\Models\PatientProcedure;
use App\Models\PatientAssessment;
use Illuminate\Support\Facades\DB;
use App\Models\PatientPhysicalExam;
use Illuminate\Support\Facades\Mail;
use GrahamCampbell\ResultType\Success;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;
use App\Models\ChiefComplaintNotes;

class PatientController extends BaseController
{
    /**
     * getPatients
     */
    public function getPatients(Request $request){
        $patients = Patient::where('deleted', 0)->get();

        $success['patients'] = $patients;
        return $this->sendResponse($success, 'Successfully get patients');
    }


    /**
     * Get Patient by name
     */
    public function getPatientByName(Request $request){
        $validator = Validator::make($request->all(), [
            'fname'   => 'required|string|min:3',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }
        $searchTerm = '%'.$request->fname.'%';        
        $patients = Patient::where('first_name', 'like', $searchTerm)
                            ->with(['encounter', 'encounterAll', 'complaint', 'assessment', 'physicalExam', 'patientPlan', 'patientProcedure'])->get();

        return $this->sendResponse(true, $patients);
    }

    public function getPatientByPhone(Request $request){
        $validator = Validator::make($request->all(), [
            'fstr'   => 'required|string|min:3',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }
        $searchTerm = '%'.$request->fstr.'%';        
        $patients = Patient::where('phone', 'like', $searchTerm)
                            ->with(['encounter', 'complaint', 'assessment', 'physicalExam', 'patientPlan', 'patientProcedure'])->get();

        return $this->sendResponse(true, $patients);
    }


    /**
     * Update Patient Data
     */
    public function updatePatient(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id'   => 'required|exists:patient,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $patientInfo = [
            "first_name" => $request->first_name,
            // "middle_name" => $request->middle_name,
            "last_name" => $request->last_name,
            "email" => $request->email,
            "birthday" => $request->birthday,
            // "cell" => $request->cell,
            "phone" => $request->phone,
            // "home" => $request->home,
            // "emergency" => $request->emergency,
            "contact" => $request->contact,
            // "referred" => $request->referred,
            "address" => $request->address,
            "city" => $request->city,
            "state" => $request->state,
            "zip" => $request->zip,
            // "age" => $request->age,
            "gender" => $request->gender,
            // "ethnicity" => $request->ethnicity,
            "current_conditions" => $request->current_conditions,
            "current_allergies" => $request->current_allergies,
            "allergy_reactions" => $request->allergy_reactions,
            "current_medications" => $request->current_medications,
            // "pregnant" => $request->pregnant,
            // "tobacco" => $request->tobacco,
            // "drug_use" => $request->drug_use,
            // "alcohol" => $request->alcohol ?? 'no',
            // "signature" => $request->signature,
        ];

        /*
        * ########### Update the Patient ################
        */
        $patient = Patient::updateOrCreate(['id' => $id], $patientInfo);

        $success['patient'] = $patient;

        return $this->sendResponse($success, "Successfully Updated Patient Info.");
    }

    /**
     * Get Patient & History by name
     */
    public function getPatientAndHistoryByName(Request $request){
        $validator = Validator::make($request->all(), [
            'fname'   => 'required|string|min:3',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }
        $searchTerm = '%'.$request->fname.'%';
        $patients = Patient::with('intake')->with('encounter')->where('first_name', 'like', $searchTerm)->get();

        return $this->sendResponse(true, $patients);
    }

    function replace_placeholders(string $template, array $data, array $opts = []): string {
        $leaveUnmatched = $opts['leave_unmatched'] ?? false; 
        $escapeHtml     = $opts['escape_html'] ?? false;     

        return preg_replace_callback('/\[(.*?)\]/', function ($m) use ($data, $leaveUnmatched, $escapeHtml) {
            $key = $m[1];

            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                if ($escapeHtml) {
                    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                }
                return (string)$val;
            }

            return $leaveUnmatched ? $m[0] : '';
        }, $template);
    }

    /**
     * Get Patient & History by Patient Id
     */
    public function getPatientAndHistoryById(Request $request){
        $validator = Validator::make($request->all(), [
            'id'   => 'required|integer|exists:patient,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }


        //Get the patient chief complaint's Note via encounter
        $patient = Patient::with('intake')->with('encounterAll')->with('complaint')->where('id', $request->id)->first();

        //Get the rewards of this patient.
        $reward = Invoice::where(['deleted' => 0, 'patient_id' => $request->id])->sum('totalPrice');

        $patient['reward'] = $reward ?? 0;
        $patient['reward_level'] = $reward >= 1000 ? 'Gold' : ($reward >= 500 ? 'Silver' : 'Bronze');

        foreach($patient['encounterAll'] as $key => $encounter){

            //Get the notes for S.O.A.P
            $complaint = ChiefComplaint::where([
                'patient_id' => $encounter->patient_id,
                'date'       => date('Y-m-d', strtotime($encounter->created_at)),
            ])->first();

            $exam = PatientPhysicalExam::where([
                'patient_id' => $encounter->patient_id,
                'date'       => date('Y-m-d', strtotime($encounter->created_at)),
            ])->first();
            
            $assessment = PatientAssessment::where([
                'patient_id' => $encounter->patient_id,
                'date'       => date('Y-m-d', strtotime($encounter->created_at)),
            ])->first();
            
            $plan = PatientPlan::where([
                'patient_id' => $encounter->patient_id,
                'date'       => date('Y-m-d', strtotime($encounter->created_at)),
            ])->first();

            $procedure = PatientProcedure::where([
                'patient_id' => $encounter->patient_id,
                'date'       => date('Y-m-d', strtotime($encounter->created_at)),
            ])->first();

            // Get the admin notes for empty encounter's notes
            $chiefComplaintNote = ChiefComplaintNotes::where('treatment_type', $encounter->type)->first();
            
            $_data = [
                'patientName' => $patient->first_name." ".$patient->last_name,
                'age'         => $patient->age,
                'gender'      => $patient->gender,
                'goal'        => $encounter->type,
                'symptoms'    => $patient->complaint[0]->symptoms ?? 'N/A',
            ];

            switch($encounter->type){
                case 'IV Therapy': 
                    $defaultNote = $this->replace_placeholders(AppConstants::IV_CONTENT, $_data, ['leave_unmatched' => false, 'escape_html' => true]); 
                    break;
                case 'Weight Loss':
                    $defaultNote = $this->replace_placeholders(AppConstants::WEIGHTLOSS_CONTENT, $_data, ['leave_unmatched' => false, 'escape_html' => true]); 
                    break;
                case 'Injectables':
                    $defaultNote = $this->replace_placeholders(AppConstants::INJECTABLE_CONTENT, $_data, ['leave_unmatched' => false, 'escape_html' => true]); 
                    break;
                case 'Other':
                    $defaultNote = $this->replace_placeholders(AppConstants::OTHER_CONTENT, $_data, ['leave_unmatched' => false, 'escape_html' => true]); 
                    break;
                default:
                    $defaultNote = $this->replace_placeholders(AppConstants::IV_CONTENT, $_data, ['leave_unmatched' => false, 'escape_html' => true]);

            }



            // Get the notes for S.O.A.P
            $encounter['notes'] = [
                "complaint"  => $complaint?->notes ?? $defaultNote, 
                "exam"       => $exam?->notes ?? $defaultNote, 
                "assessment" => $assessment?->notes ?? $defaultNote, 
                "plan"       => ($plan?->plan ?? $defaultNote).($plan->risk ?? $defaultNote).($plan->administration ?? $defaultNote), 
                "procedure"  => $procedure?->notes ?? $defaultNote,
            ];

            //inventroy data
            $inv = Inventory::find($encounter->inventory_id);
            $encounter['inventory'] = $inv ?? "";

            $patient['encounterAll'][$key] = $encounter;            
        }

        return $this->sendResponse(true, $patient);
    }

    /**
     * Get Patient & Encounter by Patient Id
     */
    public function getPatientAndEncounterById(Request $request){
        $validator = Validator::make($request->all(), [
            'id'   => 'required|integer|exists:patient,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $patient = Patient::with(['encounter', 'invoice'])->where('id', $request->id)->first();
        foreach($patient->encounter as $key => $row){
            $row->inventory = Inventory::where('id', $row->inventory_id)->first();
        }

        return $this->sendResponse(true, $patient);
    }


    /**
     * saveIntake-1
     */
    public function saveIntake1(Request $request){
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => ['required', 'email', 'max:255'],
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        if($request->type == 'new'){
            $isExist = Patient::where('email', $request->email)->exists();
            if($isExist){
                return $this->sendError('Error validation', ["$request->email is already being used!."]);
            }
        }

        $patientInfo = [
            "first_name" => $request->first_name,
            "middle_name" => $request->middle_name,
            "last_name" => $request->last_name,
            "email" => $request->email,
            "birthday" => $request->birthday,
            "cell" => $request->cell,
            "phone" => $request->phone,
            "home" => $request->home,
            "emergency" => $request->emergency,
            "contact" => $request->contact,
            "referred" => $request->referred,
            "address" => $request->address,
            "city" => $request->city,
            "state" => $request->state,
            "zip" => $request->zip,
            "age" => $request->age,
            "gender" => $request->gender,
            "ethnicity" => $request->ethnicity,
            "current_conditions" => $request->current_conditions,
            "current_allergies" => $request->current_allergies,
            "allergy_reactions" => $request->allergy_reactions,
            "current_medications" => $request->current_medications,
            "pregnant" => $request->pregnant,
            "tobacco" => $request->tobacco,
            "drug_use" => $request->drug_use,
            "alcohol" => $request->alcohol ?? 'no',
            "signature" => $request->signature,
        ];

        /*
        * ########### Check if patient is exist on Patient tbl or not. ################
        * ########### If email is same, then assume as same patient  ################
        */
        $patient = Patient::updateOrCreate(
            ['email' => $request->email], $patientInfo
        );

        /*
        * ############ Intake1 ###################
        */
        $data = [
            'patient_id' => $patient->id,
            'goal_iv'    => $request->goal_iv,
            'goal_injection' => $request->goal_injection,
            'goal_other' => $request->goal_other,
            'weight_loss' => $request->weight_loss,
            'hydration' => $request->hydration,
            'energy' => $request->energy,
            'recovery' => $request->recovery,
            'pain' => $request->pain,
            'supplements' => $request->supplements,
            'fatigue' => $request->fatigue,
            'headache' => $request->headache,
            'soreness' => $request->soreness,
            'current_illness' => $request->current_illness,
            'recent_illness' => $request->recent_illness,
            'hangover' => $request->hangover,
            'low_energy' => $request->low_energy,
            'immunity' => $request->immunity,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Check if intake_1 already exists for today
        $today = date('Y-m-d');
        $intake1Id = null;

        $exists = DB::table('intake_1')
            ->where('patient_id', $patient->id)
            ->whereDate('created_at', $today)
            ->first(); 

        if ($exists) {            
            DB::table('intake_1')
                ->where('patient_id', $patient->id)
                ->whereDate('created_at', $today)
                ->update($data);

            $intake1Id = $exists->id;
        } else {            
            $data['patient_id'] = $patient->id;
            $data['created_at'] = now();
            $intake1Id = DB::table('intake_1')->insertGetId($data);
        }

        $patient['intake1_id'] = $intake1Id;

        // If existing patient, intake3 complete
        if($request->type != 'new'){
            
            $exists = DB::table('intake_3')
                ->where(['patient_id' => $patient->id, 'intake1_id' => $intake1Id])
                ->whereDate('created_at', $today)
                ->exists(); 

            if(!$exists){
                $data = [
                    'patient_id' => $patient->id,
                    'intake1_id' => $intake1Id,
                    'agreedTxt' => "Agreed",
                    'created_at' => date('Y-m-d H:i:s')
                ];
    
                DB::table('intake_3')->insert($data);
            }
        }

        //If emailing required
        if($request->emailNotify){
            Mail::send('email.intakeNotification', ['data' => "We will notify risk beefits!"], function($message) use($request){

                $message->to($request->email);

                $message->subject('Email Verification Mail');

            });
        }

        return $this->sendResponse(true, $patient);
    }

    /**
     * saveIntake2
     */
    public function saveIntake2(Request $request){
        $validator = Validator::make($request->all(), [
            'patient_id'   => 'required|integer|exists:patient,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        /*
        * ############ Intake2 ###################
        */
        $data = [
            'patient_id' => $request->patient_id,
            'constitutional' => $request->constitutional,
            'head' => $request->head,
            'eyes' => $request->eyes,
            'nose' => $request->nose,
            'mouth' => $request->mouth,
            'ears' => $request->ears,
            'throat_neck' => $request->throat_neck,
            'respiratory' => $request->respiratory,
            'cardiovascular' => $request->cardiovascular,
            'gastrointestinal' => $request->gastrointestinal,
            'musculoskeletal' => $request->musculoskeletal,
            'skin' => $request->skin,
            'endocrine' => $request->endocrine,
            'urinary' => $request->urinary,
            'male_genitalia' => $request->male_genitalia,
            'neurological' => $request->neurological,
            'intake1_id' => $request->intake1_id,
            'created_at' => date('Y-m-d H:i:s')
        ];

        DB::table('intake_2')->insert($data);

        return $this->sendResponse(true, 'Successfully imported intake-2!');
    }

    /**
     * saveIntake3
     */
    public function saveIntake3(Request $request){
        $validator = Validator::make($request->all(), [
            'patient_id'   => 'required|integer|exists:patient,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        /*
        * ############ Intake3 ###################
        */
        $data = [
            'patient_id' => $request->patient_id,
            'intake1_id' => $request->intake1_id,
            'agreedTxt' => $request->agreedTxt,
            'created_at' => date('Y-m-d H:i:s')
        ];

        DB::table('intake_3')->insert($data);

        return $this->sendResponse(true, 'Successfully imported intake-3!');
    }

    /**
     * saveEncounter
     */
    public function saveEncounter(Request $request){
        $validator = Validator::make($request->all(), [
            'patient_id'   => 'required|integer|exists:patient,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        /*
        * ###############################
        */
        foreach($request->list as $item ){
            $data = [
                'patient_id'    => $request->patient_id,
                'type'          => $request->type,
                'inventory_id'  => $item['inventory_id'],
                'name'          => $item['name'] ?? null,
                'ingredients'   => $item['ingredients'] ?? null,
                'dosage'        => $item['dosage'] ?? null,
                'quantity'      => $item['quantity'] ?? null,
                'is_add_on'     => $item['is_add_on'] ?? null,
                'unit'          => $item['unit'],
                'paid'          => false,
                'created_at'    => date('Y-m-d H:i:s')
            ];

            DB::table('patient_encounter')->insert($data);
        }

        return $this->sendResponse(true, 'Successfully saved encounter!');
    }

    /**
     * getReports
     */
    public function getReports(Request $request){ 

        $validator = Validator::make($request->all(), [
            'keys'   => 'required|string',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }  

        $success = [];
        $sorts = explode(',', $request->keys);
        /*
        * ###############################
        */
        if(in_array('chart', $sorts)){
            $encounters = PatientEncounter::where('deleted', 0)->with('patient')->get();
            $result = [];
            foreach($encounters as $key => $encounter){
                $data['name'] = $encounter->name;
                $data['dosage'] = $encounter->dosage;
                $data['ingredients'] = $encounter->ingredients;
                $data['paid'] = $encounter->paid ? 'Paid' : 'Not Paid';
                $data['paitent_name'] = $encounter->patient['first_name']." ".$encounter->patient['middle_name']." ".$encounter->patient['last_name'];
                $data['patient_email'] = $encounter->patient['email'];
                $data['quantity'] = $encounter->quantity;
                $data['type'] = $encounter->type;
                $data['created_at'] = date('Y-m-d H:i', strtotime($encounter->created_at));
    
                array_push($result, $data);
            }
            $success['chart_history'] = $result;
        }

        if(in_array('customer', $sorts)){
            $patients = Patient::where('deleted', 0)->get();
            $result = [];
            foreach($patients as $key => $patient){                
                $data['paitent_name'] = $patient->first_name." ".$patient->middle_name." ".$patient->last_name;
                $data['patient_email'] = $patient->email;
                $data['created_at'] = date('Y-m-d H:i', strtotime($patient->created_at));
    
                array_push($result, $data);
            }
            $success['patient_metric'] = $result;
        }

        return $this->sendResponse($success, 'Chart History Data.');
    }

    /**
     * deleteEncounter
     */
    public function deleteEncounter(Request $request, $id){
        $validator = Validator::make(['id' => $id], [
            'id'   => 'required|integer|exists:patient_encounter,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        /*
        * ###############################
        */        
        DB::table('patient_encounter')->where('id', $id)->delete();

        return $this->sendResponse(true, 'Successfully removed encounter!');
    }

    /**
    ** saveInvoice
    **/
    public function saveInvoice(Request $request){
        $validator = Validator::make($request->all(), [
            'patient_id'   => 'required|integer|exists:patient,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        // Update encounters as paid
        foreach($request->data as $item ){
            foreach($item as $encounter){
                DB::table('patient_encounter')->where('id', $encounter['id'])->update(['paid' => 1, 'updated_at' => now()]);
            }
        }

        // Get arrival due
        $intake1 = Intake1::where('patient_id', $request->patient_id)->first();
        $arrival_due = $intake1 ? $intake1->created_at->diffInSeconds(now('UTC'), false) : null;

        $invoice = [
            'patient_id' => $request->patient_id,
            'data'       => json_encode($request->data),
            'tip'        => $request->tip,
            'tax'        => $request->tax,
            'totalPrice' => $request->totalPrice,
            'isEmailing' => $request->isEmailing,
            'isSendInstructions' => $request->isSendInstructions,
            'staff_id'   => $request->staff_id,
            'isPaid'     => false,
            'created_at' => now(),
            'arrival_due'=> $arrival_due,
            'payment_type'=> $request->payment_type ?? 'cash',
        ];
        DB::table('invoice')->insert($invoice);

        // If emailing required
        if($request->isEmailing){
            $patient = Patient::find($request->patient_id);
            $data['email'] = PHP_OS == 'WINNT' ?  "mvlasau@gmail.com" : $patient->email;
            $data['tip'] = $request->tip;
            $data['tax'] = $request->tax;
            $data['totalPrice'] = $request->totalPrice;
            $data['content'] = $request->data;
            $data['invoice_intro_text'] = BusinessHours::first()->invoice_intro_text ?? 'I would like to request a email with invoice details.';

            // Fetch the patient's procedure for the given date to retrieve risk notes
            $procedure = PatientProcedure::where([
                'patient_id' => $request->patient_id, 
                'date'       => date('Y-m-d'), 
                'deleted'    => 0
            ])->first();
            $data['risk_note'] = $procedure->risk ?? '';

            Mail::send('email.invoiceNotification', ['data' => $data], function($message) use($data, $request){
                $message->to($data['email']);
                $message->subject('Invoice Email');

                // isSendInstructions
                if($request->isSendInstructions){
                    $filePath = public_path("uploads/instructions/patient_instructions.pdf");                    
                    if(file_exists($filePath)){
                        $message->attach($filePath, ['as' => 'Instructions.pdf', 'mime' => 'application/pdf']);
                    }
                }
            });
        }

        // Remove intake tables
        Intake1::where('patient_id', $request->patient_id)->delete();
        Intake2::where('patient_id', $request->patient_id)->delete();
        Intake3::where('patient_id', $request->patient_id)->delete();

        return $this->sendResponse(true, 'Successfully saved invoice!');
    }


    /**
     * sendInvoiceAgain
     */
    public function sendInvoiceAgain(Request $request){
        $validator = Validator::make($request->all(), [
            'invoice_id'   => 'required|integer|exists:invoice,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $invoice = Invoice::find($request->invoice_id);

        //Send invoice email
        $patient = Patient::find($invoice->patient_id);
        $data['email'] = PHP_OS == 'WINNT' ?  "mvlasau@gmail.com" : $patient->email;
        $data['tip'] = $invoice->tip;
        $data['tax'] = $invoice->tax;
        $data['totalPrice'] = $invoice->totalPrice;
        $data['content'] = json_decode($invoice->data, true);
        $data['invoice_intro_text'] = BusinessHours::first()->invoice_intro_text ?? 'I would like to request a email with invoice details.';

        Mail::send('email.invoiceNotification', ['data' => $data], function($message) use($data){

            $message->to($data['email']);

            $message->subject('Invoice Email');

        });

        return $this->sendResponse(true, 'Successfully Sent invoice!');
    }


    /**
    * GetPatientQue
    */
    public function GetPatientQue(Request $request){

        //Get the today intake3
        $intakes = Intake3::orderBy('id', 'DESC')->where('created_at', '>=', date('Y-m-d 00:00:00'))->with('patient')->get();

        foreach($intakes as $intake){//today visit
            $intake->patient->complaint = ChiefComplaint::where('patient_id', $intake->patient->id)
                                                        ->where('created_at', '>=', date('Y-m-d 00:00:00'))->orderBy('id', 'DESC')->first();

            //if no complaint found, then create new Chief Complaint (today visit)
            if(!$intake->patient->complaint){
                $intake->patient->complaint = new ChiefComplaint();

                //get the treatment_type from intake_1
                $intake1 = Intake1::where('id', $intake->intake1_id)->first();

                if($intake1){
                    $intake->patient->complaint->treatment_type = 
                        $intake1->goal_iv ? "IV Therapy" : 
                        ($intake1->goal_injection ? "Injectables" : ($intake1->goal_other ? "Other" : "Weight Loss"));
                }else {
                    $intake->patient->complaint->treatment_type = "Other";
                }
                
                $intake->patient->complaint->patient_id = $intake->patient->id;
                $intake->patient->complaint->staff_id = Auth()->user()->id;
                $intake->patient->complaint->notes = null;
                $intake->patient->complaint->date = date('Y-m-d');
                $intake->patient->complaint->save();
            }

            /*############################## 
                physical_exam getting
            ################################
            */
            $intake->patient->physicalExam = PatientPhysicalExam::where('patient_id', $intake->patient->id)
                                                                ->where('created_at', '>=', date('Y-m-d 00:00:00'))->orderBy('id', 'DESC')->first();

            //if no physicalExam found, then create new physicalExam (today visit)
            if(!$intake->patient->physicalExam){
                $intake->patient->physicalExam = new PatientPhysicalExam();
                $intake->patient->physicalExam->patient_id = $intake->patient->id;
                $intake->patient->physicalExam->staff_id = Auth()->user()->id;
                $intake->patient->physicalExam->notes = null;
                $intake->patient->physicalExam->BP = null;
                $intake->patient->physicalExam->HR = null;
                $intake->patient->physicalExam->Temp = null;
                $intake->patient->physicalExam->WT = null;
                $intake->patient->physicalExam->date = date('Y-m-d');
                $intake->patient->physicalExam->save();
            }
        }

        $success['que'] = $intakes;
        return $this->sendResponse($success, "Patients Que");
    }

    /**
    * Remove Patient
    */
    public function removePatient(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id'   => 'required|exists:patient,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        /*
        * ########### Patient related data remove ################
        */
        Intake1::where('patient_id', $id)->delete();
        Intake2::where('patient_id', $id)->delete();
        Intake3::where('patient_id', $id)->delete();
        Invoice::where('patient_id', $id)->delete();
        PatientEncounter::where('patient_id', $id)->delete();

        Patient::where(['id' => $id])->delete();

        return $this->sendResponse(true, "Successfully Deleted Patient.");
    }

}
