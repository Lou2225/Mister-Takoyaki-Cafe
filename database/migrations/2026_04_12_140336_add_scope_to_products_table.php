<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // 'global' = visible to all branches; 'branch' = only assigned branches
            $table->enum('scope', ['global', 'branch'])->default('global')->after('is_active');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
    }
};
