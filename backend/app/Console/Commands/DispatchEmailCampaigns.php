<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmailCampaign;//Must be modified to EmailCampaign
use App\Jobs\SendEmailCampaignJob;

class DispatchEmailCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dispatch-email-campaigns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $campaigns = EmailCampaign::where('sent', false)
        ->whereDate('send_date', now()->toDateString())
        ->whereTime('send_time', '<=', now()->toTimeString())
        ->get();

        foreach ($campaigns as $campaign) {
            SendEmailCampaignJob::dispatch($campaign);
        }
    }
}
