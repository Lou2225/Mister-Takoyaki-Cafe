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
        // Drop foreign keys and columns from ingredients
        if (Schema::hasTable('ingredients')) {
            Schema::table('ingredients', function (Blueprint $table) {
                if (Schema::hasColumn('ingredients', 'supplier_id')) {
                    $table->dropForeign(['supplier_id']);
                    $table->dropColumn('supplier_id');
                }
            });
        }

        // Drop foreign keys and columns from stock_batches
        if (Schema::hasTable('stock_batches')) {
            Schema::table('stock_batches', function (Blueprint $table) {
                if (Schema::hasColumn('stock_batches', 'supplier_id')) {
                    $table->dropForeign(['supplier_id']);
                    $table->dropColumn('supplier_id');
                }
            });
        }

        // Drop foreign keys and columns from stock_movements
        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                if (Schema::hasColumn('stock_movements', 'supplier_id')) {
                    $table->dropForeign(['supplier_id']);
                    $table->dropColumn('supplier_id');
                }
            });
        }

        // Drop the suppliers table
        Schema::dropIfExists('suppliers');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Recreate the suppliers table
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        if (Schema::hasTable('ingredients')) {
            Schema::table('ingredients', function (Blueprint $table) {
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            });
        }

        if (Schema::hasTable('stock_batches')) {
            Schema::table('stock_batches', function (Blueprint $table) {
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            });
        }

        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            });
        }
    }
};
