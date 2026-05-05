<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge notifications older than 60 days to maintain performance.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = 60;
        $date = now()->subDays($days);
        
        $count = \App\Models\Notification::where('created_at', '<', $date)->delete();

        $this->info("Successfully deleted {$count} notifications older than {$days} days.");
        
        return Command::SUCCESS;
    }
}
