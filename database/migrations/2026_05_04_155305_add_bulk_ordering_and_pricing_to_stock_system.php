<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Ingredients: Add Bulk Unit and Bulk Quantity
        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('bulk_unit')->nullable()->after('unit')->comment('e.g. Box, Sack, Pack');
            $table->decimal('bulk_qty', 12, 2)->default(1)->after('bulk_unit')->comment('How many base units in 1 bulk unit');
            $table->decimal('bulk_price', 12, 2)->nullable()->after('bulk_qty')->comment('Price per bulk unit');
        });

        // 2. Stock Orders: Add Pricing Totals
        Schema::table('stock_orders', function (Blueprint $table) {
            $table->decimal('delivery_fee', 12, 2)->default(0)->after('notes');
            $table->decimal('total_amount', 12, 2)->default(0)->after('delivery_fee');
        });

        // 3. Stock Order Items: Add Order Unit and Item Pricing
        Schema::table('stock_order_items', function (Blueprint $table) {
            $table->string('order_unit')->default('base')->after('unit')->comment('base or bulk');
            $table->decimal('unit_price', 12, 2)->default(0)->after('order_unit');
            $table->decimal('subtotal', 12, 2)->default(0)->after('unit_price');
        });
    }

    public function down()
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn(['bulk_unit', 'bulk_qty', 'bulk_price']);
        });

        Schema::table('stock_orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_fee', 'total_amount']);
        });

        Schema::table('stock_order_items', function (Blueprint $table) {
            $table->dropColumn(['order_unit', 'unit_price', 'subtotal']);
        });
    }
};
