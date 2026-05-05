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
        Schema::create('order_item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_option_id']);
            $table->dropColumn('product_option_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_option_id')->nullable()->constrained('product_options')->nullOnDelete();
        });

        Schema::dropIfExists('order_item_options');
    }
};
