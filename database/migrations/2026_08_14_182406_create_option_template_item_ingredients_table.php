<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cleans up the partially-created table from the earlier failed
        // run (identifier-too-long error), so this is safe to re-run.
        Schema::dropIfExists('option_template_item_ingredients');

        Schema::create('option_template_item_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_template_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 10, 3);
            $table->timestamps();
            $table->unique(['option_template_item_id', 'ingredient_id'], 'opt_tmpl_item_ingredient_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_template_item_ingredients');
    }
};