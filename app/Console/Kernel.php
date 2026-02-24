<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:dispatch-text-campaigns')->everyMinute();
        $schedule->command('app:dispatch-email-campaigns')->everyMinute();
        // $schedule->command('app:chart-history-report')->dailyAt('08:00');
        // $schedule->command('app:customer-service-report')->dailyAt('08:00');
        // $schedule->command('app:patient-metrics-notify')->dailyAt('08:00');
        // $schedule->command('app:product-metrics-notify')->dailyAt('08:00');        
        // $schedule->command('app:appointment-report-notify')->dailyAt('08:00');
        // $schedule->command('app:reward-report-notify')->dailyAt('08:00');
        // $schedule->command('app:email-text-report')->dailyAt('08:00');
        // $schedule->command('app:invoicing-sales-report-notify')->dailyAt('08:00');
        // $schedule->command('app:staff-report-notify')->dailyAt('08:00');
        // $schedule->command('app:payroll-report-notify')->dailyAt('08:00');
        // $schedule->command('app:medrx-report-notify')->dailyAt('08:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
