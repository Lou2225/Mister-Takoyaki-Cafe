<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\StockBatch;
use App\Models\BranchIngredientStock;
use App\Models\Branch;
use App\Models\StockMovement;
use App\Helpers\StockHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\WithPagination;
use App\Traits\HandlesValidations;

class StockExpiry extends Component
{
    use WithPagination, HandlesValidations;

    public $search = '';
    public $branchFilter = '';
    public $statusFilter = 'all'; // all, expired, expiring, fresh
    public $perPage = 5;

    // Stats
    public $stats = [];

    protected $queryString = [
        'search'       => ['except' => ''],
        'branchFilter' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    public function mount()
    {
        $this->loadStats();
    }

    private function loadStats()
    {
        $today    = Carbon::today();
        $nextWeek = Carbon::today()->addDays(7);

        $this->stats = [
            'expired'  => StockBatch::whereNotNull('expiry_date')
                ->where('current_quantity', '>', 0)
                ->where('expiry_date', '<', $today)->count(),
            'expiring' => StockBatch::whereNotNull('expiry_date')
                ->where('current_quantity', '>', 0)
                ->whereBetween('expiry_date', [$today, $nextWeek])->count(),
            'fresh'    => StockBatch::whereNotNull('expiry_date')
                ->where('current_quantity', '>', 0)
                ->where('expiry_date', '>', $nextWeek)->count(),
            'no_date'  => StockBatch::whereNull('expiry_date')
                ->where('current_quantity', '>', 0)->count(),
        ];
    }

    public function wasteBatch($batchId)
    {
        if (!auth()->user()?->isAdmin() && !auth()->user()?->isSuperAdmin()) {
            abort(403);
        }

        $batch = StockBatch::with('ingredient', 'branch')->findOrFail($batchId);

        if ($batch->current_quantity <= 0) {
            $this->dispatchBrowserEvent('notify', ['type' => 'warning', 'message' => 'Batch is already empty.']);
            return;
        }

        DB::transaction(function () use ($batch) {
            $qty = $batch->current_quantity;
            $batch->current_quantity = 0;
            $batch->save();

            // Reduce aggregate stock
            $stock = BranchIngredientStock::where('branch_id', $batch->branch_id)
                ->where('ingredient_id', $batch->ingredient_id)
                ->first();
            if ($stock) {
                $stock->stock_quantity = max(0, $stock->stock_quantity - $qty);
                $stock->save();
            }

            // Audit trail
            StockMovement::create([
                'branch_id'     => $batch->branch_id,
                'ingredient_id' => $batch->ingredient_id,
                'type'          => 'waste',
                'quantity'      => $qty,
                'user_id'       => auth()->id(),
                'remarks'       => 'Expired batch disposed — Batch #' . $batch->id . ' (Expiry: ' . $batch->expiry_date . ')',
            ]);
        });

        $this->loadStats();
        $this->dispatchBrowserEvent('notify', ['type' => 'success', 'message' => 'Batch disposed and logged as waste.']);
    }

    public function updatedSearch()    { $this->resetPage(); }
    public function updatedBranchFilter() { $this->resetPage(); }
    public function updatedStatusFilter() { $this->resetPage(); }
    public function updatedPerPage()      { $this->resetPage(); }

    public function isSuperAdmin() { return auth()->user()?->isSuperAdmin(); }
    public function isAdmin()      { return auth()->user()?->isAdmin();      }
    public function isStaff()      { return auth()->user()?->isStaff();      }

    public function render()
    {
        $today    = Carbon::today();
        $nextWeek = Carbon::today()->addDays(7);

        $query = StockBatch::with(['ingredient', 'branch'])
            ->where('current_quantity', '>', 0)
            ->when($this->branchFilter, fn($q) => $q->where('branch_id', $this->branchFilter))
            ->when($this->search, fn($q) =>
                $q->whereHas('ingredient', fn($i) => $i->where('name', 'like', '%' . $this->search . '%'))
            )
            ->when($this->statusFilter === 'expired',  fn($q) => $q->where('expiry_date', '<', $today))
            ->when($this->statusFilter === 'expiring', fn($q) => $q->whereBetween('expiry_date', [$today, $nextWeek]))
            ->when($this->statusFilter === 'fresh',    fn($q) => $q->where('expiry_date', '>', $nextWeek))
            ->when($this->statusFilter === 'no_date',  fn($q) => $q->whereNull('expiry_date'))
            ->orderByRaw("CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END ASC")
            ->orderBy('expiry_date', 'asc')
            ->paginate($this->perPage);

        $branches = Branch::orderBy('branch_name')->get();

        return view('livewire.stock-expiry', [
            'batches'  => $query,
            'branches' => $branches,
        ])->layout('layouts.app');
    }
}
