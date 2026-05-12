<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Ingredient;
use App\Models\Branch;
use App\Models\BranchIngredientStock;
use App\Models\StockMovement;
use App\Models\StockBatch;
use App\Models\User;
use App\Helpers\StockHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Livewire\WithPagination;

use App\Traits\HandlesValidations;
use App\Helpers\ValidationHelper;

/**
 * StockAdjustment Component
 * 
 * High-performance, enterprise-grade inventory management module.
 * Handles movement logging, bulk physical count reconciliation, and FEFO batch tracking.
 */
class StockAdjustment extends Component
{
    use WithPagination, HandlesValidations;

    // View State
    public $panel = 'list';
    public $search = '';
    public $perPage = 10;
    
    // Core Data
    public $selectedBranchId = '';
    public $mainBranchId = '';
    public $rows = []; // Queue: [ingredient_id, ingredient_name, type, quantity, unit, cost, expiry]
    public $bulkAdjustments = []; 
    public $globalReference = '';
    public $globalRemarks = '';
    public $viewingReferenceId;
    public $viewingMovements = [];

    /**
     * Standard rules property to prevent "Missing rules" exception
     */
    protected function rules()
    {
        return [
            'globalReference' => ['required', 'string', 'max:50'],
            'newItemId' => 'nullable|exists:ingredients,id',
            'newItemQty' => 'nullable|numeric|min:0',
            'selectedBranchId' => 'required',
        ];
    }

    // New Item Form (Sidebar)
    public $newItemId = '';
    public $newItemQty = '';
    public $newItemType = 'waste';
    public $newItemCost = '';
    public $newItemExpiry = '';
    public $newItemUnit = '';          // base unit (g, ml, pcs) — for display
    public $newItemSelectedUnit = ''; // user-selected unit (may be base or bulk packaging)

    protected $queryString = [
        'panel' => ['except' => 'list'],
        'search' => ['except' => ''],
        'selectedBranchId' => ['except' => ''],
    ];

    protected $listeners = [
        'trigger-adjust-stock' => 'handleQuickAdjustment',
    ];

    public function mount($id = null)
    {
        // Enterprise Authorization: Restrict to Management roles
        if (!auth()->user() || !in_array(auth()->user()->role_id, [1, 2])) {
            abort(403, 'Unauthorized: Access restricted to Management.');
        }

        // Initialize Branch Context
        if ($this->isSuperAdmin()) {
            $this->selectedBranchId = \App\Services\BranchContext::getActiveBranchId() ?: (Branch::first()?->id ?? '');
        } else {
            $this->selectedBranchId = auth()->user()->branch_id;
        }

        // Handle direct deep-link or event trigger
        if ($id) {
            $this->handleQuickAdjustment($id);
        } else {
            $this->generateReference();
        }

        // Identify the Dynamic Main Branch
        $this->mainBranchId = Branch::where('is_main', true)->first()?->id ?? 1;

        // Validation: If non-main branch selected, default type to 'waste'
        if ((string)$this->selectedBranchId !== (string)$this->mainBranchId) {
            $this->newItemType = 'waste';
        } else {
            $this->newItemType = 'in';
        }

        $this->updateHeader();

        if ($this->panel === 'bulk') {
            $this->startBulkAdjustment();
        }
    }

    public function viewAdjustment($refId)
    {
        $this->viewingReferenceId = $refId;
        $this->viewingMovements = StockMovement::with(['ingredient', 'user'])
            ->where('reference_id', $refId)
            ->get();
        
        $this->dispatch('open-modal', name: 'view-adjustment-details');
    }

    public function closeView()
    {
        $this->viewingReferenceId = null;
        $this->viewingMovements = [];
    }

    // ── Lifecycle & Sync ──────────────────────────────────────────

    private function updateHeader()
    {
        $this->dispatch('setHeader', 
            icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            title: 'Inventory Logistics',
            breadcrumbs: [
                ['label' => 'Logistics', 'url' => '#'],
                ['label' => 'Adjustments', 'url' => '#'],
            ]
        );
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedBranchId()
    {
        $this->resetPage();

        // Validation: If non-main branch selected and type is 'in', reset to 'waste'
        if ((string)$this->selectedBranchId !== (string)$this->mainBranchId && $this->newItemType === 'in') {
            $this->newItemType = 'waste';
        } elseif ((string)$this->selectedBranchId === (string)$this->mainBranchId && $this->newItemType !== 'in') {
             // Optional: flip to 'in' if we switch TO main branch and we were on waste?
             // Maybe better to just leave it if they already picked something.
        }

        if ($this->panel === 'bulk') {
            $this->startBulkAdjustment();
        }
    }

    // ── Adjustment Entry Management ────────────────────────────────

    public function handleQuickAdjustment($ingredientId = null)
    {
        $this->reset(['rows', 'globalReference', 'globalRemarks', 'newItemId', 'newItemQty', 'newItemType', 'newItemCost', 'newItemExpiry']);
        $this->globalReference = 'ADJ-' . now()->format('Ymd') . '-' . str_pad(rand(1, 99), 2, '0', STR_PAD_LEFT);
        
        if ($ingredientId) {
            $this->newItemId = $ingredientId;
            $ing = Ingredient::find($ingredientId);
            $this->newItemUnit = $ing ? StockHelper::getDefaultInputUnit($ing->unit) : '';
        }

        $this->panel = 'adjust';
    }

    public function updatedNewItemId($value)
    {
        if ($value) {
            $ing = Ingredient::with('unitConversions')->find($value);
            $this->newItemUnit = $ing ? StockHelper::getDefaultInputUnit($ing->unit) : '';
            $this->newItemSelectedUnit = $this->newItemUnit; // default to base
            // Auto-fill cost from ingredient's stored cost
            $this->newItemCost = $ing ? $ing->cost : '';
        } else {
            $this->newItemUnit = '';
            $this->newItemSelectedUnit = '';
            $this->newItemCost = '';
        }
    }

    public function updatedNewItemSelectedUnit($unitName)
    {
        if (!$this->newItemId) return;

        $ing = Ingredient::with('unitConversions')->find($this->newItemId);
        if (!$ing) return;

        if ($unitName === $ing->unit) {
            $this->newItemCost = $ing->cost;
        } else {
            $conv = $ing->unitConversions->where('unit_name', $unitName)->first();
            if ($conv) {
                // If the conversion has a specific price defined, use it. 
                // Otherwise, fall back to base price * multiplier as an estimate.
                if ($conv->price_per_unit > 0) {
                    $this->newItemCost = $conv->price_per_unit;
                } else {
                    $this->newItemCost = (float)$ing->cost * $conv->qty_in_base;
                }
            }
        }
    }

    public function addToQueue()
    {
        $this->validate([
            'newItemId'     => 'required|exists:ingredients,id',
            'newItemQty'    => 'required|numeric|min:0.01',
            'newItemType'   => 'required|in:in,out,waste,adjust,return_to_supplier',
            'newItemExpiry' => 'nullable|date|after_or_equal:today',    
            'newItemCost'   => 'nullable|numeric|min:0',
        ], [
            'newItemId.required'  => 'Select an ingredient.',
            'newItemQty.required' => 'Quantity is required.',
            'newItemType.required'=> 'Select a type.',
        ]);

        $ing = Ingredient::with('unitConversions')->find($this->newItemId);
        $inputQty   = (float) $this->newItemQty;
        $selectedUnit = $this->newItemSelectedUnit ?: $this->newItemUnit;

        // ── Convert to base units using the universal converter ──
        // e.g. 2 boxes × 18,000 ml/box = 36,000 ml stored
        $qtyInBase = StockHelper::convertToBase($inputQty, $selectedUnit, $ing);

        // Auto-compute unit_cost from conversion table if stocking in bulk
        $unitCostPerBase = StockHelper::getPricePerBase($selectedUnit, $ing);
        $costToStore = $this->newItemCost ?: ($unitCostPerBase > 0 ? $unitCostPerBase : null);

        $this->rows[] = [
            'ingredient_id'   => $ing->id,
            'ingredient_name' => $ing->name,
            'type'            => $this->newItemType,
            'quantity'        => $qtyInBase,          // ALWAYS in base units
            'display_qty'     => $inputQty,           // for readability in the queue
            'display_unit'    => $selectedUnit,       // e.g. 'box', 'bottle', 'g'
            'unit'            => $ing->unit,          // base unit (g/ml/pcs)
            'cost'            => $costToStore,
            'price'           => null, // Ingredients track cost, but we add key to prevent undefined error
            'expiry'          => $this->newItemExpiry ?: null,
            'remarks'         => null,
        ];

        // Reset form
        $this->reset(['newItemId', 'newItemQty', 'newItemCost', 'newItemExpiry', 'newItemUnit', 'newItemSelectedUnit']);
        $this->newItemType = ((string)$this->selectedBranchId !== (string)$this->mainBranchId) ? 'waste' : 'in';
    }

    public function removeRow($index)
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function backToList()
    {
        $this->panel = 'list';
        $this->reset(['rows', 'globalReference', 'globalRemarks', 'newItemId', 'newItemQty']);
        $this->generateReference();
    }

    // ── Physical Count Reconciliation ──────────────────────────────

    public function startBulkAdjustment()
    {
        if (empty($this->selectedBranchId)) return;

        $this->bulkAdjustments = [];
        $ingredients = Ingredient::orderBy('name')->get();
        $stocks = BranchIngredientStock::where('branch_id', $this->selectedBranchId)
            ->pluck('stock_quantity', 'ingredient_id');

        foreach ($ingredients as $ing) {
            $current = (float)($stocks[$ing->id] ?? 0);
            $this->bulkAdjustments[$ing->id] = [
                'name' => $ing->name,
                'unit' => $ing->unit,
                'current' => $current,
                'actual' => '',
                'variance' => 0,
                'avg_cost' => $ing->cost ?? 0,
            ];
        }

        $this->panel = 'bulk';
    }

    public function updatedBulkAdjustments($value, $key)
    {
        // key: "5.actual"
        if (str_contains($key, '.actual')) {
            $id = explode('.', $key)[0];
            $actual = $this->bulkAdjustments[$id]['actual'] === '' ? null : (float)$this->bulkAdjustments[$id]['actual'];
            
            if ($actual !== null) {
                $current = $this->bulkAdjustments[$id]['current'];
                $this->bulkAdjustments[$id]['variance'] = $actual - $current;
            } else {
                $this->bulkAdjustments[$id]['variance'] = 0;
            }
        }
    }

    // ── Unified Processing Logic ──────────────────────────────────

    public function validateBeforeCommit()
    {
        $rules = [
            'globalReference' => ['required', 'string', 'max:50'],
            'rows.*.ingredient_id' => 'required|exists:ingredients,id',
            'rows.*.type' => 'required|string',
            'rows.*.quantity' => 'required|numeric|min:0.01',
        ];
        $messages = [
            'rows.*.ingredient_id.required' => 'Selection required',
            'rows.*.quantity.required' => 'Qty required',
        ];

        $this->validateBeforeModal($rules, $messages, 'confirm-save-adjustment');
    }

    public function commitAdjustment()
    {
        $this->validate([
            'globalReference' => ['required', 'string', 'max:50'],
            'rows.*.ingredient_id' => 'required|exists:ingredients,id',
            'rows.*.type' => 'required|string',
            'rows.*.quantity' => 'required|numeric|min:0.01',
        ], [
            'rows.*.ingredient_id.required' => 'Selection required',
            'rows.*.quantity.required' => 'Qty required',
        ]);

        try {
            DB::transaction(function () {
                foreach ($this->rows as $row) {
                    $this->processMovement(
                        $this->selectedBranchId,
                        $row['ingredient_id'],
                        $row['type'],
                        (float)$row['quantity'],
                        $row['unit'],
                        $row['cost'],
                        $row['expiry'],
                        ($row['remarks'] ?? null) ?: $this->globalRemarks,
                        $this->globalReference
                    );
                }
            });

            $this->dispatch('notify', type: 'success', message: 'Transaction successfully committed.');
            $this->backToList();
            $this->triggerLowStockAlert($this->selectedBranchId);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function confirmReconcile()
    {
        // Pre-validate that at least one item has an actual count entered
        $hasActual = collect($this->bulkAdjustments)->contains(fn($i) => $i['actual'] !== '');
        
        if (!$hasActual) {
            $this->dispatch('notify', type: 'error', message: 'Please enter at least one physical count.');
            return;
        }

        // We don't have complex per-item rules here beyond numeric, 
        // but we'll use the standardize pattern.
        $this->validateBeforeModal([], [], 'confirm-bulk-save');
    }

    public function commitReconcile()
    {
        $modifiedCount = 0;
        try {
            DB::transaction(function () use (&$modifiedCount) {
                foreach ($this->bulkAdjustments as $id => $data) {
                    if ($data['actual'] === '') continue;
                    
                    $variance = (float)$data['variance'];
                    if ($variance == 0) continue;

                    $this->processMovement(
                        $this->selectedBranchId,
                        $id,
                        'adjust',
                        (float)$data['actual'],
                        $data['unit'],
                        null, // cost
                        null, // expiry
                        "Physical Count Reconciliation (from {$data['current']} to {$data['actual']})", // remarks
                        'RECON-' . now()->format('Ymd') // ref
                    );
                    $modifiedCount++;
                }
            });

            if ($modifiedCount > 0) {
                $this->dispatch('notify', type: 'success', message: "Reconciled $modifiedCount items.");
                $this->triggerLowStockAlert($this->selectedBranchId);
            }
            $this->backToList();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    /**
     * Core Inventory Engine
     * Handles Batching, FEFO Deductions, and Movement Logging in one place.
     */
    private function processMovement($branchId, $ingId, $type, $inputQty, $unit, $cost = null, $expiry = null, $remarks = null, $ref = null)
    {
        $baseQty = StockHelper::toBase($inputQty, $unit);
        $stock = BranchIngredientStock::lockForUpdate()->firstOrCreate(
            ['branch_id' => $branchId, 'ingredient_id' => $ingId],
            ['stock_quantity' => 0]
        );
        $oldStock = (float)$stock->stock_quantity;

        // Update Ingredient Master Cost & Bulk Unit Cost if provided
        $ingMaster = Ingredient::find($ingId);
        if ($ingMaster && $cost !== null && $cost !== '' && (float)$cost > 0) {
            // 1. If it's a bulk unit, update the specific conversion tier cost
            if ($unit !== $ingMaster->unit) {
                \App\Models\IngredientUnitConversion::where('ingredient_id', $ingId)
                    ->where('unit_name', $unit)
                    ->update(['price_per_unit' => $cost]);
            }

            // 2. Re-calculate the best base cost from all tiers (including the one we just updated)
            $bestCost = \App\Models\IngredientUnitConversion::where('ingredient_id', $ingId)
                ->where('price_per_unit', '>', 0)
                ->where('qty_in_base', '>', 0)
                ->get()
                ->map(fn($c) => $c->price_per_unit / $c->qty_in_base)
                ->min();

            // 3. Update master ingredient cost
            $ingMaster->cost = $bestCost ?: $cost; // fallback to current cost if no tiers
            $ingMaster->save();
        }

        // 1. Determine New Stock & Batch Operations
        if ($type === 'in' || $type === 'customer_return') {
            $stock->stock_quantity += $baseQty;
            StockBatch::create([
                'ingredient_id' => $ingId,
                'branch_id' => $branchId,
                'batch_number' => $ref,
                'current_quantity' => $baseQty,
                'expiry_date' => empty($expiry) ? null : $expiry,
            ]);
        } elseif (in_array($type, ['out', 'waste', 'waste_expired', 'return_to_supplier'])) {
            $result = \App\Services\StockDeductionService::deductByFEFO(
                $branchId, $ingId, $baseQty, null, auth()->id(), $remarks, $type, $ref
            );
            if (!$result['success']) throw new \Exception("{$ingId}: " . $result['message']);
            // Aggregate stock is updated inside the service
            return; 
        } elseif ($type === 'adjust') {
            // "Adjust" type is typically "Set to value" in physical count
            $newStock = $baseQty;
            $diff = $newStock - $oldStock;
            
            if ($diff < 0) {
                \App\Services\StockDeductionService::deductByFEFO(
                    $branchId, $ingId, abs($diff), null, auth()->id(), "Reconciliation Deficit", 'adjust', $ref
                );
            } elseif ($diff > 0) {
                StockBatch::create([
                    'ingredient_id' => $ingId,
                    'branch_id' => $branchId,
                    'batch_number' => $ref,
                    'current_quantity' => $diff,
                ]);
                $stock->stock_quantity = $newStock;
                $stock->save();
            }
        }

        // 2. Log Movement (If not handled by Service)
        if ($type !== 'out' && !str_starts_with($type, 'waste')) {
            $stock->save();
            StockMovement::create([
                'branch_id' => $branchId,
                'ingredient_id' => $ingId,
                'type' => $type,
                'quantity' => $baseQty,
                'unit_cost' => $cost ? str_replace(',', '', $cost) : null,
                'reference_id' => $ref,
                'expiry_date' => $expiry,
                'user_id' => auth()->id(),
                'remarks' => $remarks,
            ]);
        }
    }

    // ── Navigation & Actions ──────────────────────────────────────

    public function exportToCsv()
    {
        if ($this->isStaff()) return;

        $movements = StockMovement::with(['ingredient', 'branch', 'user'])
            ->where('branch_id', $this->selectedBranchId)
            ->when($this->search, function($q) {
                $q->where(function($sub) {
                    $sub->whereHas('ingredient', fn($ing) => $ing->where('name', 'like', '%' . $this->search . '%'))
                        ->orWhere('reference_id', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->get();

        $filename = "audit_ledger_" . now()->format('Ymd_His') . ".csv";
        $headers = ["Content-type" => "text/csv", "Content-Disposition" => "attachment; filename=$filename"];

        $callback = function() use ($movements) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Branch', 'Ingredient', 'Type', 'Quantity', 'Ref', 'Attendant']);
            foreach ($movements as $mov) {
                fputcsv($file, [
                    $mov->created_at->format('Y-m-d H:i:s'),
                    $mov->branch->branch_name ?? 'N/A',
                    $mov->ingredient->name ?? 'N/A',
                    strtoupper($mov->type),
                    $mov->quantity,
                    $mov->reference_id,
                    $mov->user->first_name . ' ' . $mov->user->last_name,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── System Utilities ──────────────────────────────────────────

    private function triggerLowStockAlert($branchId)
    {
        // Simple, non-redundant check
        $lowStocks = BranchIngredientStock::with(['ingredient'])
            ->where('branch_id', $branchId)
            ->get()
            ->filter(fn($s) => $s->ingredient->minimum_stock > 0 && $s->stock_quantity < $s->ingredient->minimum_stock);

        if ($lowStocks->isNotEmpty()) {
            Log::info("Low stock detected in branch {$branchId}: " . $lowStocks->pluck('ingredient.name')->implode(', '));
            // Emailing logic omitted for brevity as per "simple functions"
        }
    }

    public function isSuperAdmin() { return auth()->user()->role_id === 1; }
    public function isStaff() { return auth()->user()->role_id === 3; }

    private function generateReference()
    {
        $this->globalReference = 'ADJ-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
    }

    public function render()
    {
        // Get grouped adjustments (by reference_id)
        $movements = StockMovement::with(['user', 'branch'])
            ->select('reference_id', 'created_at', 'user_id', 'branch_id', 'type', 'remarks')
            ->selectRaw('COUNT(*) as item_count')
            ->where('branch_id', $this->selectedBranchId)
            ->when($this->search, function($q) {
                $q->where(function($sub) {
                    $sub->whereHas('ingredient', fn($ing) => $ing->where('ingredients.name', 'like', '%' . $this->search . '%'))
                        ->orWhere('reference_id', 'like', '%' . $this->search . '%')
                        ->orWhere('remarks', 'like', '%' . $this->search . '%');
                });
            })
            ->groupBy('reference_id', 'created_at', 'user_id', 'branch_id', 'type', 'remarks')
            ->latest('created_at')
            ->paginate($this->perPage);

        $stats = [
            'today_count' => StockMovement::whereDate('created_at', now())->where('branch_id', $this->selectedBranchId)->count(),
            'waste_count' => StockMovement::where('type', 'waste')->whereDate('created_at', now())->where('branch_id', $this->selectedBranchId)->count(),
            'in_value'    => StockMovement::where('type', 'in')->whereDate('created_at', now())->where('branch_id', $this->selectedBranchId)->sum(DB::raw('unit_cost * quantity')),
            'out_count'   => StockMovement::whereIn('type', ['out', 'waste'])->whereDate('created_at', now())->where('branch_id', $this->selectedBranchId)->count(),
        ];

        return view('livewire.stock-adjustment', [
            'movements' => $movements,
            'stats' => $stats,
            'branches' => Branch::orderBy('branch_name')->get(),
            'ingredients' => Ingredient::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
