<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class MigratePosOrderStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:migrate-pos-statuses {--dry-run : Preview changes without applying}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate POS order statuses: Complete paid orders, remove old delivery statuses (Ready, Preparing, etc)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // ═════════════════════════════════════════════════════════════════
        // 1. AUTO-COMPLETE PAID POS ORDERS
        // ═════════════════════════════════════════════════════════════════
        $paidNotCompleted = Order::where('source', 'POS')
            ->where('payment_status', 'Paid')
            ->where('status', '!=', Order::STATUS_COMPLETED)
            ->where('status', '!=', Order::STATUS_VOID)
            ->where('status', '!=', Order::STATUS_REFUNDED)
            ->where('status', '!=', Order::STATUS_PARTIALLY_REFUNDED)
            ->count();

        if ($paidNotCompleted > 0) {
            $this->warn("\nFound {$paidNotCompleted} PAID POS orders that should be COMPLETED:");
            
            $byStatus = Order::where('source', 'POS')
                ->where('payment_status', 'Paid')
                ->where('status', '!=', Order::STATUS_COMPLETED)
                ->where('status', '!=', Order::STATUS_VOID)
                ->where('status', '!=', Order::STATUS_REFUNDED)
                ->where('status', '!=', Order::STATUS_PARTIALLY_REFUNDED)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();

            foreach ($byStatus as $row) {
                $this->line("  • {$row->status}: {$row->count} orders");
            }

            if ($this->option('dry-run')) {
                $this->info("[DRY RUN] Would update {$paidNotCompleted} orders.");
            } else {
                if (!$this->confirm("Update {$paidNotCompleted} PAID POS orders to COMPLETED?")) {
                    return Command::SUCCESS;
                }
                
                $updated = Order::where('source', 'POS')
                    ->where('payment_status', 'Paid')
                    ->where('status', '!=', Order::STATUS_COMPLETED)
                    ->where('status', '!=', Order::STATUS_VOID)
                    ->where('status', '!=', Order::STATUS_REFUNDED)
                    ->where('status', '!=', Order::STATUS_PARTIALLY_REFUNDED)
                    ->update(['status' => Order::STATUS_COMPLETED]);

                $this->info("✓ Updated {$updated} PAID POS orders to COMPLETED.\n");
            }
        }

        // ═════════════════════════════════════════════════════════════════
        // 2. REMOVE OLD DELIVERY STATUSES FROM UNPAID/VOIDED POS ORDERS
        // ═════════════════════════════════════════════════════════════════
        $oldStatuses = ['Ready', 'Preparing', 'Out for Delivery', 'Delivered', 'Cancelled'];

        $oldStatusCount = Order::where('source', 'POS')
            ->whereIn('status', $oldStatuses)
            ->count();

        if ($oldStatusCount > 0) {
            $this->warn("\nFound {$oldStatusCount} POS orders with old delivery statuses:");
            
            $byStatus = Order::where('source', 'POS')
                ->whereIn('status', $oldStatuses)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();

            foreach ($byStatus as $row) {
                $this->line("  • {$row->status}: {$row->count} orders");
            }

            if ($this->option('dry-run')) {
                $this->info("[DRY RUN] Would update {$oldStatusCount} orders.");
            } else {
                if (!$this->confirm("Remove old delivery statuses from {$oldStatusCount} POS orders?")) {
                    return Command::SUCCESS;
                }
                
                // For unpaid orders: move to VOID
                // For paid orders: move to COMPLETED
                $unpaidOldStatus = Order::where('source', 'POS')
                    ->whereIn('status', $oldStatuses)
                    ->where('payment_status', '!=', 'Paid')
                    ->update(['status' => Order::STATUS_VOID]);

                $paidOldStatus = Order::where('source', 'POS')
                    ->whereIn('status', $oldStatuses)
                    ->where('payment_status', 'Paid')
                    ->update(['status' => Order::STATUS_COMPLETED]);

                $this->info("✓ Updated {$unpaidOldStatus} unpaid orders to VOID.");
                $this->info("✓ Updated {$paidOldStatus} paid orders to COMPLETED.\n");
            }
        }

        if ($paidNotCompleted === 0 && $oldStatusCount === 0) {
            $this->info('✓ All POS orders have correct statuses. System is up to date.');
        }

        return Command::SUCCESS;
    }
}
