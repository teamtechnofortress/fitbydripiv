<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmailTextRewardReport;
use App\Jobs\SendEmailTextRewardReport;

class EmailTextReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:email-text-report';

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
        $rewardReport = EmailTextRewardReport::where('deleted', 0)->orderBy('created_at', 'desc')->first();
        SendEmailTextRewardReport::dispatch($rewardReport);
    }
}
