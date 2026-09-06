<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('rider_latitude', 10, 7)->nullable()->after('rider_phone');
            $table->decimal('rider_longitude', 10, 7)->nullable()->after('rider_latitude');
            $table->timestamp('location_updated_at')->nullable()->after('rider_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['rider_latitude', 'rider_longitude', 'location_updated_at']);
        });
    }
};