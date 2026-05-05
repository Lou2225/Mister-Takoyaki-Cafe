<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OptionTemplate;
use App\Models\OptionTemplateItem;

class OptionTemplateSeeder extends Seeder
{
    public function run()
    {
        // 1. Sizes
        $size = OptionTemplate::create([
            'name' => 'Serving Sizes',
            'price_mode' => 'fixed',
            'is_required' => true,
        ]);
        $size->items()->createMany([
            ['name' => 'Regular (4 pcs)', 'price' => 0, 'is_default' => true],
            ['name' => 'Large (8 pcs)', 'price' => 45, 'is_default' => false],
            ['name' => 'Family (12 pcs)', 'price' => 85, 'is_default' => false],
        ]);

        // 2. Flavors
        $flavors = OptionTemplate::create([
            'name' => 'Flavor Choice',
            'price_mode' => 'additive',
            'is_required' => true,
        ]);
        $flavors->items()->createMany([
            ['name' => 'Original Takoyaki', 'price' => 0, 'is_default' => true],
            ['name' => 'Cheese Overload', 'price' => 15, 'is_default' => false],
            ['name' => 'Spicy Garlic', 'price' => 10, 'is_default' => false],
        ]);

        // 3. Add-ons
        $addons = OptionTemplate::create([
            'name' => 'Premium Add-ons',
            'price_mode' => 'additive',
            'is_required' => false,
        ]);
        $addons->items()->createMany([
            ['name' => 'Extra Mayo', 'price' => 5, 'is_default' => false],
            ['name' => 'Extra Bonito Flakes', 'price' => 10, 'is_default' => false],
            ['name' => 'Mozzarella Melt', 'price' => 25, 'is_default' => false],
        ]);
    }
}
