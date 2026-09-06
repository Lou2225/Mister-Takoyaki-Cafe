<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Ingredient;
use App\Models\Branch;
use App\Models\BranchIngredientStock;
use App\Models\StockMovement;
use App\Models\StockBatch;
use App\Services\ConfigurationService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Traits\HandlesValidations;
use App\Traits\HandlesExports;
use App\Helpers\ValidationHelper;
use App\Models\IngredientCategory;
use App\Helpers\StockHelper;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class StockManagement extends Component
{
    use WithPagination, HandlesValidations, HandlesExports;

    // ── Filters & Display ─────────────────────────────────────────
    // ── Filters & Display ─────────────────────────────────────────
public $selectedBranchId = '';
public $filterStatus = ''; // 'low', 'critical', 'healthy'
public $filterCategoryId = '';

    // ── Panel Context ─────────────────────────────────────────────
    public $panel = 'list'; // Alpine listens to this via event

    // ── Form: Global Ingredient (Create/Edit) ─────────────────────
    public $editIngredientId = null;
    public $ingredientName = '';
    public $ingredientCategoryId = '';
    public $ingredientCategorySearch = ''; // Search for the category dropdown
    public $selectedCategoryName = 'Select Category'; // Persistent display name
    public $ingredientScope = 'global';
    public $ingredientUnit = 'pcs';
    public $ingredientMinStock = '';
    public $ingredientCost = 0;
    // Multi-level packaging conversions (replaces single bulk_unit/bulk_qty/bulk_price)
    // Each row: ['unit_name'=>'', 'qty_in_base'=>'', 'price_per_unit'=>'', 'sort_order'=>0,
    //            'chain_multiplier'=>'', 'chain_from_index'=>null]  ← helper fields for calculator
    public $conversionRows = [];
    public $newCategoryName = ''; // For Quick Add
    public $assignedBranchIds = []; // For Super Admin branch initialization
    public $availableUnits = [];
    public $availableBulkUnits = [];

    // ── Deletion State ────────────────────────────────────────────
    public $deleteTargetId = null;
    public $deleteTargetName = '';

    // ── Disposal State ────────────────────────────────────────────
    public $wasteTargetId = null;
    public $wasteTargetName = '';
    public $wasteTargetQty = 0;
    public $wasteTargetUnit = '';

    protected $queryString = [
        'panel'            => ['except' => 'list'],
        'selectedBranchId' => ['except' => '', 'as' => 'st_branch'],
    ];

    protected $listeners = [
        'inventorySettingsUpdated' => 'onInventorySettingsUpdated',
        'settingsUpdated' => 'onSettingsUpdated',
    ];

    public function mount()
    {
        // General Authorization: Super Admin (1), Admin (2) have full access. Staff (3) has View-Only access.
        if (!auth()->user() || !in_array(auth()->user()->role_id, [1, 2, 3])) {
            abort(403, 'Unauthorized access to stock management.');
        }

        $this->availableUnits = StockHelper::UNITS;
        $this->updateAvailableBulkUnits();
        if (auth()->user()->isSuperAdmin()) {
            $this->selectedBranchId = \App\Services\BranchContext::getActiveBranchId() ?: (Branch::first()?->id ?? '');
        } else {
            $this->selectedBranchId = auth()->user()->branch_id ?? '';
        }

        $this->updateGlobalHeader('list');
    }

    public function triggerExpiryAlerts()
    {
        $cacheKey = 'stock_alert_sent_' . today()->toDateString();
        
        // Only trigger once a day, specifically in the morning (between 8:00 AM and 12:00 PM)
        $hour = (int)now()->format('H');
        if ($hour >= 8 && $hour < 12) {
            if (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin()) {
                // Use atomic cache addition to ensure only one process triggers the email alert globally per day
                $added = \Illuminate\Support\Facades\Cache::add($cacheKey, true, now()->endOfDay());
                if ($added) {
                    $this->checkAndSendExpiryAlerts();
                }
            }
        }
    }

    /**
     * Event-Driven Alert: Check for low stock and expiring/expired batches.
     * Called once per day on first page load by an admin/super admin in the morning.
     * Also called explicitly after any stock save that may drop below minimum.
     */
    public function checkAndSendExpiryAlerts(): void
    {
        try {
            $branchData = []; // [branch_id => ['name' => ..., 'low' => [], 'expiring' => [], 'expired' => []]]
            $today    = Carbon::today();
            $config   = ConfigurationService::getInventoryConfig();
            
            // Critical check: Only proceed if auto-notifications are enabled in settings
            if (!($config['auto_reorder_enabled'] ?? false)) {
                return;
            }

            $alertDays = (int)($config['expiry_alert_days'] ?? 7);
            $nextWeek = Carbon::today()->addDays($alertDays);

            // Gather low stocks
            $stocks = BranchIngredientStock::with(['branch', 'ingredient'])->get();
            foreach ($stocks as $stock) {
                $min = (float) ($stock->ingredient->minimum_stock ?? 0);
                if ($min > 0 && $stock->stock_quantity < $min) {
                    $bid = $stock->branch_id;
                    if (!isset($branchData[$bid])) {
                        $branchData[$bid] = [
                            'name' => $stock->branch->branch_name ?? 'Unknown',
                            'low' => [],
                            'expiring' => [],
                            'expired' => []
                        ];
                    }
                    
                    $branchData[$bid]['low'][] = [
                        'ingredient' => $stock->ingredient->name,
                        'current'    => StockHelper::formatForDisplay($stock->stock_quantity, $stock->ingredient->unit),
                        'minimum'    => StockHelper::formatForDisplay($min, $stock->ingredient->unit),
                    ];
                }
            }

            // Gather batch expiry data
            $batches = StockBatch::with(['branch', 'ingredient'])
                ->whereNotNull('expiry_date')
                ->where('current_quantity', '>', 0)
                ->get();

            foreach ($batches as $batch) {
                $expiry = Carbon::parse($batch->expiry_date)->startOfDay();
                $bid = $batch->branch_id;
                if (!isset($branchData[$bid])) {
                    $branchData[$bid] = [
                        'name' => $batch->branch->branch_name ?? 'Unknown',
                        'low' => [],
                        'expiring' => [],
                        'expired' => []
                    ];
                }

                if ($expiry->lt($today)) {
                    $branchData[$bid]['expired'][] = [
                        'ingredient'  => $batch->ingredient->name,
                        'quantity'    => StockHelper::formatForDisplay($batch->current_quantity, $batch->ingredient->unit),
                        'expiry_date' => $batch->expiry_date,
                    ];
                } elseif ($expiry->lte($nextWeek)) {
                    $branchData[$bid]['expiring'][] = [
                        'ingredient'  => $batch->ingredient->name,
                        'quantity'    => StockHelper::formatForDisplay($batch->current_quantity, $batch->ingredient->unit),
                        'expiry_date' => $batch->expiry_date,
                    ];
                }
            }

            if (empty($branchData)) {
                return;
            }

            $admins = User::whereIn('role_id', [1, 2])->where('is_active', true)->whereNotNull('email')->get();
            foreach ($admins as $admin) {
                $userLow = [];
                $userExpiring = [];
                $userExpired = [];

                foreach ($branchData as $bid => $data) {
                    // Super Admin (role 1) gets everything, Branch Admin (role 2) gets only their assigned branch
                    if ($admin->role_id == 1 || $admin->branch_id == $bid) {
                        if (!empty($data['low'])) $userLow[$data['name']] = $data['low'];
                        if (!empty($data['expiring'])) $userExpiring[$data['name']] = $data['expiring'];
                        if (!empty($data['expired'])) $userExpired[$data['name']] = $data['expired'];
                    }
                }

                // Only send if there is data for this specific user
                if (!empty($userLow) || !empty($userExpiring) || !empty($userExpired)) {
                    $reportData = [
                        'lowStocks' => $userLow,
                        'expiringBatches' => $userExpiring,
                        'expiredBatches' => $userExpired,
                    ];

                    Mail::to($admin->email)->send(new \App\Mail\StockAlertMail($reportData));
                }
            }
        } catch (\Exception $e) {
            // Silently fail — don't interrupt the user's session over an email
            \Log::error('StockAlertMail failed: ' . $e->getMessage());
        }
    }

    /**
     * React to inventory settings updates
     */
    public function onInventorySettingsUpdated($newConfig)
    {
        // Settings were updated in system config, refresh the inventory view
        $this->dispatch('notify', 
            type: 'info',
            message: 'Inventory settings updated. Refresh required for thresholds.'
        );
    }

    /**
     * React to all settings updates
     */
    public function onSettingsUpdated($data)
    {
        if (isset($data['inventory'])) {
            $this->onInventorySettingsUpdated($data['inventory']);
        }
    }



    public function updatedIngredientName()
    {
        $this->validateFieldLive('ingredientName', array_merge(ValidationHelper::rulesName(2, 255), [Rule::unique('ingredients', 'name')->ignore($this->editIngredientId)]), ValidationHelper::commonMessages());
    }

    public function updatedIngredientUnit()
    {
        $this->updateAvailableBulkUnits();
        
        // Reset conversion rows when base unit changes to prevent invalid chain calculations
        if (!empty($this->conversionRows)) {
            $this->conversionRows = [];
            $this->dispatch('notify', type: 'info', message: 'Bulk packaging reset due to Base Unit change.');
        }
    }

    public function updatedIngredientCategoryId($value)
    {
        $this->ingredientCategorySearch = '';
        $this->selectedCategoryName = $value === '' || $value === null
            ? 'Uncategorized'
            : IngredientCategory::find($value)?->name ?? 'Select Category';
    }

    public function selectIngredientCategory($categoryId)
    {
        $this->ingredientCategoryId = $categoryId;
    }

    public function selectIngredientUnit($unit)
    {
        $this->ingredientUnit = $unit;
    }

    private function updateAvailableBulkUnits()
    {
        $allBulk = StockHelper::BULK_UNITS;
        $base = $this->ingredientUnit;
        
        $filtered = [];
        foreach ($allBulk as $key => $label) {
            if ($base === 'ml') {
                if (in_array($key, ['l', 'bottle', 'box', 'can', 'pack', 'bundle', 'tray'])) $filtered[$key] = $label;
            } elseif ($base === 'g') {
                if (in_array($key, ['kg', 'sack', 'pack', 'box', 'bundle', 'can'])) $filtered[$key] = $label;
            } elseif ($base === 'pcs') {
                if (in_array($key, ['box', 'pack', 'bundle', 'tray', 'sack'])) $filtered[$key] = $label;
            } else {
                $filtered[$key] = $label;
            }
        }
        $this->availableBulkUnits = $filtered ?: $allBulk;
    }

    public function updatedIngredientMinStock()
    {
        $this->validateFieldLive('ingredientMinStock', ValidationHelper::RULES_PRICE, ValidationHelper::commonMessages());
    }

    // ── Role Helpers ──────────────────────────────────────────────





    public function isSuperAdmin(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function isAdmin(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function isStaff(): bool
    {
        return auth()->user()?->isStaff() ?? false;
    }

    public function confirmWasteBatch($id)
    {
        if (auth()->user()->isStaff()) return;
        
        $batch = StockBatch::with('ingredient')->findOrFail($id);
        $this->wasteTargetId = $batch->id;
        $this->wasteTargetName = $batch->ingredient->name ?? 'Unknown Ingredient';
        $this->wasteTargetQty = $batch->current_quantity;
        $this->wasteTargetUnit = $batch->ingredient->unit ?? 'pcs';
        
        $this->dispatch('open-modal', name: 'confirm-waste-batch');
    }

    public function wasteBatch()
    {
        if (auth()->user()->isStaff()) abort(403);
        if (!$this->wasteTargetId) return;

        $batch = StockBatch::with('ingredient', 'branch')->findOrFail($this->wasteTargetId);

        if ($batch->current_quantity <= 0) {
            $this->dispatch('notify', type: 'warning', message: 'Batch is already empty.');
            return;
        }

        DB::transaction(function () use ($batch) {
            $qty = $batch->current_quantity;
            $batch->current_quantity = 0;
            $batch->save();

            $stock = BranchIngredientStock::where('branch_id', $batch->branch_id)
                ->where('ingredient_id', $batch->ingredient_id)->first();
            if ($stock) {
                $stock->stock_quantity = max(0, $stock->stock_quantity - $qty);
                $stock->save();
            }

            StockMovement::create([
                'branch_id'     => $batch->branch_id,
                'ingredient_id' => $batch->ingredient_id,
                'type'          => 'waste',
                'quantity'      => $qty,
                'unit_cost'     => $batch->ingredient->cost ?? 0,
                'user_id'       => auth()->id(),
                'remarks'       => 'Expired batch disposed — Batch #' . $batch->id . ' (Expiry: ' . $batch->expiry_date . ')',
            ]);
        });

        $this->wasteTargetId = null;
        $this->dispatch('close-modal', name: 'confirm-waste-batch');
        $this->dispatch('notify', type: 'success', message: 'Batch disposed and logged as waste.');
    }

    // ── UI Actions ────────────────────────────────────────────────
    public function showCreate()
    {
        if (auth()->user()->isStaff()) return;
        if (!auth()->user()->isSuperAdmin()) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized action.');
            return;
        }
        $this->resetIngredientForm();
        $this->updateGlobalHeader('create');
        $this->dispatch('switch-panel', panel: 'form', mode: 'create');
    }

    public function showEdit($id)
    {
        if (auth()->user()->isStaff()) return;
        if (!auth()->user()->isSuperAdmin()) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized action.');
            return;
        }
        $ing = Ingredient::with('unitConversions')->findOrFail($id);
        $this->editIngredientId = $ing->id;
        $this->ingredientName = $ing->name;
        $this->ingredientCategoryId = $ing->category_id;
        $this->ingredientUnit = $ing->unit;
        $this->updateAvailableBulkUnits();
        $this->ingredientMinStock = $ing->minimum_stock;
        $this->ingredientCost = $ing->cost;
        $this->ingredientScope = 'global';

        // Load existing conversion rows and reverse-compute chain fields
        $conversions = $ing->unitConversions->sortBy('sort_order')->values();
        $this->conversionRows = [];
        
        foreach ($conversions as $i => $c) {
            // Reverse-compute chain_multiplier and chain_from_index from qty_in_base
            $chainFromIndex = 'base';
            $chainMultiplier = $c->qty_in_base;
            
            // Check if this row chains from a previous row
            foreach ($this->conversionRows as $j => $prevRow) {
                $prevQty = (float) $prevRow['qty_in_base'];
                if ($prevQty > 0) {
                    $ratio = $c->qty_in_base / $prevQty;
                    $rounded = round($ratio, 4);
                    if ($ratio > 0.9999 && abs($ratio - $rounded) < 0.0001 && $rounded == round($rounded, 0)) {
                        $chainFromIndex = $j;
                        $chainMultiplier = $rounded;
                        break;
                    }
                }
            }
            
            $this->conversionRows[] = [
                'unit_name'        => $c->unit_name,
                'qty_in_base'      => $c->qty_in_base,
                'price_per_unit'   => $c->price_per_unit,
                'sort_order'       => $c->sort_order,
                'chain_multiplier' => $chainMultiplier,
                'chain_from_index' => $chainFromIndex,
            ];
        }
        
        $this->resetValidation();
        $this->updateGlobalHeader('edit');
        $this->dispatch('switch-panel', panel: 'form', mode: 'edit');
    }

    // ── Packaging Conversion Rows Management ─────────────────────

    public function addConversionRow(): void
    {
        $this->conversionRows[] = [
            'unit_name'       => '',
            'qty_in_base'     => '',
            'price_per_unit'  => '',
            'sort_order'      => count($this->conversionRows),
            // Calculator helper fields (not stored in DB directly)
            'chain_multiplier' => '',
            'chain_from_index' => null,
        ];
    }

    public function removeConversionRow(int $index): void
    {
        unset($this->conversionRows[$index]);
        // Re-index and update sort_order
        $this->conversionRows = array_values($this->conversionRows);
        foreach ($this->conversionRows as $i => &$row) {
            $row['sort_order'] = $i;
        }
    }



    /**
     * Auto-compute qty_in_base when the chain calculator fields change.
     * Called by Livewire when any conversionRows.*.chain_* field updates.
     */
    public function setConversionLink(int $rowIndex, $fromIndex): void
    {
        if (!isset($this->conversionRows[$rowIndex])) {
            return;
        }
        // Log for debugging when selection is made from the UI
        try {
            \Log::info('setConversionLink called', ['rowIndex' => $rowIndex, 'fromIndex' => $fromIndex]);
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        $this->conversionRows[$rowIndex]['chain_from_index'] = $fromIndex === 'base' ? 'base' : (int) $fromIndex;
        $this->computeQtyInBase($rowIndex);
        $this->cascadeRecompute($rowIndex + 1);
    }

    public function updateConversionMultiplier(int $rowIndex): void
    {
        if (!isset($this->conversionRows[$rowIndex])) {
            return;
        }

        $multiplier = (float) ($this->conversionRows[$rowIndex]['chain_multiplier'] ?? 0);
        if ($multiplier <= 0) {
            $this->conversionRows[$rowIndex]['qty_in_base'] = '';
            return;
        }

        if ($this->conversionRows[$rowIndex]['chain_from_index'] === null || $this->conversionRows[$rowIndex]['chain_from_index'] === '') {
            $this->conversionRows[$rowIndex]['chain_from_index'] = 'base';
        }

        $this->computeQtyInBase($rowIndex);
        $this->cascadeRecompute($rowIndex + 1);
    }

    // Note: conversion link updates are handled explicitly via `setConversionLink`
    // which is invoked from the UI (now using Alpine `$wire.call(...)`).

    /**
     * Compute qty_in_base for a row from its chain calculator fields.
     * Chain: multiplier × previous_row_qty_in_base = qty_in_base
     */
    private function computeQtyInBase(int $index): void
    {
        $row = $this->conversionRows[$index];
        $multiplier = (float) ($row['chain_multiplier'] ?? 0);
        $fromIndex  = $row['chain_from_index'] ?? null;

        if ($multiplier <= 0) {
            // Clear qty_in_base if multiplier is invalid
            $this->conversionRows[$index]['qty_in_base'] = '';
            return;
        }

        // Default chain_from_index to 'base' if null or empty string
        if ($fromIndex === null || $fromIndex === '') {
            $fromIndex = 'base';
            $this->conversionRows[$index]['chain_from_index'] = 'base';
        }

        if ($fromIndex === 'base') {
            // Multiply against the ingredient's base unit directly
            // qty_in_base = multiplier × 1 base unit
            $this->conversionRows[$index]['qty_in_base'] = $multiplier;
        } else {
            $fromIndex = (int) $fromIndex;
            if (isset($this->conversionRows[$fromIndex])) {
                $prevQty = (float) ($this->conversionRows[$fromIndex]['qty_in_base'] ?? 0);
                if ($prevQty > 0) {
                    $this->conversionRows[$index]['qty_in_base'] = $multiplier * $prevQty;
                } else {
                    // Previous row doesn't have valid qty_in_base yet
                    $this->conversionRows[$index]['qty_in_base'] = '';
                }
            }
        }
    }

    /**
     * After a row's qty_in_base changes, cascade-recompute any subsequent rows
     * that chain from this one.
     */
    private function cascadeRecompute(int $startIndex): void
    {
        for ($i = $startIndex; $i < count($this->conversionRows); $i++) {
            $fromIndex = $this->conversionRows[$i]['chain_from_index'] ?? null;
            if ($fromIndex !== null && $fromIndex !== '') {
                $this->computeQtyInBase($i);
            }
        }
    }



    public function backToList()
    {
        $this->resetIngredientForm();
        $this->updateGlobalHeader('list');
        $this->dispatch('switch-panel', panel: 'list', mode: 'list');
        $this->resetPage();
    }



    public function validateBeforeSaveIngredient()
    {
        if (!auth()->user()->isSuperAdmin()) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized action.');
            return;
        }

        $rules = [
            'ingredientName'       => ['required', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME, Rule::unique('ingredients', 'name')->ignore($this->editIngredientId)],
            'ingredientCategoryId' => ['nullable', 'exists:ingredient_categories,id'],
            'ingredientUnit'       => ['required', 'in:' . implode(',', array_keys($this->availableUnits))],
            'ingredientMinStock'   => ['required', 'numeric', 'min:0'],
            
            // Dynamic Conversion Rows Validation
            'conversionRows'                  => ['nullable', 'array'],
            'conversionRows.*.unit_name'      => ['required_with:conversionRows.*.chain_multiplier', 'nullable', 'string', 'in:' . implode(',', array_keys($this->availableBulkUnits))],
            'conversionRows.*.chain_multiplier' => ['required_with:conversionRows.*.unit_name', 'nullable', 'numeric', 'min:0.0001'],
            'conversionRows.*.price_per_unit'   => ['nullable', 'numeric', 'min:0'],
        ];

        if ($this->ingredientScope === 'branch') {
            $rules['assignedBranchIds'] = ['required', 'array', 'min:1'];
            $rules['assignedBranchIds.*'] = ['exists:branches,id'];
        }

        $this->validateBeforeModal($rules, ValidationHelper::commonMessages(), 'confirm-save-ingredient');
    }

    // ── Actions: Ingredients ──────────────────────────────────────
    public function saveIngredient()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized ingredient catalog modification.');
        }

        $rules = [
            'ingredientName'       => ['required', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME, Rule::unique('ingredients', 'name')->ignore($this->editIngredientId)],
            'ingredientCategoryId' => ['nullable', 'exists:ingredient_categories,id'],
            'ingredientUnit'       => ['required', 'in:' . implode(',', array_keys($this->availableUnits))],
            'ingredientMinStock'   => ['required', 'numeric', 'min:0'],
            
            // Dynamic Conversion Rows Validation
            'conversionRows'                  => ['nullable', 'array'],
            'conversionRows.*.unit_name'      => ['required_with:conversionRows.*.chain_multiplier', 'nullable', 'string', 'in:' . implode(',', array_keys($this->availableBulkUnits))],
            'conversionRows.*.chain_multiplier' => ['required_with:conversionRows.*.unit_name', 'nullable', 'numeric', 'min:0.0001'],
            'conversionRows.*.price_per_unit'   => ['nullable', 'numeric', 'min:0'],
        ];

        if ($this->ingredientScope === 'branch') {
            $rules['assignedBranchIds'] = ['required', 'array', 'min:1'];
            $rules['assignedBranchIds.*'] = ['exists:branches,id'];
        }

        // Validate and close modal on error, show scroll-to-error
        $this->validateSecure($rules, ValidationHelper::commonMessages(), [], 'confirm-save-ingredient');



        $this->ingredientName = $this->normalizeString($this->ingredientName);

        $updateData = [
            'name'          => $this->ingredientName,
            'scope'         => 'global',
            'category_id'   => $this->ingredientCategoryId ?: null,
            'unit'          => $this->ingredientUnit,
            'minimum_stock' => $this->ingredientMinStock ?: 0,
        ];

        // Auto-compute cost per base unit from the cheapest conversion tier
        // This is used for recipe costing: price per gram/ml/pcs
        $bestCostPerBase = null;
        foreach ($this->conversionRows as $row) {
            $qtyInBase  = (float) ($row['qty_in_base'] ?? 0);
            $price      = (float) ($row['price_per_unit'] ?? 0);
            if ($qtyInBase > 0 && $price > 0) {
                $costPerBase = $price / $qtyInBase;
                if ($bestCostPerBase === null || $costPerBase < $bestCostPerBase) {
                    $bestCostPerBase = $costPerBase;
                }
            }
        }
        if ($bestCostPerBase !== null) {
            $updateData['cost'] = $bestCostPerBase;
        }

        DB::transaction(function () use ($updateData) {
            if ($this->editIngredientId) {
                $ing = Ingredient::find($this->editIngredientId);
                $ing->update($updateData);
            } else {
                $ing = Ingredient::create($updateData);
            }

            // ── Sync Packaging Conversion Rows ───────────────────────────
            // Delete removed rows, upsert the rest
            $keepUnitNames = [];
            foreach ($this->conversionRows as $i => $row) {
                $unitName = strtolower(trim($row['unit_name'] ?? ''));
                $qtyInBase = (float) ($row['qty_in_base'] ?? 0);
                $price     = (float) ($row['price_per_unit'] ?? 0);

                if (!$unitName || $qtyInBase <= 0) continue; // skip blank rows

                $keepUnitNames[] = $unitName;
                \App\Models\IngredientUnitConversion::updateOrCreate(
                    ['ingredient_id' => $ing->id, 'unit_name' => $unitName],
                    ['qty_in_base' => $qtyInBase, 'price_per_unit' => $price, 'sort_order' => $i]
                );
            }
            // Delete any conversion rows that were removed in the UI
            if (!empty($keepUnitNames)) {
                \App\Models\IngredientUnitConversion::where('ingredient_id', $ing->id)
                    ->whereNotIn('unit_name', $keepUnitNames)
                    ->delete();
            } else {
                // All rows removed → clear all conversions
                \App\Models\IngredientUnitConversion::where('ingredient_id', $ing->id)->delete();
            }

            // ── Sync branch availability/stocks - ALL GLOBAL ─────────────
            $allBranchIds = Branch::pluck('id')->toArray();
            foreach ($allBranchIds as $bId) {
                BranchIngredientStock::firstOrCreate(
                    ['branch_id' => $bId, 'ingredient_id' => $ing->id],
                    ['stock_quantity' => 0]
                );
            }
        });

        $msg = $this->editIngredientId ? 'Ingredient updated successfully.' : 'Ingredient created and assigned successfully.';
        $this->dispatch('notify', type: 'success', message: $msg);
        $this->dispatch('close-modal', name: 'confirm-save-ingredient');
        $this->backToList();
    }

    public function quickAddCategory()
    {
        if (auth()->user()->isStaff()) abort(403);
        if (!auth()->user()->isSuperAdmin()) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized action.');
            return;
        }

        $this->validateSecure([
            'newCategoryName' => ['required', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME, 'unique:ingredient_categories,name']
        ], [
            'newCategoryName.regex' => 'Category name has invalid characters.',
        ]);

        $cat = IngredientCategory::create([
            'name' => $this->newCategoryName
        ]);

        $this->newCategoryName = '';
        $this->ingredientCategoryId = $cat->id; // Auto select for draft

        $this->dispatch('notify', type: 'success', message: 'New ingredient category ' . $cat->name . ' launched.');
        $this->dispatch('close-modal', name: 'quick-add-ingredient-category');
    }

    public function confirmIngredientDeletion($id)
    {
        if (auth()->user()->isStaff()) return;
        if (!auth()->user()->isSuperAdmin()) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized action.');
            return;
        }

        $ing = Ingredient::findOrFail($id);
        $this->deleteTargetId = $ing->id;
        $this->deleteTargetName = $ing->name;
        $this->dispatch('open-modal', name: 'confirm-delete-ingredient');
    }

    public function deleteIngredient()
    {
        if (auth()->user()->isStaff()) abort(403);
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized ingredient deletion.');
        }

        if (!$this->deleteTargetId) return;

        $name = $this->deleteTargetName;
        Ingredient::findOrFail($this->deleteTargetId)->delete();
        
        $this->deleteTargetId = null;
        $this->deleteTargetName = '';
        
        $this->dispatch('close-modal', name: 'confirm-delete-ingredient');
        $this->dispatch('notify', type: 'success', message: 'Ingredient "' . $name . '" deleted globally.');
        $this->backToList();
    }







    // ── Helpers ───────────────────────────────────────────────────
    private function resetIngredientForm()
    {
        $this->editIngredientId = null;
        $this->ingredientName = '';
        $this->ingredientCategoryId = '';
        $this->ingredientUnit = 'pcs';
        $this->updateAvailableBulkUnits();
        $this->ingredientScope = 'global';
        $this->ingredientMinStock = '';
        $this->ingredientCost = 0;
        $this->conversionRows = [];
        $this->resetValidation();
    }

    private function updateGlobalHeader($panel = 'list')
    {
        $title = 'Stock & Ingredients';
        $breadcrumbs = [
            ['label' => 'Inventory', 'url' => '#'],
            ['label' => $title, 'url' => route('stock.index')],
        ];

        if ($panel === 'create') {
            $breadcrumbs[] = ['label' => 'Add Catalog Item', 'url' => '#'];
            $title = 'New Ingredient';
        } elseif ($panel === 'edit') {
            $breadcrumbs[] = ['label' => 'Edit Catalog Item', 'url' => '#'];
            $title = 'Edit Ingredient';
        } elseif ($panel === 'log') {
            $breadcrumbs[] = ['label' => 'Movement Log', 'url' => '#'];
            $title = 'Stock Movement Log';
        }

        $this->dispatch('setHeader', 
            icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            title: $title,
            breadcrumbs: $breadcrumbs
        );
    }

    // ── Render ────────────────────────────────────────────────────
    public function render()
    {
        $inventoryConfig = ConfigurationService::getInventoryConfig();
        $today = Carbon::today();
        $alertDays = (int)($inventoryConfig['expiry_alert_days'] ?? 7);

        // Ensure a branch is always selected
        if (!$this->selectedBranchId) {
            $this->selectedBranchId = \App\Services\BranchContext::getActiveBranchId() ?: (Branch::first()?->id ?? '');
        }
        $branchId = $this->selectedBranchId;

        // ── Component Data ──
        $branches = $this->getAvailableBranches();
        $kpis = $this->getKpiMetrics($branchId, $today, $alertDays, $inventoryConfig);
        $expiryTracking = $this->getExpiryTracking($branchId, $today, $alertDays);
        
        $ingredientCategories = IngredientCategory::orderBy('name', 'asc')->get();
        $this->updateCategorySelection($ingredientCategories);

        return view('livewire.stock-management', array_merge([
    'branches' => $branches,
    'ingredientCategories' => $this->getFilteredCategories($ingredientCategories),
    'allIngredientCategories' => IngredientCategory::orderBy('name')->get(),
    'inventoryConfig' => $inventoryConfig,
    'alertDays' => $alertDays,
], $kpis, $expiryTracking))->layout('layouts.app');
    }

    private function getAvailableBranches()
    {
        if (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin()) {
            return Branch::orderBy('branch_name', 'asc')->get();
        }
        return collect([]);
    }

    private function getKpiMetrics($branchId, $today, $alertDays, $inventoryConfig)
    {
        $totalIngredients = Ingredient::count();
        $lowStockWarnings = 0;
        $expiringCount = 0;
        $monthlyProcurement = 0;

        if ($branchId) {
            $globalLow = (int)($inventoryConfig['low_stock_threshold'] ?? 10);
            $lowStockWarnings = DB::table('ingredients')
                ->leftJoin('branch_ingredient_stocks', function ($join) use ($branchId) {
                    $join->on('ingredients.id', '=', 'branch_ingredient_stocks.ingredient_id')
                         ->where('branch_ingredient_stocks.branch_id', '=', $branchId);
                })
                ->whereRaw('COALESCE(branch_ingredient_stocks.stock_quantity, 0) <= CASE WHEN ingredients.minimum_stock > 0 THEN ingredients.minimum_stock ELSE ? END', [$globalLow])
                ->count();

            $expiringCount = StockBatch::where('branch_id', $branchId)
                ->where('current_quantity', '>', 0)
                ->where('expiry_date', '>', $today)
                ->where('expiry_date', '<=', $today->copy()->addDays($alertDays))
                ->count();

            $monthlyProcurement = StockMovement::where('branch_id', $branchId)
                ->where('type', 'in')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum(DB::raw('unit_cost * quantity'));
        }

        $allIngredients = Ingredient::with(['branchStocks', 'category'])->get()->sort(function ($a, $b) use ($branchId, $inventoryConfig) {
            $statusOrder = ['healthy' => 0, 'low' => 1, 'critical' => 2];
            $statusA = $this->getIngredientStockStatus($a, $branchId, $inventoryConfig);
            $statusB = $this->getIngredientStockStatus($b, $branchId, $inventoryConfig);

            if ($statusA !== $statusB) {
                return $statusOrder[$statusA] <=> $statusOrder[$statusB];
            }

            return strcasecmp($a->name, $b->name);
        })->values();

        return [
            'totalIngredients' => $totalIngredients,
            'lowStockWarnings' => $lowStockWarnings,
            'expiringCount' => $expiringCount,
            'monthlyProcurement' => $monthlyProcurement,
            'allIngredients' => $allIngredients,
        ];
    }

    private function getIngredientStockStatus($ingredient, $branchId, $inventoryConfig)
    {
        $globalLow = (int)($inventoryConfig['low_stock_threshold'] ?? 10);
        $globalCritical = (int)($inventoryConfig['critical_stock_threshold'] ?? 5);
        $effectiveMin = $ingredient->minimum_stock > 0 ? $ingredient->minimum_stock : $globalLow;

        $stockQuantity = $branchId
            ? (collect($ingredient->branchStocks)->where('branch_id', $branchId)->first()?->stock_quantity ?? 0)
            : collect($ingredient->branchStocks)->sum('stock_quantity');

        if ($stockQuantity <= $globalCritical) {
            return 'critical';
        }

        if ($stockQuantity <= $effectiveMin) {
            return 'low';
        }

        return 'healthy';
    }

    private function getExpiryTracking($branchId, $today, $alertDays)
    {
        $nextWeek = $today->copy()->addDays($alertDays);

        $allBatches = StockBatch::with(['ingredient', 'branch'])
            ->where('current_quantity', '>', 0)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByRaw("CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END ASC")
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(function($b) use ($today, $nextWeek) {
                $expiry = $b->expiry_date ? \Carbon\Carbon::parse($b->expiry_date)->startOfDay() : null;
                $status = 'no_date';
                if ($expiry) {
                    if ($expiry->lt($today->startOfDay())) {
                        $status = 'expired';
                    } elseif ($expiry->lte($nextWeek->startOfDay())) {
                        $status = 'expiring';
                    } else {
                        $status = 'fresh';
                    }
                }
                $b->computed_status = $status;
                return $b;
            });

        $expiryStats = [
            'expired'  => StockBatch::where('current_quantity', '>', 0)->where('expiry_date', '<', $today)->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count(),
            'expiring' => StockBatch::where('current_quantity', '>', 0)->whereBetween('expiry_date', [$today, $nextWeek])->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count(),
            'fresh'    => StockBatch::where('current_quantity', '>', 0)->where('expiry_date', '>', $nextWeek)->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count(),
            'no_date'  => StockBatch::where('current_quantity', '>', 0)->whereNull('expiry_date')->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count(),
        ];

        return [
            'allBatches' => $allBatches,
            'expiryStats' => $expiryStats
        ];
    }

    private function updateCategorySelection($ingredientCategories)
    {
        $this->selectedCategoryName = $this->ingredientCategoryId 
            ? ($ingredientCategories->firstWhere('id', $this->ingredientCategoryId)?->name ?? 'Select Category')
            : 'Select Category';
    }

    private function getFilteredCategories($categories)
    {
        if ($this->ingredientCategorySearch) {
            return $categories->filter(fn($c) => 
                str_contains(strtolower($c->name), strtolower($this->ingredientCategorySearch))
            );
        }
        return $categories;
    }

    // ── Reports ───────────────────────────────────────────────────
    public function exportPdf()
    {
        $data = $this->getExportDataForReport();
        return $this->generatePdfReport('reports.template', $data, 'Stock_Report_' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportCsv()
    {
        $stocks = $this->getExportData();
        $data = $stocks->map(fn($s) => [
            'ID' => $s->id,
            'Branch' => $s->branch->branch_name,
            'Ingredient' => $s->ingredient->name,
            'Category' => $s->ingredient->category?->name ?? 'Uncategorized',
            'Quantity' => $s->stock_quantity . ' ' . $s->ingredient->unit,
            'Min Stock' => $s->ingredient->minimum_stock,
            'Status' => $s->stock_quantity < $s->ingredient->minimum_stock ? 'Low Stock' : 'Good'
        ])->toArray();

        return $this->generateCsvReport('Stock_Report_' . now()->format('Y-m-d') . '.csv', $data);
    }

    public function exportExcel()
    {
        return $this->exportCsv();
    }

    private function getExportDataForReport(): array
    {
        $stocks = $this->getExportData();
        $branch = Branch::find($this->selectedBranchId)?->branch_name ?? 'Global';

        $lowItems  = $stocks->filter(fn($s) => $s->stock_quantity < $s->ingredient->minimum_stock);
        $goodItems = $stocks->filter(fn($s) => $s->stock_quantity >= $s->ingredient->minimum_stock);

        $allRows = $stocks->map(fn($s) => [
            $s->ingredient->name,
            $s->ingredient->category?->name ?? 'Uncategorized',
            $s->stock_quantity . ' ' . $s->ingredient->unit,
            $s->ingredient->minimum_stock . ' ' . $s->ingredient->unit,
            $s->stock_quantity < $s->ingredient->minimum_stock ? 'LOW STOCK' : 'Good',
        ])->toArray();

        $lowRows = $lowItems->map(fn($s) => [
            $s->ingredient->name,
            $s->ingredient->category?->name ?? 'N/A',
            $s->stock_quantity . ' ' . $s->ingredient->unit,
            $s->ingredient->minimum_stock . ' ' . $s->ingredient->unit,
            number_format($s->ingredient->minimum_stock - $s->stock_quantity, 2) . ' ' . $s->ingredient->unit . ' needed',
        ])->toArray();

        return [
            'reportType'  => 'stock',
            'title'       => 'Inventory Status Report',
            'subtitle'    => 'Stock Levels, Low Alerts & Expiration Overview',
            'period'      => 'As of ' . now()->format('F d, Y'),
            'branch'      => $branch,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'kpis' => [
                'Total Ingredients'   => number_format($stocks->count()),
                'Healthy Stock Items' => number_format($goodItems->count()),
                'Low Stock Items'     => number_format($lowItems->count()),
                'Active Branch'       => $branch,
            ],
            'sections' => [
                [
                    'title'   => 'Low Stock Alerts',
                    'headers' => ['Ingredient', 'Category', 'Current Stock', 'Minimum Required', 'Deficit'],
                    'rows'    => $lowRows,
                    'empty'   => 'All stock levels are within acceptable limits.',
                    'highlight' => true,
                ],
                [
                    'title'   => 'Full Inventory Listing',
                    'headers' => ['Ingredient', 'Category', 'Current Stock', 'Min. Required', 'Status'],
                    'rows'    => $allRows,
                    'empty'   => 'No inventory data found.',
                ],
            ],
        ];
    }

    private function getExportData()
    {
        return BranchIngredientStock::with(['ingredient.category', 'branch'])
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->get();
    }
}
