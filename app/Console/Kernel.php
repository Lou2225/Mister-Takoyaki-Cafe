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
        $schedule->command('stock:check-alerts')
            ->dailyAt('08:00')
            ->appendOutputTo(storage_path('logs/stock-alerts.log'));

        $schedule->command('orders:auto-reject')->everyMinute();

        // Nightly financial rollup — aggregates yesterday's orders per branch
        // into daily_branch_summaries so the dashboard doesn't re-scan all rows.
        // Hostinger hPanel cron: "5 0 * * * php /path/to/artisan schedule:run"
        $schedule->command('dashboard:rollup', ['--date' => 'yesterday'])
            ->dailyAt('00:05')
            ->appendOutputTo(storage_path('logs/dashboard-rollup.log'))
            ->withoutOverlapping();
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
