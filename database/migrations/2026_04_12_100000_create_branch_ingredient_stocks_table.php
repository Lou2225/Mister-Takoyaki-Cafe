<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('branch_ingredient_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('stock_quantity', 10, 2)->default(0);
            $table->timestamps();
            
            // A branch can only have one stock record per ingredient
            $table->unique(['branch_id', 'ingredient_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('branch_ingredient_stocks');
    }
};
