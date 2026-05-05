<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('unit');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('branch_name');
        });

        Schema::table('stock_transfer_requests', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->nullable()->after('requested_quantity');
            $table->decimal('subtotal', 10, 2)->nullable()->after('unit_price');
            $table->decimal('delivery_fee', 10, 2)->nullable()->after('subtotal');
        });
    }

    public function down()
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('delivery_fee');
        });

        Schema::table('stock_transfer_requests', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'subtotal', 'delivery_fee']);
        });
    }
};
