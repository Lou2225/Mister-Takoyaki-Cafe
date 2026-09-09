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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
    
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();
    
            $table->foreignId('order_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
    
            $table->foreignId('order_item_id')
                ->nullable();
    
            $table->unsignedTinyInteger('rating')
                ->comment('Rating between 1 and 5');
    
            $table->text('comment')->nullable();
    
            $table->timestamps();
    
            $table->index(['product_id', 'rating']);
            $table->index(['user_id', 'product_id']);
            $table->index('order_id');
            $table->index('order_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
