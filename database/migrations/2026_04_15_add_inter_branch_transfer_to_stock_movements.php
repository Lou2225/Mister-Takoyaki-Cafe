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
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('from_branch_id')->nullable()->after('branch_id')->constrained('branches')->nullOnDelete();
            $table->string('transfer_reference')->nullable()->after('remarks')->comment('Unique reference for paired transfer movements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeignIdFor('from_branch_id');
            $table->dropColumn(['from_branch_id', 'transfer_reference']);
        });
    }
};
