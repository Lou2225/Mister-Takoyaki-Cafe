<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\File;

class ExportMenuData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:export-menu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export products and categories for the offline POS Electron app';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Exporting categories...');
        $categories = ProductCategory::withCount(['products' => function ($q) {
            $q->where('is_active', true);
        }])->orderBy('sort_order', 'asc')->orderBy('name', 'asc')->get();

        $this->info('Exporting products...');
        $products = Product::with(['category', 'optionGroups.options', 'modifiers'])
            ->where('is_active', true)
            ->get();

        $data = [
            'categories' => $categories,
            'products' => $products,
            'currencySymbol' => \App\Models\SystemSetting::get('currency_symbol', '₱'),
            'serviceChargeRate' => (float) \App\Models\SystemSetting::get('service_charge', 0),
            'discountRate' => (float) \App\Models\SystemSetting::get('discount_rate', 0),
            'seniorDiscountRate' => (float) \App\Models\SystemSetting::get('senior_discount_rate', 0.20),
            'orderTypes' => (array) \App\Models\SystemSetting::get('pos_order_types', ['Dine-in', 'Take-out']),
            'paymentMethods' => (array) \App\Models\SystemSetting::get('pos_payment_methods', ['Cash', 'GCash']),
        ];

        $exportPath = base_path('desktop/src/renderer/menu_data.json');
        
        // Ensure directory exists
        if (!File::exists(dirname($exportPath))) {
            File::makeDirectory(dirname($exportPath), 0755, true);
        }

        File::put($exportPath, json_encode($data, JSON_PRETTY_PRINT));

        $this->info("Menu data exported successfully to: {$exportPath}");
        return 0;
    }
}
