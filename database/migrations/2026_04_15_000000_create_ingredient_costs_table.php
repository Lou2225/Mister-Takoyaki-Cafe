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
        Schema::create('ingredient_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->decimal('unit_cost', 10, 4)->default(0); // Cost per unit of ingredient
            $table->decimal('cost_per_base_unit', 10, 4)->nullable(); // For reference/calculation
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Unique constraint to prevent duplicate costs per ingredient per branch
            $table->unique(['ingredient_id', 'branch_id']);
            
            // Indexes for faster queries
            $table->index('branch_id');
            $table->index('ingredient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_costs');
    }
};
