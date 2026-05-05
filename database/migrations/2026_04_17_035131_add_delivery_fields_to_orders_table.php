<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('user_id')->constrained('users');
            $table->string('customer_name')->nullable()->after('customer_id');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->text('delivery_address')->nullable()->after('customer_phone');
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('total_amount');
            $table->text('delivery_notes')->nullable()->after('delivery_fee');
            $table->foreignId('rider_id')->nullable()->after('delivery_notes')->constrained('users');
            $table->string('source')->default('POS')->after('rider_id'); // POS, App, Web
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn(['customer_name', 'customer_phone', 'delivery_address', 'delivery_fee', 'delivery_notes', 'source']);
            $table->dropConstrainedForeignId('rider_id');
        });
    }
};
