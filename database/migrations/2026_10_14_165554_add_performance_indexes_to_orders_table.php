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
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'created_at'], 'idx_orders_user_status_created');
            $table->index(['rider_id', 'status', 'created_at'], 'idx_orders_rider_status_created');
            $table->index(['branch_id', 'source', 'status'], 'idx_orders_branch_source_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_user_status_created');
            $table->dropIndex('idx_orders_rider_status_created');
            $table->dropIndex('idx_orders_branch_source_status');
        });
    }
};
