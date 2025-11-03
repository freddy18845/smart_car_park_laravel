<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application’s command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Optional: log scheduler activity for debugging
        Log::info('⏰ Laravel scheduler ticked...');

        // Expire past reservations every minute
        $schedule->command('reservations:auto-expire')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Send reservation reminders every 10 minutes
        $schedule->command('reservations:send-reminders')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
