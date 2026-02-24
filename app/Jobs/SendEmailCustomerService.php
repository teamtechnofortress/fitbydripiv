<?php

namespace App\Jobs;

use Carbon\Carbon;
use Stripe\Customer;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use App\Models\CustomerServiceReport;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailCustomerService implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customerServiceReport;

    /**
     * Create a new job instance.
     */
    public function __construct(CustomerServiceReport $customerServiceReport)
    {
        $this->customerServiceReport = $customerServiceReport;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $range_sdate  = $this->customerServiceReport->range_sdate; 
        $range_edate  = $this->customerServiceReport->range_edate;
        $arrive_stime = $this->customerServiceReport->arrive_stime;
        $arrive_etime = $this->customerServiceReport->arrive_etime; 

        //
        $localStart = Carbon::parse("{$range_sdate} {$arrive_stime}");
        $localEnd   = Carbon::parse("{$range_edate} {$arrive_etime}");
        
        $reportStartDate = $localStart->clone()->setTimezone('UTC')->format('Y-m-d H:i:s');
        $reportEndDate   = $localEnd->clone()->setTimezone('UTC')->format('Y-m-d H:i:s');

        
        $rewards = Invoice::where(['deleted' => 0])
            ->whereBetween('created_at', [$reportStartDate, $reportEndDate])
            ->with('patient')
            ->get();

        //Get Reward Sales Volume
        foreach($rewards as $key => $reward){
            foreach(json_decode($reward->data, true) as $_data){
                foreach($_data as $row){
                    $inventory = $row['inventory'];
                    $price = $inventory['price'] * 1;
                    $realValue = $price > 0 ? $price * (1 - (($row['discount'] ?? 0) / 100)) : 0;
                    $rewards[$key]->total_reward_value = ($rewards[$key]->total_reward_value ?? 0) + $realValue;

                    $rewards[$key]->add_on_count = $row['is_add_on'] ? ($rewards[$key]->add_on_count ?? 0 + 1) : ($rewards[$key]->add_on_count ?? 0);
                }
            }
        }        

        $data['rewards'] = $rewards;
        $data['range_due']  = $this->customerServiceReport->range_sdate." ~ ".$this->customerServiceReport->range_edate;
        // $data['arrive_due'] = $arriveStartTime." ~ ".$arriveEndTime;
        $data['arrive_due'] = $arrive_stime." ~ ".$arrive_etime;
        $data['reward_sales'] = $this->customerServiceReport->reward_sales;
        $data['add_on'] = $this->customerServiceReport->add_on_beg;

        $receiverEmail = $this->customerServiceReport->email;

        $this->doSendEmail($data, $receiverEmail);        
    }

    public function doSendEmail($data, $receiverEmail){
        Mail::send('email.customerServiceNotification', ['data' => $data], function($message) use($receiverEmail){

            $message->to($receiverEmail);

            $message->subject('Customer Service Report Notification');

        });
        
        //upate with reported
        $this->customerServiceReport->update([
            'reported_date' => now(),
        ]); 
    }
}
