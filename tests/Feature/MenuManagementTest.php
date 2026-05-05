<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $ingredient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role_id' => 1]);
        $this->ingredient = Ingredient::create([
            'name' => 'Flour',
            'unit' => 'kg',
            'minimum_stock' => 1
        ]);
    }

    /** @test */
    public function it_can_create_a_product_with_recipe_and_image()
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('classic_tako.jpg');

        Livewire::actingAs($this->user)
            ->test(\App\Http\Livewire\MenuManagement::class)
            ->set('name', 'Classic Takoyaki')
            ->set('price', 85)
            ->set('image', $image)
            ->set('recipeIngredients', [
                ['id' => $this->ingredient->id, 'quantity' => 0.5]
            ])
            ->call('saveProduct');

        $this->assertDatabaseHas('products', [
            'name' => 'Classic Takoyaki',
            'price' => 85
        ]);

        $product = Product::where('name', 'Classic Takoyaki')->first();
        
        $this->assertDatabaseHas('recipes', [
            'product_id' => $product->id,
            'ingredient_id' => $this->ingredient->id,
            'quantity' => 0.5
        ]);

        $this->assertNotNull($product->image);
        Storage::disk('public')->assertExists($product->image);
    }
}
