<?php

namespace App\Jobs;

use App\Models\PatientEncounter;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailMedrxReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $medrxReport;

    /**
     * Create a new job instance.
     */
    public function __construct($medrxReport)
    {
        $this->medrxReport = $medrxReport;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $range_sdate = $this->medrxReport->range_sdate;
        $range_edate = Carbon::parse($this->medrxReport->range_edate)->addDay();
        
        /*
        ###############################################################
         * IV Solutions        
        ###############################################################
        */
        if($this->medrxReport->iv_solutions){
            $data['is_iv_solutions'] = true;
            $data['iv_solutions'] = PatientEncounter::whereBetween('created_at', [$range_sdate, $range_edate])
                ->where('type', 'IV Therapy')
                ->with('patient', 'inventory')
                ->orderBy('created_at', 'DESC')
                ->get();                 
        }

        /*
        ###############################################################
         * Injectables        
        ###############################################################
        */
        if($this->medrxReport->injectable){
            $data['is_injectable'] = true;
            $data['injectable'] = PatientEncounter::whereBetween('created_at', [$range_sdate, $range_edate])
                ->where('type', 'Injectables')
                ->with('patient', 'inventory')
                ->orderBy('created_at', 'DESC')
                ->get();                
        }

        /*
        ###############################################################
         * Peptides        
        ###############################################################
        */
        if($this->medrxReport->peptides){
            $data['is_peptides'] = true;
            $data['peptides'] = PatientEncounter::whereBetween('created_at', [$range_sdate, $range_edate])
                ->where('type', 'Weight Loss')
                ->with('patient', 'inventory')
                ->orderBy('created_at', 'DESC')
                ->get();                
        }

        /*
        ###############################################################
         * Consumables        
        ###############################################################
        */
        if($this->medrxReport->consumables){
            $data['is_consumables'] = true;
            $data['consumables'] = PatientEncounter::whereBetween('created_at', [$range_sdate, $range_edate])
                ->where('type', 'Other')
                ->with('patient', 'inventory')
                ->orderBy('created_at', 'DESC')
                ->get();                
        }

        /*
        ###############################################################
         * Onhand        
        ###############################################################
        */
        if($this->medrxReport->on_hand){
            $data['is_on_hand'] = true;            
            $data['on_hand'] = PatientEncounter::whereBetween('created_at', [$range_sdate, $range_edate])
                ->where('type', 'OnHand')
                ->with('patient', 'inventory')
                ->orderBy('created_at', 'DESC')
                ->get();                
        }

        /*
        ###############################################################
         * SoldInvoice        
        ###############################################################
        */
        if($this->medrxReport->sold_invoiced){
            $data['is_sold_invoice'] = true;
            $data['sold_invoice'] = PatientEncounter::whereBetween('created_at', [$range_sdate, $range_edate])
                ->where('paid', 1)
                ->with('patient', 'inventory')
                ->orderBy('created_at', 'DESC')
                ->get();                
        }
            
        

        $data['range_due']  = $this->medrxReport->range_sdate." ~ ".$this->medrxReport->range_edate;

        $receiverEmail = $this->medrxReport->email;

        $this->doSendEmail($data, $receiverEmail);        
    }

    public function doSendEmail($data, $receiverEmail){
        Mail::send('email.medrxReportNotify', ['data' => $data], function($message) use($receiverEmail){

            $message->to($receiverEmail);

            $message->subject('MedRx Report Notification');

        });

        //update with reported
        $this->medrxReport->update([
            'reported_date' => now(),
        ]); 
    }
}
