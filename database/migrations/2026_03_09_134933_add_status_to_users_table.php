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
        // All required columns are now in the main users table migration. No action needed.
        // You may safely delete this migration after confirming the schema.
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No action needed.
    }
};
