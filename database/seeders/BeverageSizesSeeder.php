<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OptionTemplate;
use App\Models\OptionTemplateItem;

class BeverageSizesSeeder extends Seeder
{
    public function run()
    {
        $size = OptionTemplate::create([
            'name' => 'Beverage Sizes',
            'price_mode' => 'fixed',
            'is_required' => true,
        ]);
        $size->items()->createMany([
            ['name' => 'Grande', 'price' => 89.00, 'is_default' => true],
            ['name' => 'Venti', 'price' => 99.00, 'is_default' => false],
        ]);
    }
}
