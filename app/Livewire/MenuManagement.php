<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Branch;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Models\OptionTemplate;
use App\Models\OptionTemplateItem;
use Illuminate\Validation\Rule;
use App\Traits\HandlesValidations;
use App\Helpers\ValidationHelper;
use App\Helpers\StockHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MenuManagement extends Component
{
    use WithPagination, WithFileUploads, HandlesValidations;

    public function importOptionTemplate(int $templateId)
    {
        $template = OptionTemplate::with('items')->find($templateId);
        if (!$template) return;

        // Duplicate check
        foreach ($this->optionGroups as $group) {
            if (strtolower($group['name']) === strtolower($template->name)) {
                $this->dispatch('close-modal', name: 'import-template-library');
                $this->dispatch('notify', type: 'error', message: "The '{$template->name}' template is already added to this product.");
                return;
            }
        }

        $options = [];
        foreach ($template->items as $item) {
            $options[] = [
                'name'       => $item->name,
                'price'      => $item->price,
                'is_default' => $item->is_default,
                'sort_order' => count($options)
            ];
        }

        $this->optionGroups[] = [
            'name'        => $template->name,
            'price_mode'  => $template->price_mode,
            'is_required' => $template->is_required,
            'options'     => $options
        ];

        $this->dispatch('close-modal', name: 'import-template-library');
        $this->dispatch('notify', type: 'success', message: "Imported '{$template->name}' template.");
    }

    public function saveGroupToLibrary(int $index)
    {
        $groupData = $this->optionGroups[$index] ?? null;
        if (!$groupData) return;

        // Duplicate check in Library
        if (OptionTemplate::where('name', $groupData['name'])->exists()) {
            $this->dispatch('notify', type: 'error', message: "A template named '{$groupData['name']}' already exists in the library.");
            return;
        }

        DB::transaction(function () use ($groupData) {
            $template = OptionTemplate::create([
                'name'        => $groupData['name'],
                'price_mode'  => $groupData['price_mode'],
                'is_required' => $groupData['is_required'],
            ]);

            foreach ($groupData['options'] as $oData) {
                $template->items()->create([
                    'name'       => $oData['name'],
                    'price'      => $oData['price'],
                    'is_default' => $oData['is_default'],
                ]);
            }
        });

        $this->dispatch('notify', type: 'success', message: "Group '{$groupData['name']}' saved to library.");
    }

    // ── Filters & Display ─────────────────────────────────────────
    public $search = '';
    public $selectedCategoryId = '';
    public $selectedBranchId = '';
    public $perPage = 5;
    public $view = 'table';
    public string $newCategoryStation = 'kitchen'; // Default
    public string $categorySearch = '';
    public string $categoryFilterSearch = '';
    public $panel = 'list';
    public $mode = 'list';

    // ── Deletion State ────────────────────────────────────────────
    public ?int $deleteTargetId = null;
    public string $deleteTargetName = '';

    // ── Form: Product (Create/Edit) ───────────────────────────────
    public ?int $editProductId = null;
    public string $name = '';
    public int|string|null $categoryId = null;
    public string $description = '';
    public string $price = '';
    public bool $isActive = true;
    /** @var TemporaryUploadedFile|null */
    public $image = null;
    public ?string $existingImage = null;
    public int $sortOrder = 0;
    public array $recipeIngredients = [];
    public string $newCategoryName = '';

    // ── Option Groups ─────────────────────────────────────────────
    public $optionGroups = [];
    public $newGroupName = '';
    public $newGroupPriceMode = 'additive';
    public $newGroupIsRequired = false;
    public $activeGroupIndex = 0;

    // ── Recipe ────────────────────────────────────────────────────
    public $newIngredientId = '';
    public $newIngredientQty = '';
    public $newIngredientUnit = '';
    public $newIngredientOwner = 'base';

    // ── Tab State ─────────────────────────────────────────────────
    public $activeTab = 'basic';

    protected $queryString = [
        'search'             => ['except' => '', 'as' => 'm_search'],
        'selectedCategoryId' => ['except' => '', 'as' => 'm_cat'],
        'selectedBranchId'   => ['except' => '', 'as' => 'm_branch'],
        'view'               => ['except' => 'table', 'as' => 'm_view'],
        'perPage'            => ['except' => 5, 'as' => 'm_pp'],
    ];

    protected $listeners = ['refresh' => '$refresh'];

    public function mount()
    {
        if (!auth()->user() || !in_array(auth()->user()->role_id, [1, 2])) {
            abort(403, 'Unauthorized access to menu management.');
        }

        if (auth()->user()->isAdmin()) {
            $this->selectedBranchId = auth()->user()->branch_id ?? '';
        }

        $this->updateGlobalHeader('list');
    }

    // ── Live Validation ───────────────────────────────────────────
    public function updatedName()
    {
        $this->validateFieldLive('name', array_merge(
            ValidationHelper::rulesProductName(2, 255),
            [Rule::unique('products', 'name')->ignore($this->editProductId)]
        ), ValidationHelper::commonMessages());
    }

    public function updatedPrice()
    {
        $this->validateFieldLive('price', ValidationHelper::RULES_PRICE, ValidationHelper::commonMessages());
    }

    public function updatedDescription()
    {
        $this->validateFieldLive('description', ['nullable', 'string', 'max:500'], ValidationHelper::commonMessages());
    }

    public function updatedNewGroupName()
    {
        $this->validateFieldLive('newGroupName', ['required', 'string', 'max:100'], ValidationHelper::commonMessages());
    }

    public function updatedNewIngredientId(int|string|null $id)
    {
        if ($id) {
            $ing = Ingredient::find($id);
            $this->newIngredientUnit = $ing ? StockHelper::getAbbreviation($ing->unit) : '';
            if (!$ing) $this->newIngredientId = '';
        } else {
            $this->newIngredientUnit = '';
        }
    }

    public function updatedNewCategoryName()
    {
        $this->validateFieldLive('newCategoryName', ['required', 'string', 'max:255', 'unique:product_categories,name'], ValidationHelper::commonMessages());
    }

    // ── Role Helpers ──────────────────────────────────────────────
    public function isSuperAdmin() { return auth()->user()?->isSuperAdmin(); }
    public function isAdmin()      { return auth()->user()?->isAdmin();      }
    public function isStaff()      { return auth()->user()?->isStaff();      }

    /**
     * Magic method to handle all updating* methods that reset pagination
     * and live validation for form fields.
     */
    public function __call($method, $parameters)
    {
        // 1. Pagination Reset
        if (str_starts_with($method, 'updating') && !str_ends_with($method, 'Page')) {
            $this->resetPage();
        }

        // 2. Live Validation
        $formFields = ['Name', 'Price', 'Description', 'CategoryId', 'IsActive',
            'NewGroupName', 'NewIngredientId', 'NewIngredientQty', 'NewCategoryName'];

        foreach ($formFields as $field) {
            if ($method === 'updated' . $field) {
                $prop = lcfirst($field);
                $rules = $this->getProductValidationRules();
                if (isset($rules[$prop])) {
                    $this->validateFieldLive($prop, $rules[$prop], ValidationHelper::commonMessages());
                }
                return;
            }
        }
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    // ── UI Panel Actions ──────────────────────────────────────────
    public function showCreate()
    {
        if (!$this->isSuperAdmin() && !$this->isAdmin()) return;
        $this->resetProductForm();
        $this->panel = 'form';
        $this->mode = 'create';
        $this->updateGlobalHeader('create');
        $this->dispatch('switch-panel', panel: 'form');
    }

    public function showEdit(int $id)
    {
        $product = Product::with(['category', 'recipes.ingredient', 'branches', 'optionGroups.options'])->findOrFail($id);

        $this->editProductId     = $product->id;
        $this->name              = $product->name;
        $this->categoryId        = $product->category_id;
        $this->description       = $product->description;
        $this->price             = $product->price;
        $this->isActive          = (bool)$product->is_active;
        $this->sortOrder         = (int)$product->sort_order;
        $this->existingImage     = $product->image;
        $this->activeTab         = 'basic';

        $this->optionGroups = $product->optionGroups->map(fn($g) => [
            'id'          => $g->id,
            'name'        => $g->name,
            'price_mode'  => $g->price_mode,
            'is_required' => (bool)$g->is_required,
            'options'     => $g->options->map(fn($o) => [
                'id'         => $o->id,
                'name'       => $o->name,
                'price'      => $o->price,
                'is_default' => (bool)$o->is_default,
            ])->toArray(),
        ])->toArray();

        $this->recipeIngredients = $product->recipes->map(function ($r) {
            $owner = 'base';
            if ($r->product_option_id) $owner = 'option:' . $r->product_option_id;
            return [
                'id'       => $r->ingredient_id,
                'name'     => $r->ingredient->name,
                'unit'     => $r->ingredient->unit,
                'quantity' => $r->quantity,
                'cost'     => $r->ingredient->cost,
                'owner'    => $owner,
            ];
        })->toArray();

        $this->resetValidation();
        $this->panel = 'form';
        $this->mode = 'edit';
        $this->updateGlobalHeader('edit');
        $this->dispatch('switch-panel', panel: 'form');
    }

    public function backToList()
    {
        $this->panel = 'list';
        $this->mode = 'list';
        $this->resetProductForm();
        $this->updateGlobalHeader('list');
        $this->dispatch('switch-panel', panel: 'list');
    }

    public function toggleStatus(int $id)
    {
        if (!$this->isSuperAdmin() && !$this->isAdmin()) return;
        $product = Product::findOrFail($id);
        $product->is_active = !$product->is_active;
        $product->save();
        $this->dispatch('notify', type: 'success', message: "Product '{$product->name}' status updated.");
    }

    public function getActiveCountProperty()
    {
        return $this->getBaseProductQuery()->where('is_active', true)->count();
    }

    public function getHiddenCountProperty()
    {
        return $this->getBaseProductQuery()->where('is_active', false)->count();
    }

    public function getOptionTemplatesProperty()
    {
        return OptionTemplate::with('items')->get();
    }

    private function getBaseProductQuery()
    {
        $query = Product::query();
        if ($this->selectedBranchId) {
            $bid = $this->selectedBranchId;
            $query->where(function ($q) use ($bid) {
                $q->where('scope', 'global')
                  ->orWhereHas('branches', fn($b) => $b->where('branches.id', $bid));
            });
        }
        return $query;
    }

    // ── Option Group Actions ──────────────────────────────────────
    public function addOptionGroup()
    {
        $this->validate(['newGroupName' => 'required|string|max:100', 'newGroupPriceMode' => 'required|in:fixed,additive']);

        // Duplicate check
        foreach ($this->optionGroups as $group) {
            if (strtolower($group['name']) === strtolower($this->newGroupName)) {
                $this->dispatch('notify', type: 'error', message: "An option group named '{$this->newGroupName}' already exists.");
                return;
            }
        }

        $this->optionGroups[] = [
            'id' => null, 'name' => $this->newGroupName,
            'price_mode' => $this->newGroupPriceMode,
            'is_required' => (bool)$this->newGroupIsRequired,
            'options' => [],
        ];
        $this->newGroupName = '';
        $this->newGroupPriceMode = 'additive';
        $this->newGroupIsRequired = false;

        $this->dispatch('close-modal', name: 'add-option-group');
    }

    public function removeOptionGroup(int $index)
    {
        unset($this->optionGroups[$index]);
        $this->optionGroups = array_values($this->optionGroups);
        if ($this->activeGroupIndex >= count($this->optionGroups)) {
            $this->activeGroupIndex = max(0, count($this->optionGroups) - 1);
        }
    }

    public function addOptionToGroup(int $groupIndex)
    {
        $this->optionGroups[$groupIndex]['options'][] = [
            'id' => null, 'name' => '', 'price' => 0, 'is_default' => false,
        ];
    }

    public function removeOptionFromGroup(int $groupIndex, int $optionIndex)
    {
        unset($this->optionGroups[$groupIndex]['options'][$optionIndex]);
        $this->optionGroups[$groupIndex]['options'] = array_values($this->optionGroups[$groupIndex]['options']);
    }

    public function setOptionAsDefault(int $groupIndex, int $optionIndex)
    {
        foreach ($this->optionGroups[$groupIndex]['options'] as $idx => $opt) {
            $this->optionGroups[$groupIndex]['options'][$idx]['is_default'] = ($idx == $optionIndex);
        }
    }

    // ── Recipe Actions ────────────────────────────────────────────
    public function addRecipeIngredient()
    {
        $this->validate([
            'newIngredientId'    => 'required|exists:ingredients,id',
            'newIngredientQty'   => 'required|numeric|min:0.01',
            'newIngredientOwner' => 'required',
        ], ['newIngredientQty.min' => 'Quantity must be at least 0.01.']);

        $ing = Ingredient::find($this->newIngredientId);
        if (!$ing) return;

        foreach ($this->recipeIngredients as $ri) {
            if ($ri['id'] == $this->newIngredientId && $ri['owner'] == $this->newIngredientOwner) {
                $this->dispatch('notify', type: 'error', message: 'Ingredient already added for this option.');
                return;
            }
        }

        $this->recipeIngredients[] = [
            'id' => $ing->id, 'name' => $ing->name, 'unit' => $ing->unit,
            'quantity' => (float)$this->newIngredientQty, 
            'cost' => $ing->cost,
            'owner' => $this->newIngredientOwner,
        ];
        $this->newIngredientId = '';
        $this->newIngredientQty = '';
    }

    public function removeRecipeIngredient(int $index)
    {
        unset($this->recipeIngredients[$index]);
        $this->recipeIngredients = array_values($this->recipeIngredients);
    }

    public function getSelectedIngredient()
    {
        return $this->newIngredientId ? Ingredient::find($this->newIngredientId) : null;
    }

    public function clearSelectedIngredient()
    {
        $this->newIngredientId = '';
        $this->newIngredientUnit = '';
    }

    // ── Category Actions ──────────────────────────────────────────
    public function quickAddCategory()
    {
        if (!$this->isSuperAdmin() && !$this->isAdmin()) return;
        $this->validateSecure([
            'newCategoryName' => ['required', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME, 'unique:product_categories,name'],
            'newCategoryStation' => ['required', 'string', 'in:kitchen,barista'],
        ]);
        $cat = ProductCategory::create([
            'name' => $this->newCategoryName,
            'production_station' => $this->newCategoryStation,
        ]);
        $this->newCategoryName = '';
        $this->newCategoryStation = 'kitchen';
        $this->categoryId = $cat->id;
        $this->dispatch('notify', type: 'success', message: 'Category "' . $cat->name . '" created.');
        $this->dispatch('close-modal', name: 'quick-add-category');
    }

    public function setCategoryAndValidate(int|string|null $categoryId)
    {
        $this->categoryId = $categoryId;
        $this->validateOnly('categoryId', ['categoryId' => ['nullable', 'exists:product_categories,id']]);
    }

    // ── Save Product ──────────────────────────────────────────────
    private function getProductValidationRules(): array
    {
        $rules = [
            'name'         => ['required', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_PRODUCT_NAME, Rule::unique('products', 'name')->ignore($this->editProductId)],
            'categoryId'   => ['nullable', 'exists:product_categories,id'],
            'price'        => ValidationHelper::RULES_PRICE,
            'description'  => ['nullable', 'string', 'max:500'],
            'image'        => ['nullable', 'image', 'max:4096'],
            'sortOrder'    => ['integer', 'min:0'],
            'optionGroups.*.name'              => 'required|string|max:100',
            'optionGroups.*.price_mode'        => 'required|in:fixed,additive',
            'optionGroups.*.options.*.name'    => 'required|string|max:100',
            'optionGroups.*.options.*.price'   => 'required|numeric|min:0',
            'recipeIngredients.*.id'           => 'required|exists:ingredients,id',
            'recipeIngredients.*.quantity'     => 'required|numeric|min:0.01',
        ];
        return $rules;
    }

    private function prepareData()
    {
        $this->name        = $this->normalizeString($this->name);
        $this->description = $this->normalizeString($this->description);
        $this->price       = $this->cleanPrice($this->price);
    }

    public function validateBeforeSave()
    {
        if (!$this->isSuperAdmin() && !$this->isAdmin()) return;
        
        $this->prepareData();

        $this->validateBeforeModal(
            $this->getProductValidationRules(), 
            ValidationHelper::commonMessages(), 
            'confirm-save-product'
        );
    }

    public function saveProduct()
    {
        if (!$this->isSuperAdmin() && !$this->isAdmin()) abort(403);

        $this->prepareData();

        $this->validateSecure($this->getProductValidationRules(), ValidationHelper::commonMessages());

        try {
            DB::transaction(function () {
                $imagePath = $this->existingImage;
                if ($this->image) {
                    if ($imagePath) Storage::disk('public')->delete($imagePath);
                    $imagePath = $this->image->store('products', 'public');
                }

                $data = [
                    'name'        => $this->name,
                    'category_id' => $this->categoryId ?: null,
                    'description' => $this->description,
                    'price'       => $this->price,
                    'is_active'   => $this->isActive,
                    'sort_order'  => $this->sortOrder,
                    'image'       => $imagePath,
                    'scope'       => 'global',
                ];

                $product = $this->editProductId
                    ? tap(Product::findOrFail($this->editProductId), fn($p) => $p->update($data))
                    : Product::create($data);

                // Ensure branch assignments are detached as it's now global
                $product->branches()->detach();

                // Sync Option Groups & Options
                $optionIdMap = [];
                $keepGroupIds = [];
                foreach ($this->optionGroups as $gIdx => $gData) {
                    $group = !empty($gData['id']) ? $product->optionGroups()->find($gData['id']) : null;
                    $groupPayload = ['name' => $gData['name'], 'price_mode' => $gData['price_mode'], 'is_required' => (bool)$gData['is_required'], 'sort_order' => $gIdx];
                    $group = $group ? tap($group, fn($g) => $g->update($groupPayload)) : $product->optionGroups()->create($groupPayload);
                    $keepGroupIds[] = $group->id;

                    $keepOptionIds = [];
                    foreach ($gData['options'] as $oIdx => $oData) {
                        $option = !empty($oData['id']) ? $group->options()->find($oData['id']) : null;
                        $optPayload = ['name' => $oData['name'], 'price' => $oData['price'], 'is_default' => (bool)$oData['is_default'], 'sort_order' => $oIdx];
                        $option = $option ? tap($option, fn($o) => $o->update($optPayload)) : $group->options()->create($optPayload);
                        $keepOptionIds[] = $option->id;
                        $optionIdMap["option:{$gIdx}_{$oIdx}"] = $option->id;
                        if (!empty($oData['id'])) $optionIdMap["option:{$oData['id']}"] = $option->id;
                    }
                    $group->options()->whereNotIn('id', $keepOptionIds)->delete();
                }
                $product->optionGroups()->whereNotIn('id', $keepGroupIds)->delete();

                // Rebuild Recipes
                Recipe::where('product_id', $product->id)->delete();
                foreach ($this->recipeIngredients as $ri) {
                    $optionId = null;
                    if (str_starts_with($ri['owner'], 'option:')) {
                        $ref = str_replace('option:', '', $ri['owner']);
                        if (isset($optionIdMap[$ri['owner']])) {
                            $optionId = $optionIdMap[$ri['owner']];
                        } elseif (is_numeric($ref)) {
                            $optionId = (int)$ref;
                        } else {
                            $parts = explode('_', $ref);
                            if (count($parts) === 2) $optionId = $optionIdMap["option:{$parts[0]}_{$parts[1]}"] ?? null;
                        }
                    }
                    if ($optionId && !ProductOption::find($optionId)) $optionId = null;

                    Recipe::create([
                        'product_id'        => $product->id,
                        'ingredient_id'     => $ri['id'],
                        'quantity'          => $ri['quantity'],
                        'product_option_id' => $optionId,
                    ]);
                }
            });

            $msg = $this->editProductId ? 'Product updated successfully.' : 'Product added to catalog.';
            $this->dispatch('notify', type: 'success', message: $msg);
            $this->dispatch('close-modal', name: 'confirm-save-product');
            $this->backToList();
        } catch (\Exception $e) {
            Log::error('MenuManagement.saveProduct failed: ' . $e->getMessage());
            $this->dispatch('notify', type: 'error', message: 'Failed to save product. Please try again.');
        }
    }

    // ── Delete Product ────────────────────────────────────────────
    public function confirmDeleteProduct(int $id)
    {
        if (!$this->isSuperAdmin() && !$this->isAdmin()) return;
        $product = Product::findOrFail($id);
        $this->deleteTargetId = $product->id;
        $this->deleteTargetName = $product->name;
        $this->dispatch('open-modal', name: 'delete-product');
    }

    public function deleteProduct()
    {
        if (!$this->isSuperAdmin() && !$this->isAdmin()) abort(403);
        if (!$this->deleteTargetId) return;

        try {
            DB::transaction(function () {
                $product = Product::findOrFail($this->deleteTargetId);
                if ($product->image) Storage::disk('public')->delete($product->image);
                $product->branches()->detach();
                Recipe::where('product_id', $product->id)->delete();
                $product->optionGroups()->each(fn($g) => $g->options()->delete());
                $product->optionGroups()->delete();
                $product->delete();
            });

            $name = $this->deleteTargetName;
            $this->deleteTargetId = null;
            $this->deleteTargetName = '';
            $this->dispatch('notify', type: 'success', message: '"' . $name . '" removed from catalog.');
            $this->backToList();
        } catch (\Exception $e) {
            Log::error('MenuManagement.deleteProduct failed: ' . $e->getMessage());
            $this->dispatch('notify', type: 'error', message: 'Failed to delete product.');
        }
    }

    // ── Profitability ─────────────────────────────────────────────
    public function calculateRecipeCost(): float
    {
        $baseIngredients = collect($this->recipeIngredients)->where('owner', 'base');
        if ($baseIngredients->isEmpty()) return 0;

        $total = 0;
        foreach ($baseIngredients as $ing) {
            $total += (float)($ing['cost'] ?? 0) * (float)$ing['quantity'];
        }
        return round($total, 2);
    }

    public function getCostBreakdown(): array
    {
        $baseIngredients = collect($this->recipeIngredients)->where('owner', 'base');
        if ($baseIngredients->isEmpty()) return [];

        $totalCost = $this->calculateRecipeCost();
        if ($totalCost <= 0) return [];

        return $baseIngredients->map(function ($ing) use ($totalCost) {
            $cost = (float)($ing['cost'] ?? 0) * (float)$ing['quantity'];
            return [
                'name'       => $ing['name'],
                'cost'       => $cost,
                'qty'        => $ing['quantity'],
                'unit'       => $ing['unit'],
                'percentage' => ($totalCost > 0) ? ($cost / $totalCost) * 100 : 0,
            ];
        })->sortByDesc('cost')->values()->toArray();
    }

    // ── Helpers ───────────────────────────────────────────────────
    private function resetProductForm()
    {
        $this->editProductId     = null;
        $this->name              = '';
        $this->categoryId        = '';
        $this->description       = '';
        $this->price             = '';
        $this->isActive          = true;
        $this->image             = null;
        $this->existingImage     = null;
        $this->sortOrder         = 0;
        $this->recipeIngredients = [];
        $this->optionGroups      = [];
        $this->newGroupName      = '';
        $this->newGroupPriceMode = 'additive';
        $this->newGroupIsRequired = false;
        $this->newIngredientId   = '';
        $this->newIngredientQty  = '';
        $this->newIngredientOwner = 'base';
        $this->resetValidation();
    }

    private function updateGlobalHeader(string $state = 'list')
    {
        $title = 'Menu Items';
        $breadcrumbs = [
            ['label' => 'Products', 'url' => '#'],
            ['label' => 'Menu Catalog', 'url' => route('menu.index')],
        ];
        if ($state === 'create') {
            $breadcrumbs[] = ['label' => 'Add Product', 'url' => '#'];
            $title = 'Add New Product';
        } elseif ($state === 'edit') {
            $breadcrumbs[] = ['label' => 'Edit Product', 'url' => '#'];
            $title = 'Edit Product';
        }
        $this->dispatch('setHeader', 
            icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
            title: $title,
            breadcrumbs: $breadcrumbs
        );
    }

    // ── Render ────────────────────────────────────────────────────
    public function render()
    {
        $query = $this->getBaseProductQuery()->with(['category', 'branches']);

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }
        if ($this->selectedCategoryId) {
            $query->where('category_id', $this->selectedCategoryId);
        }
        if ($this->isActive !== null && $this->isActive !== '') {
            $query->where('is_active', $this->isActive === '1' || $this->isActive === 1 || $this->isActive === true);
        }

        $products = $query->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->select('products.*')
            ->orderBy('product_categories.sort_order', 'asc')
            ->orderBy('products.name', 'asc')
            ->paginate($this->perPage);
        $categories    = ProductCategory::orderBy('name', 'asc')->get();
        $branches      = Branch::orderBy('branch_name', 'asc')->get();
        $allIngredients = Ingredient::orderBy('name', 'asc')->get();
        $totalProducts = $this->getBaseProductQuery()->count();
        $totalCategories = ProductCategory::count();

        $recipeCost   = $this->calculateRecipeCost();
        $salePrice    = (float)($this->price ?: 0);
        $netProfit    = round($salePrice - $recipeCost, 2);
        $profitMargin = $salePrice > 0 ? round(($netProfit / $salePrice) * 100, 2) : 0;

        return view('livewire.menu-management', compact(
            'products', 'categories', 'branches', 'allIngredients',
            'totalProducts', 'totalCategories',
            'recipeCost', 'salePrice', 'netProfit', 'profitMargin'
        ))->layout('layouts.app');
    }
}
