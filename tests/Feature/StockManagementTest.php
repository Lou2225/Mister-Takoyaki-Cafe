<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\User;
use App\Models\BranchIngredientStock;
use App\Models\StockBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;
    protected $ingredient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->branch = Branch::create(['branch_name' => 'Main Branch', 'address' => 'City']);
        $this->user = User::factory()->create([
            'role_id' => 1, // Super Admin
            'branch_id' => $this->branch->id
        ]);
        
        $this->ingredient = Ingredient::create([
            'name' => 'Takoyaki Flour',
            'unit' => 'kg', // Standard display unit
            'minimum_stock' => 5
        ]);
    }

    /** @test */
    public function it_can_initialize_stock_for_a_branch()
    {
        // 10kg should be converted to 10,000g in database
        Livewire::actingAs($this->user)
            ->test(\App\Http\Livewire\StockManagement::class)
            ->set('adjustBranchId', $this->branch->id)
            ->set('adjustIngredientId', $this->ingredient->id)
            ->set('movementType', 'in')
            ->set('movementQuantity', 10)
            ->set('inputUnit', 'kg')
            ->call('saveAdjustment');

        $this->assertDatabaseHas('branch_ingredient_stocks', [
            'branch_id' => $this->branch->id,
            'ingredient_id' => $this->ingredient->id,
            'stock_quantity' => 10000 // 10kg = 10,000g
        ]);

        $this->assertDatabaseHas('stock_batches', [
            'ingredient_id' => $this->ingredient->id,
            'branch_id' => $this->branch->id,
            'current_quantity' => 10000
        ]);
    }

    /** @test */
    public function it_can_record_waste_movement()
    {
        // Setup initial stock in GRAMS (10kg = 10,000g)
        BranchIngredientStock::create([
            'branch_id' => $this->branch->id,
            'ingredient_id' => $this->ingredient->id,
            'stock_quantity' => 10000
        ]);

        // Subtract 2kg (2,000g)
        Livewire::actingAs($this->user)
            ->test(\App\Http\Livewire\StockManagement::class)
            ->set('adjustBranchId', $this->branch->id)
            ->set('adjustIngredientId', $this->ingredient->id)
            ->set('movementType', 'waste')
            ->set('movementQuantity', 2)
            ->set('inputUnit', 'kg')
            ->call('saveAdjustment');

        $this->assertDatabaseHas('branch_ingredient_stocks', [
            'branch_id' => $this->branch->id,
            'stock_quantity' => 8000 // 10,000 - 2,000 = 8,000
        ]);
    }
}
