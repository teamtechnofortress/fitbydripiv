<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductMetricsReport;
use App\Jobs\SendEmailProductMetrics;

class ProductMetricsNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:product-metrics-notify';

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
        $productMetricsReport = ProductMetricsReport::where('deleted', 0)->orderBy('created_at', 'desc')->first();
        SendEmailProductMetrics::dispatch($productMetricsReport);        
    }
}
