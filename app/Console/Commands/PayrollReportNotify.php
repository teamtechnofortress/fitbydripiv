<?php

namespace App\Console\Commands;

use App\Models\PayrollReport;
use Illuminate\Console\Command;
use App\Jobs\SendEmailPayrollReport;

class PayrollReportNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:payroll-report-notify';

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
        $payrollReport = PayrollReport::orderBy('created_at', 'desc')->first();
        SendEmailPayrollReport::dispatch($payrollReport);        
    }
}
