<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Intake1;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\PatientMetricsReport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailPatientMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $patientMetricsReport;

    /**
     * Create a new job instance.
     */
    public function __construct(PatientMetricsReport $patientMetricsReport)
    {
        $this->patientMetricsReport = $patientMetricsReport;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $range_sdate = $this->patientMetricsReport->range_sdate;
        $range_edate = Carbon::parse($this->patientMetricsReport->range_edate)->addDay();

        /*
        ###############################################################
         * Get the Reward Sales Volume if it is marked as Yes
        ###############################################################
        */
        //Get the patient Count 
        if($this->patientMetricsReport->type == 'new'){
            $newPatients = Patient::where('deleted', 0)
                ->where('created_at', '>=', $range_sdate)
                ->where('created_at', '<=', $range_edate)
                ->with('encounterAll')
                ->get();
            $data['patient_count'] = $newPatients->count();
            $data['patients'] = $newPatients;

        }else if($this->patientMetricsReport->type == 'returning'){

            $returningPatients = Patient::where('deleted', 0)
                ->where('updated_at', '>=', $range_sdate)
                ->where('updated_at', '<=', $range_edate)
                ->where('created_at', '<', 'updated_at')
                ->with('encounterAll')
                ->get();
            $data['patient_count'] = $returningPatients->count();
            $data['patients'] = $returningPatients;

        }else{
            
            $rewardPatients = Controller::getPatientLevel($range_sdate, $range_edate);
            $data['patient_count'] = $rewardPatients->count();
            $data['patients'] = $rewardPatients;
        }

        $patientIds = $data['patients']->pluck('id')->toArray();

        /*
        ###############################################################
         * Get the Total Reward 
        ###############################################################
        */
        if($this->patientMetricsReport->reward_usage){
            $data['rewards'] = Invoice::where(['invoice.deleted' => 0])
                ->join('patient', 'patient.id', '=', 'invoice.patient_id')
                ->select('invoice.*', 'patient.first_name', 'patient.middle_name', 'patient.last_name')
                ->whereBetween('patient.created_at', [$range_sdate, $range_edate])                
                ->whereIn('invoice.patient_id', $patientIds)                
                ->get();            
        }

        /*
        ###############################################################
         * Get the Add On purchase if it is marked as Yes
        ###############################################################
        */
        if($this->patientMetricsReport->add_on_purchase){
            $addOnList = [];
            $addOnPurchase = Invoice::where(['deleted' => 0])                
                ->whereBetween('created_at', [$range_sdate, $range_edate]) 
                ->whereIn('patient_id', $patientIds)  
                ->with('patient')      
                ->get();

            foreach ($addOnPurchase as $invoice) {
                $encounters = json_decode($invoice->data, true);
                foreach ($encounters as $rows) {
                    foreach ($rows as $encounter) {
                        if (!isset($encounter['id'])) continue;

                        // Get the Add on
                        if ($encounter['is_add_on'] == 1) {
                            $addOn = [];                            
                            $ptName = $invoice->patient->first_name." ".$invoice->patient->last_name;

                            $addOn['patient_name'] = $ptName;
                            $addOn['enc_name']     = $encounter['name'];
                            $addOn['enc_type']     = $encounter['type'];
                            $addOn['enc_dosage']   = $encounter['dosage'];
                            $addOn['enc_ingredients'] = $encounter['ingredients'];
                            $addOn['quantity']     = $encounter['quantity'];
                            $addOn['inv_name']     = $encounter['inventory']['name'];
                            $addOn['inv_price']    = $encounter['inventory']['price'];
                            $addOn['sub_price']    = ($encounter['quantity'] * $encounter['inventory']['price']); 
                            $addOn['created_at']   = $encounter['created_at'];

                            $addOnList[] = $addOn; 
                        }
                    }
                }        
            }

            $data['add_on_purchase'] = $addOnList;
        }

        /*
        ###############################################################
         * Utilized Discount
        ###############################################################
        */
        if($this->patientMetricsReport->utilized_discount){
            $utilizedDiscounts = Invoice::where(['deleted' => 0])
                ->where('tip', '>', 0)
                ->whereBetween('created_at', [$range_sdate, $range_edate]) 
                ->whereIn('patient_id', $patientIds)  
                ->with('patient')      
                ->get();

            $data['utilized_discounts'] = $utilizedDiscounts;
        }

        $data['range_due']  = $this->patientMetricsReport->range_sdate." ~ ".$this->patientMetricsReport->range_edate;        

        $data['patient_report_type'] = $this->patientMetricsReport->type;

        $receiverEmail = $this->patientMetricsReport->email;

        $this->doSendEmail($data, $receiverEmail);        
    }

    public function doSendEmail($data, $receiverEmail){
        Mail::send('email.patientMectricsNotify', ['data' => $data], function($message) use($receiverEmail){

            $message->to($receiverEmail);

            $message->subject('Patient Metrics Report Notification');

        });
        
        //upate with reported
        $this->patientMetricsReport->update([
            'reported_date' => now(),
        ]); 
    }    
}
