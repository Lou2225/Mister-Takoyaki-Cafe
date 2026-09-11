<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Branch;
use App\Models\User;
use App\Models\Order;
use Illuminate\Validation\Rule;
use App\Traits\HandlesValidations;
use App\Helpers\ValidationHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Traits\HandlesExports;

class BranchManagement extends Component
{
    use WithPagination, HandlesValidations, HandlesExports;

    // Navigation & View State (Managed in Alpine, Livewire fires events)
    public $view = 'table';
    public $perPage = 5;
    public $panel = 'list'; // Kept for server-side logic (e.g., insights calculation)
    public $mode = 'list';  // create, edit, list
    
    // Filter State
    public $search = '';
    public $is_active = '';
    
    // Insights / Comparison state
    public $comparisonDateRange = 'all';
    public $selectedBranchIds = [];
    public $startDate = '';
    public $endDate = '';
    public $activeFilter = 'All Time';
    public $dateError = '';
    
    // Form fields
    public ?int $branch_id = null;
    public ?string $branch_name = null;
    public ?string $branch_code = null;
    public ?string $phone = null;
    public ?string $email = null;
    public ?string $address = null; // Full JSON address blob
    public ?int $user_id = null; // Manager
    public bool $status = true;
    public bool $is_main = false;
    public bool $confirmMainDesignation = false;
    public bool $skipValidation = false;
    public ?int $deleteTargetId = null;
    public string $deleteTargetName = '';
    public string $pendingManagerName = '';

    // PSGC address sub-fields (bound by Alpine PSGC dropdowns + map picker)
    public string $addr_region   = '';
    public string $addr_province = '';
    public string $addr_city     = '';
    public string $addr_barangay = '';
    public string $addr_street   = '';
    public ?float $addr_lat      = null;
    public ?float $addr_lng      = null;

    protected $queryString = [
        'search'    => ['except' => '', 'as' => 'b_search'],
        'is_active' => ['except' => '', 'as' => 'b_active'],
        'view'      => ['except' => 'table', 'as' => 'b_view'],
        'perPage'   => ['except' => 5, 'as' => 'b_pp'],
    ];

    protected $listeners = ['refresh' => '$refresh'];

    public function mount()
    {
        if (!auth()->user() || !in_array(auth()->user()->role_id, [1, 2])) {
            abort(403, 'Unauthorized access to branch management.');
        }

        $this->selectedBranchIds = Branch::pluck('id')->toArray();
        $this->updateGlobalHeader('list');
    }

    public function toggleBranch(int $id)
    {
        if (in_array($id, $this->selectedBranchIds)) {
            $this->selectedBranchIds = array_filter($this->selectedBranchIds, fn($bid) => $bid != $id);
        } else {
            $this->selectedBranchIds[] = $id;
        }
    }

    public function updatedBranchName(string $value)
    {
        if ($this->skipValidation) return;
        $rules = [
            'required', 'string', 'min:3', 'max:150',
            'regex:' . ValidationHelper::REGEX_NAME,
            Rule::unique('branches', 'branch_name')->ignore($this->branch_id),
        ];
        $this->validateFieldLive('branch_name', $rules, ValidationHelper::commonMessages());

        // Auto-generate Branch Code logic for Mister Takoyaki Cafe
        if ($this->mode === 'create' || empty($this->branch_code)) {
            $cleaned = preg_replace('/[^A-Za-z0-9 ]/', '', $value);
            $words = array_filter(explode(' ', strtoupper($cleaned)));
            $code = 'MTC-';
            
            if (count($words) === 1) {
                $code .= substr($words[0], 0, 4);
            } else {
                foreach ($words as $word) {
                    $code .= substr($word, 0, 1);
                }
                // If only 2 words, add second letter of first word for better distinction
                if (count($words) === 2 && isset($words[0][1])) {
                    $code = 'MTC-' . $words[0][0] . $words[0][1] . $words[1][0];
                }
            }
            $this->branch_code = $code;
        }
    }

    public function updatedBranchCode()
    {
        if ($this->skipValidation) return;
        $rules = [
            'required', 'string', 'max:50',
            Rule::unique('branches', 'branch_code')->ignore($this->branch_id),
        ];
        $this->validateFieldLive('branch_code', $rules, ValidationHelper::commonMessages());
    }

    public function updatedPhone()
    {
        if ($this->skipValidation) return;
        $this->validateFieldLive('phone', ['nullable', 'string', 'regex:~^[0-9]{10}$~'], ['phone.regex' => 'Enter 10-digit mobile number.']);
    }

    public function updatedEmail()
    {
        if ($this->skipValidation) return;
        $rules = array_merge(ValidationHelper::rulesEmail(false), [
            $this->branch_id ? Rule::unique('branches', 'email')->ignore($this->branch_id) : 'unique:branches,email'
        ]);
        $this->validateFieldLive('email', $rules, ValidationHelper::commonMessages());
    }

    public function updatedAddrStreet()
    {
        if ($this->skipValidation) return;
        $this->validateFieldLive('addr_street', ['nullable', 'string', 'max:255'], ValidationHelper::commonMessages());
    }

    public function updatedAddrBarangay()
    {
        if ($this->skipValidation) return;
        $this->validateFieldLive('addr_barangay', ['nullable', 'string'], ValidationHelper::commonMessages());
    }

    public function updatedAddrCity()
    {
        if ($this->skipValidation) return;
        $this->validateFieldLive('addr_city', ['nullable', 'string'], ValidationHelper::commonMessages());
    }

    public function updatedAddrProvince()
    {
        if ($this->skipValidation) return;
        $this->validateFieldLive('addr_province', ['nullable', 'string'], ValidationHelper::commonMessages());
    }

    public function updatedAddrRegion()
    {
        if ($this->skipValidation) return;
        $this->validateFieldLive('addr_region', ['nullable', 'string'], ValidationHelper::commonMessages());
    }

    public function selectAllBranches()
    {
        $this->selectedBranchIds = Branch::pluck('id')->toArray();
    }

    public function clearAllBranches()
    {
        $this->selectedBranchIds = [];
    }

    // ── Form management ───────────────────────────────────────────
    public function resetForm()
    {
        $this->branch_id   = null;
        $this->branch_name = '';
        $this->branch_code = '';
        $this->phone       = '';
        $this->email       = '';
        $this->address     = '';
        $this->user_id     = null;
        $this->status      = true;
        $this->is_main     = false;
        // Clear address sub-fields
        $this->addr_region   = '';
        $this->addr_province = '';
        $this->addr_city     = '';
        $this->addr_barangay = '';
        $this->addr_street   = '';
        $this->addr_lat      = null;
        $this->addr_lng      = null;
        $this->resetValidation();
    }

    public function showCreate()
    {
        if (!$this->isSuperAdmin()) return;
        $this->panel = 'form';
        $this->mode = 'create';
        $this->resetForm();
        $this->updateGlobalHeader('create');
    }

    public function showEdit(int $id)
    {
        if (!$this->isSuperAdmin()) return;

        $this->skipValidation = true; // suppress hooks while populating
        $this->panel = 'form';
        $this->mode = 'edit';
        $branch = Branch::findOrFail($id);
        $this->branch_id   = $branch->id;
        $this->branch_name = $branch->branch_name;
        $this->branch_code = $branch->branch_code;
        $this->phone       = $branch->phone ? str_replace('+63', '', $branch->phone) : '';
        $this->email       = $branch->email;
        $this->user_id     = $branch->user_id;
        $this->status      = (bool)$branch->status;
        $this->is_main     = (bool)$branch->is_main;

        // Decode stored JSON address into individual fields
        $addr = is_array($branch->address)
            ? $branch->address
            : json_decode($branch->address, true);

        $this->address       = $branch->address;
        $this->addr_region   = $addr['region']   ?? '';
        $this->addr_province = $addr['province']  ?? '';
        $this->addr_city     = $addr['city']      ?? '';
        $this->addr_barangay = $addr['barangay']  ?? '';
        $this->addr_street   = $addr['street']    ?? '';
        $this->addr_lat      = $addr['lat']       ?? null;
        $this->addr_lng      = $addr['lng']       ?? null;

        $this->skipValidation = false;
        $this->resetValidation();
        $this->updateGlobalHeader('edit');
    }

    public function showInsights()
    {
        $this->panel = 'insights';
        $this->updateGlobalHeader('insights');
    }

    // ── Deletion Workflow ──────────────────────────────────────────
    public function confirmDeleteBranch(int $id)
    {
        if (!$this->isSuperAdmin()) return;

        $branch = Branch::findOrFail($id);

        // Safety Check 1: Are there active staff members (excluding the
        // branch's own manager, who is handled separately below)? This
        // still hard-blocks, since staff can't be auto-reassigned safely
        // from this modal.
        $staffCount = User::where('branch_id', $id)
            ->where(function ($q) use ($branch) {
                if ($branch->user_id) {
                    $q->where('id', '!=', $branch->user_id);
                }
            })
            ->count();
        if ($staffCount > 0) {
            $this->dispatch('notify', 
                type: 'error',
                message: "Integrity Guard: '{$branch->branch_name}' has {$staffCount} staff members registered. Transfer these users before decommissioning the node."
            );
            return;
        }

        $this->deleteTargetId = $id;
        $this->deleteTargetName = $branch->branch_name;

        // Safety Check 2: Is there an active manager? Offer to unassign + delete
        // instead of just blocking with an error.
        if ($branch->user_id) {
            $manager = User::find($branch->user_id);
            $this->pendingManagerName = $manager
                ? trim($manager->first_name . ' ' . $manager->last_name)
                : 'the assigned manager';
            $this->dispatch('open-modal', 'confirm-delete-with-manager');
            return;
        }

        $this->dispatch('open-modal', 'delete-branch');
    }

    public function deleteBranchAndUnassignManager(int $id)
    {
        if (!$this->isSuperAdmin()) return;

        $branch = Branch::findOrFail($id);

        // Re-validate staff safety condition at the moment of deletion,
        // excluding the manager we're about to unassign in this same flow.
        $staffCount = User::where('branch_id', $id)
            ->where(function ($q) use ($branch) {
                if ($branch->user_id) {
                    $q->where('id', '!=', $branch->user_id);
                }
            })
            ->count();
        if ($staffCount > 0) {
            $this->dispatch('notify', type: 'error', message: "Integrity Guard: '{$branch->branch_name}' has {$staffCount} staff members registered. Transfer these users before decommissioning the node.");
            $this->dispatch('close-modal', 'confirm-delete-with-manager');
            return;
        }

        if ($branch->user_id) {
            User::where('id', $branch->user_id)->update(['branch_id' => null]);
            $branch->update(['user_id' => null]);
        }

        DB::transaction(function () use ($branch) {
            DB::table('branch_ingredient_stocks')->where('branch_id', $branch->id)->delete();
            $branch->delete();
        });

        $this->dispatch('notify', type: 'success', message: 'Manager unassigned and branch node decommissioned successfully.');
        $this->dispatch('refresh-global-map', branches: json_decode($this->buildPlottableBranches(), true));
        $this->dispatch('refreshTopbar');
        $this->dispatch('branchContextUpdated');
        $this->dispatch('close-modal', 'confirm-delete-with-manager');
        $this->deleteTargetId = null;
        $this->deleteTargetName = '';
        $this->pendingManagerName = '';
        $this->backToList();
    }

    public function deleteBranch(int $id)
    {
        if (!$this->isSuperAdmin()) return;

        $branch = Branch::findOrFail($id);

        // Re-validate safety conditions at the moment of deletion — state may
        // have changed since confirmDeleteBranch() first checked, and this
        // method is reachable directly regardless of that earlier check.
        if ($branch->user_id) {
            $this->dispatch('notify', type: 'error', message: "Safety Lock: '{$branch->branch_name}' currently has an active Branch Manager assigned. Reassign or remove the manager before deleting.");
            $this->dispatch('close-modal', 'delete-branch');
            return;
        }

        $staffCount = User::where('branch_id', $id)
            ->where(function ($q) use ($branch) {
                if ($branch->user_id) {
                    $q->where('id', '!=', $branch->user_id);
                }
            })
            ->count();
        if ($staffCount > 0) {
            $this->dispatch('notify', type: 'error', message: "Integrity Guard: '{$branch->branch_name}' has {$staffCount} staff members registered. Transfer these users before decommissioning the node.");
            $this->dispatch('close-modal', 'delete-branch');
            return;
        }

        DB::transaction(function () use ($branch) {
            // Clean up related branch stocks
            DB::table('branch_ingredient_stocks')->where('branch_id', $branch->id)->delete();
            $branch->delete();
        });

        $this->dispatch('notify', type: 'success', message: 'Branch node decommissioned successfully.');
        $this->dispatch('refresh-global-map', branches: json_decode($this->buildPlottableBranches(), true));
        $this->dispatch('refreshTopbar');
        $this->dispatch('branchContextUpdated');
        $this->deleteTargetId = null;
        $this->deleteTargetName = '';
        $this->backToList();
    }

    public function validateBeforeSaveBranch()
    {
        $rules = [
            'branch_name' => [
                'required', 'string', 'min:3', 'max:150',
                'regex:' . ValidationHelper::REGEX_NAME,
                Rule::unique('branches', 'branch_name')->ignore($this->branch_id),
            ],
            'branch_code' => [
                'required', 'string', 'max:50',
                Rule::unique('branches', 'branch_code')->ignore($this->branch_id),
            ],
            'phone' => ['nullable', 'string', 'regex:~^[0-9]{10}$~'],
                        'email' => array_merge(ValidationHelper::rulesEmail(false), [
                $this->branch_id ? Rule::unique('branches', 'email')->ignore($this->branch_id) : 'unique:branches,email'
            ]),
            'user_id' => ['nullable', Rule::exists('users', 'id')->whereIn('role_id', [1, 2])->where('is_active', 1)],
            'status'  => ['boolean'],
            'addr_street' => ['nullable', 'string', 'max:255'],
            'addr_barangay' => ['required', 'string'],
            'addr_city' => ['required', 'string'],
            'addr_province' => ['required', 'string'],
            'addr_region' => ['required', 'string'],
        ];

        $this->validateBeforeModal($rules, ValidationHelper::commonMessages(), 'confirm-save-branch');
    }

    public function saveBranch()
    {
        if (!$this->isSuperAdmin()) return;
        $this->branch_name = $this->normalizeString($this->branch_name);

        $this->validate([
            'branch_name' => [
                'required', 'string', 'min:3', 'max:150',
                'regex:' . ValidationHelper::REGEX_NAME,
                Rule::unique('branches', 'branch_name'),
            ],
            'branch_code' => [
                'required', 'string', 'max:50',
                Rule::unique('branches', 'branch_code'),
            ],
            'phone' => ['nullable', 'string', 'regex:~^[0-9]{10}$~'],
                        'email' => array_merge(ValidationHelper::rulesEmail(false), ['unique:branches,email']),
            'user_id' => ['nullable', Rule::exists('users', 'id')->whereIn('role_id', [1, 2])->where('is_active', 1)],
            'status'  => ['boolean'],
            'addr_barangay' => ['required', 'string'],
            'addr_city' => ['required', 'string'],
            'addr_province' => ['required', 'string'],
            'addr_region' => ['required', 'string'],
        ], ValidationHelper::commonMessages());

        // Compose address JSON from PSGC sub-fields
        $parts = array_filter([
            $this->addr_street,
            $this->addr_barangay,
            $this->addr_city,
            $this->addr_province,
            $this->addr_region,
        ]);
        $formatted = implode(', ', $parts);

        $addressJson = json_encode([
            'region'    => $this->addr_region,
            'province'  => $this->addr_province,
            'city'      => $this->addr_city,
            'barangay'  => $this->addr_barangay,
            'street'    => $this->addr_street,
            'lat'       => $this->addr_lat,
            'lng'       => $this->addr_lng,
            'formatted' => $formatted,
        ]);

        // Get main branch for distance calculation
        $mainBranch = Branch::where('is_main', true)->first();
        $distance = 0;
        if ($mainBranch && $this->addr_lat && $this->addr_lng) {
            $mainAddr = is_array($mainBranch->address) ? $mainBranch->address : json_decode($mainBranch->address, true);
            $distance = $this->calculateDistance($mainAddr['lat'] ?? 0, $mainAddr['lng'] ?? 0, $this->addr_lat, $this->addr_lng);
        }

        // A branch cannot go live as Active without someone accountable
        // for it — block registration in that state rather than allowing
        // it and merely warning after the fact.
        if ($this->status && !$this->user_id) {
            $this->dispatch('notify', type: 'error', message: 'Cannot register branch as Active without an assigned manager.');
            return;
        }

        if ($this->user_id) {
            // Clear any other branch that currently claims this manager,
            // so a manager is never referenced by more than one branch.
            Branch::where('user_id', $this->user_id)->update(['user_id' => null]);
        }

        $branch = Branch::create([
            'branch_name' => $this->branch_name,
            'branch_code' => $this->branch_code,
            'phone'       => $this->phone ? '+63' . trim($this->phone) : null,
            'email'       => $this->email,
            'address'     => $addressJson,
            'user_id'     => $this->user_id ?: null,
            'status'      => $this->status,
            'distance_from_main' => $distance,
        ]);

        if ($this->user_id) {
            User::where('id', $this->user_id)->update(['branch_id' => $branch->id]);
        }

        $this->dispatch('notify', type: 'success', message: 'Branch successfully registered.');
        $this->dispatch('refresh-global-map', branches: json_decode($this->buildPlottableBranches(), true));
        $this->dispatch('refreshTopbar');
        $this->dispatch('branchContextUpdated');
        $this->backToList();
    }

    public function updateBranch()
    {
        if (!$this->isSuperAdmin()) return;
        $this->branch_name = $this->normalizeString($this->branch_name);

        $this->validate([
            'branch_name' => [
                'required', 'string', 'min:3', 'max:150',
                'regex:' . ValidationHelper::REGEX_NAME,
                Rule::unique('branches', 'branch_name')->ignore($this->branch_id),
            ],
            'branch_code' => [
                'required', 'string', 'max:50',
                Rule::unique('branches', 'branch_code')->ignore($this->branch_id),
            ],
            'phone' => ['nullable', 'string', 'regex:~^[0-9]{10}$~'],
                        'email' => array_merge(ValidationHelper::rulesEmail(false), [
                Rule::unique('branches', 'email')->ignore($this->branch_id)
            ]),
            'user_id' => ['nullable', Rule::exists('users', 'id')->whereIn('role_id', [1, 2])->where('is_active', 1)],
            'status'  => ['boolean'],
            'addr_barangay' => ['required', 'string'],
            'addr_city' => ['required', 'string'],
            'addr_province' => ['required', 'string'],
            'addr_region' => ['required', 'string'],
        ], ValidationHelper::commonMessages());

        // Compose address JSON from PSGC sub-fields
        $parts = array_filter([
            $this->addr_street,
            $this->addr_barangay,
            $this->addr_city,
            $this->addr_province,
            $this->addr_region,
        ]);
        $formatted = implode(', ', $parts);

        $addressJson = json_encode([
            'region'    => $this->addr_region,
            'province'  => $this->addr_province,
            'city'      => $this->addr_city,
            'barangay'  => $this->addr_barangay,
            'street'    => $this->addr_street,
            'lat'       => $this->addr_lat,
            'lng'       => $this->addr_lng,
            'formatted' => $formatted,
        ]);

        $branch = Branch::findOrFail($this->branch_id);
        $oldManagerId = $branch->user_id;
        $newManagerId = $this->user_id ?: null;

        // A branch cannot remain/become Active without an assigned manager.
        if ($this->status && !$newManagerId) {
            $this->dispatch('notify', type: 'error', message: 'Cannot save as Active without an assigned manager.');
            return;
        }

        // If the incoming manager currently manages a different branch,
        // clear that branch's stale user_id reference so it doesn't end up
        // pointing at a manager who has moved elsewhere.
        if ($newManagerId) {
            Branch::where('user_id', $newManagerId)->where('id', '!=', $branch->id)->update(['user_id' => null]);
        }

        // Recalculate distance
        $mainBranch = Branch::where('is_main', true)->where('id', '!=', $this->branch_id)->first();
        $distance = 0;
        if ($mainBranch && $this->addr_lat && $this->addr_lng) {
            $mainAddr = is_array($mainBranch->address) ? $mainBranch->address : json_decode($mainBranch->address, true);
            $distance = $this->calculateDistance($mainAddr['lat'] ?? 0, $mainAddr['lng'] ?? 0, $this->addr_lat, $this->addr_lng);
        }

        $branch->update([
            'branch_name' => $this->branch_name,
            'branch_code' => $this->branch_code,
            'phone'       => $this->phone ? '+63' . trim($this->phone) : null,
            'email'       => $this->email,
            'address'     => $addressJson,
            'user_id'     => $newManagerId,
            'status'      => $this->status,
            'distance_from_main' => $branch->is_main ? 0 : $distance,
        ]);

        if ($oldManagerId && $oldManagerId !== $newManagerId) {
            User::where('id', $oldManagerId)->update(['branch_id' => null]);
        }
        if ($newManagerId) {
            User::where('id', $newManagerId)->update(['branch_id' => $branch->id]);
        }

        $this->dispatch('notify', type: 'success', message: 'Node configuration updated.');
        $this->dispatch('refresh-global-map', branches: json_decode($this->buildPlottableBranches(), true));
        $this->dispatch('refreshTopbar');
        $this->dispatch('branchContextUpdated');
        $this->backToList();
    }


    public function backToList()
    {
        $this->mode = 'list';
        $this->panel = 'list';
        $this->resetForm();
        $this->updateGlobalHeader('list');
    }

    public function toggleStatus(int $id)
    {
        if (!$this->isSuperAdmin()) return;
        $branch = Branch::findOrFail($id);

        // A branch cannot operate without someone accountable for it — do
        // not allow activation without an assigned manager. Deactivating
        // is always allowed regardless of manager status.
        $activating = !$branch->status;
        if ($activating && !$branch->user_id) {
            $this->dispatch('notify', type: 'error', message: "Cannot activate '{$branch->branch_name}': assign a manager first.");
            return;
        }

        $branch->status = !$branch->status;
        $branch->save();
        $this->dispatch('notify', type: 'success', message: "Operational status for {$branch->branch_name} updated.");
    }

    public function validateBeforeSetMain()
    {
        if (!$this->isSuperAdmin()) return;
        
        // If it's already main, no need to warn (or it shouldn't be clickable)
        if ($this->is_main) return;

        $this->confirmMainDesignation = false;
        $this->dispatch('open-modal', 'confirm-set-main');
    }

    public function setMainBranch(int $id)
    {
        if (!$this->isSuperAdmin()) return;

        DB::transaction(function () use ($id) {
            // Remove main flag from everyone
            Branch::where('is_main', true)->update(['is_main' => false, 'distance_from_main' => 0]);
            // Set new main
            $newMain = Branch::findOrFail($id);
            $newMain->update(['is_main' => true, 'distance_from_main' => 0]);

            // Recalculate everyone else's distance from this new main
            $newMainAddr = is_array($newMain->address) ? $newMain->address : json_decode($newMain->address, true);
            $newMainLat = $newMainAddr['lat'] ?? 0;
            $newMainLng = $newMainAddr['lng'] ?? 0;

            Branch::where('id', '!=', $id)->get()->each(function ($b) use ($newMainLat, $newMainLng) {
                $addr = is_array($b->address) ? $b->address : json_decode($b->address, true);
                $dist = $this->calculateDistance($newMainLat, $newMainLng, $addr['lat'] ?? 0, $addr['lng'] ?? 0);
                $b->update(['distance_from_main' => $dist]);
            });
        });

        $this->is_main = true;
        $this->dispatch('notify', type: 'success', message: 'Primary operations center successfully reassigned.');
        $this->dispatch('refresh-global-map', branches: json_decode($this->buildPlottableBranches(), true));
        $this->dispatch('refreshTopbar');
        $this->dispatch('branchContextUpdated');
        $this->dispatch('refresh');
    }

    public function setView(string $view)
    {
        if (in_array($view, ['table', 'board'])) {
            $this->view = $view;
        }
    }

    public function applyQuickDateFilter(string $range)
    {
        switch ($range) {
            case 'today':
                $this->startDate = now()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'week':
                $this->startDate = now()->subDays(7)->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'month':
                $this->startDate = now()->subDays(30)->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'all':
                $this->startDate = '';
                $this->endDate = '';
                break;
        }
        $this->dateError = '';
        $this->comparisonDateRange = $range; // Keep for label/state
        $this->activeFilter = match($range) {
            'today' => 'Today Only',
            'week'  => 'Last 7 Days',
            'month' => 'Last 30 Days',
            'all'   => 'All Time',
            default => 'Last 30 Days'
        };
    }

    public function updatedStartDate()
    {
        $this->validateDateRange();
    }

    public function updatedEndDate()
    {
        $this->validateDateRange();
    }

    private function validateDateRange()
    {
        $this->dateError = '';
        if ($this->startDate && $this->endDate) {
            if (strtotime($this->startDate) > strtotime($this->endDate)) {
                $this->dateError = 'Start date cannot be after end date.';
            } else {
                $this->activeFilter = 'Custom Range';
                $this->comparisonDateRange = '';
            }
        }
    }

    // ── Role Helpers ──────────────────────────────────────────────
    public function isSuperAdmin() { return auth()->user()?->isSuperAdmin(); }
    public function isAdmin()      { return auth()->user()?->isAdmin();      }
    public function isStaff()      { return auth()->user()?->isStaff();      }

    public function updating($name, $value)
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    public function getSystemStatsProperty()
    {
        return [
            'total'    => Branch::count(),
            'active'   => Branch::where('status', 1)->count(),
            'inactive' => Branch::where('status', 0)->count(),
            'staff'    => User::whereIn('role_id', [3, 5])->count(),
        ];
    }

    public function getAssignedManagerLabelProperty()
    {
        if (!$this->user_id) {
            return 'Unassigned';
        }

        $manager = User::find($this->user_id);

        return ($manager && $manager->is_active)
            ? trim($manager->first_name . ' ' . $manager->last_name)
            : 'Unassigned';
    }

    public function render()
    {
        $filteredBranches = $this->getBranches();
        $plottableBranches = $this->buildPlottableBranches($filteredBranches);

        $this->dispatch('refresh-global-map', 
            branches: json_decode($plottableBranches, true)
        );

        $matrix = $this->getComparisonMatrix();
        $maxNetSales = $matrix->max('net_sales') ?? 0;

        return view('livewire.branch-management', [
            'branches' => $filteredBranches,
                        'managers' => User::whereIn('role_id', [1, 2])->where('is_active', 1)->get(),
            'systemStats' => $this->systemStats,
            'matrix' => $matrix,
            'maxNetSales' => $maxNetSales,
            'totals' => $this->getComparisonTotals(),
            'plottableBranches' => $plottableBranches,
            'allBranches' => Branch::orderBy('is_main', 'desc')->orderBy('branch_name')->get(),
        ])->layout('layouts.app');
    }

    private function getBranches()
    {
        return Branch::with('manager')
            ->where('branch_name', 'like', '%' . $this->search . '%')
            ->when($this->is_active !== '', fn($q) => $q->where('status', $this->is_active))
            ->orderBy('is_main', 'desc')
            ->orderBy('branch_name')
            ->paginate($this->perPage);
    }

    /**
     * One grouped aggregate query across all selected branches, instead of
     * one Order query per branch. Also caches the result for this request
     * so getComparisonTotals() (called separately in render()) doesn't
     * redo the same aggregation a second time.
     */
    private ?\Illuminate\Support\Collection $comparisonMatrixCache = null;

    private function getComparisonMatrix()
    {
        if ($this->comparisonMatrixCache !== null) return $this->comparisonMatrixCache;

        if ($this->panel !== 'insights' || empty($this->selectedBranchIds)) {
            return $this->comparisonMatrixCache = collect([]);
        }

        $range = $this->getDateRange();
        $branches = Branch::whereIn('id', $this->selectedBranchIds)->get()->keyBy('id');

        $stats = Order::whereIn('branch_id', $this->selectedBranchIds)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->where('status', Order::STATUS_COMPLETED)
            ->selectRaw('
                branch_id,
                SUM(total_amount) as total_collected,
                SUM(delivery_fee) as delivery_fees,
                SUM(discount_amount) as total_discounts,
                COUNT(*) as order_count
            ')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');

        $matrix = $branches->map(function ($branch) use ($stats) {
            $s = $stats->get($branch->id);

            $totalCollected = (float) ($s->total_collected ?? 0);
            $deliveryFees   = (float) ($s->delivery_fees ?? 0);
            $totalDiscounts = (float) ($s->total_discounts ?? 0);
            $orderCount     = (int) ($s->order_count ?? 0);

            $netSales   = $totalCollected - $deliveryFees;
            $grossSales = $netSales + $totalDiscounts;
            $atv        = $orderCount > 0 ? $totalCollected / $orderCount : 0;

            return [
                'id'              => $branch->id,
                'name'            => $branch->branch_name,
                'order_count'     => $orderCount,
                'gross_sales'     => $grossSales,
                'net_sales'       => $netSales,
                'atv'             => $atv,
                'total_collected' => $totalCollected,
            ];
        });

        $totalNetSales = $matrix->sum('net_sales');

        $matrix = $matrix->map(function ($b) use ($totalNetSales) {
            $b['share_pct'] = $totalNetSales > 0 ? round(($b['net_sales'] / $totalNetSales) * 100, 2) : 0;
            return $b;
        })->sortByDesc('net_sales');

        return $this->comparisonMatrixCache = $matrix;
    }

    private function getComparisonTotals()
    {
        if ($this->panel !== 'insights' || empty($this->selectedBranchIds)) {
            return ['gross' => 0, 'net' => 0, 'orders' => 0, 'atv' => 0];
        }

        $matrix = $this->getComparisonMatrix();

        $grossSales     = $matrix->sum('gross_sales');
        $netSales       = $matrix->sum('net_sales');
        $orderCount     = $matrix->sum('order_count');
        $totalCollected = $matrix->sum('total_collected');

        return [
            'gross'  => $grossSales,
            'net'    => $netSales,
            'orders' => $orderCount,
            'atv'    => $orderCount > 0 ? ($totalCollected / $orderCount) : 0,
        ];
    }

    private function getDateRange()
    {
        if ($this->startDate && $this->endDate && !$this->dateError) {
            return [
                'start' => Carbon::parse($this->startDate)->startOfDay(),
                'end' => Carbon::parse($this->endDate)->endOfDay()
            ];
        }

        $end = Carbon::now()->endOfDay();
        $start = match($this->comparisonDateRange) {
            'today' => Carbon::now()->startOfDay(),
            'week'  => Carbon::now()->subDays(7)->startOfDay(),
            'month' => Carbon::now()->subDays(30)->startOfDay(),
            'last_7' => Carbon::now()->subDays(7)->startOfDay(), // Legacy support
            'all'   => Carbon::createFromTimestamp(0)->startOfDay(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        return ['start' => $start, 'end' => $end];
    }

    private function calculateHealthScore(int $orders, float $sales)
    {
        if ($orders == 0) return 0;
        // Simplified benchmark: 500 PHP per order as "ideal" base for this calculation
        $score = ($sales / ($orders * 500)) * 100; 
        return min(100, round($score));
    }

    private function updateGlobalHeader($panel = 'list')
    {
        $breadcrumbs = [
            ['label' => 'Infrastructure', 'url' => '#'],
            ['label' => 'Branch Locations', 'url' => route('branches.index')],
        ];
        $title = 'Branch Locations';

        if ($panel === 'create') {
            $breadcrumbs[] = ['label' => 'Register Node', 'url' => '#'];
            $title = 'Register New Node';
        } elseif ($panel === 'edit') {
            $breadcrumbs[] = ['label' => 'Configure Node', 'url' => '#'];
            $title = 'Node Configuration';
        } elseif ($panel === 'insights') {
            $breadcrumbs[] = ['label' => 'Fleet Intelligence', 'url' => '#'];
            $title = 'Performance Comparison';
        }

        $this->dispatch('setHeader', 
            icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            title: $title,
            breadcrumbs: $breadcrumbs
        );
    }

    private function buildPlottableBranches($collection = null): string
    {
        $branches = $collection 
            ? ($collection instanceof \Illuminate\Pagination\LengthAwarePaginator ? $collection->getCollection() : collect($collection))
            : Branch::all();

        return $branches->map(function ($b) {
            $addr = is_array($b->address) ? $b->address : json_decode($b->address, true);
            return [
                'id'        => $b->id,
                'name'      => $b->branch_name,
                'lat'       => $addr['lat'] ?? null,
                'lng'       => $addr['lng'] ?? null,
                'formatted' => $addr['formatted'] ?? '',
                'status'    => (bool)$b->status,
                'is_main'   => (bool)$b->is_main,
            ];
        })->filter(fn($b) => $b['lat'] && $b['lng'])->values()->toJson();
    }

    // ── Reports ───────────────────────────────────────────────────
    public function exportPdf()
    {
        $data = $this->getExportDataForReport();
        return $this->generatePdfReport('reports.template', $data, 'Branch_Comparison_Report_' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportCsv()
    {
        $matrix = $this->getComparisonMatrix();
        $data = $matrix->map(fn($b) => [
            'Branch'      => $b['name'],
            'Orders'      => $b['order_count'],
            'Gross Sales' => number_format($b['gross_sales'], 2),
            'Net Sales'   => number_format($b['net_sales'], 2),
            'ATV'         => number_format($b['atv'], 2),
            'Share'       => number_format($b['share_pct'], 1) . '%',
        ])->toArray();

        return $this->generateCsvReport('Branch_Comparison_' . now()->format('Y-m-d') . '.csv', $data);
    }

    public function exportExcel()
    {
        return $this->exportCsv();
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2)
    {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 0;
        $earthRadius = 6371; // KM
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }

    private function getExportDataForReport(): array
    {
        $matrix = $this->getComparisonMatrix();
        $totals = $this->getComparisonTotals();
        $range  = $this->getDateRange();

        $periodLabel = $this->activeFilter !== 'All Time'
            ? $this->activeFilter
            : $range['start']->format('M d, Y') . '  to  ' . $range['end']->format('M d, Y');

        $branchRows = $matrix->map(fn($b) => [
            $b['name'],
            number_format($b['order_count']),
            'PHP ' . number_format($b['gross_sales'], 2),
            'PHP ' . number_format($b['net_sales'], 2),
            'PHP ' . number_format($b['atv'], 2),
            number_format($b['share_pct'], 1) . '%',
        ])->toArray();

        // Pull branch operational info
        $branchDetailRows = Branch::whereIn('id', $this->selectedBranchIds)
            ->with('manager')
            ->get()
            ->map(fn($b) => [
                $b->branch_name,
                $b->branch_code ?? 'N/A',
                // Concatenating first + ' ' + last before falling back to
                // 'Unassigned' via ?: was buggy: with no manager, the
                // expression evaluates to a single space (' '), which PHP
                // treats as truthy, so ?: never triggered and the report
                // printed a blank cell instead of "Unassigned".
                $b->manager ? trim($b->manager->first_name . ' ' . $b->manager->last_name) : 'Unassigned',
                $b->status ? 'Active' : 'Inactive',
                $b->phone ?? 'N/A',
            ])->toArray();

        return [
            'reportType'  => 'branch',
            'title'       => 'Fleet Performance Report',
            'subtitle'    => 'Branch-by-Branch Comparison & Operational Overview',
            'period'      => $periodLabel,
            'branch'      => 'Network-Wide (' . count($this->selectedBranchIds) . ' nodes)',
            'generatedAt' => now()->format('F d, Y h:i A'),
            'kpis' => [
                'Total Network Sales' => 'PHP ' . number_format($totals['net'], 2),
                'Gross Network Sales' => 'PHP ' . number_format($totals['gross'], 2),
                'Total Orders'        => number_format($totals['orders']),
                'Avg. Ticket Value'   => 'PHP ' . number_format($totals['atv'], 2),
                'Nodes Compared'      => count($this->selectedBranchIds),
            ],
            'sections' => [
                [
                    'title'   => 'Performance Comparison',
                    'headers' => ['Branch', 'Orders', 'Gross Sales', 'Net Sales', 'Avg. Ticket', 'Share'],
                    'rows'    => $branchRows,
                    'empty'   => 'No branch data available.',
                ],
                [
                    'title'   => 'Branch Directory',
                    'headers' => ['Branch Name', 'Code', 'Manager', 'Status', 'Phone'],
                    'rows'    => $branchDetailRows,
                    'empty'   => 'No branch directory data.',
                ],
            ],
        ];
    }
}
