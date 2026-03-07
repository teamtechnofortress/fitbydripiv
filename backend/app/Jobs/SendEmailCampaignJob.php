<?php

namespace App\Jobs;

use App\Mail\SendEmail;
use App\Models\Patient;
use App\Models\EmailCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaign;
    /**
     * Create a new job instance.
     */
    public function __construct(EmailCampaign $campaign)
    {
        $this->campaign = $campaign;

    }
    

    public function handle(): void
    {
        $patientsQuery = Patient::whereBetween('id', [
            $this->campaign->patient_start,
            $this->campaign->patient_end
        ]);

        $messageContent = $this->campaign->content;
        if ($this->campaign->include_signature) {
            $messageContent .= "\n\n" . $this->campaign->company_signature;
        }

        $patientsQuery->chunk(100, function($patients) use ($messageContent) {
            foreach ($patients as $patient) {
                try {
                    if (PHP_OS === 'WINNT') {
                        Mail::to("mvlasau@gmail.com")->send(new SendEmail($messageContent, $this->campaign->attachments));
                        break; // Only for testing
                    }

                    Mail::to($patient->email)->send(new SendEmail($messageContent, $this->campaign->attachments));

                } catch (\Exception $e) {
                    Log::error("Failed to send email to {$patient->email}: " . $e->getMessage());
                }
            }
        });

        $this->campaign->update([
            'sent'    => true,
            'deleted' => $this->campaign->archive_after_send ? false : true,
        ]);
    }

}
