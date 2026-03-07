<?php

namespace App\Jobs;

use App\Models\Patient;
use App\Models\SpecialPromo;
use App\Models\TextCampaign;
use App\Models\EmailCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\EmailTextRewardReport;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailTextRewardReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $emailTextRewardReport;
    /**
     * Create a new job instance.
     */
    public function __construct(EmailTextRewardReport $emailTextRewardReport)
    {
        $this->emailTextRewardReport = $emailTextRewardReport;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $range_sdate = $this->emailTextRewardReport->range_sdate;
        $range_edate = Carbon::parse($this->emailTextRewardReport->range_edate)->addDay();
        $data['emailTextRewardReport'] = $this->emailTextRewardReport;
        /*
        ###############################################################
         * Get the Email Campaign List
        ###############################################################
        */
        if($this->emailTextRewardReport->email_sent){

            $emailCampaignList = EmailCampaign::where(['deleted' => 0,])
                ->where('created_at', '>=', $range_sdate)
                ->where('created_at', '<=', $range_edate)        
                ->orderBy('created_at', 'desc')            
                ->get();
    
            $data['emailCampaignList'] = $emailCampaignList;
    
            $data['emailMarketingSent'] = [];
            foreach($emailCampaignList as $key => $emailCampain){
                //email marketing sent
                $patients = Patient::where('id', '>=', $emailCampain->patient_start)
                    ->where('id', '<=', $emailCampain->patient_end)
                    ->select('email', 'first_name', 'last_name', 'middle_name')
                    ->addSelect(DB::raw("'".date('Y-m-d H:i:s', strtotime($emailCampain->created_at))."' as send_date"))
                    ->addSelect(DB::raw("'".$emailCampain->content."' as content"))
                    ->get();
                $patientArray = $patients->toArray();
    
                $data['emailMarketingSent'] = [...$patientArray, ...$data['emailMarketingSent']];            
            }
        }

        /*
        ###############################################################
         * Get the Text Campaign List
        ###############################################################
        */
        if($this->emailTextRewardReport->text_sent){
            
            $textCampaignList = TextCampaign::where(['deleted' => 0,])
                ->where('created_at', '>=', $range_sdate)
                ->where('created_at', '<=', $range_edate)        
                ->orderBy('created_at', 'desc')            
                ->get();

            $data['textCampaignList'] = $textCampaignList;

            $data['textMarketingSent'] = [];
            foreach($textCampaignList as $key => $textCampaign){
                //text marketing sent
                $patients = Patient::where('id', '>=', $textCampaign->patient_start)
                    ->where('id', '<=', $textCampaign->patient_end)
                    ->select('email', 'first_name', 'last_name', 'middle_name')
                    ->addSelect(DB::raw("'".date('Y-m-d H:i:s', strtotime($textCampaign->created_at))."' as send_date"))
                    ->addSelect(DB::raw("'".$textCampaign->message."' as message"))
                    ->get();
                $patientArray = $patients->toArray();

                $data['textMarketingSent'] = [...$patientArray, ...$data['textMarketingSent']];            
            }
        }


        /*
        ###############################################################
         * Get the Reward marketing List
        ###############################################################
        */
        if($this->emailTextRewardReport->reward_sent){
            
            $rewardCampaignList = SpecialPromo::where(['deleted' => 0])
                ->where('created_at', '>=', $range_sdate)
                ->where('created_at', '<=', $range_edate)
                ->orderBy('created_at', 'desc')
                ->get();

            $data['rewardCampaignList'] = $rewardCampaignList;

            $data['rewardMarketingSent'] = [];
            foreach($rewardCampaignList as $key => $rewardCampaign){
                $content = "Special Promo/Reward: $rewardCampaign->promoTitle , 
                            Discount Join: $rewardCampaign->discountJoin %, 
                            Discount for Bronze: $rewardCampaign->discountForBronze %, 
                            Discount for Silver: $rewardCampaign->discountForSilver %, 
                            Discount for Gold: $rewardCampaign->discountForGold %, 
                            $ Volume for Gold: $rewardCampaign->volumeToGold %, 
                            $ Volume for Silver: $rewardCampaign->volumeToSilver %, 
                            $ Volume for Bronze: $rewardCampaign->volumeToBronze %, 
                            ";
                //reward marketing sent
                $patients = Patient::where('deleted', 0)                    
                    ->select('email', 'first_name', 'last_name', 'middle_name')
                    ->addSelect(DB::raw("'".date('Y-m-d H:i:s', strtotime($rewardCampaign->created_at))."' as send_date"))
                    ->addSelect(DB::raw("'".$content."' as content"))
                    ->get();
                $patientArray = $patients->toArray();

                $data['rewardMarketingSent'] = [...$patientArray, ...$data['rewardMarketingSent']];            
            }
        }


        /*
        ###############################################################
         * Get the Birthday marketing list
        ###############################################################
        */
        if($this->emailTextRewardReport->birth_sent){            
            
            $patients = Patient::where('deleted', 0)
                ->whereBetween(
                    DB::raw("DATE_FORMAT(birthday, '%m-%d')"),
                    [
                        date('m-d', strtotime($range_sdate)),
                        date('m-d', strtotime($range_edate)),
                    ]
                )
                ->select('email', 'first_name', 'last_name', 'middle_name', 'birthday')
                ->addSelect(DB::raw(
                    "CONCAT(YEAR('" . $range_sdate . "'), '-', DATE_FORMAT(birthday, '%m-%d'), ' 00:00:00') as send_date"
                ))
                ->addSelect(DB::raw("'Happy Birthday!' as content"))
                ->get();

            $patientArray = $patients->toArray();

            $data['birthdayMarketingSent'] = [...$patientArray];                
        }



        $data['range_due']  = $this->emailTextRewardReport->range_sdate." ~ ".$this->emailTextRewardReport->range_edate;

        $receiverEmail = $this->emailTextRewardReport->email;

        $this->doSendEmail($data, $receiverEmail);        
    }

    public function doSendEmail($data, $receiverEmail){
        Mail::send('email.emailTextRewardReportNotify', ['data' => $data], function($message) use($receiverEmail){

            $message->to($receiverEmail);

            $message->subject('Email/Text Marketing Reward Report Notification');

        });

        //update with reported
        $this->emailTextRewardReport->update([
            'reported_date' => now(),
        ]); 
    }
}
