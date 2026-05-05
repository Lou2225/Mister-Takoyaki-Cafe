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
        Schema::create('option_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('option_templates')->onDelete('cascade');
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('option_template_items');
    }
};
