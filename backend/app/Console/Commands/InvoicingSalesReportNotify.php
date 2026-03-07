<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailInvoicingSalesReport;
use App\Models\InvoicingSalesReport;
use Illuminate\Console\Command;

class InvoicingSalesReportNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:invoicing-sales-report-notify';

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
        $invoicingSalesReport = InvoicingSalesReport::where('deleted', 0)->orderBy('created_at', 'desc')->first();
        SendEmailInvoicingSalesReport::dispatch($invoicingSalesReport);
    }
}
