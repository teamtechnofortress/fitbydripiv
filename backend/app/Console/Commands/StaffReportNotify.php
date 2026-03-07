<?php

namespace App\Console\Commands;

use App\Models\StaffReport;
use Illuminate\Console\Command;
use App\Jobs\SendEmailStaffReport;

class StaffReportNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:staff-report-notify';

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
        $staffReport = StaffReport::orderBy('created_at', 'desc')->first();
        SendEmailStaffReport::dispatch($staffReport);        
    }
}
