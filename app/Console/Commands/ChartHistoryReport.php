<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailChartHistory;
use App\Models\ChartHistory;
use Illuminate\Console\Command;

class ChartHistoryReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:chart-history-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chart History Sender';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chartHistory = ChartHistory::where('deleted', 0)->orderBy('created_at', 'desc')->first();
        SendEmailChartHistory::dispatch($chartHistory);        
    }
}
