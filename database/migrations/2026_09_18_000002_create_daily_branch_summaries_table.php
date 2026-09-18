<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the daily_branch_summary rollup table.
     *
     * Each row stores one day's pre-aggregated financial snapshot for one branch.
     * The dashboard reads from this table for all past-day queries, avoiding
     * full re-scans of the orders + order_items + recipes chain on every render.
     *
     * TODAY is always queried live from the indexed orders table, so the summary
     * only needs to cover completed calendar days.
     */
    public function up(): void
    {
        Schema::create('daily_branch_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->date('summary_date');

            // Revenue
            $table->integer('order_count')->default(0);
            $table->decimal('gross_sales', 14, 4)->default(0);
            $table->decimal('net_sales', 14, 4)->default(0);
            $table->decimal('total_discounts', 14, 4)->default(0);
            $table->decimal('refunds', 14, 4)->default(0);
            $table->decimal('delivery_fees', 14, 4)->default(0);
            $table->decimal('service_charges', 14, 4)->default(0);

            // COGS & Profit
            $table->decimal('total_cogs', 14, 4)->default(0);
            $table->decimal('waste_cost', 14, 4)->default(0);
            $table->decimal('gross_profit', 14, 4)->default(0);

            // Payment breakdown (for chart donut)
            $table->integer('cash_orders')->default(0);
            $table->decimal('cash_total', 14, 4)->default(0);
            $table->integer('gcash_orders')->default(0);
            $table->decimal('gcash_total', 14, 4)->default(0);

            // Channel breakdown (for chart donut)
            $table->integer('dine_in_orders')->default(0);
            $table->integer('take_out_orders')->default(0);
            $table->integer('delivery_orders')->default(0);

            // Metadata
            $table->boolean('is_partial')->default(false); // true while day is still open
            $table->timestamp('computed_at')->nullable();

            $table->timestamps();

            // One row per branch per day — ON DUPLICATE KEY UPDATE will use this
            $table->unique(['branch_id', 'summary_date'], 'udx_daily_summary_branch_date');

            // Range-query index for the dashboard's date filter
            $table->index(['branch_id', 'summary_date'], 'idx_daily_summary_branch_date');
            $table->index('summary_date', 'idx_daily_summary_date');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_branch_summaries');
    }
};

