<?php

namespace App\Console\Commands;

use App\Models\MedrxReports;
use Illuminate\Console\Command;
use App\Jobs\SendEmailMedrxReport;

class MedrxReportNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:medrx-report-notify';

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
        $medrxReport = MedrxReports::orderBy('created_at', 'desc')->first();
        SendEmailMedrxReport::dispatch($medrxReport);        
    }
}
