<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchIngredientStock;
use App\Models\Ingredient;
use App\Models\Modifier;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStockAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;
    protected User $user;
    protected Product $product;
    protected Ingredient $ingredient;
    protected ProductOption $option;
    protected Modifier $modifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'branch_name' => 'Main Branch',
            'branch_code' => 'MAIN',
            'address' => json_encode(['formatted' => 'Main St']),
            'status' => 1,
        ]);

        $this->user = User::factory()->create([
            'role_id' => 1,
            'branch_id' => $this->branch->id,
        ]);

        $this->ingredient = Ingredient::create([
            'name' => 'Flour',
            'unit' => 'kg',
            'minimum_stock' => 1,
        ]);

        BranchIngredientStock::create([
            'branch_id' => $this->branch->id,
            'ingredient_id' => $this->ingredient->id,
            'stock_quantity' => 10,
        ]);

        $this->product = Product::create([
            'name' => 'Takoyaki',
            'price' => 100,
            'category_id' => null,
            'is_active' => true,
            'scope' => 'global',
        ]);

        Recipe::create([
            'product_id' => $this->product->id,
            'ingredient_id' => $this->ingredient->id,
            'quantity' => 1,
        ]);

        $group = ProductOptionGroup::create([
            'product_id' => $this->product->id,
            'name' => 'Serving',
            'price_mode' => 'fixed',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $this->option = ProductOption::create([
            'group_id' => $group->id,
            'name' => 'Regular',
            'price' => 0,
            'cost' => 0,
            'is_default' => true,
            'sort_order' => 1,
        ]);

        $this->modifier = Modifier::create([
            'name' => 'Extra Cheese',
            'price' => 15,
            'is_active' => true,
        ]);

        $this->product->modifiers()->attach($this->modifier->id);

        Recipe::create([
            'product_id' => $this->product->id,
            'ingredient_id' => $this->ingredient->id,
            'quantity' => 1,
            'modifier_id' => $this->modifier->id,
        ]);
    }

    public function test_it_blocks_an_order_when_quantity_exceeds_available_stock(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'branch_id' => $this->branch->id,
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => 11,
                'options' => [$this->option->id],
                'modifiers' => [$this->modifier->id],
            ]],
            'payment_method' => 'COD',
            'order_type' => 'Pickup',
            'customer_name' => 'Alice',
            'customer_phone' => '09171234567',
            'delivery_address' => 'Address',
            'notes' => 'Test order',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_it_merges_duplicate_product_lines_with_the_same_options_and_modifiers(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'branch_id' => $this->branch->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'options' => [$this->option->id],
                    'modifiers' => [$this->modifier->id],
                ],
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'options' => [$this->option->id],
                    'modifiers' => [$this->modifier->id],
                ],
            ],
            'payment_method' => 'COD',
            'order_type' => 'Pickup',
            'customer_name' => 'Alice',
            'customer_phone' => '09171234567',
            'delivery_address' => 'Address',
            'notes' => 'Test order',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);

        $order = Order::first();
        $this->assertSame(3, (int) $order->items()->first()->quantity);
    }
}
