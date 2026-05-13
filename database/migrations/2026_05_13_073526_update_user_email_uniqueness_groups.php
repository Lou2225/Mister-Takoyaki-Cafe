<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the unique constraint from the previous attempt if it exists
            // Since we don't know the exact index name Laravel generated for [email, role_id],
            // we should try to drop it safely. Laravel usually names it users_email_role_id_unique.
            try {
                $table->dropUnique(['email', 'role_id']);
            } catch (\Exception $e) {
                // If it fails, maybe it was just 'email'
                try {
                    $table->dropUnique(['email']);
                } catch (\Exception $e) {
                    // Ignore if no unique index exists
                }
            }

            // Add virtual column for role grouping
            // Group 1: Customers (role_id = 4)
            // Group 0: System Users (all other role_ids)
            $table->tinyInteger('is_customer_group')->virtualAs('IF(role_id = 4, 1, 0)')->after('role_id');
            
            // Add unique index for (email, is_customer_group)
            $table->unique(['email', 'is_customer_group'], 'users_email_group_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_group_unique');
            $table->dropColumn('is_customer_group');
            $table->unique('email');
        });
    }
};
