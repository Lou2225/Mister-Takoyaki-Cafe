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
            if (!Schema::hasColumn('orders', 'amount_tendered')) {
                $table->decimal('amount_tendered', 12, 2)->nullable()->after('payment_reference');
            }
            if (!Schema::hasColumn('orders', 'change_amount')) {
                $table->decimal('change_amount', 12, 2)->nullable()->after('amount_tendered');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('orders', 'amount_tendered')) {
                $columnsToDrop[] = 'amount_tendered';
            }
            if (Schema::hasColumn('orders', 'change_amount')) {
                $columnsToDrop[] = 'change_amount';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
