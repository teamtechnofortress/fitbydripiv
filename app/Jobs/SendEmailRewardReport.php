<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\RewardReport;
use Illuminate\Bus\Queueable;
use App\Models\PatientEncounter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailRewardReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rewardReport;

    /**
     * Create a new job instance.
     */
    public function __construct(RewardReport $rewardReport)
    {
        $this->rewardReport = $rewardReport;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $range_sdate = $this->rewardReport->range_sdate;
        $range_edate = Carbon::parse($this->rewardReport->range_edate)->addDay();

        /*
        ###############################################################
         * Get the RewardReport List
        ###############################################################
        */
        //Total Reward Program        
        $rewardPatients = Controller::getPatientLevel($range_sdate, $range_edate);
        if($this->rewardReport->totalRewardPurchases){
            $data['totalRewardPurchases'] = $rewardPatients;        
            $data['totalRewardPurchasesCount'] = count($rewardPatients);        
        }      
        
        //Reward Gold
        if($this->rewardReport->rewardGold){
            $data['rewardGold'] = $rewardPatients->filter(function($patient){
                return $patient->totalPrice >= 5000;
            })->values();
        }

        //Reward Silver
        if($this->rewardReport->rewardSilver){
            $data['rewardSilver'] = $rewardPatients->filter(function($patient){
                return ($patient->totalPrice < 5000) && ($patient->totalPrice >= 2000);
            })->values();
        }

        //Reward Bronze
        if($this->rewardReport->rewardBronze){
            $data['rewardBronze'] =$rewardPatients->filter(function($patient){
                return ($patient->totalPrice < 2000) && ($patient->totalPrice >= 500);
            })->values();
        }

        
        //Reward Discount
        $data['rewardDiscount'] = Invoice::where('deleted', 0)
            ->whereBetween('created_at', [$range_sdate, $range_edate])
            ->where('tip', '>', 0)
            ->selectRaw('patient_id, SUM(tip) as totalTip')
            ->with('patient')
            ->groupBy('patient_id')
            ->orderByDesc('totalPrice')
            ->get();

        $data['range_due']  = $this->rewardReport->range_sdate." ~ ".$this->rewardReport->range_edate;

        $this->doSendEmail($data, $this->rewardReport->email);        
    }

    public function doSendEmail($data, $receiverEmail){
        Mail::send('email.rewardReportNotify', ['data' => $data], function($message) use($receiverEmail){

            $message->to($receiverEmail);

            $message->subject('Reward Report Notification');

        });
        
        //upate with reported
        $this->rewardReport->update([
            'reported_date' => now(),
        ]); 
    }
}
