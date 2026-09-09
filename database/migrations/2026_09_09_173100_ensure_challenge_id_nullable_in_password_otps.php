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
        Schema::table('password_otps', function (Blueprint $table) {
            // Ensure challenge_id is nullable — on some deployments the original
            // create migration created it as NOT NULL with no default, causing
            // insert failures when older code paths didn't supply the value.
            $table->uuid('challenge_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('password_otps', function (Blueprint $table) {
            //
        });
    }
};
