<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add review tracking columns to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'review_token')) {
                $table->string('review_token', 64)->nullable()->unique()->after('source');
            }
            if (!Schema::hasColumn('orders', 'review_scan_count')) {
                $table->unsignedInteger('review_scan_count')->default(0)->after('review_token');
            }
            if (!Schema::hasColumn('orders', 'review_last_scanned_at')) {
                $table->timestamp('review_last_scanned_at')->nullable()->after('review_scan_count');
            }
        });

        // 2. Add order and device tracking columns to customer_reviews table
        Schema::table('customer_reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_reviews', 'order_id')) {
                $table->foreignId('order_id')
                    ->nullable()
                    ->after('branch_id')
                    ->constrained('orders')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('customer_reviews', 'device_id')) {
                $table->string('device_id', 64)->nullable()->index()->after('order_id');
            }
            if (!Schema::hasColumn('customer_reviews', 'device_fingerprint')) {
                $table->string('device_fingerprint', 64)->nullable()->after('device_id');
            }
            if (!Schema::hasColumn('customer_reviews', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('contact_number');
            }
            if (!Schema::hasColumn('customer_reviews', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }

            // Ensure unique compound constraint: 1 review per device per receipt
            $table->unique(['order_id', 'device_id'], 'unique_order_device_review');
        });

        // 3. Backfill existing orders with unique review tokens
        DB::table('orders')->whereNull('review_token')->orderBy('id')->chunk(500, function ($orders) {
            foreach ($orders as $order) {
                DB::table('orders')->where('id', $order->id)->update([
                    'review_token' => Str::random(32),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('customer_reviews', 'order_id')) {
                $table->dropForeign(['order_id']);
            }
            $table->dropUnique('unique_order_device_review');
            $table->dropColumn([
                'order_id',
                'device_id',
                'device_fingerprint',
                'ip_address',
                'user_agent',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'review_token',
                'review_scan_count',
                'review_last_scanned_at',
            ]);
        });
    }
};

