<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            // The human-readable unit name, e.g. "bottle", "box", "sack", "case"
            $table->string('unit_name', 50);
            // Pre-computed: how many base units (g / ml / pcs) are in ONE of this unit.
            // Admin enters this directly or uses the chain calculator in the UI.
            // e.g. bottle=750, box=18000 (24 bottles × 750ml), case=108000 (6 boxes × 18000ml)
            $table->decimal('qty_in_base', 14, 4);
            // Purchase price for one of this unit
            $table->decimal('price_per_unit', 12, 2)->default(0);
            // Display order — smallest unit first (0 = first shown)
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['ingredient_id', 'unit_name']);
            $table->index('ingredient_id');
        });

        // ── Backward Compatibility: Migrate existing bulk_unit/bulk_qty/bulk_price data ──
        // Any ingredient that already has a bulk_unit configured gets seeded into the new table.
        $ingredients = DB::table('ingredients')
            ->whereNotNull('bulk_unit')
            ->where('bulk_unit', '!=', '')
            ->where('bulk_qty', '>', 0)
            ->get();

        foreach ($ingredients as $ing) {
            DB::table('ingredient_unit_conversions')->insertOrIgnore([
                'ingredient_id'  => $ing->id,
                'unit_name'      => strtolower($ing->bulk_unit),
                'qty_in_base'    => (float) $ing->bulk_qty,
                'price_per_unit' => (float) ($ing->bulk_price ?? 0),
                'sort_order'     => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_unit_conversions');
    }
};
