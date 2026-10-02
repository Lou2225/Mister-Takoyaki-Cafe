<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Daily stock alert email — respects the Settings auto-notifications toggle
        // and uses an atomic cache lock to prevent duplicate emails per day.
        $schedule->command('stock:check-alerts')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/stock-alerts.log'));

        // Every-minute guard: auto-rejects unacknowledged App delivery orders
        // older than 1 hour. withoutOverlapping ensures only one instance runs
        // at a time even if a slow DB response delays the previous invocation.
        $schedule->command('orders:auto-reject')
            ->everyMinute()
            ->withoutOverlapping();

        // Background queue worker — processes all pending database queue jobs
        // (e.g. email receipts, notifications, stock tasks) every minute.
        // --stop-when-empty: exits cleanly once the queue is drained.
        // --max-time=50:     ensures it stops before the next cron tick (60s).
        // withoutOverlapping: prevents parallel workers on slow jobs.
        // No extra Hostinger cron needed — runs under the existing schedule:run.
        $schedule->command('queue:work --stop-when-empty --tries=3 --max-time=50')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/queue-worker.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
