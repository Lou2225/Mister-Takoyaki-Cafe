<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run()
    {
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Super Admin', 'description' => 'Full system control'],
            ['id' => 2, 'name' => 'Admin', 'description' => 'Manages a specific branch'],
            ['id' => 3, 'name' => 'Cashier', 'description' => 'Handles point-of-sale transactions'],
            ['id' => 4, 'name' => 'Customer', 'description' => 'Regular customer user'],
            ['id' => 5, 'name' => 'Delivery Rider', 'description' => 'Delivery rider for the mobile app'],
        ]);
    }
}
