<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('financial_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('order_id');
            $table->string('transaction_type'); // SALE, TAX_COLLECTED, REFUND, TAX_ADJUSTMENT
            $table->string('account_type'); // SALES, TAX_LIABILITY, BUSINESS
            $table->decimal('amount', 12, 2); // Amount being recorded
            $table->decimal('tax_rate', 5, 4)->nullable(); // Tax rate used (e.g. 0.1200)
            $table->decimal('vat_amount', 12, 2)->nullable(); // VAT/Tax amount collected
            $table->string('reference_no')->nullable(); // Order reference number
            $table->text('description')->nullable(); // Transaction description
            $table->unsignedBigInteger('recorded_by')->nullable(); // User who recorded
            $table->timestamps();

            // Foreign keys
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for performance
            $table->index('branch_id');
            $table->index('order_id');
            $table->index('transaction_type');
            $table->index('account_type');
            $table->index('created_at');
            $table->unique(['order_id', 'transaction_type']); // Prevent duplicate recordings
        });
    }

    public function down()
    {
        Schema::dropIfExists('financial_ledger');
    }
};
