<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('distance_from_main', 8, 2)->default(0)->after('address');
        });
    }

    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('distance_from_main');
        });
    }
};
