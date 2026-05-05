<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add fields for draft orders and refunds
            $table->string('status')->default('Completed')->change(); // Completed, Drafted, Void, Refunded, Partially Refunded
            $table->decimal('refunded_amount', 12, 2)->default(0)->after('discount_amount');
            $table->string('refund_reason')->nullable()->after('refunded_amount');
            $table->unsignedBigInteger('refunded_by')->nullable()->after('refund_reason');
            $table->timestamp('refunded_at')->nullable()->after('refunded_by');
            $table->text('notes')->nullable()->after('refunded_at'); // Draft notes, orders notes
            $table->string('table_number')->nullable()->after('notes'); // Table for dine-in
            
            // Foreign key for refunded_by
            $table->foreign('refunded_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index('status');
            $table->index('reference_no');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['refunded_by']);
            $table->dropIndex('status_index');
            $table->dropIndex('reference_no_index');
            $table->dropColumn([
                'refunded_amount',
                'refund_reason',
                'refunded_by',
                'refunded_at',
                'notes',
                'table_number',
            ]);
        });
    }
};
