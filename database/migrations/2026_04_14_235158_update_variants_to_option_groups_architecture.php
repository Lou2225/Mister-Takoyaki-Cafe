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
        // 1. Create Option Groups table
        Schema::create('product_option_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., "Pieces", "Flavor"
            $table->enum('price_mode', ['fixed', 'additive'])->default('additive');
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Create Options table
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('product_option_groups')->cascadeOnDelete();
            $table->string('name'); // e.g., "4 Pieces", "Classic Original"
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. Update Recipes table references
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropColumn('variant_id');
            $table->foreignId('product_option_id')->nullable()->constrained('product_options')->nullOnDelete();
        });

        // 4. Update Order Items table references
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropColumn('variant_id');
            $table->foreignId('product_option_id')->nullable()->constrained('product_options')->nullOnDelete();
        });

        // 5. Drop old table
        Schema::dropIfExists('product_variants');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_option_id']);
            $table->dropColumn('product_option_id');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['product_option_id']);
            $table->dropColumn('product_option_id');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
        });

        Schema::dropIfExists('product_options');
        Schema::dropIfExists('product_option_groups');
    }
};
