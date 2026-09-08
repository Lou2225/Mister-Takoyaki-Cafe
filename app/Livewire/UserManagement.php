<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\Order;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Traits\HandlesValidations;
use App\Helpers\ValidationHelper;
class UserManagement extends Component
{
    use WithPagination, HandlesValidations;

    // ── List filters ──────────────────────────────────────────────
    public $search = '';
    public $role_id = '';
    public $branch_id = '';
    public $is_active = '';
    public $view = 'table';
    public $perPage = 5;

    protected $listeners = [
        'refreshTopbar' => '$refresh',
    ];

    // ── Panel state is managed in Alpine via browser events ─────
    // Livewire fires 'switch-panel' after data is ready.

    // ── Form fields ───────────────────────────────────────────────
    public $editUserId = null;
    public $firstName = '';
    public $middleName = '';
    public $lastName = '';
    public $email = '';
    public $phone = '';
    public $password = '';
    public $passwordConfirm = '';
    public $formRoleId = '';
    public $formBranchId = '';
    public $position = '';
    public $employeeId = '';
    public $dateHired = '';
    
    // ── Address / PSGC fields ─────────────────────────────────────
    public $addr_region   = '';
    public $addr_province = '';
    public $addr_city     = '';
    public $addr_barangay = '';
    public $addr_street   = '';
    public $addr_lat      = null;
    public $addr_lng      = null;
    // ── Panel state synced with Alpine ───────────────────────────
    public $panel = 'list'; // 'list' | 'form'
    public $mode = 'list';  // 'list' | 'create' | 'edit' | 'view'
    public $formIsActive = true;
    public $forceReplaceManager = false;
    public $conflictingManagerName = '';
    public $skipValidation = false;
    public $deleteTargetId = null;
    public $deleteTargetName = '';
    public ?array $editUserAvatar = null;

    // ── User History Dashboard State ──────────────────────────────
    public $historyTab = 'overview';
    public $historyStartDate = '';
    public $historyEndDate = '';
    public $activeFilter = 'All Time';
    public $viewingOrder = null;

    // Standardized positions for consistent selection
    public $availablePositions = [
        'Cashier',
        'Delivery Rider',
    ];

    protected $queryString = [
        'search'    => ['except' => '', 'as' => 'u_search'],
        'role_id'   => ['except' => '', 'as' => 'u_role'],
        'branch_id' => ['except' => '', 'as' => 'u_branch'],
        'is_active' => ['except' => '', 'as' => 'u_active'],
        'view'      => ['except' => 'table', 'as' => 'u_view'],
        'perPage'   => ['except' => 5, 'as' => 'u_pp'],
    ];

    public function mount()
    {
        if (!auth()->user() || !in_array(auth()->user()->role_id, [1, 2])) {
            abort(403, 'Unauthorized access to user management.');
        }

        if (auth()->user()->isAdmin()) {
            $this->formRoleId = 3;
            $this->formBranchId = auth()->user()->branch_id;
        }

        $this->updateGlobalHeader('list');
    }

    /**
     * Reset pagination when search changes.
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    // ── Real-time validation hooks ───────────────────────────────
    public function updatedFirstName() { 
    $this->validateFieldLive('firstName', ValidationHelper::rulesName(), ValidationHelper::nameMessages()); 
}
public function updatedMiddleName() { 
    $this->validateFieldLive('middleName', ValidationHelper::rulesOptionalName(), ValidationHelper::nameMessages()); 
}
public function updatedLastName() { 
    $this->validateFieldLive('lastName', ValidationHelper::rulesName(), ValidationHelper::nameMessages()); 
}

    public function updatedEmail()
    {
        if ($this->skipValidation) return;
        
        // System users (all except role 4) share the same email uniqueness pool.
        // In this component, we only manage system users.
        $rules = array_merge(ValidationHelper::rulesEmail(), [
            $this->editUserId 
                ? Rule::unique('users', 'email')->whereNot('role_id', 4)->ignore($this->editUserId) 
                : Rule::unique('users', 'email')->whereNot('role_id', 4)
        ]);
        $this->validateFieldLive('email', $rules, ValidationHelper::commonMessages());
    }

    public function updatedPhone()
    {
        if ($this->skipValidation) return;
        $this->validateFieldLive('phone', ['nullable', 'string', 'regex:/^[0-9]{10}$/'], ['phone.regex' => 'Enter 10-digit mobile number.']);
    }

    /**
     * Clear position if role is not staff
     */
    public function updatedFormRoleId($value)
    {
        if (!in_array($value, [3, 5])) {
            $this->position = '';
        }
    }

    public function updatedAddrStreet() { $this->validateFieldLive('addr_street', ['nullable', 'string', 'max:255'], ValidationHelper::commonMessages()); }
    public function updatedAddrBarangay() { $this->validateFieldLive('addr_barangay', ['nullable', 'string'], ValidationHelper::commonMessages()); }
    public function updatedAddrCity() { $this->validateFieldLive('addr_city', ['nullable', 'string'], ValidationHelper::commonMessages()); }
    public function updatedAddrProvince() { $this->validateFieldLive('addr_province', ['nullable', 'string'], ValidationHelper::commonMessages()); }
    public function updatedAddrRegion() { $this->validateFieldLive('addr_region', ['nullable', 'string'], ValidationHelper::commonMessages()); }

    /**
     * Set formRoleId and clear validation errors for this field.
     * Called from the Account Type dropdown to ensure errors are cleared on selection.
     */
    public function setFormRoleId($roleId)
    {
        $this->formRoleId = $roleId;
        // Clear validation errors for this specific field only, not the whole form
        $this->resetValidation('formRoleId');
    }

    /**
     * Set formBranchId and clear validation errors for this field.
     */
    public function setFormBranchId($branchId)
    {
        $this->formBranchId = $branchId;
        $this->resetValidation('formBranchId');
    }

    // ── Show create form ──────────────────────────────────────────
    public function showCreate()
    {
        $this->panel = 'form';
        $this->mode = 'create';
        $this->resetForm();
        $this->updateGlobalHeader('create');
    }

    // ── Show view form ────────────────────────────────────────────
    public function loadViewProfile($userId)
    {
        $this->showEdit($userId, 'view');
    }

    // ── Show edit form ────────────────────────────────────────────
    public function showEdit($userId, $mode = 'edit')
{
    $user = User::findOrFail($userId);

    // Admins may only view/edit Staff accounts within their own branch —
    // without this, showEdit() is directly callable via $wire.call() with
    // any user ID, letting an Admin load (and, via updateUser(), silently
    // demote) a Super Admin or another branch's staff.
    if (auth()->user()->isAdmin() && ($user->role_id !== 3 || $user->branch_id !== auth()->user()->branch_id)) {
        abort(403, 'Unauthorized to access this profile.');
    }

    $this->skipValidation = true; // suppress live validation while populating fields

    $this->panel = 'form';
        $this->mode = $mode;
        $this->editUserId = $user->id;
        $this->firstName = $user->first_name;
        $this->middleName = $user->middle_name ?? '';
        $this->lastName = $user->last_name;
        $this->email = $user->email;
        $this->phone = $user->phone ? str_replace('+63', '', $user->phone) : '';
        $this->formRoleId = $user->role_id;
        $this->formBranchId = $user->branch_id ?? '';
        if ($this->formRoleId == 5) {
            $this->formRoleId = 3;
            $this->position = 'Delivery Rider';
        } else {
            $this->position = $user->position ?? '';
        }
        $this->employeeId = $user->employee_id ?? '';
        $this->dateHired = $user->date_hired ?? '';
        $this->formIsActive = (bool) $user->is_active;
        $this->password = '';
        $this->passwordConfirm = '';

        // Load avatar display data
        $this->editUserAvatar = null;
        if ($user->avatar) {
            $all = collect(\App\Livewire\ProfileSettings::avatarCollection())->flatten(1);
            $this->editUserAvatar = $all->firstWhere('id', $user->avatar);
        }

        // Decode stored JSON address if exists
        $addr = json_decode($user->address, true);
        if (is_array($addr)) {
            $this->addr_region   = $addr['region']   ?? '';
            $this->addr_province = $addr['province']  ?? '';
            $this->addr_city     = $addr['city']      ?? '';
            $this->addr_barangay = $addr['barangay']  ?? '';
            $this->addr_street   = $addr['street']    ?? '';
            $this->addr_lat      = $user->latitude    ?? $addr['lat'] ?? null;
            $this->addr_lng      = $user->longitude   ?? $addr['lng'] ?? null;
        } else {
            $this->addr_street = $user->address;
            $this->addr_lat = $user->latitude;
            $this->addr_lng = $user->longitude;
        }

         $this->skipValidation = false;
        $this->resetValidation();
        $this->updateGlobalHeader($mode);
    }

    // ── History Computed Properties ───────────────────────────────
    public function getHistoryStatsProperty()
    {
        if (!$this->editUserId) return [];
        
        $baseOrderQuery = Order::where(function($q) {
            $q->where('user_id', $this->editUserId)->orWhere('rider_id', $this->editUserId);
        });
        
        if ($this->historyStartDate) {
            $baseOrderQuery->whereDate('created_at', '>=', $this->historyStartDate);
        }
        if ($this->historyEndDate) {
            $baseOrderQuery->whereDate('created_at', '<=', $this->historyEndDate);
        }

        // Clone for counts to avoid mutating the base query if we need sum
        $orderCount = (clone $baseOrderQuery)->count();
        $totalSales = (clone $baseOrderQuery)->where('status', 'Completed')->sum('total_amount');

        $completedOrderCount = (clone $baseOrderQuery)->where('status', 'Completed')->count();
        $completionRate = $orderCount > 0 ? ($completedOrderCount / $orderCount) * 100 : 0;
        $avgOrderValue = $orderCount > 0 ? ($totalSales / $orderCount) : 0;

        $recentThreshold = now('Asia/Manila')->subDays(7);
        $recentOrderCount = (clone $baseOrderQuery)->where('created_at', '>=', $recentThreshold)->count();
        $recentActivity = $recentOrderCount;

        return [
            'total_orders' => $orderCount,
            'total_sales'  => $totalSales,
            'avg_order_value' => $avgOrderValue,
            'completion_rate' => $completionRate,
            'recent_activity' => $recentActivity,
        ];
    }

    public function getSystemStatsProperty()
    {
        $baseQuery = User::where('role_id', '!=', 4);
        
        if (auth()->user()->isAdmin()) {
            $baseQuery->where('branch_id', auth()->user()->branch_id);
        }

        // Apply filters same as render()
        if ($this->search) {
            $baseQuery->where(function ($q) {
                $searchTerm = "%{$this->search}%";
                $q->where('first_name', 'like', $searchTerm)
                    ->orWhere('last_name', 'like', $searchTerm)
                    ->orWhere('middle_name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('employee_id', 'like', $searchTerm)
                    ->orWhere('phone', 'like', $searchTerm)
                    ->orWhere(\Illuminate\Support\Facades\DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', $searchTerm);
            });
        }

        if ($this->role_id) {
            if ($this->role_id == 3) {
                $baseQuery->whereIn('role_id', [3, 5]);
            } else {
                $baseQuery->where('role_id', $this->role_id);
            }
        }
        if ($this->branch_id) {
            $baseQuery->where('branch_id', $this->branch_id);
        }
        if ($this->is_active !== '') {
            $baseQuery->where('is_active', $this->is_active);
        }

        return [
            'total'    => (clone $baseQuery)->count(),
            'active'   => (clone $baseQuery)->where('is_active', 1)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', 0)->count(),
            'staff'    => (clone $baseQuery)->whereIn('role_id', [3, 5])->count(),
        ];
    }

    public function viewOrder($orderId)
    {
        $order = Order::with(['items.product', 'items.options.option', 'branch', 'rider', 'user'])->findOrFail($orderId);

        // This is only ever meant to show an order from the currently
        // loaded profile's own history — without this check, viewOrder()
        // is directly callable with any order ID, disclosing another
        // branch's order/customer details regardless of which profile is
        // open.
        $belongsToLoadedProfile = $this->editUserId
            && ($order->user_id === $this->editUserId || $order->rider_id === $this->editUserId);

        if (!$belongsToLoadedProfile) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized to view this order.');
            return;
        }

        $this->viewingOrder = $order;
        $this->dispatch('open-modal', name: 'view-order-detail');
    }

    public function closeOrder()
    {
        $this->viewingOrder = null;
    }

    public function getHistoryOrdersProperty()
    {
        if (!$this->editUserId) return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        $query = Order::where(function($q) {
            $q->where('user_id', $this->editUserId)->orWhere('rider_id', $this->editUserId);
        });

        if ($this->historyStartDate) {
            $query->whereDate('created_at', '>=', $this->historyStartDate);
        }
        if ($this->historyEndDate) {
            $query->whereDate('created_at', '<=', $this->historyEndDate);
        }

        return $query->latest()->paginate(5, ['*'], 'historyOrdersPage');
    }



    public function applyQuickDateFilter($preset)
    {
        switch ($preset) {
            case 'today':
                $this->historyStartDate = now('Asia/Manila')->startOfDay()->format('Y-m-d');
                $this->historyEndDate = now('Asia/Manila')->endOfDay()->format('Y-m-d');
                $this->activeFilter = 'Today Only';
                break;
            case 'week':
                $this->historyStartDate = now('Asia/Manila')->subDays(6)->startOfDay()->format('Y-m-d');
                $this->historyEndDate = now('Asia/Manila')->endOfDay()->format('Y-m-d');
                $this->activeFilter = 'Last 7 Days';
                break;
            case 'month':
                $this->historyStartDate = now('Asia/Manila')->subDays(29)->startOfDay()->format('Y-m-d');
                $this->historyEndDate = now('Asia/Manila')->endOfDay()->format('Y-m-d');
                $this->activeFilter = 'Last 30 Days';
                break;
            case 'all':
            default:
                $this->historyStartDate = '';
                $this->historyEndDate = '';
                $this->activeFilter = 'All Time';
                break;
        }
    }

    // ── Back to list ──────────────────────────────────────────────
    public function backToList()
    {
        $this->panel = 'list';
        $this->mode = 'list';
        $this->resetForm();
        $this->updateGlobalHeader('list');
    }

    public function runPreSaveValidation()
    {
        $this->firstName  = ucwords($this->normalizeString($this->firstName));
        $this->middleName = ucwords($this->normalizeString($this->middleName));
        $this->lastName   = ucwords($this->normalizeString($this->lastName));
        $this->position   = $this->normalizeString($this->position);

        $rules = [
            'firstName'    => ValidationHelper::rulesName(),
            'middleName'   => ValidationHelper::rulesOptionalName(),
            'lastName'     => ValidationHelper::rulesName(),
            'email'        => array_merge(ValidationHelper::rulesEmail(), [
                $this->editUserId 
                    ? Rule::unique('users', 'email')->whereNot('role_id', 4)->ignore($this->editUserId) 
                    : Rule::unique('users', 'email')->whereNot('role_id', 4)
            ]),
            'phone'        => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'formRoleId'   => ['required', 'exists:roles,id', 'in:1,2,3'],
            'formBranchId' => ['required', 'exists:branches,id'],
            'position'     => $this->formRoleId == 3 ? ['required', 'string', 'max:100', Rule::in($this->availablePositions)] : ['nullable', 'string', 'max:100'],
            'dateHired'    => ['nullable', 'date', 'before_or_equal:today'],
            'formIsActive' => ['boolean'],
        ];

        if ($this->editUserId) {
            $rules['employeeId'] = ['nullable', 'string', 'max:50', 'regex:' . ValidationHelper::REGEX_ID, Rule::unique('users', 'employee_id')->ignore($this->editUserId)];
        }

        $messages = array_merge(
            ValidationHelper::commonMessages(),
            ValidationHelper::nameMessages(),
            [
                'phone.regex' => 'Enter 10-digit mobile number (e.g. 9123456789).',
                'formBranchId.required' => 'Work location is required for all accounts.'
            ]
        );

        $this->validateBeforeModal($rules, $messages, 'confirm-save-user');
    }

    // ── Save (create) ─────────────────────────────────────────────
        public function saveUser()
    {
        $this->firstName = ucwords($this->normalizeString($this->firstName));
        $this->middleName = ucwords($this->normalizeString($this->middleName));
        $this->lastName = ucwords($this->normalizeString($this->lastName));
        $this->email = trim(strtolower($this->email));
        $this->phone = trim($this->phone);
        $this->position = $this->normalizeString($this->position);

        $this->validate([
            'firstName'    => ValidationHelper::rulesName(),
            'middleName'   => ValidationHelper::rulesOptionalName(),
            'lastName'     => ValidationHelper::rulesName(),
            'email'        => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->whereNot('role_id', 4)],
            'phone'        => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'formRoleId'   => ['required', 'exists:roles,id', 'in:1,2,3'],
            'formBranchId' => ['required', 'exists:branches,id'],
            'position'     => $this->formRoleId == 3 ? ['required', 'string', 'max:100', Rule::in($this->availablePositions)] : ['nullable', 'string', 'max:100'],
            'dateHired'    => ['nullable', 'date', 'before_or_equal:today'],
            'formIsActive' => ['boolean'],
        ], array_merge(ValidationHelper::commonMessages(), ValidationHelper::nameMessages(), [
            'phone.regex' => 'Enter 10-digit mobile number (e.g. 9123456789).',
            'formBranchId.required' => 'Work location is required.'
        ]));

        // Admins can only create Staff roles (3=Cashier, 5=Rider) mapped via formRoleId=3
        if (auth()->user()->isAdmin()) {
            if (!in_array($this->formRoleId, [3])) {
                $this->formRoleId = 3; // Default to Staff
            }
            $this->formBranchId = auth()->user()->branch_id;
        }

        // Data is already sanitized in validateBeforeCreate()
        $plainPassword = \Illuminate\Support\Str::random(10);

        $attempts = 0;
        do {
            $nextId = (User::max('id') ?? 0) + 1 + $attempts;
            $generatedEmployeeId = 'MT-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            $attempts++;
        } while (User::where('employee_id', $generatedEmployeeId)->exists() && $attempts < 20);

        // Manager conflict check
        if ($this->formRoleId == 2 && $this->formBranchId && !$this->forceReplaceManager) {
            $branch = Branch::with('manager')->find($this->formBranchId);
            if ($branch && $branch->user_id) {
                $this->conflictingManagerName = $branch->manager->first_name . ' ' . $branch->manager->last_name;
                $this->dispatch('close-modal', name: 'confirm-save-user');
                $this->dispatch('open-modal', name: 'confirm-manager-replace');
                return;
            }
        }

        // Enforce position requirement: only Staff (role_id 3) needs a position
        if (!in_array($this->formRoleId, [3])) {
            $this->position = null;
        }

        $finalRoleId = $this->formRoleId;
        if ($this->formRoleId == 3 && $this->position === 'Delivery Rider') {
            $finalRoleId = 5;
        } else if ($this->formRoleId == 3 && $this->position === 'Cashier') {
            $finalRoleId = 3;
        }

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

        $user = User::create([
            'employee_id' => $generatedEmployeeId,
            'first_name' => $this->firstName,
            'middle_name' => $this->middleName ?: null,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone ? '+63' . trim($this->phone) : null,
            'password' => Hash::make($plainPassword),
            'role_id' => $finalRoleId,
            'branch_id' => $this->formBranchId ?: null,
            'position' => $this->position ?: null,
            'date_hired' => $this->dateHired ?: null,
            'is_active' => $this->formIsActive,
            'address' => $addressJson,
            'latitude' => $this->addr_lat,
            'longitude' => $this->addr_lng,
        ]);

        // Sync: If this user is a Branch Manager, update the branch record.
        if ($user->role_id == 2 && $user->branch_id) {
            $branch = Branch::find($user->branch_id);
            if ($branch) {
                // If this branch already had a different manager (the one
                // just replaced via the conflict-confirmation modal), clear
                // their branch_id — otherwise they stay stuck pointing at a
                // branch they no longer manage.
                if ($branch->user_id && $branch->user_id != $user->id) {
                    User::where('id', $branch->user_id)->update(['branch_id' => null]);
                }
                // Clear this user's manager status on any other branch first
                Branch::where('user_id', $user->id)->update(['user_id' => null]);
                $branch->update(['user_id' => $user->id]);
            }
        }

        $message = 'User created successfully. Credentials are being emailed.';
        $this->dispatch('notify', type: 'success', message: $message);
        $this->dispatch('close-modal', name: 'confirm-save-user');
        $this->dispatch('close-modal', name: 'confirm-manager-replace');

        dispatch(function () use ($user, $plainPassword) {
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)
                    ->send(new \App\Mail\UserCredentialsMail($user, $plainPassword));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send credential email: ' . $e->getMessage());
            }
        })->afterResponse();

        $this->backToList();
    }

       public function updateUser()
    {
        $user = User::findOrFail($this->editUserId);

        if (auth()->user()->isAdmin()) {
            if ($user->role_id !== 3 || $user->branch_id !== auth()->user()->branch_id) {
                $this->dispatch('notify', type: 'error', message: 'Unauthorized to modify this account.');
                $this->dispatch('close-modal', name: 'confirm-save-user');
                return;
            }
            if (!in_array($this->formRoleId, [3])) {
                $this->formRoleId = 3; // Force back to Staff
            }
            $this->formBranchId = auth()->user()->branch_id; // Force own branch
        }

        $this->firstName = ucwords($this->normalizeString($this->firstName));
        $this->middleName = ucwords($this->normalizeString($this->middleName));
        $this->lastName = ucwords($this->normalizeString($this->lastName));
        $this->email = trim(strtolower($this->email));
        $this->phone = trim($this->phone);
        $this->position = $this->normalizeString($this->position);
        $this->employeeId = trim($this->employeeId);

        $this->validate([
            'employeeId'   => ['nullable', 'string', 'max:50', 'regex:' . ValidationHelper::REGEX_ID, Rule::unique('users', 'employee_id')->ignore($user->id)],
            'firstName'    => ValidationHelper::rulesName(),
            'middleName'   => ValidationHelper::rulesOptionalName(),
            'lastName'     => ValidationHelper::rulesName(),
            'email'        => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->whereNot('role_id', 4)->ignore($user->id)],
            'phone'        => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'formRoleId'   => ['required', 'exists:roles,id', 'in:1,2,3'],
            'formBranchId' => ['required', 'exists:branches,id'],
            'position'     => $this->formRoleId == 3 ? ['required', 'string', 'max:100', Rule::in($this->availablePositions)] : ['nullable', 'string', 'max:100'],
            'dateHired'    => ['nullable', 'date', 'before_or_equal:today'],
            'formIsActive' => ['boolean'],
        ], array_merge(ValidationHelper::commonMessages(), ValidationHelper::nameMessages(), [
            'phone.regex' => 'Enter 10-digit mobile number (e.g. 9123456789).',
            'formBranchId.required' => 'Work location is required.'
        ]));

        // Manager conflict check
        if ($this->formRoleId == 2 && $this->formBranchId && !$this->forceReplaceManager) {
            $branch = Branch::with('manager')->find($this->formBranchId);
            if ($branch && $branch->user_id && $branch->user_id != $user->id) {
                $this->conflictingManagerName = $branch->manager->first_name . ' ' . $branch->manager->last_name;
                $this->dispatch('close-modal', name: 'confirm-save-user');
                $this->dispatch('open-modal', name: 'confirm-manager-replace');
                return;
            }
        }

        $oldRoleId = $user->role_id;
        $oldBranchId = $user->branch_id;

        if (!in_array($this->formRoleId, [3])) {
            $this->position = null;
        }

        $finalRoleId = $this->formRoleId;
        if ($this->formRoleId == 3 && $this->position === 'Delivery Rider') {
            $finalRoleId = 5;
        } else if ($this->formRoleId == 3 && $this->position === 'Cashier') {
            $finalRoleId = 3;
        }

        $user->first_name = $this->firstName;
        $user->middle_name = $this->middleName ?: null;
        $user->last_name = $this->lastName;
        $user->email = $this->email;
        $user->phone = $this->phone ? '+63' . trim($this->phone) : null;
        $user->role_id = $finalRoleId;
        $user->branch_id = $this->formBranchId ?: null;
        $user->position = $this->position ?: null;
        $user->date_hired = $this->dateHired ?: null;
        $user->is_active = $this->formIsActive;

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

        $user->address = $addressJson;
        $user->latitude = $this->addr_lat;
        $user->longitude = $this->addr_lng;

        $user->save();

        // Sync manager status
        if ($user->role_id == 2) {
            if ($oldBranchId != $user->branch_id || $oldRoleId != 2) {
                Branch::where('user_id', $user->id)->update(['user_id' => null]);

                if ($user->branch_id) {
                    $branch = Branch::find($user->branch_id);
                    if ($branch) {
                        // If this branch already had a different manager
                        // (the one just replaced via the conflict modal),
                        // clear their branch_id — otherwise they stay stuck
                        // pointing at a branch they no longer manage.
                        if ($branch->user_id && $branch->user_id != $user->id) {
                            User::where('id', $branch->user_id)->update(['branch_id' => null]);
                        }
                        $branch->update(['user_id' => $user->id]);
                    }
                }
            } elseif ($user->branch_id) {
                // Same branch, same role — but this branch might still point
                // to a stale different manager if it was reassigned to THIS
                // user via the conflict-replace flow while branch/role didn't
                // change on the user side. Ensure it's pointed correctly.
                $branch = Branch::find($user->branch_id);
                if ($branch && $branch->user_id != $user->id) {
                    if ($branch->user_id) {
                        User::where('id', $branch->user_id)->update(['branch_id' => null]);
                    }
                    $branch->update(['user_id' => $user->id]);
                }
            }
        } else {
            if ($oldRoleId == 2) {
                Branch::where('user_id', $user->id)->update(['user_id' => null]);
            }
        }

        $this->dispatch('notify', type: 'success', message: 'User updated successfully.');
        $this->dispatch('close-modal', name: 'confirm-save-user');
        $this->dispatch('close-modal', name: 'confirm-manager-replace');
        $this->backToList();
    }

    // ── Force Replace Manager ─────────────────────────────────────
    public function replaceManager()
    {
        $this->forceReplaceManager = true;
        if ($this->editUserId) {
            $this->updateUser();
        } else {
            $this->saveUser();
        }
        $this->forceReplaceManager = false;
    }

    // ── Toggle status ─────────────────────────────────────────────
    public function toggleStatus($userId)
    {
        $user = User::findOrFail($userId);

        if (auth()->user()->isAdmin() && ($user->role_id !== 3 || $user->branch_id !== auth()->user()->branch_id)) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized to change this account\'s status.');
            return;
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $this->dispatch('notify', type: 'success', message: 'Status for ' . $user->first_name . ' updated successfully.');
    }

    // ── Delete ────────────────────────────────────────────────────
    public function deleteUser($userId = null)
    {
        $idToDelete = $userId ?: $this->deleteTargetId;
        if (!$idToDelete) return;

        $user = User::findOrFail($idToDelete);

        // Admins may only delete Staff accounts within their own branch —
        // without this, deleteUser() is directly callable with any user
        // ID, letting an Admin permanently delete a Super Admin or another
        // branch's staff account.
        if (auth()->user()->isAdmin() && ($user->role_id !== 3 || $user->branch_id !== auth()->user()->branch_id)) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized to delete this account.');
            return;
        }

        // Nullify foreign key references before deletion to avoid constraint violations
        \App\Models\Order::where('rider_id', $idToDelete)->update(['rider_id' => null]);
        \App\Models\Order::where('user_id', $idToDelete)->update(['user_id' => null]);

        // If user was a branch manager, unlink them from the branch
        \App\Models\Branch::where('user_id', $idToDelete)->update(['user_id' => null]);

        $user->delete();

        $this->deleteTargetId = null;
        $this->deleteTargetName = '';
        
        $this->dispatch('notify', type: 'success', message: 'User deleted successfully.');
        $this->backToList();
    }

    public function confirmDelete($id, $name)
    {
        $user = User::find($id);
        if ($user && auth()->user()->isAdmin() && ($user->role_id !== 3 || $user->branch_id !== auth()->user()->branch_id)) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized.');
            return;
        }

        $this->deleteTargetId = $id;
        $this->deleteTargetName = $name;
        $this->dispatch('open-modal', name: 'delete-user');
    }

    // ── Reset form fields ─────────────────────────────────────────
    public function updateGlobalHeader($panel = 'list')
    {
        $title = auth()->user()->role_id === 1 ? 'User Management' : 'Staff Management';
        $breadcrumbs = [
            ['label' => 'Operations', 'url' => '#'],
            ['label' => $title, 'url' => route('users.index')],
        ];

        if ($panel === 'create') {
            $breadcrumbs[] = ['label' => 'Add New', 'url' => '#'];
            $title = 'Add New User';
        } elseif ($panel === 'edit') {
            $breadcrumbs[] = ['label' => 'Edit Profile', 'url' => '#'];
            $title = 'Edit Member Profile';
        } elseif ($panel === 'view') {
            $breadcrumbs[] = ['label' => 'View Profile', 'url' => '#'];
            $title = 'View Customer Profile';
        }

        $this->dispatch('setHeader', 
            icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
            title: $title,
            breadcrumbs: $breadcrumbs
        );
    }

    public function resetForm()
    {
        $this->editUserId = null;
        $this->firstName = '';
        $this->middleName = '';
        $this->lastName = '';
        $this->email = '';
        $this->phone = '';
        $this->password = '';
        $this->passwordConfirm = '';
        
        if (auth()->check() && auth()->user()->isAdmin()) {
            $this->formRoleId = 3;
            $this->formBranchId = auth()->user()->branch_id;
        } else {
            $this->formRoleId = '';
            $this->formBranchId = '';
        }
        
        $this->position = '';
        $this->employeeId = '';
        $this->dateHired = '';
        $this->formIsActive = true;
        $this->addr_region   = '';
        $this->addr_province = '';
        $this->addr_city     = '';
        $this->addr_barangay = '';
        $this->addr_street   = '';
        $this->addr_lat      = null;
        $this->addr_lng      = null;
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────────────
    public function render()
    {
        $authUser = auth()->user();
        $query = User::with(['role', 'branch'])->where('role_id', '!=', 4);

        // Admins (role_id=2) can only see Staff (role_id=3) in their own branch
        if (auth()->user()->isAdmin()) {
            $query->where('role_id', 3)
                ->where('branch_id', $authUser->branch_id);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $searchTerm = "%{$this->search}%";
                $q->where('first_name', 'like', $searchTerm)
                    ->orWhere('last_name', 'like', $searchTerm)
                    ->orWhere('middle_name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('employee_id', 'like', $searchTerm)
                    ->orWhere('phone', 'like', $searchTerm)
                    ->orWhere(\Illuminate\Support\Facades\DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', $searchTerm);
            });
        }

        if ($this->role_id) {
            if ($this->role_id == 3) {
                $query->whereIn('role_id', [3, 5]);
            } else {
                $query->where('role_id', $this->role_id);
            }
        }
        if ($this->branch_id) {
            $query->where('branch_id', $this->branch_id);
        }
        if ($this->is_active !== '') {
            $query->where('is_active', $this->is_active);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($this->perPage);
        $roles = auth()->user()->isSuperAdmin()
            ? Role::whereIn('id', [1, 2, 3])->get()       // Super Admin: Super Admin, Admin, Staff
            : Role::whereIn('id', [3])->get();            // Admin: Staff only
            
        // Rename Cashier to Staff for UI
        if ($staffRole = $roles->firstWhere('id', 3)) {
            $staffRole->name = 'Staff';
        }
        
        $branches = Branch::where('status', 1)->get();
        $totalUsers = $users->total();

        return view('livewire.user-management', compact('users', 'roles', 'branches', 'totalUsers'))->layout('layouts.app');
    }
}
