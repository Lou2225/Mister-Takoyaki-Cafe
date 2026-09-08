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
            if (!Schema::hasColumn('password_otps', 'purpose')) {
                $table->string('purpose')->default('forgot_password')->after('otp');
            }
            if (!Schema::hasColumn('password_otps', 'challenge_id')) {
                $table->uuid('challenge_id')->nullable()->unique()->after('purpose');
            }
        });

        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE password_otps MODIFY expires_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        } catch (\Throwable $e) {
            // Ignore if already adjusted or non-MySQL
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
