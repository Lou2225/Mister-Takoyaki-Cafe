<?php

namespace Tests\Unit;

use App\Http\Livewire\CategoryManagement;
use App\Models\ProductCategory;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    /** @test */
    public function it_clears_the_selected_category_when_the_form_is_reset()
    {
        $component = new CategoryManagement();
        $component->editCategoryId = 5;
        $component->name = 'Drinks';
        $component->description = 'Beverages';
        $component->icon = 'coffee';
        $component->selectedCategory = new ProductCategory([
            'name' => 'Drinks',
        ]);

        $component->resetForm();

        $this->assertNull($component->editCategoryId);
        $this->assertSame('', $component->name);
        $this->assertSame('', $component->description);
        $this->assertSame('tag', $component->icon);
        $this->assertNull($component->selectedCategory);
    }

    /** @test */
    public function it_returns_zero_associated_items_when_the_selected_model_does_not_match_the_active_tab()
    {
        $component = new CategoryManagement();
        $component->activeTab = 'ingredient';
        $component->selectedCategory = new ProductCategory([
            'name' => 'Drinks',
        ]);

        $this->assertSame(0, $component->getSelectedCategoryAssociatedCountProperty());
    }
}
