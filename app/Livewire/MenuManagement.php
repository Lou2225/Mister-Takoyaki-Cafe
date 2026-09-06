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

    public function importOptionTemplate(int $templateId)
    {
        $template = OptionTemplate::with('items.ingredients.ingredient')->find($templateId);
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
                'price'      => $item->price == 0 ? '' : $item->price,
                'is_default' => $item->is_default,
                'sort_order' => count($options)
            ];
        }

                $this->optionGroups[] = [
            'name'        => $template->name,
            'price_mode'  => $template->price_mode,
            'is_required' => $template->is_required,
            'no_recipe_required' => (bool) $template->no_recipe_required,
            'options'     => $options
        ];

        // The group we just pushed sits at the end of optionGroups — carry
        // over each item's saved ingredient recipe into recipeIngredients,
        // tagged with the same "option:{groupIndex}_{optionIndex}" owner
        // key the rest of the form already uses (see showEdit()). This is
        // the whole point of the feature: importing a template no longer
        // means re-typing every ingredient mapping by hand.
        $groupIndex = count($this->optionGroups) - 1;
        $addedIngredientCount = 0;

        if (!$template->no_recipe_required) {
            foreach ($template->items as $optIndex => $item) {
                foreach ($item->ingredients as $ri) {
                    $owner = "option:{$groupIndex}_{$optIndex}";

                    $alreadyPresent = collect($this->recipeIngredients)
                        ->contains(fn($existing) => $existing['owner'] === $owner && $existing['id'] == $ri->ingredient_id);
                    if ($alreadyPresent) continue;

                    $this->recipeIngredients[] = [
                        'id'       => $ri->ingredient_id,
                        'name'     => $ri->ingredient->name,
                        'unit'     => $ri->ingredient->unit,
                        'quantity' => (float) $ri->quantity,
                        'cost'     => $ri->ingredient->cost,
                        'owner'    => $owner,
                    ];
                    $addedIngredientCount++;
                }
            }
        }

        $this->dispatch('close-modal', name: 'import-template-library');
        $msg = "Imported '{$template->name}' template.";
        if ($addedIngredientCount > 0) {
            $msg .= " {$addedIngredientCount} recipe ingredient(s) auto-mapped — review under the Recipe tab.";
        }
        $this->dispatch('notify', type: 'success', message: $msg);
    }
    
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
    /**
     * Pulls ingredient mappings from the Library into an option group
     * already on this product, matched by group name + option name. This
     * covers the case importOptionTemplate() can't: a group that was
     * added to the product BEFORE its ingredients were defined (or
     * edited) in the Library. Only fills in missing ingredients — never
     * touches prices, defaults, or ingredients already present.
     */
    public function syncGroupFromLibrary(int $groupIndex)
    {
                $groupData = $this->optionGroups[$groupIndex] ?? null;
        if (!$groupData) return;
        $noRecipe = (bool)($groupData['no_recipe_required'] ?? false);

        $template = OptionTemplate::with('items.ingredients.ingredient')
            ->whereRaw('LOWER(name) = ?', [strtolower($groupData['name'])])
            ->first();

        if (!$template) {
            $this->dispatch('notify', type: 'error', message: "No Library template named '{$groupData['name']}' found to sync from.");
            return;
        }

        $addedIngredientCount = 0;
        $addedOptionCount = 0;
        $matchedItemIds = [];

        // 1) Sync ingredients into options that already exist on this product.
        foreach ($this->optionGroups[$groupIndex]['options'] as $optIndex => $option) {
            $templateItem = $template->items->first(
                fn($ti) => strtolower($ti->name) === strtolower($option['name'])
            );
                        if (!$templateItem) continue;
            $matchedItemIds[] = $templateItem->id;

            if ($noRecipe) continue;

            $owner = "option:{$groupIndex}_{$optIndex}";
            $legacyOwner = !empty($option['id']) ? "option:{$option['id']}" : null;

            foreach ($templateItem->ingredients as $ri) {
                $alreadyPresent = collect($this->recipeIngredients)->contains(
                    fn($existing) => $existing['id'] == $ri->ingredient_id
                        && ($existing['owner'] === $owner || ($legacyOwner && $existing['owner'] === $legacyOwner))
                );
                if ($alreadyPresent) continue;

                $this->recipeIngredients[] = [
                    'id'       => $ri->ingredient_id,
                    'name'     => $ri->ingredient->name,
                    'unit'     => $ri->ingredient->unit,
                    'quantity' => (float) $ri->quantity,
                    'cost'     => $ri->ingredient->cost,
                    'owner'    => $legacyOwner ?? $owner,
                ];
                $addedIngredientCount++;
            }
        }

        // 2) Pull in template items that aren't options on this product yet.
        foreach ($template->items as $templateItem) {
            if (in_array($templateItem->id, $matchedItemIds)) continue;

            $alreadyExistsByName = collect($this->optionGroups[$groupIndex]['options'])
                ->contains(fn($o) => strtolower($o['name']) === strtolower($templateItem->name));
            if ($alreadyExistsByName) continue;

            $newOptIndex = count($this->optionGroups[$groupIndex]['options']);
            $this->optionGroups[$groupIndex]['options'][] = [
                'id'         => null,
                'name'       => $templateItem->name,
                'price'      => $templateItem->price == 0 ? '' : $templateItem->price,
                'is_default' => $templateItem->is_default,
            ];
                        $addedOptionCount++;

            if (!$noRecipe) {
                $owner = "option:{$groupIndex}_{$newOptIndex}";
                foreach ($templateItem->ingredients as $ri) {
                    $this->recipeIngredients[] = [
                        'id'       => $ri->ingredient_id,
                        'name'     => $ri->ingredient->name,
                        'unit'     => $ri->ingredient->unit,
                        'quantity' => (float) $ri->quantity,
                        'cost'     => $ri->ingredient->cost,
                        'owner'    => $owner,
                    ];
                    $addedIngredientCount++;
                }
            }
        }

        if ($addedIngredientCount > 0 || $addedOptionCount > 0) {
            $parts = [];
            if ($addedOptionCount > 0) $parts[] = "{$addedOptionCount} option(s)";
            if ($addedIngredientCount > 0) $parts[] = "{$addedIngredientCount} ingredient(s)";
            $this->dispatch('notify', type: 'success', message: 'Synced ' . implode(' and ', $parts) . " from the '{$template->name}' template.");
        } else {
            $this->dispatch('notify', type: 'info', message: 'Already up to date — nothing new to sync.');
        }
    }

    public function saveGroupToLibrary(int $index)
    {
        $groupData = $this->optionGroups[$index] ?? null;
        if (!$groupData) return;

        $existingTemplate = OptionTemplate::where('name', $groupData['name'])->first();

        try {
        DB::transaction(function () use ($groupData, $index, $existingTemplate) {
                        $noRecipe = (bool)($groupData['no_recipe_required'] ?? false);

            if ($existingTemplate) {
                // Update in place, rather than blocking — this is the
                // path that lets ingredients added on a product's Recipe
                // tab flow back into an already-existing Library
                // template, matching options by name (same convention
                // syncGroupFromLibrary uses going the other direction).
                $template = $existingTemplate;
                $template->update([
                    'price_mode'  => $groupData['price_mode'],
                    'is_required' => $groupData['is_required'],
                    'no_recipe_required' => $noRecipe,
                ]);
            } else {
                $template = OptionTemplate::create([
                    'name'        => $groupData['name'],
                    'price_mode'  => $groupData['price_mode'],
                    'is_required' => $groupData['is_required'],
                    'no_recipe_required' => $noRecipe,
                ]);
            }

            $keepItemIds = [];

            foreach ($groupData['options'] as $oIdx => $oData) {
                $item = $existingTemplate
                    ? $template->items()->whereRaw('LOWER(name) = ?', [strtolower($oData['name'])])->first()
                    : null;

                if ($item) {
                    $item->update([
                        'price'      => (float)($oData['price'] ?: 0),
                        'is_default' => $oData['is_default'],
                    ]);
                } else {
                    $item = $template->items()->create([
                        'name'       => $oData['name'],
                        'price'      => (float)($oData['price'] ?: 0),
                        'is_default' => $oData['is_default'],
                    ]);
                }
                $keepItemIds[] = $item->id;

                $owner = "option:{$index}_{$oIdx}";
                $legacyOwner = !empty($oData['id']) ? "option:{$oData['id']}" : null;

                // Ingredients can be keyed either by the unsaved
                // "{groupIndex}_{optionIndex}" reference or, for options
                // that already exist in the DB, by their real option ID
                // (see showEdit()). Match against both so ingredients
                // aren't lost when saving an already-saved group.
                $matchingIngredients = collect($this->recipeIngredients)
                    ->filter(fn($ri) => $ri['owner'] === $owner || ($legacyOwner && $ri['owner'] === $legacyOwner))
                    // Same ingredient can appear twice here — once under the
                    // index-based owner key, once under the legacy real-ID
                    // owner key — if both got added before the row was fully
                    // normalized. Keep one row per ingredient_id so the
                    // insert below never collides with the unique index.
                    ->unique('id');

                // Rebuild this item's ingredients from what's currently on
                // the product, so updates (additions AND removals on the
                // product side) are reflected in the template.
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

            // Don't delete template items whose names simply don't appear
            // in this product's current option list (e.g. this product
            // only uses a subset of a shared template) — only cleanup
            // makes sense when this product is genuinely the template's
            // source of truth, which we can't reliably assume here. Skip
            // deletion to avoid destroying items other products may
            // still rely on.
        });

        $msg = $existingTemplate
            ? "Template '{$groupData['name']}' updated in library."
            : "Group '{$groupData['name']}' saved to library.";
        $this->dispatch('notify', type: 'success', message: $msg);
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
    public string $categorySearch = '';
    public string $categoryFilterSearch = '';
    public string $ingredientSearch = '';
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
        public $newGroupName = '';
    public $newGroupPriceMode = 'additive';
    public $newGroupIsRequired = false;
    public $newGroupNoRecipeRequired = false;
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
        $this->updateGlobalHeader('create');
    }

    public function discardDraft()
    {
        $this->resetProductForm();
        $this->updateGlobalHeader('list');
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
        return OptionTemplate::with('items.ingredients')->get();
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
            'no_recipe_required' => (bool)$this->newGroupNoRecipeRequired,
            'options' => [],
        ];
        $this->newGroupName = '';
        $this->newGroupPriceMode = 'additive';
        $this->newGroupIsRequired = false;
        $this->newGroupNoRecipeRequired = false;

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
            'id' => null, 'name' => '', 'price' => '', 'is_default' => false,
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

                if ($this->ownerBelongsToNoRecipeGroup($this->newIngredientOwner)) {
            $this->dispatch('notify', type: 'error', message: 'This option belongs to a "No Recipe Required" group and cannot have ingredients.');
            return;
        }

        $ing = Ingredient::find($this->newIngredientId);
        if (!$ing) return;

        $owner = $this->resolveOwnerKey($this->newIngredientOwner);

        foreach ($this->recipeIngredients as $ri) {
            if ($ri['id'] == $this->newIngredientId && $ri['owner'] == $owner) {
                $this->dispatch('notify', type: 'error', message: 'Ingredient already added for this option.');
                return;
            }
        }

        $this->recipeIngredients[] = [
            'id' => $ing->id, 'name' => $ing->name, 'unit' => $ing->unit,
            'quantity' => (float)$this->newIngredientQty, 
            'cost' => $ing->cost,
            'owner' => $owner,
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
            'optionGroups.*.options.*.price'   => 'nullable|numeric|min:0',
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
                'confirm-save-product'
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
            $this->validateSecure($this->getProductValidationRules(), ValidationHelper::commonMessages());
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

                // Ensure branch assignments are detached as it's now global
                $product->branches()->detach();

                                // Sync Option Groups & Options
                $optionIdMap = [];
                $keepGroupIds = [];
                $noRecipeOptionIds = [];
                foreach ($this->optionGroups as $gIdx => $gData) {
                    $group = !empty($gData['id']) ? $product->optionGroups()->find($gData['id']) : null;
                    $groupPayload = [
                        'name' => $gData['name'],
                        'price_mode' => $gData['price_mode'],
                        'is_required' => (bool)$gData['is_required'],
                        'no_recipe_required' => (bool)($gData['no_recipe_required'] ?? false),
                        'sort_order' => $gIdx,
                    ];
                    $group = $group ? tap($group, fn($g) => $g->update($groupPayload)) : $product->optionGroups()->create($groupPayload);
                    $keepGroupIds[] = $group->id;

                    $keepOptionIds = [];
                    foreach ($gData['options'] as $oIdx => $oData) {
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
        $this->newGroupNoRecipeRequired = false;
        $this->newIngredientId   = '';
        $this->newIngredientQty  = '';
        $this->newIngredientOwner = 'base';
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
        $totalCategories = ProductCategory::count();

        // ── Dropdown Data with Filtering ──
        $categoriesQuery = ProductCategory::orderBy('name', 'asc');
        if ($this->categorySearch) {
            $categoriesQuery->where('name', 'like', "%{$this->categorySearch}%");
        }
        $categories = $categoriesQuery->get();

        $filterCategoriesQuery = ProductCategory::orderBy('name', 'asc');
        if ($this->categoryFilterSearch) {
            $filterCategoriesQuery->where('name', 'like', "%{$this->categoryFilterSearch}%");
        }
        $filterCategories = $filterCategoriesQuery->get();

        $allIngredientsQuery = Ingredient::orderBy('name', 'asc');
        if ($this->ingredientSearch) {
            $allIngredientsQuery->where('name', 'like', "%{$this->ingredientSearch}%");
        }
        $allIngredients = $allIngredientsQuery->get();

        $branches = Branch::orderBy('branch_name', 'asc')->get();

        // ── Selected Display Names (Persistent during search) ──
        $selectedCategoryName = $this->categoryId 
            ? (ProductCategory::find($this->categoryId)?->name ?? 'Uncategorized')
            : 'Uncategorized';

        $selectedFilterCategoryName = $this->selectedCategoryId 
            ? (ProductCategory::find($this->selectedCategoryId)?->name ?? 'All Categories')
            : 'All Categories';

        $selectedIngredientName = $this->newIngredientId 
            ? (Ingredient::find($this->newIngredientId)?->name ?? 'Choose an item...')
            : 'Choose an item...';

        $recipeCost   = $this->calculateRecipeCost();
        $salePrice    = (float)($this->price ?: 0);
        $netProfit    = round($salePrice - $recipeCost, 2);
        $profitMargin = $salePrice > 0 ? round(($netProfit / $salePrice) * 100, 2) : 0;

        return view('livewire.menu-management', compact(
            'products', 'categories', 'filterCategories', 'branches', 'allIngredients',
            'selectedCategoryName', 'selectedFilterCategoryName', 'selectedIngredientName',
            'totalProducts', 'totalCategories',
            'recipeCost', 'salePrice', 'netProfit', 'profitMargin'
        ))->layout('layouts.app', ['noPadding' => true]);
    }
}
