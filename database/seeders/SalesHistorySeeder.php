<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Product;
use App\Models\Branch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class SalesHistorySeeder extends Seeder
{
    public function run()
    {
        $branches = Branch::all();
        $products = Product::where('is_active', 1)->get();
        $users = User::all();

        if ($branches->isEmpty() || $products->isEmpty() || $users->isEmpty()) {
            return;
        }

        $paymentMethods = ['Cash', 'GCash', 'PayMaya', 'Card'];
        $orderTypes = ['Dine-in', 'Take-out', 'Delivery'];

        // Generate sales for the last 90 days
        for ($i = 90; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);

            foreach ($branches as $branch) {
                // Random number of orders per branch per day (scaled by branch size if needed)
                $orderCount = rand(5, 25);
                $branchUsers = $users->where('branch_id', $branch->id);
                if ($branchUsers->isEmpty()) $branchUsers = $users;

                for ($j = 0; $j < $orderCount; $j++) {
                    $totalAmount = 0;
                    $items = [];
                    $itemCount = rand(1, 4);

                    for ($k = 0; $k < $itemCount; $k++) {
                        $product = $products->random();
                        $qty = rand(1, 3);
                        $subtotal = $product->price * $qty;
                        $totalAmount += $subtotal;

                        $items[] = [
                            'product_id' => $product->id,
                            'quantity' => $qty,
                            'unit_price' => $product->price,
                            'subtotal' => $subtotal,
                            'created_at' => $date,
                            'updated_at' => $date,
                        ];
                    }

                    $order = Order::create([
                        'reference_no' => 'ORD-' . $date->format('Ymd') . '-' . strtoupper(Str::random(6)),
                        'branch_id' => $branch->id,
                        'user_id' => $branchUsers->random()->id,
                        'total_amount' => $totalAmount,
                        'tax_amount' => $totalAmount * 0.12,
                        'discount_amount' => rand(0, 10) > 8 ? ($totalAmount * 0.10) : 0,
                        'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                        'order_type' => $orderTypes[array_rand($orderTypes)],
                        'status' => 'Completed',
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);

                    foreach ($items as $item) {
                        $item['order_id'] = $order->id;
                        OrderItem::create($item);
                    }
                }
            }
        }
    }
}
