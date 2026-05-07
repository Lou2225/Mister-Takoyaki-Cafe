<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductCategory;
use App\Models\IngredientCategory;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Traits\HandlesValidations;
use App\Helpers\ValidationHelper;

class CategoryManagement extends Component
{
    use WithPagination, HandlesValidations;

    public string $panel = 'list'; // 'list' | 'detail'
    public string $filterType = 'all'; // 'all' | 'product' | 'ingredient'
    public string $search = '';
    public int $perPage = 5;
    
    // Form fields
    public ?int $editCategoryId = null;
    public ?string $editCategoryType = null; // 'product' | 'ingredient'
    public string $name = '';
    public string $description = '';
    public string $icon = 'tag';

    // Deletion targets
    public ?int $deleteTargetId = null;
    public string $deleteTargetName = '';

    protected $queryString = [
        'filterType' => ['except' => 'all', 'as' => 'type'],
        'search' => ['except' => ''],
    ];

    public function mount()
    {
        if (!auth()->user() || auth()->user()->role_id !== 1) {
            abort(403, 'Unauthorized access to the Category Catalog. This area is reserved for Super Administrators.');
        }
    }

    public function updatedFilterType()
    {
        $this->resetPage();
        $this->resetForm();
    }

    public function updatedName()
    {
        $table = $this->editCategoryType === 'product' ? 'product_categories' : 'ingredient_categories';
        $this->validateFieldLive('name', ['required', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME, Rule::unique($table, 'name')->ignore($this->editCategoryId)], ValidationHelper::commonMessages());
    }

    public function updatedDescription()
    {
        $this->validateFieldLive('description', ['nullable', 'string', 'max:500'], ValidationHelper::commonMessages());
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function resetForm()
    {
        $this->editCategoryId = null;
        $this->editCategoryType = null;
        $this->name = '';
        $this->description = '';
        $this->icon = 'tag';
        $this->resetValidation();
    }

    public function getSelectedCategoryAssociatedCountProperty()
    {
        if (!$this->editCategoryId || !$this->editCategoryType) return 0;
        
        $model = $this->editCategoryType === 'product' ? ProductCategory::class : IngredientCategory::class;
        $category = $model::find($this->editCategoryId);
        
        if (!$category) return 0;

        return $this->editCategoryType === 'product' 
            ? $category->products()->count() 
            : $category->ingredients()->count();
    }

    public function getSelectedCategoryRefIdProperty()
    {
        if (!$this->editCategoryId || !$this->editCategoryType) return 'PENDING';
        
        $prefix = $this->editCategoryType === 'product' ? 'PRD-CAT' : 'ING-CAT';
        return $prefix . '-' . str_pad($this->editCategoryId, 4, '0', STR_PAD_LEFT);
    }

    public function getKpiStatsProperty()
    {
        return [
            'product_count' => ProductCategory::count(),
            'ingredient_count' => IngredientCategory::count(),
            'total_count' => ProductCategory::count() + IngredientCategory::count(),
        ];
    }

    public function showCreate(string $type = 'product')
    {
        $this->resetForm();
        $this->editCategoryType = $type;
        $this->panel = 'detail';
        $this->dispatchBrowserEvent('switch-panel', ['panel' => 'detail']);
    }

    public function selectCategory(int $id, string $type)
    {
        $this->resetForm();
        $this->editCategoryId = $id;
        $this->editCategoryType = $type;
        
        $model = $type === 'product' ? ProductCategory::class : IngredientCategory::class;
        $category = $model::findOrFail($id);
        
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->icon = $category->icon ?? 'tag';
        
        $this->panel = 'detail';
        $this->dispatchBrowserEvent('switch-panel', ['panel' => 'detail']);
    }

    public function backToList()
    {
        $this->panel = 'list';
        $this->resetForm();
    }

    /**
     * Pre-validate before showing confirmation modal.
     */
    public function validateBeforeSaveCategory()
    {
        $table = $this->editCategoryType === 'product' ? 'product_categories' : 'ingredient_categories';

        $rules = [
            'name' => ['required', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME, Rule::unique($table, 'name')->ignore($this->editCategoryId)],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
        ];

        $this->validateBeforeModal($rules, ValidationHelper::commonMessages(), 'confirm-save-category');
    }

    public function saveCategory()
    {
        $model = $this->editCategoryType === 'product' ? ProductCategory::class : IngredientCategory::class;
        $table = $this->editCategoryType === 'product' ? 'product_categories' : 'ingredient_categories';

        $rules = [
            'name' => ['required', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME, Rule::unique($table, 'name')->ignore($this->editCategoryId)],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
        ];

        $this->validate($rules, ValidationHelper::commonMessages());

        if ($this->editCategoryId) {
            $category = $model::findOrFail($this->editCategoryId);
            $category->update([
                'name' => $this->name,
                'description' => $this->description,
                'icon' => $this->icon,
            ]);
            $msg = 'Category updated successfully.';
        } else {
            $model::create([
                'name' => $this->name,
                'description' => $this->description,
                'icon' => $this->icon,
            ]);
            $msg = 'Category created successfully.';
        }

        $this->dispatchBrowserEvent('notify', ['type' => 'success', 'message' => $msg]);
        $this->dispatchBrowserEvent('close-modal', 'confirm-save-category');
        $this->panel = 'list';
        $this->dispatchBrowserEvent('switch-panel', ['panel' => 'list']);
        $this->resetPage();
        $this->resetForm();
    }

    public function confirmDelete(int $id, string $type)
    {
        $this->deleteTargetId = $id;
        $this->editCategoryType = $type;
        $model = $type === 'product' ? ProductCategory::class : IngredientCategory::class;
        $category = $model::findOrFail($id);
        $this->deleteTargetName = $category->name;

        $this->dispatchBrowserEvent('open-modal', ['name' => 'confirm-delete-category']);
    }

    public function deleteCategory()
    {
        if (!$this->deleteTargetId) return;

        $model = $this->editCategoryType === 'product' ? ProductCategory::class : IngredientCategory::class;
        $category = $model::findOrFail($this->deleteTargetId);
        
        // Safety check: restricted deletion
        $count = $this->editCategoryType === 'product' ? $category->products()->count() : $category->ingredients()->count();
        
        if ($count > 0) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error', 
                'message' => "Cannot delete category. It still has $count assigned " . ($this->editCategoryType === 'product' ? 'products' : 'ingredients') . "."
            ]);
            $this->dispatchBrowserEvent('close-modal', 'confirm-delete-category');
            return;
        }

        $category->delete();
        $this->dispatchBrowserEvent('notify', ['type' => 'success', 'message' => 'Category removed.']);
        $this->dispatchBrowserEvent('close-modal', 'confirm-delete-category');
        $this->panel = 'list';
        $this->dispatchBrowserEvent('switch-panel', ['panel' => 'list']);
        $this->resetPage();
        $this->resetForm();
        $this->deleteTargetId = null;
        $this->deleteTargetName = '';
    }

    private function updateHeader()
    {
        $this->emit('setHeader', [
            'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
            'title' => 'Category Management Hub',
            'subtitle' => 'Global Category Catalog - Sorting affects all branches',
            'breadcrumbs' => [
                ['label' => 'Management', 'url' => '#'],
                ['label' => 'Categories', 'url' => route('categories.index')],
            ]
        ]);
    }

    public function render()
    {
        $this->updateHeader();
        
        if ($this->filterType === 'product') {
            $categories = ProductCategory::query()
                ->withCount('products as associated_count')
                ->select('id', 'name', 'description', 'icon', 'created_at', DB::raw("'product' as cat_type"))
                ->where('name', 'like', "%{$this->search}%")
                ->orderBy('name', 'asc')
                ->paginate($this->perPage);
        } elseif ($this->filterType === 'ingredient') {
            $categories = IngredientCategory::query()
                ->withCount('ingredients as associated_count')
                ->select('id', 'name', 'description', 'icon', 'created_at', DB::raw("'ingredient' as cat_type"))
                ->where('name', 'like', "%{$this->search}%")
                ->orderBy('name', 'asc')
                ->paginate($this->perPage);
        } else {
            // Merge both for 'all' with counts
            $productQuery = ProductCategory::query()
                ->select([
                    'id',
                    'name',
                    'description',
                    'icon',
                    'created_at',
                    DB::raw("'product' as cat_type"),
                    DB::raw('(select count(*) from products where products.category_id = product_categories.id) as associated_count')
                ])
                ->where('name', 'like', "%{$this->search}%");

            $ingredientQuery = IngredientCategory::query()
                ->select([
                    'id',
                    'name',
                    'description',
                    'icon',
                    'created_at',
                    DB::raw("'ingredient' as cat_type"),
                    DB::raw('(select count(*) from ingredients where ingredients.category_id = ingredient_categories.id) as associated_count')
                ])
                ->where('name', 'like', "%{$this->search}%");

            $unifiedQuery = $productQuery->union($ingredientQuery);
            
            $categories = DB::table(DB::raw("({$unifiedQuery->toSql()}) as unified"))
                ->mergeBindings($unifiedQuery->getQuery())
                ->orderBy('name', 'asc')
                ->paginate($this->perPage);
        }

        return view('livewire.category-management', [
            'categories' => $categories
        ])->layout('layouts.app');
    }
}
