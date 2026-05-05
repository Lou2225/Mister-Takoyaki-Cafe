<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requesting_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('source_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->decimal('requested_quantity', 10, 4);
            $table->string('unit', 20)->default('pcs');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('request_notes')->nullable();
            $table->text('response_notes')->nullable();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_requests');
    }
};
