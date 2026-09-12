<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('product_option_groups', 'max_select')) {
            Schema::table('product_option_groups', function (Blueprint $table) {
                $table->unsignedInteger('max_select')->nullable()->after('price_mode');
            });
        }

        if (!Schema::hasColumn('option_templates', 'max_select')) {
            Schema::table('option_templates', function (Blueprint $table) {
                $table->unsignedInteger('max_select')->nullable()->after('price_mode');
            });
        }

        // Smart Backfill for Option Templates:
        // 1. Any fixed price mode template is single-select (max_select = 1)
        DB::table('option_templates')
            ->where('price_mode', 'fixed')
            ->update(['max_select' => 1]);

        // 2. Specific known single-choice templates: "Flavor Choices", "Beverage Sizes", "Hot or Iced"
        DB::table('option_templates')
            ->where(function ($query) {
                $query->where('name', 'like', '%Flavor%')
                      ->orWhere('name', 'like', '%Size%')
                      ->orWhere('name', 'like', '%Hot or Iced%');
            })
            ->update(['max_select' => 1]);

        // Smart Backfill for Product Option Groups:
        // 1. Any fixed price mode group is single-select (max_select = 1)
        DB::table('product_option_groups')
            ->where('price_mode', 'fixed')
            ->update(['max_select' => 1]);

        // 2. Any group whose name relates to flavor, size, temperature or hot/iced
        DB::table('product_option_groups')
            ->where(function ($query) {
                $query->where('name', 'like', '%Flavor%')
                      ->orWhere('name', 'like', '%Size%')
                      ->orWhere('name', 'like', '%Hot or Iced%');
            })
            ->update(['max_select' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('product_option_groups', 'max_select')) {
            Schema::table('product_option_groups', function (Blueprint $table) {
                $table->dropColumn('max_select');
            });
        }

        if (Schema::hasColumn('option_templates', 'max_select')) {
            Schema::table('option_templates', function (Blueprint $table) {
                $table->dropColumn('max_select');
            });
        }
    }
};

