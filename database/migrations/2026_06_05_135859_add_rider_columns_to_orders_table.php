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
            if (!Schema::hasColumn('orders', 'riders_id')) {
                $table->unsignedBigInteger('riders_id')->nullable();
            }
            if (!Schema::hasColumn('orders', 'dispatched_at')) {
                $table->dateTime('dispatched_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['riders_id', 'dispatched_at']);
        });
    }
};