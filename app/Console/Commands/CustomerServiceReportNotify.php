<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CustomerServiceReport;
use App\Jobs\SendEmailCustomerService;

class CustomerServiceReportNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:customer-service-report';

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
        $customerServiceReport = CustomerServiceReport::where('deleted', 0)->orderBy('created_at', 'desc')->first();
        SendEmailCustomerService::dispatch($customerServiceReport);        
    }
}
