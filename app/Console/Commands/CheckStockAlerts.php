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

        $branchData = []; // [branch_id => ['name' => ..., 'low' => [], 'expiring' => [], 'expired' => []]]

        // 1. Gather Low Stocks
        $stocks = BranchIngredientStock::with(['branch', 'ingredient'])->get();
        foreach ($stocks as $stock) {
            $min = (float) $stock->ingredient->minimum_stock;
            if ($min > 0 && $stock->stock_quantity < $min) {
                $bid = $stock->branch_id;
                if (!isset($branchData[$bid])) {
                    $branchData[$bid] = ['name' => $stock->branch->branch_name, 'low' => [], 'expiring' => [], 'expired' => []];
                }
                
                $branchData[$bid]['low'][] = [
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
            $bid = $batch->branch_id;
            if (!isset($branchData[$bid])) {
                $branchData[$bid] = ['name' => $batch->branch->branch_name, 'low' => [], 'expiring' => [], 'expired' => []];
            }

            if ($expiry->lt($today)) {
                $branchData[$bid]['expired'][] = [
                    'ingredient' => $batch->ingredient->name,
                    'quantity' => StockHelper::formatForDisplay($batch->current_quantity, $batch->ingredient->unit),
                    'expiry_date' => $batch->expiry_date,
                    'batch_id' => $batch->id,
                ];
            } elseif ($expiry->lte($nextWeek)) {
                $branchData[$bid]['expiring'][] = [
                    'ingredient' => $batch->ingredient->name,
                    'quantity' => StockHelper::formatForDisplay($batch->current_quantity, $batch->ingredient->unit),
                    'expiry_date' => $batch->expiry_date,
                    'batch_id' => $batch->id,
                ];
            }
        }

        if (empty($branchData)) {
            $this->info("All good. No alerts generated.");
            return 0;
        }

        // Send Email to Super Admins (role 1) and Admins (role 2)
        $admins = User::whereIn('role_id', [1, 2])->where('is_active', true)->whereNotNull('email')->get();

        if ($admins->isEmpty()) {
            $this->warn("No active admins found to email.");
            return 0;
        }

        foreach ($admins as $admin) {
            $userLow = [];
            $userExpiring = [];
            $userExpired = [];

            foreach ($branchData as $bid => $data) {
                // Super Admin gets everything, Branch Admin gets only their branch
                if ($admin->role_id == 1 || $admin->branch_id == $bid) {
                    if (!empty($data['low'])) $userLow[$data['name']] = $data['low'];
                    if (!empty($data['expiring'])) $userExpiring[$data['name']] = $data['expiring'];
                    if (!empty($data['expired'])) $userExpired[$data['name']] = $data['expired'];
                }
            }

            // Only send if there is data for this specific user
            if (!empty($userLow) || !empty($userExpiring) || !empty($userExpired)) {
                $reportData = [
                    'lowStocks' => $userLow,
                    'expiringBatches' => $userExpiring,
                    'expiredBatches' => $userExpired,
                ];

                Mail::to($admin->email)->send(new StockAlertMail($reportData));
                $this->line("Sent alert to: " . $admin->email . ($admin->role_id == 1 ? " (Enterprise)" : " (Branch: " . $admin->branch_id . ")"));
            }
        }

        $this->info("Stock check complete. Emails dispatched.");
        return 0;
    }
}
