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
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->nullable()->after('quantity');
            $table->string('reference_id')->nullable()->after('unit_cost');
            $table->date('expiry_date')->nullable()->after('reference_id');
            $table->foreignId('supplier_id')->nullable()->after('expiry_date')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            //
        });
    }
};
