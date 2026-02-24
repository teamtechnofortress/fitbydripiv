<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Patient;
use App\Models\TextCampaign;
use App\Services\SmsService;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendEmail;

class SendTextCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaign;
    /**
     * Create a new job instance.
     */
    public function __construct(TextCampaign $campaign)
    {
        $this->campaign = $campaign;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $twilio = new SmsService();

        $patients = Patient::whereBetween('id', [$this->campaign->patient_start, $this->campaign->patient_end])->get();

        foreach ($patients as $patient) {
            // Use SMS API like Twilio to send message
            $messageContent = $this->campaign->message;
            if ($this->campaign->include_signature) {
                $messageContent .= "\n\n".$this->campaign->company_signature;
            }

           $twilio->sendSms($patient->phone, $messageContent);//$client->phone
        //    $twilio->sendSms("+19167647017", $messageContent);//$client->phone
        }

        $this->campaign->update(['sent' => true]);
    }
}
