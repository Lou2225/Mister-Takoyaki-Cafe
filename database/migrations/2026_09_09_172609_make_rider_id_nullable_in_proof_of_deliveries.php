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
        Schema::table('proof_of_deliveries', function (Blueprint $table) {
            //
            // Drop existing FK before modifying the column
            $table->dropForeign(['rider_id']);
            // Make rider_id nullable so a deleted rider doesn't block the delete
            $table->unsignedBigInteger('rider_id')->nullable()->change();
            // Re-add FK with nullOnDelete so it's automatically nulled on user delete
            $table->foreign('rider_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proof_of_deliveries', function (Blueprint $table) {
            //
            $table->dropForeign(['rider_id']);
            $table->unsignedBigInteger('rider_id')->nullable(false)->change();
            $table->foreign('rider_id')->references('id')->on('users');
        });
    }
};
