<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailAppointmentReport;
use App\Models\AppointmentReport;
use Illuminate\Console\Command;

class AppointmentReportNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:appointment-report-notify';

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
        $appointmentReport = AppointmentReport::where('deleted', 0)->orderBy('created_at', 'desc')->first();
        SendEmailAppointmentReport::dispatch($appointmentReport);
    }
}
