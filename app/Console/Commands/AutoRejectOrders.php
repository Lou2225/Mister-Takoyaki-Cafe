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

    // Only auto-reject delivery/App orders — a POS order left "Pending"
    // is typically a parked in-person transaction a cashier will resume,
    // not an unacknowledged delivery request. Auto-rejecting those was
    // likely causing orders to vanish out from under the POS Orders tab.
    $orders = Order::where('source', 'App')
        ->where('status', Order::STATUS_PENDING)
        ->where('created_at', '<=', $cutoff)
        ->get();

    $count = 0;
    foreach ($orders as $order) {
        try {
            $order->reject("Automatically rejected: Not accepted within 1 hour.");
            $count++;
        } catch (\Exception $e) {
            \Log::error("AutoRejectOrders failed for order #{$order->id}: " . $e->getMessage());
        }
    }

    if ($count > 0) {
        \Log::info("AutoRejectOrders: rejected {$count} stale pending orders.");
        $this->info("Successfully rejected {$count} pending orders.");
    } else {
        $this->info("No pending orders to reject.");
    }

    return 0;
}
}
