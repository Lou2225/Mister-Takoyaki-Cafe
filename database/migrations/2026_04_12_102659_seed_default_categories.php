<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('ingredient_categories')->insert([
            ['name' => 'Base & Batter', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Seafood/Proteins', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Seasoning & Sauces', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Packaging', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Beverage Bases', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('product_categories')->insert([
            ['name' => 'Takoyaki Classic', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Premium Series', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Platters/Combo', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Refreshments', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
