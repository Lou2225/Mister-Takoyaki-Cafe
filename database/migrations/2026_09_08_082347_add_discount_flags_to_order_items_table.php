<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('apply_regular_discount')->default(false)->after('special_instructions');
            $table->boolean('apply_senior_discount')->default(false)->after('apply_regular_discount');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['apply_regular_discount', 'apply_senior_discount']);
        });
    }
};