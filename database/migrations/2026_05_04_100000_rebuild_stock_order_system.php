<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Drop old partial implementation ───────────────────────────
        Schema::dropIfExists('stock_transfer_requests');

        // ── 1. Stock Orders (batch header) ────────────────────────────
        Schema::create('stock_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 30)->unique()->index();
            $table->foreignId('requesting_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('source_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->enum('priority', ['normal', 'urgent', 'critical'])->default('normal');
            $table->enum('status', [
                'pending', 'approved', 'preparing',
                'in_transit', 'delivered', 'rejected', 'cancelled'
            ])->default('pending');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_remarks')->nullable();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        // ── 2. Stock Order Items (line items) ─────────────────────────
        Schema::create('stock_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('requested_quantity', 10, 4);
            $table->decimal('approved_quantity', 10, 4)->nullable();
            $table->string('unit', 20)->default('pcs');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── 3. Stock Transfers (execution records) ────────────────────
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_order_id')->constrained()->cascadeOnDelete();
            $table->string('reference_no', 30)->unique()->index();
            $table->foreignId('from_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('to_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('transferred_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('transferred_at')->useCurrent();
            $table->timestamps();
        });

        // ── 4. Stock Transfer Items (per-ingredient movement) ─────────
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 10, 4);
            $table->string('unit', 20)->default('pcs');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_order_items');
        Schema::dropIfExists('stock_orders');

        // Restore old table (minimal structure for rollback safety)
        Schema::create('stock_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->string('batch_reference')->nullable()->index();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requesting_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('source_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->decimal('requested_quantity', 10, 4);
            $table->string('unit', 20)->default('pcs');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('delivery_fee', 10, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('request_notes')->nullable();
            $table->text('response_notes')->nullable();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }
};
