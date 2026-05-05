<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BranchIngredientStock;
use App\Models\StockBatch;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\StockAlertMail;
use Carbon\Carbon;
use App\Helpers\StockHelper;

class CheckStockAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for low stock and expiring items, and sends email alerts to Super Admins & Admins';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Running enterprise stock checks...");

        $lowStocks = [];
        $expiringBatches = [];
        $expiredBatches = [];

        // 1. Gather Low Stocks
        $stocks = BranchIngredientStock::with(['branch', 'ingredient'])->get();
        foreach ($stocks as $stock) {
            $min = (float) $stock->ingredient->minimum_stock;
            if ($min > 0 && $stock->stock_quantity < $min) {
                $bn = $stock->branch->branch_name;
                
                if (!isset($lowStocks[$bn])) $lowStocks[$bn] = [];
                $lowStocks[$bn][] = [
                    'ingredient' => $stock->ingredient->name,
                    'current' => StockHelper::formatForDisplay($stock->stock_quantity, $stock->ingredient->unit),
                    'minimum' => StockHelper::formatForDisplay($min, $stock->ingredient->unit),
                ];
            }
        }

        // 2. Gather Batches
        $batches = StockBatch::with(['branch', 'ingredient'])
            ->whereNotNull('expiry_date')
            ->where('current_quantity', '>', 0)
            ->get();

        $today = Carbon::today();
        $nextWeek = Carbon::today()->addDays(7);

        foreach ($batches as $batch) {
            $expiry = Carbon::parse($batch->expiry_date)->startOfDay();
            $bn = $batch->branch->branch_name;

            if ($expiry->lt($today)) {
                // Expired
                if (!isset($expiredBatches[$bn])) $expiredBatches[$bn] = [];
                $expiredBatches[$bn][] = [
                    'ingredient' => $batch->ingredient->name,
                    'quantity' => StockHelper::formatForDisplay($batch->current_quantity, $batch->ingredient->unit),
                    'expiry_date' => $batch->expiry_date,
                    'batch_id' => $batch->id,
                ];
            } elseif ($expiry->lte($nextWeek)) {
                // Expiring Soon
                if (!isset($expiringBatches[$bn])) $expiringBatches[$bn] = [];
                $expiringBatches[$bn][] = [
                    'ingredient' => $batch->ingredient->name,
                    'quantity' => StockHelper::formatForDisplay($batch->current_quantity, $batch->ingredient->unit),
                    'expiry_date' => $batch->expiry_date,
                    'batch_id' => $batch->id,
                ];
            }
        }

        if (empty($lowStocks) && empty($expiringBatches) && empty($expiredBatches)) {
            $this->info("All good. No alerts generated.");
            return 0;
        }

        // Send Email to Super Admins (role 1) and Admins (role 2)
        $admins = User::whereIn('role_id', [1, 2])->where('is_active', true)->whereNotNull('email')->get();

        if ($admins->isEmpty()) {
            $this->warn("No active admins found to email.");
            return 0;
        }

        $reportData = [
            'lowStocks' => $lowStocks,
            'expiringBatches' => $expiringBatches,
            'expiredBatches' => $expiredBatches,
        ];

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new StockAlertMail($reportData));
            $this->line("Sent alert to: " . $admin->email);
        }

        $this->info("Stock check complete. Emails dispatched.");
        return 0;
    }
}
