<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add composite indexes for dashboard query performance.
     * These turn full-table scans into targeted index seeks on the
     * hot paths: financial orders filter, stock movement queries,
     * ingredient cost lookups, and FEFO batch reads.
     */
    public function up(): void
    {
        // ── orders ─────────────────────────────────────────────────────────
        // Dashboard always filters: branch_id + status IN (...) + created_at range
        Schema::table('orders', function (Blueprint $table) {
            if (!$this->indexExists('orders', 'idx_orders_branch_status_created')) {
                $table->index(['branch_id', 'status', 'created_at'], 'idx_orders_branch_status_created');
            }
            // Super-admin "all branches" queries: status + date only
            if (!$this->indexExists('orders', 'idx_orders_status_created')) {
                $table->index(['status', 'created_at'], 'idx_orders_status_created');
            }
        });

        // ── order_items ─────────────────────────────────────────────────────
        // BestSellers JOIN: order_items.order_id + product_id groupBy
        Schema::table('order_items', function (Blueprint $table) {
            if (!$this->indexExists('order_items', 'idx_order_items_order_product')) {
                $table->index(['order_id', 'product_id'], 'idx_order_items_order_product');
            }
        });

        // ── stock_movements ─────────────────────────────────────────────────
        // Inventory Intelligence + waste queries: branch_id + type + created_at
        Schema::table('stock_movements', function (Blueprint $table) {
            if (!$this->indexExists('stock_movements', 'idx_stock_movements_branch_type_created')) {
                $table->index(['branch_id', 'type', 'created_at'], 'idx_stock_movements_branch_type_created');
            }
            // Velocity groupBy ingredient_id + type
            if (!$this->indexExists('stock_movements', 'idx_stock_movements_ingredient_type')) {
                $table->index(['ingredient_id', 'type', 'created_at'], 'idx_stock_movements_ingredient_type');
            }
        });

        // ── stock_batches ────────────────────────────────────────────────────
        // FEFO reads: branch_id + ingredient_id + expiry_date + current_quantity > 0
        Schema::table('stock_batches', function (Blueprint $table) {
            if (!$this->indexExists('stock_batches', 'idx_stock_batches_fefo')) {
                $table->index(['branch_id', 'ingredient_id', 'current_quantity', 'expiry_date'], 'idx_stock_batches_fefo');
            }
        });

        // ── ingredient_costs ─────────────────────────────────────────────────
        // Cost map build: WHERE branch_id = ? (already has FK, but composite
        // with ingredient_id avoids a secondary lookup for the grouped map)
        Schema::table('ingredient_costs', function (Blueprint $table) {
            if (!$this->indexExists('ingredient_costs', 'idx_ingredient_costs_branch_ingredient')) {
                $table->index(['branch_id', 'ingredient_id'], 'idx_ingredient_costs_branch_ingredient');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_branch_status_created');
            $table->dropIndex('idx_orders_status_created');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_items_order_product');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_stock_movements_branch_type_created');
            $table->dropIndex('idx_stock_movements_ingredient_type');
        });

        Schema::table('stock_batches', function (Blueprint $table) {
            $table->dropIndex('idx_stock_batches_fefo');
        });

        Schema::table('ingredient_costs', function (Blueprint $table) {
            $table->dropIndex('idx_ingredient_costs_branch_ingredient');
        });
    }

    /**
     * Check whether a named index already exists on a table.
     * MariaDB 10.4 does not have IF NOT EXISTS for ADD INDEX.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );

        return count($result) > 0;
    }
};

