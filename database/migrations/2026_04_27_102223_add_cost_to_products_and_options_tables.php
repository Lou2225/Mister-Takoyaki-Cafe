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
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost', 15, 2)->default(0)->after('price');
        });
        Schema::table('product_options', function (Blueprint $table) {
            $table->decimal('cost', 15, 2)->default(0)->after('price');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cost');
        });
        Schema::table('product_options', function (Blueprint $table) {
            $table->dropColumn('cost');
        });
    }
};
