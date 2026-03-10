<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TextCampaign;
use App\Jobs\SendTextCampaignJob;


class DispatchTextCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dispatch-text-campaigns';

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
        $campaigns = TextCampaign::where('sent', false)
        ->whereDate('send_date', now()->toDateString())
        ->whereTime('send_time', '<=', now()->toTimeString())
        ->get();

        foreach ($campaigns as $campaign) {
            SendTextCampaignJob::dispatch($campaign);
        }
    }
}
