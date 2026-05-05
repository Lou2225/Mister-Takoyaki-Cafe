<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TestUserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'employee_id' => 'EMP001',
            'first_name' => 'Lou',
            'middle_name' => '',
            'last_name' => 'Valdellon',
            'email' => 'lou.lava16@gmail.com',
            'phone' => '09171234567',
            'email_verified_at' => now(),
            'password' => Hash::make('Password123'),
            'role_id' => 1, // Super Admin
            'branch_id' => 1,
            'date_hired' => now(),
            'position' => 'Administrator',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
