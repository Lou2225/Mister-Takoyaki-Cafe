<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\Order;
use App\Models\UserActivityLog;
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

    // ── Form fields ───────────────────────────────────────────────
    public $editUserId = null;
    public $firstName = '';
    public $middleName = '';
    public $lastName = '';
    public $email = '';
    public $phone = '';
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
    public $statusTargetId = null;
    public $statusTargetName = '';
    public $statusTargetActive = false; // the status BEFORE the toggle, for modal copy
    public $archiveTargetId = null;
    public $archiveTargetName = '';
    public $archiveReason = '';
    public $restoreTargetId = null;
    public $restoreTargetName = '';
    public $activeTab = 'directory'; // 'directory' | 'archived' — display only; both lists are queried every render (see render())
    public ?array $editUserAvatar = null;
    public $editUserArchived = false;

    
    // ── User History Dashboard State ──────────────────────────────
    public $historyTab = 'overview';
    public $historyStartDate = '';
    public $historyEndDate = '';
    public $activeFilter = 'All Time';
    public $viewingOrder = null;
    public $timelineLimit = 5; // number of timeline events loaded; incremented by loadMoreTimeline()

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
        'activeTab' => ['except' => 'directory', 'as' => 'tab'],
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
        $this->resetPage('page');
        $this->resetPage('archivedPage');
    }

    public function updatedRoleId()
    {
        $this->resetPage('page');
        $this->resetPage('archivedPage');
    }

    public function updatedBranchId()
    {
        $this->resetPage('page');
        $this->resetPage('archivedPage');
    }

    public function updatedIsActive()
    {
        $this->resetPage('page');
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

    $this->resetForm();

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
        $this->editUserArchived = (bool) $user->archived_at;
        $this->archiveReason = $user->archive_reason ?? '';

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

    public function getTimelineTotalCountProperty()
    {
        if (!$this->editUserId) return 0;

        $count = UserActivityLog::where('user_id', $this->editUserId)->count();

        // Check if fallback hired event should be included
        $hasHiredLog = UserActivityLog::where('user_id', $this->editUserId)
            ->where('event_type', 'hired')
            ->exists();

        if (!$hasHiredLog) {
            $user = User::find($this->editUserId);
            if ($user && ($user->date_hired || $user->created_at)) {
                $count++;
            }
        }

        return $count;
    }

    public function loadMoreTimeline()
    {
        $this->timelineLimit += 5;
    }

    public function getTimelineEventsProperty()
    {
        if (!$this->editUserId) return collect();

        $user = User::with(['role', 'branch'])->find($this->editUserId);
        if (!$user) return collect();

        // ── Pull from the persistent log table with limit ─────────
        // We query up to $this->timelineLimit to avoid heavy overhead
        $logs = UserActivityLog::with('performer')
            ->where('user_id', $this->editUserId)
            ->orderByDesc('created_at')
            ->take($this->timelineLimit)
            ->get();

        $events = $logs->map(function ($log) {
            return [
                'type'        => $log->event_type,
                'title'       => $log->event_label,
                'description' => $log->description,
                'timestamp'   => $log->created_at,
                'color'       => $log->color,
                'badge'       => $log->badge,
                'performed_by'=> $log->performer ? $log->performer->first_name . ' ' . $log->performer->last_name : null,
            ];
        });

        // ── Backward-compatible fallback ──────────────────────────
        // If no 'hired' log exists anywhere in table for this user,
        // synthesise one from date_hired / created_at so old accounts still
        // show a "Joined the Team" milestone.
        $hasHiredLog = UserActivityLog::where('user_id', $this->editUserId)
            ->where('event_type', 'hired')
            ->exists();

        if (!$hasHiredLog) {
            $hireDate = $user->date_hired
                ? \Carbon\Carbon::parse($user->date_hired)
                : $user->created_at;

            if ($hireDate) {
                $events->push([
                    'type'         => 'hired',
                    'title'        => 'Joined the Team',
                    'description'  => 'Account registered as ' . (optional($user->role)->name ?? 'Team Member')
                                    . ($user->branch ? ' at ' . $user->branch->branch_name : ''),
                    'timestamp'    => $hireDate,
                    'color'        => 'indigo',
                    'badge'        => 'Milestone',
                    'performed_by' => null,
                ]);
            }
        }

        return $events->sortByDesc('timestamp')->take($this->timelineLimit)->values();
    }



    public function getSystemStatsProperty()
    {
        $baseQuery = User::where('role_id', '!=', 4)->notArchived();
        
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

    public function getArchivedStatsProperty()
    {
        $baseQuery = User::where('role_id', '!=', 4)->archived();

        if (auth()->user()->isAdmin()) {
            $baseQuery->where('role_id', 3)->where('branch_id', auth()->user()->branch_id);
        }
        if ($this->search) {
            $searchTerm = "%{$this->search}%";
            $baseQuery->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                    ->orWhere('last_name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('employee_id', 'like', $searchTerm);
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

        return [
            'total'  => (clone $baseQuery)->count(),
            'staff'  => (clone $baseQuery)->whereIn('role_id', [3, 5])->count(),
            'admins' => (clone $baseQuery)->where('role_id', 2)->count(),
            'recent' => (clone $baseQuery)->where('archived_at', '>=', now()->subDays(30))->count(),
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
            $this->dispatch('notify', type: 'warning', message: 'Unauthorized to view this order.');
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

    protected function sanitizeInput(): void
    {
        $this->firstName  = ucwords($this->normalizeString($this->firstName));
        $this->middleName = ucwords($this->normalizeString($this->middleName));
        $this->lastName   = ucwords($this->normalizeString($this->lastName));
        $this->email      = trim(strtolower($this->email));
        $this->phone      = trim($this->phone);
        $this->position   = $this->normalizeString($this->position);
        if ($this->editUserId) {
            $this->employeeId = trim($this->employeeId);
        }
    }

    protected function getValidationRules(): array
    {
        $emailRules = array_merge(ValidationHelper::rulesEmail(), [
            $this->editUserId
                ? Rule::unique('users', 'email')->whereNot('role_id', 4)->ignore($this->editUserId)
                : Rule::unique('users', 'email')->whereNot('role_id', 4)
        ]);

        $rules = [
            'firstName'    => ValidationHelper::rulesName(),
            'middleName'   => ValidationHelper::rulesOptionalName(),
            'lastName'     => ValidationHelper::rulesName(),
            'email'        => $emailRules,
            'phone'        => ['nullable', 'string', 'regex:' . ValidationHelper::REGEX_PH_MOBILE],
            'formRoleId'   => ['required', 'exists:roles,id', 'in:1,2,3'],
            'formBranchId' => ['required', 'exists:branches,id'],
            'position'     => $this->formRoleId == 3
                ? ['required', 'string', 'max:100', Rule::in($this->availablePositions)]
                : ['nullable', 'string', 'max:100'],
            'dateHired'    => ['nullable', 'date', 'before_or_equal:today'],
            'formIsActive' => ['boolean'],
        ];

        if ($this->editUserId) {
            $rules['employeeId'] = [
                'nullable',
                'string',
                'max:50',
                'regex:' . ValidationHelper::REGEX_ID,
                Rule::unique('users', 'employee_id')->ignore($this->editUserId)
            ];
        }

        return $rules;
    }

    protected function getValidationMessages(): array
    {
        return array_merge(
            ValidationHelper::commonMessages(),
            ValidationHelper::nameMessages(),
            [
                'phone.regex'           => 'Enter 10-digit mobile number (e.g. 9123456789).',
                'formBranchId.required' => 'Work location is required.'
            ]
        );
    }

    protected function buildAddressData(): array
    {
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

        return [
            'address'   => $addressJson,
            'latitude'  => $this->addr_lat,
            'longitude' => $this->addr_lng,
        ];
    }

    protected function syncBranchManager(User $user, ?int $oldBranchId = null, ?int $oldRoleId = null): void
    {
        if ($user->role_id == 2) {
            if ($oldBranchId != $user->branch_id || $oldRoleId != 2) {
                Branch::where('user_id', $user->id)->update(['user_id' => null]);

                if ($user->branch_id) {
                    $branch = Branch::find($user->branch_id);
                    if ($branch) {
                        if ($branch->user_id && $branch->user_id != $user->id) {
                            User::where('id', $branch->user_id)->update(['branch_id' => null]);
                        }
                        $branch->update(['user_id' => $user->id]);
                    }
                }
            } elseif ($user->branch_id) {
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
    }

    public function runPreSaveValidation()
    {
        $this->sanitizeInput();
        $this->validateBeforeModal(
            $this->getValidationRules(),
            $this->getValidationMessages(),
            'confirm-save-user'
        );
    }

    // ── Save (create) ─────────────────────────────────────────────
    public function saveUser()
    {
        $this->sanitizeInput();
        $this->validate($this->getValidationRules(), $this->getValidationMessages());

        // Admins can only create Staff roles mapped via formRoleId=3
        if (auth()->user()->isAdmin()) {
            $this->formRoleId = 3;
            $this->formBranchId = auth()->user()->branch_id;
        }

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

        if (!in_array($this->formRoleId, [3])) {
            $this->position = null;
        }

        $finalRoleId = ($this->formRoleId == 3 && $this->position === 'Delivery Rider') ? 5 : (int) $this->formRoleId;
        $addrData = $this->buildAddressData();

        $user = User::create(array_merge([
            'employee_id' => $generatedEmployeeId,
            'first_name'  => $this->firstName,
            'middle_name' => $this->middleName ?: null,
            'last_name'   => $this->lastName,
            'email'       => $this->email,
            'phone'       => $this->phone ? '+63' . trim($this->phone) : null,
            'password'    => Hash::make($plainPassword),
            'role_id'     => $finalRoleId,
            'branch_id'   => $this->formBranchId ?: null,
            'position'    => $this->position ?: null,
            'date_hired'  => $this->dateHired ?: null,
            'is_active'   => $this->formIsActive,
        ], $addrData));

        $this->syncBranchManager($user);

        // Log the initial hire event
        UserActivityLog::create([
            'user_id'      => $user->id,
            'performed_by' => auth()->id(),
            'event_type'   => 'hired',
            'description'  => 'Account registered as ' . (optional($user->role)->name ?? 'Team Member')
                            . ($user->branch ? ' at ' . $user->branch->branch_name : ''),
        ]);

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
                $this->dispatch('notify', type: 'warning', message: 'Unauthorized to modify this account.');
                $this->dispatch('close-modal', name: 'confirm-save-user');
                return;
            }
            if (!in_array($this->formRoleId, [3])) {
                $this->formRoleId = 3;
            }
            $this->formBranchId = auth()->user()->branch_id;
        }

        $this->sanitizeInput();
        $this->validate($this->getValidationRules(), $this->getValidationMessages());

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

        $finalRoleId = ($this->formRoleId == 3 && $this->position === 'Delivery Rider') ? 5 : (int) $this->formRoleId;
        $addrData = $this->buildAddressData();

        $user->first_name  = $this->firstName;
        $user->middle_name = $this->middleName ?: null;
        $user->last_name   = $this->lastName;
        $user->email       = $this->email;
        $user->phone       = $this->phone ? '+63' . trim($this->phone) : null;
        $user->role_id     = $finalRoleId;
        $user->branch_id   = $this->formBranchId ?: null;
        $user->position    = $this->position ?: null;
        $user->date_hired  = $this->dateHired ?: null;
        $user->is_active   = $this->formIsActive;
        $user->address     = $addrData['address'];
        $user->latitude    = $addrData['latitude'];
        $user->longitude   = $addrData['longitude'];
        $user->save();

        $this->syncBranchManager($user, $oldBranchId, $oldRoleId);

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
    public function confirmToggleStatus($userId, $name, $isActive)
    {
        $user = User::find($userId);

        if ($user && auth()->user()->isAdmin() && ($user->role_id !== 3 || $user->branch_id !== auth()->user()->branch_id)) {
            $this->dispatch('notify', type: 'warning', message: 'Unauthorized: You do not have permission to change this account\'s status.');
            return;
        }

        $this->statusTargetId = $userId;
        $this->statusTargetName = $name;
        $this->statusTargetActive = (bool) $isActive;
        $this->dispatch('open-modal', name: 'toggle-status');
    }

    public function toggleStatus($userId = null)
    {
        $idToToggle = $userId ?: $this->statusTargetId;
        if (!$idToToggle) return;

        $user = User::findOrFail($idToToggle);

        if (auth()->user()->isAdmin() && ($user->role_id !== 3 || $user->branch_id !== auth()->user()->branch_id)) {
            $this->dispatch('notify', type: 'warning', message: 'Unauthorized to change this account\'s status.');
            return;
        }

        $user->is_active = !$user->is_active;
        $user->save();

        // Log the status change
        UserActivityLog::create([
            'user_id'      => $user->id,
            'performed_by' => auth()->id(),
            'event_type'   => $user->is_active ? 'activated' : 'deactivated',
            'description'  => 'Account ' . ($user->is_active ? 'set to Active' : 'set to Inactive') . '.',
        ]);

        $this->statusTargetId   = null;
        $this->statusTargetName = '';

        $this->dispatch('notify', type: 'success', message: 'Status for ' . $user->first_name . ' updated successfully.');
    }

    // ── Archive (soft-delete replacement) ───────────────────────────
    public function confirmArchive($id, $name)
    {
        $user = User::find($id);
        if ($user && auth()->user()->isAdmin() && ($user->role_id !== 3 || $user->branch_id !== auth()->user()->branch_id)) {
            $this->dispatch('notify', type: 'warning', message: 'Unauthorized: You do not have permission to archive this account.');
            return;
        }

        $this->archiveTargetId = $id;
        $this->archiveTargetName = $name;
        $this->archiveReason = '';
        $this->dispatch('open-modal', name: 'archive-user');
    }

    public function archiveUser($userId = null)
    {
        $idToArchive = $userId ?: $this->archiveTargetId;
        if (!$idToArchive) return;

        $user = User::findOrFail($idToArchive);

        if (auth()->user()->isAdmin() && ($user->role_id !== 3 || $user->branch_id !== auth()->user()->branch_id)) {
            $this->dispatch('notify', type: 'warning', message: 'Unauthorized to archive this account.');
            return;
        }

        // If this user was a branch manager, free up the branch — an
        // archived account shouldn't stay wired as the acting manager.
        \App\Models\Branch::where('user_id', $idToArchive)->update(['user_id' => null]);

        $user->is_active = false;
        $user->archived_at = now();
        $user->archive_reason = $this->archiveReason ?: null;
        $user->save();

        // Log the archive event
        UserActivityLog::create([
            'user_id'      => $user->id,
            'performed_by' => auth()->id(),
            'event_type'   => 'archived',
            'description'  => $user->archive_reason ? 'Reason: ' . $user->archive_reason : 'Account moved to archives.',
        ]);

        $this->archiveTargetId   = null;
        $this->archiveTargetName = '';
        $this->archiveReason     = '';

        $this->dispatch('notify', type: 'success', message: 'User archived successfully.');
        $this->backToList();
    }

    public function confirmRestore($id, $name)
    {
        $user = User::find($id);
        if ($user && auth()->user()->isAdmin() && ($user->role_id !== 3 || $user->branch_id !== auth()->user()->branch_id)) {
            $this->dispatch('notify', type: 'warning', message: 'Unauthorized: You do not have permission to restore this account.');
            return;
        }

        $this->restoreTargetId = $id;
        $this->restoreTargetName = $name;
        $this->dispatch('open-modal', name: 'restore-user');
    }

    public function restoreUser($userId = null)
    {
        $idToRestore = $userId ?: $this->restoreTargetId;
        if (!$idToRestore) return;

        $user = User::findOrFail($idToRestore);

        if (auth()->user()->isAdmin() && ($user->role_id !== 3 || $user->branch_id !== auth()->user()->branch_id)) {
            $this->dispatch('notify', type: 'warning', message: 'Unauthorized to restore this account.');
            return;
        }

        // Restoring clears the archive flag only — is_active stays false,
        // so the account doesn't silently regain sign-in access. A
        // separate deliberate "Activate" action (the status toggle) is
        // required to let them sign in again.
        $user->archived_at = null;
        $user->archive_reason = null;
        $user->save();

        // Log the restore event
        UserActivityLog::create([
            'user_id'      => $user->id,
            'performed_by' => auth()->id(),
            'event_type'   => 'restored',
            'description'  => 'Account restored to the directory.',
        ]);

        $this->restoreTargetId = null;
        $this->restoreTargetName = '';

        if ($this->editUserId == $user->id) {
            $this->editUserArchived = false;
        }

        $this->dispatch('close-modal', name: 'restore-user');

        if ($this->panel === 'form') {
            $this->backToList();
        }

        $this->dispatch('notify', type: 'success', message: 'User restored to the directory.');
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
        $this->editUserArchived = false;
        $this->archiveReason = '';
        $this->addr_region   = '';
        $this->addr_province = '';
        $this->addr_city     = '';
        $this->addr_barangay = '';
        $this->addr_street   = '';
        $this->addr_lat      = null;
        $this->addr_lng      = null;
        $this->timelineLimit = 5;
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────────────
    public function render()
    {
        $applyCommonFilters = function ($query) {
            if (auth()->user()->isAdmin()) {
                $query->where('role_id', 3)->where('branch_id', auth()->user()->branch_id);
            }
            if ($this->search) {
                $searchTerm = "%{$this->search}%";
                $query->where(function ($q) use ($searchTerm) {
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
            return $query;
        };

        // Both tabs are rendered together and toggled client-side (same
        // pattern as Stock Management's Ingredients/Expiry tabs), so both
        // lists are queried on every request.

        $directoryQuery = User::with(['role', 'branch'])->where('role_id', '!=', 4)->notArchived();
        $applyCommonFilters($directoryQuery);
        if ($this->is_active !== '') {
            $directoryQuery->where('is_active', $this->is_active);
        }
        $users = $directoryQuery->orderByRaw("
                CASE
                    WHEN is_active = 0 THEN 4
                    WHEN role_id = 1 THEN 1
                    WHEN role_id = 2 THEN 2
                    WHEN role_id IN (3, 5) THEN 3
                    ELSE 5
                END ASC
            ")
            ->orderBy('first_name')
            ->paginate($this->perPage, ['*'], 'page');

        $archivedQuery = User::with(['role', 'branch'])->where('role_id', '!=', 4)->archived();
        $applyCommonFilters($archivedQuery);
        $archivedUsers = $archivedQuery->orderBy('archived_at', 'desc')
            ->paginate($this->perPage, ['*'], 'archivedPage');

        $roles = auth()->user()->isSuperAdmin()
            ? Role::whereIn('id', [1, 2, 3])->get()
            : Role::whereIn('id', [3])->get();

        if ($staffRole = $roles->firstWhere('id', 3)) {
            $staffRole->name = 'Staff';
        }

        $branches = Branch::where('status', 1)->get();
        $totalUsers = $users->total();

        return view('livewire.user-management', compact('users', 'archivedUsers', 'roles', 'branches', 'totalUsers'))->layout('layouts.app');
    }
}
