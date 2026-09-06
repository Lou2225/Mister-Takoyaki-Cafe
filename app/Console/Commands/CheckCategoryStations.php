<?php

namespace App\Console\Commands;

use App\Models\ProductCategory;
use Illuminate\Console\Command;

class CheckCategoryStations extends Command
{
    protected $signature = 'categories:check-stations';
    protected $description = 'Check production_station values for all categories';

    public function handle()
    {
        $categories = ProductCategory::all();
        
        $this->line('Total categories: ' . $categories->count());
        $this->line('---');
        
        foreach ($categories as $c) {
            $station = $c->production_station ?? 'NULL';
            $this->line("ID: {$c->id} | Name: {$c->name} | Station: {$station}");
        }

        $this->line('---');
        $this->info('Checking item distribution...');
        
        $kitchenCount = ProductCategory::where('production_station', 'kitchen')->count();
        $baristaCount = ProductCategory::where('production_station', 'barista')->count();
        $nullCount = ProductCategory::whereNull('production_station')->count();
        
        $this->line("Kitchen categories: {$kitchenCount}");
        $this->line("Barista categories: {$baristaCount}");
        $this->line("NULL/Unset categories: {$nullCount}");
    }
}
