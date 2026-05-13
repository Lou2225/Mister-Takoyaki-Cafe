<?php

namespace Tests\Feature;

use App\Livewire\DashboardOverview;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\IngredientCost;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardOverviewHealthIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-12 12:00:00');
        $this->createMinimalTables();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** @test */
    public function it_calculates_health_index_from_inventory_value_and_respects_date_filters()
    {
        $branch = Branch::create([
            'branch_name' => 'Makati Central',
            'address' => 'Metro Manila',
        ]);

        $powder = Ingredient::create([
            'name' => 'Tempura Powder',
            'unit' => 'g',
            'minimum_stock' => 0,
            'cost' => 0.01,
        ]);

        $tray = Ingredient::create([
            'name' => 'Takoyaki Tray',
            'unit' => 'pcs',
            'minimum_stock' => 0,
            'cost' => 5.00,
        ]);

        IngredientCost::create([
            'branch_id' => $branch->id,
            'ingredient_id' => $powder->id,
            'cost_per_base_unit' => 0.01,
            'unit_cost' => 0.01,
        ]);

        IngredientCost::create([
            'branch_id' => $branch->id,
            'ingredient_id' => $tray->id,
            'cost_per_base_unit' => 5.00,
            'unit_cost' => 5.00,
        ]);

        StockMovement::insert([
            [
                'branch_id' => $branch->id,
                'ingredient_id' => $powder->id,
                'type' => 'order',
                'quantity' => 100,
                'created_at' => '2026-05-01 09:00:00',
                'updated_at' => '2026-05-01 09:00:00',
            ],
            [
                'branch_id' => $branch->id,
                'ingredient_id' => $powder->id,
                'type' => 'waste',
                'quantity' => 10,
                'created_at' => '2026-05-01 11:00:00',
                'updated_at' => '2026-05-01 11:00:00',
            ],
            [
                'branch_id' => $branch->id,
                'ingredient_id' => $tray->id,
                'type' => 'order',
                'quantity' => 1,
                'created_at' => '2026-05-10 10:00:00',
                'updated_at' => '2026-05-10 10:00:00',
            ],
            [
                'branch_id' => $branch->id,
                'ingredient_id' => $tray->id,
                'type' => 'waste',
                'quantity' => 1,
                'created_at' => '2026-05-10 12:00:00',
                'updated_at' => '2026-05-10 12:00:00',
            ],
        ]);

        $component = new DashboardOverview();
        $component->isSuperAdmin = true;
        $component->selectedBranchId = $branch->id;

        $allTime = $this->inventoryIntel($component);

        $this->assertEquals(6.00, $allTime['sales_value']);
        $this->assertEquals(5.10, $allTime['waste_value']);
        $this->assertEquals(85.00, $allTime['variance_pct']);
        $this->assertEquals(15.00, $allTime['health_score']);

        $component->startDate = '2026-05-10';
        $component->endDate = '2026-05-10';

        $filtered = $this->inventoryIntel($component);

        $this->assertEquals(5.00, $filtered['sales_value']);
        $this->assertEquals(5.00, $filtered['waste_value']);
        $this->assertEquals(100.00, $filtered['variance_pct']);
        $this->assertEquals(0.00, $filtered['health_score']);
    }

    /** @test */
    public function it_respects_branch_filters_when_building_the_health_index()
    {
        $manila = Branch::create([
            'branch_name' => 'Manila HQ',
            'address' => 'Manila',
        ]);

        $qc = Branch::create([
            'branch_name' => 'Quezon City',
            'address' => 'Quezon City',
        ]);

        $ingredient = Ingredient::create([
            'name' => 'Takoyaki Sauce',
            'unit' => 'ml',
            'minimum_stock' => 0,
            'cost' => 1.00,
        ]);

        IngredientCost::insert([
            [
                'branch_id' => $manila->id,
                'ingredient_id' => $ingredient->id,
                'cost_per_base_unit' => 1.00,
                'unit_cost' => 1.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'branch_id' => $qc->id,
                'ingredient_id' => $ingredient->id,
                'cost_per_base_unit' => 1.00,
                'unit_cost' => 1.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        StockMovement::insert([
            [
                'branch_id' => $manila->id,
                'ingredient_id' => $ingredient->id,
                'type' => 'order',
                'quantity' => 10,
                'created_at' => '2026-05-11 09:00:00',
                'updated_at' => '2026-05-11 09:00:00',
            ],
            [
                'branch_id' => $manila->id,
                'ingredient_id' => $ingredient->id,
                'type' => 'waste',
                'quantity' => 5,
                'created_at' => '2026-05-11 10:00:00',
                'updated_at' => '2026-05-11 10:00:00',
            ],
            [
                'branch_id' => $qc->id,
                'ingredient_id' => $ingredient->id,
                'type' => 'order',
                'quantity' => 10,
                'created_at' => '2026-05-11 11:00:00',
                'updated_at' => '2026-05-11 11:00:00',
            ],
        ]);

        $component = new DashboardOverview();
        $component->isSuperAdmin = true;

        $component->selectedBranchId = $manila->id;
        $manilaIntel = $this->inventoryIntel($component);
        $this->assertEquals(50.00, $manilaIntel['variance_pct']);
        $this->assertEquals(50.00, $manilaIntel['health_score']);

        $component->selectedBranchId = $qc->id;
        $qcIntel = $this->inventoryIntel($component);
        $this->assertEquals(0.00, $qcIntel['variance_pct']);
        $this->assertEquals(100.00, $qcIntel['health_score']);
    }

    private function inventoryIntel(DashboardOverview $component): array
    {
        return \Closure::bind(function () {
            return $this->getInventoryIntelligence();
        }, $component, DashboardOverview::class)();
    }

    private function createMinimalTables(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('ingredient_costs');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('branches');

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_name');
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit');
            $table->decimal('minimum_stock', 10, 2)->default(0);
            $table->decimal('cost', 10, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('ingredient_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ingredient_id');
            $table->unsignedBigInteger('branch_id');
            $table->decimal('unit_cost', 10, 4)->default(0);
            $table->decimal('cost_per_base_unit', 10, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('ingredient_id');
            $table->string('type');
            $table->decimal('quantity', 10, 2);
            $table->timestamps();
        });
    }
}
