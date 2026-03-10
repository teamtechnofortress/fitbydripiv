<?php

namespace App\Console\Commands;

use App\Models\RewardReport;
use Illuminate\Console\Command;
use App\Jobs\SendEmailRewardReport;

class RewardReportNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reward-report-notify';

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
        $rewardReport = RewardReport::where('deleted', 0)->orderBy('created_at', 'desc')->first();
        SendEmailRewardReport::dispatch($rewardReport);
    }
}
