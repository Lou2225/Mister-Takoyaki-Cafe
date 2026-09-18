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
    public $noPadding = true;

public function getOwnerLabel(string $owner): string
    {
        if ($owner === 'base') return 'Base';

        $ref = str_replace('option:', '', $owner);

        // Unsaved/new format: "{groupIndex}_{optionIndex}"
        if (str_contains($ref, '_')) {
            [$gIdx, $oIdx] = explode('_', $ref);
            if (isset($this->optionGroups[$gIdx]['options'][$oIdx])) {
                return $this->optionGroups[$gIdx]['options'][$oIdx]['name'];
            }
        }

        // Saved format: real option ID
        if (is_numeric($ref)) {
            foreach ($this->optionGroups as $group) {
                foreach ($group['options'] as $opt) {
                    if (($opt['id'] ?? null) == $ref) {
                        return $opt['name'];
                    }
                }
            }
        }

        return 'Option';
    }

    public function getTemplatesData(): array
    {
        return $this->optionTemplates->map(fn($t) => [
            'id' => (int)$t->id,
            'name' => (string)$t->name,
            'price_mode' => (string)$t->price_mode,
            'max_select' => $t->max_select !== null ? (int)$t->max_select : null,
            'is_required' => (bool)$t->is_required,
            'no_recipe_required' => (bool)$t->no_recipe_required,
            'items' => $t->items->map(fn($item) => [
                'id' => (int)$item->id,
                'name' => (string)$item->name,
                'price' => (float)($item->price ?: 0),
                'is_default' => (bool)$item->is_default,
                'ingredients' => $item->ingredients->map(fn($ri) => [
                    'ingredient_id' => (int)$ri->ingredient_id,
                    'quantity' => (float)$ri->quantity,
                    'ingredient' => $ri->ingredient ? [
                        'id' => (int)$ri->ingredient->id,
                        'name' => (string)$ri->ingredient->name,
                        'unit' => (string)\App\Helpers\StockHelper::getAbbreviation($ri->ingredient->unit),
                        'cost' => (float)($ri->ingredient->cost ?? 0),
                    ] : null,
                ])->values(),
            ])->values(),
        ])->values()->toArray();
    }

    public function saveGroupToLibrary(int $index)
    {
        $groupData = $this->optionGroups[$index] ?? null;
        if (!$groupData) return;

        $groupName = trim($groupData['name'] ?? '');
        if ($groupName === '') return;

        $existingTemplate = OptionTemplate::whereRaw('LOWER(name) = ?', [strtolower($groupName)])->first();

        try {
            DB::transaction(function () use ($groupData, $groupName, $index, $existingTemplate) {
                $noRecipe = (bool)($groupData['no_recipe_required'] ?? false);
                $maxSelect = !empty($groupData['max_select']) ? (int)$groupData['max_select'] : null;

                if ($existingTemplate) {
                    $template = $existingTemplate;
                    $template->update([
                        'price_mode'  => $groupData['price_mode'],
                        'max_select'  => $maxSelect,
                        'is_required' => (bool)$groupData['is_required'],
                        'no_recipe_required' => $noRecipe,
                    ]);
                } else {
                    $template = OptionTemplate::create([
                        'name'        => $groupName,
                        'price_mode'  => $groupData['price_mode'],
                        'max_select'  => $maxSelect,
                        'is_required' => (bool)$groupData['is_required'],
                        'no_recipe_required' => $noRecipe,
                    ]);
                }

                $keepItemIds = [];

                foreach (($groupData['options'] ?? []) as $oIdx => $oData) {
                    $optName = trim($oData['name'] ?? '');
                    if ($optName === '') continue;

                    $item = $existingTemplate
                        ? $template->items()->whereRaw('LOWER(name) = ?', [strtolower($optName)])->first()
                        : null;

                    if ($item) {
                        $item->update([
                            'price'      => (float)($oData['price'] ?: 0),
                            'is_default' => (bool)($oData['is_default'] ?? false),
                        ]);
                    } else {
                        $item = $template->items()->create([
                            'name'       => $optName,
                            'price'      => (float)($oData['price'] ?: 0),
                            'is_default' => (bool)($oData['is_default'] ?? false),
                        ]);
                    }
                    $keepItemIds[] = $item->id;

                    $owner = "option:{$index}_{$oIdx}";
                    $legacyOwner = !empty($oData['id']) ? "option:{$oData['id']}" : null;

                    $matchingIngredients = collect($this->recipeIngredients)
                        ->filter(fn($ri) => $ri['owner'] === $owner || ($legacyOwner && $ri['owner'] === $legacyOwner))
                        ->unique('id');

                    $item->ingredients()->delete();
                    if (!$noRecipe) {
                        foreach ($matchingIngredients as $ri) {
                            $item->ingredients()->create([
                                'ingredient_id' => $ri['id'],
                                'quantity'      => $ri['quantity'],
                            ]);
                        }
                    }
                }

                if ($existingTemplate && !empty($keepItemIds)) {
                    $template->items()->whereNotIn('id', $keepItemIds)->delete();
                }
            });

            $msg = $existingTemplate
                ? "Template '{$groupName}' updated in library."
                : "Group '{$groupName}' saved to library.";
            $this->dispatch('notify', type: 'success', message: $msg);
            $this->dispatch('templates-updated', templates: $this->getTemplatesData());
        } catch (\Exception $e) {
            Log::error('MenuManagement.saveGroupToLibrary failed: ' . $e->getMessage());
            $this->dispatch('notify', type: 'error', message: 'Failed to save group to library. Please check option prices and try again.');
        }
    }

    // ── Filters & Display ─────────────────────────────────────────
    public $search = '';
    public $selectedCategoryId = '';
    public $selectedBranchId = '';
    public $perPage = 5;
    public $view = 'table';
    public $statusFilter = '';
    public string $newCategoryStation = 'kitchen'; // Default
    public string $categoryFilterSearch = '';
    public $panel = 'list';
    public $mode = 'list';

    // ── Deletion State ────────────────────────────────────────────
    public ?int $deleteTargetId = null;
    public string $deleteTargetName = '';

    // ── Form: Product (Create/Edit) ───────────────────────────────
    public ?int $editProductId = null;
    public ?string $name = '';
    public int|string|null $categoryId = null;
    public ?string $description = '';
    public ?string $price = '';
    public $isActive = '1';
    /** @var TemporaryUploadedFile|null */
    public $image = null;
    public ?string $existingImage = null;
    public int $sortOrder = 0;
    public array $recipeIngredients = [];
    public string $newCategoryName = '';

    // ── Option Groups ─────────────────────────────────────────────
    public $optionGroups = [];

    // ── Tab State ─────────────────────────────────────────────────
    public $activeTab = 'basic';

    protected $queryString = [
        'search'             => ['except' => '', 'as' => 'm_search'],
        'selectedCategoryId' => ['except' => '', 'as' => 'm_cat'],
        'selectedBranchId'   => ['except' => '', 'as' => 'm_branch'],
        'statusFilter'       => ['except' => '', 'as' => 'm_status'],
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

    public function updatedNewCategoryName()
    {
        $this->validateFieldLive('newCategoryName', array_merge(
            ValidationHelper::rulesCategoryName(2, 255, true),
            ['unique:product_categories,name']
        ), ValidationHelper::commonMessages());
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
        $formFields = ['Name', 'Price', 'Description', 'CategoryId', 'IsActive', 'NewCategoryName'];

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
        $this->panel = 'form';
        $this->mode = 'create';
        $this->resetProductForm();
        $this->updateGlobalHeader('create');
    }

    public function discardDraft()
    {
        $this->panel = 'list';
        $this->mode = 'list';
        $this->resetProductForm();
        $this->updateGlobalHeader('list');
    }

    public function removeImage()
    {
        if ($this->existingImage) {
            Storage::disk('public')->delete($this->existingImage);
        }
        $this->image = null;
        $this->existingImage = null;
        $this->resetValidation('image');
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
            'max_select'  => $g->max_select !== null ? (int)$g->max_select : null,
            'is_required' => (bool)$g->is_required,
            'no_recipe_required' => (bool)$g->no_recipe_required,
            'options'     => $g->options->map(fn($o) => [
                'id'         => $o->id,
                'name'       => $o->name,
                'price'      => $o->price == 0 ? '' : $o->price,
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

        // Resolve which branch this toggle should apply to:
        // 1) the active branch filter, if one is selected
        // 2) otherwise, the acting user's own assigned branch
        // Only when neither exists do we fall back to a global toggle.
        $operatingBranchId = $this->getOperatingBranchId();

        if ($operatingBranchId) {
            // Toggle availability for THIS branch only, without touching
            // the product's global is_active flag or other branches.
            $pivot = DB::table('branch_product')
                ->where('branch_id', $operatingBranchId)
                ->where('product_id', $product->id)
                ->first();

            $currentlyActive = $pivot ? (bool)$pivot->is_active : (bool)$product->is_active;
            $newStatus = !$currentlyActive;

            DB::table('branch_product')->updateOrInsert(
                ['branch_id' => $operatingBranchId, 'product_id' => $product->id],
                ['is_active' => $newStatus, 'updated_at' => now()]
            );

            $branchName = Branch::find($operatingBranchId)?->branch_name ?? 'this branch';
            $this->dispatch('notify', type: 'success', message: "Product '{$product->name}' " . ($newStatus ? 'enabled' : 'hidden') . " for {$branchName}.");
        } else {
            // No branch context at all (user has no assigned branch and
            // no filter is active) — toggle the product's global default.
            $product->is_active = !$product->is_active;
            $product->save();
            $this->dispatch('notify', type: 'success', message: "Product '{$product->name}' global status updated.");
        }
    }

    public function getActiveCountProperty()
    {
        return $this->getBaseProductQuery()->whereRaw($this->effectiveActiveExpr() . ' = 1')->count();
    }

    public function getHiddenCountProperty()
    {
        return $this->getBaseProductQuery()->whereRaw($this->effectiveActiveExpr() . ' = 0')->count();
    }

    public function getOptionTemplatesProperty()
    {
        return OptionTemplate::with('items.ingredients.ingredient')->get();
    }
/**
     * The branch this session is currently acting on: the active filter
     * if one is selected, otherwise the logged-in user's own branch.
     */
    private function getOperatingBranchId()
    {
        return $this->selectedBranchId ?: auth()->user()?->branch_id;
    }

    /**
     * Canonical owner key for a given group/option index — prefers the
     * real saved option ID when one exists, falling back to the
     * index-based key for options not yet saved. Used to normalize
     * whatever the form submits so duplicate checks and storage stay
     * consistent regardless of when a given row was added.
     */
        private function resolveOwnerKey(string $rawOwner): string
    {
        if (!str_starts_with($rawOwner, 'option:')) return $rawOwner; // 'base'
        $ref = str_replace('option:', '', $rawOwner);
        if (!str_contains($ref, '_')) return $rawOwner; // already a real-ID owner
        [$gIdx, $oIdx] = explode('_', $ref);
        $optionId = $this->optionGroups[$gIdx]['options'][$oIdx]['id'] ?? null;
        return $optionId ? "option:{$optionId}" : $rawOwner;
    }

    private function ownerBelongsToNoRecipeGroup(string $rawOwner): bool
    {
        if (!str_starts_with($rawOwner, 'option:')) return false;
        $ref = str_replace('option:', '', $rawOwner);

        if (str_contains($ref, '_')) {
            [$gIdx, ] = explode('_', $ref);
            return (bool)($this->optionGroups[$gIdx]['no_recipe_required'] ?? false);
        }

        foreach ($this->optionGroups as $group) {
            foreach ($group['options'] as $opt) {
                if (($opt['id'] ?? null) == $ref) {
                    return (bool)($group['no_recipe_required'] ?? false);
                }
            }
        }
        return false;
    }

    /**
     * If a group's "No Recipe Required" flag is switched on live in the
     * form, strip out any ingredients already staged for its options so
     * the UI and eventual save can't disagree with the flag.
     */
    public function updated($propertyName, $value = null)
    {
        if (preg_match('/^optionGroups\.(\d+)\.no_recipe_required$/', $propertyName, $m) && $value) {
            $gIdx = (int)$m[1];
            foreach ($this->optionGroups[$gIdx]['options'] ?? [] as $oIdx => $opt) {
                $ownerIndexed = "option:{$gIdx}_{$oIdx}";
                $ownerReal = !empty($opt['id']) ? "option:{$opt['id']}" : null;
                $this->recipeIngredients = collect($this->recipeIngredients)
                    ->reject(fn($ri) => $ri['owner'] === $ownerIndexed || ($ownerReal && $ri['owner'] === $ownerReal))
                    ->values()->all();
            }
        }
    }
    private function getBaseProductQuery()
    {
        $query = Product::query();
        $bid = $this->getOperatingBranchId();

        if ($bid) {
            $query->where(function ($q) use ($bid) {
                $q->where('scope', 'global')
                  ->orWhereHas('branches', fn($b) => $b->where('branches.id', $bid));
            });

            // Per-branch availability override, falling back to the
            // product's global is_active flag when no override exists.
            $query->leftJoin('branch_product', function ($join) use ($bid) {
                $join->on('products.id', '=', 'branch_product.product_id')
                     ->where('branch_product.branch_id', '=', $bid);
            })->addSelect(DB::raw('COALESCE(branch_product.is_active, products.is_active) as effective_is_active'));
        } else {
            $query->addSelect(DB::raw('products.is_active as effective_is_active'));
        }
        return $query;
    }

    /**
     * SQL fragment for the currently-effective availability flag —
     * branch-specific override if a branch is selected, otherwise global.
     */
    private function effectiveActiveExpr(): string
    {
        return $this->getOperatingBranchId()
            ? 'COALESCE(branch_product.is_active, products.is_active)'
            : 'products.is_active';
    }


    // ── Category Actions ──────────────────────────────────────────
    public function quickAddCategory()
    {
        if (!$this->isSuperAdmin() && !$this->isAdmin()) return;
        $this->validateSecure([
            'newCategoryName' => array_merge(
                ValidationHelper::rulesCategoryName(2, 255, true),
                ['unique:product_categories,name']
            ),
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
            'optionGroups.*.max_select'        => 'nullable|integer|min:1',
            'optionGroups.*.options.*.name'    => 'required|string|max:100',
            'optionGroups.*.options.*.price'   => 'nullable|numeric|min:0',
            'recipeIngredients.*.id'           => 'required|exists:ingredients,id',
            'recipeIngredients.*.quantity'     => 'required|numeric|min:0.01',
        ];
        return $rules;
    }

    /**
     * Friendly display names for validator attributes, so wildcard-indexed
     * fields (e.g. optionGroups.0.options.1.name) don't leak their raw
     * dot-notation array path into user-facing error messages.
     */
    private function getProductValidationAttributes(): array
    {
        return [
            'name'                              => 'product name',
            'categoryId'                        => 'category',
            'price'                              => 'sale price',
            'description'                       => 'description',
            'image'                              => 'image',
            'optionGroups.*.name'               => 'group name',
            'optionGroups.*.price_mode'         => 'price mode',
            'optionGroups.*.max_select'         => 'selection limit',
            'optionGroups.*.options.*.name'     => 'option name',
            'optionGroups.*.options.*.price'    => 'option price',
            'recipeIngredients.*.id'            => 'ingredient',
            'recipeIngredients.*.quantity'      => 'quantity',
        ];
    }

    private function prepareData()
    {
        $this->name        = $this->normalizeString($this->name);
        $this->description = $this->normalizeString($this->description);
        $this->price       = $this->cleanPrice($this->price);
    }

    private function switchToFailedTab(array $errorFields)
    {
        foreach ($errorFields as $field) {
            // Basic tab fields
            if (in_array($field, ['name', 'price', 'description', 'categoryId'])) {
                $this->activeTab = 'basic';
                return;
            }
            // Variants/options tab
            if (str_starts_with($field, 'optionGroups')) {
                $this->activeTab = 'variants';
                return;
            }
            // Recipe tab
            if (str_starts_with($field, 'recipeIngredients')) {
                $this->activeTab = 'recipe';
                return;
            }
        }
    }

    public function validateBeforeSave()
    {
        if (!$this->isSuperAdmin() && !$this->isAdmin()) return;
        
        $this->prepareData();

        try {
            $this->validateBeforeModal(
                $this->getProductValidationRules(), 
                ValidationHelper::commonMessages(), 
                'confirm-save-product',
                $this->getProductValidationAttributes()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->switchToFailedTab($e->validator->errors()->keys());
            throw $e;
        }
    }

    public function saveProduct()
    {
        if (!$this->isSuperAdmin() && !$this->isAdmin()) abort(403);

        $this->prepareData();

         try {
            $this->validateSecure($this->getProductValidationRules(), ValidationHelper::commonMessages(), $this->getProductValidationAttributes());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->switchToFailedTab($e->validator->errors()->keys());
            throw $e;
        }

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
                    'is_active'   => $this->isActive === '1' || $this->isActive === 1 || $this->isActive === true,
                    'sort_order'  => $this->sortOrder,
                    'image'       => $imagePath,
                    'scope'       => 'global',
                ];

                $product = $this->editProductId
                    ? tap(Product::findOrFail($this->editProductId), fn($p) => $p->update($data))
                    : Product::create($data);

                // Sync Option Groups & Options
                $optionIdMap = [];
                $keepGroupIds = [];
                $noRecipeOptionIds = [];
                foreach ($this->optionGroups as $gIdx => $gData) {
                    $group = !empty($gData['id']) ? $product->optionGroups()->find($gData['id']) : null;
                    $groupPayload = [
                        'name' => $gData['name'],
                        'price_mode' => $gData['price_mode'],
                        'max_select' => !empty($gData['max_select']) ? (int)$gData['max_select'] : null,
                        'is_required' => (bool)$gData['is_required'],
                        'no_recipe_required' => (bool)($gData['no_recipe_required'] ?? false),
                        'sort_order' => $gIdx,
                    ];
                    $group = $group ? tap($group, fn($g) => $g->update($groupPayload)) : $product->optionGroups()->create($groupPayload);
                    $keepGroupIds[] = $group->id;

                    $keepOptionIds = [];
                    foreach (($gData['options'] ?? []) as $oIdx => $oData) {
                        $option = !empty($oData['id']) ? $group->options()->find($oData['id']) : null;
                        $optPayload = [
                            'name' => $oData['name'], 
                            'price' => (float)($oData['price'] ?: 0), 
                            'is_default' => (bool)$oData['is_default'], 
                            'sort_order' => $oIdx
                        ];
                        $option = $option ? tap($option, fn($o) => $o->update($optPayload)) : $group->options()->create($optPayload);
                        $keepOptionIds[] = $option->id;
                        $optionIdMap["option:{$gIdx}_{$oIdx}"] = $option->id;
                        if (!empty($oData['id'])) $optionIdMap["option:{$oData['id']}"] = $option->id;

                        if (!empty($gData['no_recipe_required'])) {
                            $noRecipeOptionIds[$option->id] = true;
                        }
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

                    // A "No Recipe Required" group is authoritative — never
                    // persist a stock requirement for its options, even if
                    // stale form state still carries an ingredient row.
                    if ($optionId && isset($noRecipeOptionIds[$optionId])) {
                        continue;
                    }

                    Recipe::create([
                        'product_id'        => $product->id,
                        'ingredient_id'     => $ri['id'],
                        'quantity'          => $ri['quantity'],
                        'product_option_id' => $optionId,
                    ]);
                }
            });

            $msg = $this->editProductId ? 'Product updated successfully.' : 'Product added to catalog.';
            $this->dispatch('close-modal', 'confirm-save-product');
            $this->dispatch('close-modal', name: 'confirm-save-product');
            $this->dispatch('notify', type: 'success', message: $msg);
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
        $this->activeTab         = 'basic';
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
                $allCategories = ProductCategory::orderBy('name', 'asc')->get(['id', 'name'])->map(fn($c) => [
            'id' => (int)$c->id,
            'name' => (string)$c->name,
        ])->values();

        $allIngredients = Ingredient::orderBy('name', 'asc')->get();
        $templatesData  = $this->getTemplatesData();

        $recipeCost   = $this->calculateRecipeCost();
        $salePrice    = (float)($this->price ?: 0);
        $netProfit    = round($salePrice - $recipeCost, 2);
        $profitMargin = $salePrice > 0 ? round(($netProfit / $salePrice) * 100, 2) : 0;

        $query = $this->getBaseProductQuery()->with('category');

        if ($this->selectedCategoryId) {
            $query->where('category_id', $this->selectedCategoryId);
        }
        if ($this->statusFilter !== null && $this->statusFilter !== '') {
            $query->whereRaw($this->effectiveActiveExpr() . ' = ?', [$this->statusFilter === '1' ? 1 : 0]);
        }

        $products = $query->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->addSelect('products.*')
            ->orderBy('product_categories.sort_order', 'asc')
            ->orderBy('products.name', 'asc')
            ->get();
        $totalProducts = $products->count();

        $filterCategoriesQuery = ProductCategory::orderBy('name', 'asc');
        if ($this->categoryFilterSearch) {
            $filterCategoriesQuery->where('name', 'like', "%{$this->categoryFilterSearch}%");
        }
        $filterCategories = $filterCategoriesQuery->get();

        $branches = Branch::orderBy('branch_name', 'asc')->get();

        $totalCategories = ProductCategory::count();

        $selectedFilterCategoryName = $this->selectedCategoryId 
            ? (ProductCategory::find($this->selectedCategoryId)?->name ?? 'All Categories')
            : 'All Categories';

        return view('livewire.menu-management', compact(
            'products', 'filterCategories', 'branches', 'allIngredients',
            'selectedFilterCategoryName',
            'totalProducts', 'totalCategories',
            'recipeCost', 'salePrice', 'netProfit', 'profitMargin',
            'allCategories', 'templatesData'
        ))->layout('layouts.app', ['noPadding' => true]);
    }
}
