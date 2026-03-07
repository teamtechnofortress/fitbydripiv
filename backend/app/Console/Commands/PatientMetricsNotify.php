<?php

namespace App\Console\Commands;

use App\Models\Patient;
use Illuminate\Console\Command;
use App\Models\PatientMetricsReport;
use App\Jobs\SendEmailPatientMetrics;

class PatientMetricsNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:patient-metrics-notify';

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
        $patientMetricsReport = PatientMetricsReport::where('deleted', 0)->latest()->first();

        SendEmailPatientMetrics::dispatch($patientMetricsReport);        
    }
}
