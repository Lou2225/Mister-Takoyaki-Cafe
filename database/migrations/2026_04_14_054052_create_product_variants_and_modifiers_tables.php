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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., "4 Pieces", "Large", "Spicy"
            $table->decimal('price', 12, 2)->nullable(); // Overrides base price if set
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('modifiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Extra Cheese", "No Mayo"
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('modifier_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modifier_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('modifier_id')->nullable()->constrained('modifiers')->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
        });
        
        Schema::create('order_item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modifier_id')->constrained()->cascadeOnDelete();
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropColumn('variant_id');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropForeign(['modifier_id']);
            $table->dropColumn(['variant_id', 'modifier_id']);
        });

        Schema::dropIfExists('order_item_modifiers');
        Schema::dropIfExists('modifier_product');
        Schema::dropIfExists('modifiers');
        Schema::dropIfExists('product_variants');
    }
};
