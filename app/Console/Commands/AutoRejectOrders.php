<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Carbon\Carbon;
use App\Events\OrderStatusUpdated;

class AutoRejectOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:auto-reject';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically reject pending orders that have not been accepted within 1 hour.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cutoff = Carbon::now()->subHour();

        $orders = Order::where('status', Order::STATUS_PENDING)
            ->where('created_at', '<=', $cutoff)
            ->get();

        $count = $orders->count();

        foreach ($orders as $order) {
            $order->reject("Automatically rejected: Not accepted within 1 hour.");
        }

        if ($count > 0) {
            $this->info("Successfully rejected {$count} pending orders.");
        } else {
            $this->info("No pending orders to reject.");
        }

        return 0;
    }
}
