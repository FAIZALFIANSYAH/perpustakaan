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
        // Check and apply penalties daily at 01:00 AM
        $schedule->command('penalties:check')->dailyAt('01:00');
        
        // Check and update overdue borrowings daily at 02:00 AM
        $schedule->command('overdue:check')->dailyAt('02:00');
        
        // You can add more scheduled commands here
        // $schedule->command('inspire')->hourly();
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