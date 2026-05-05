<?php

namespace App\Http\Livewire;

use Livewire\Component;

use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Traits\HandlesValidations;
use App\Helpers\ValidationHelper;
use App\Helpers\QrCodeHelper;
use App\Models\SystemSetting;
use App\Models\Branch;
use App\Services\ConfigurationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SystemSettings extends Component
{
    use WithPagination, WithFileUploads, HandlesValidations;

    public $tab = 'general';
    public $perPage = 5;
    public $page = 1;

    // Business Settings
    public $businessName;
    public $businessEmail;
    public $businessPhone;
    public $businessTin;
    public $businessAddress;
    public $businessLogo;
    public $existingLogo;

    // Address / PSGC fields
    public $addr_region   = '';
    public $addr_province = '';
    public $addr_city     = '';
    public $addr_barangay = '';
    public $addr_street   = '';
    public $addr_lat      = null;
    public $addr_lng      = null;

    // Financial Settings
    public $vatRate;
    public $serviceCharge;
    public $currency;
    public $currencySymbol;
    public $taxDestination; // Where tax revenue is credited: 'TAX_LIABILITY' or 'BUSINESS'

    // Receipt Settings
    public $receiptLogoEnabled;
    public $receiptShowVat;
    public $receiptFooterMessage;
    public $receiptReturnPolicy;
    public $receiptCopies;
    public $receiptQrUrl;

    // Inventory Settings
    public $lowStockThreshold;
    public $criticalStockThreshold;
    public $expiryAlertDays;
    public $autoReorderEnabled;

    // POS Settings
    public $discountRate;
    public $seniorDiscountRate;
    public $posBusinessName;
    public $posOrderTypes = [];
    public $posPaymentMethods = [];
    public $newOrderType = '';
    public $newPaymentMethod = '';
    public $gcashAccountName;
    public $gcashAccountNumber;
    public $gcashQrImage;
    public $existingGcashQrImage;

    // System Settings
    public $hideOperationalModules = false;
    public $opBranchId;
    public $branches = [];

    // Review Settings
    public $reviewFormTitle = 'How was your experience?';
    public $reviewFormSubtitle = 'Thank you for your feedback!';
    public $reviewQuestions = [];
    public $sampleQrCode = ''; // Data URI for sample QR code in receipt preview


    protected $queryString = [
        'perPage' => ['except' => 5, 'as' => 'ss_pp'],
    ];

    protected $listeners = [
        'branchContextUpdated' => 'handleBranchSwitch',
        'branch-switched' => 'handleBranchSwitch',
        'refresh' => '$refresh'
    ];

    public function handleBranchSwitch()
    {
        $this->opBranchId = \App\Services\BranchContext::getActiveBranchId() ?: auth()->user()->branch_id;
        $this->loadSettings();
    }

    public function mount()
    {
        // Authorize: Only Super Admin and Admin can access system settings
        if (!auth()->user() || (!auth()->user()->isSuperAdmin() && !auth()->user()->isAdmin())) {
            abort(403, 'Unauthorized access to system settings.');
        }

        // Set default tab for non-super admins
        if (!auth()->user()->isSuperAdmin()) {
            $this->tab = 'inventory';
        }

        $this->branches = Branch::all();
        $this->opBranchId = auth()->user()->branch_id;
        $this->loadSettings();
        $this->updateHeader();
        // Generate a sample QR code for the receipt preview
        $this->sampleQrCode = QrCodeHelper::generateReviewQrCode('SAMPLE-' . uniqid());
    }


    private function loadSettings()
    {
        $this->businessName    = SystemSetting::get('business_name', 'Mister Takoyaki Cafe');
        $this->businessEmail   = SystemSetting::get('business_email', 'contact@mistertakoyaki.com');
        $this->businessPhone   = SystemSetting::get('business_phone', '');
        if (str_starts_with($this->businessPhone, '+63')) {
            $this->businessPhone = substr($this->businessPhone, 3);
        }

        $this->businessTin     = SystemSetting::get('business_tin', '');
        $this->businessAddress = SystemSetting::get('business_address', '');
        $this->existingLogo    = SystemSetting::get('business_logo', '');

        // Decode stored JSON address if exists
        $addr = $this->businessAddress;
        if (is_string($addr)) {
            $addr = json_decode($addr, true);
        }
        
        if (is_array($addr)) {
            $this->addr_region   = $addr['region']   ?? '';
            $this->addr_province = $addr['province']  ?? '';
            $this->addr_city     = $addr['city']      ?? '';
            $this->addr_barangay = $addr['barangay']  ?? '';
            $this->addr_street   = $addr['street']    ?? '';
            $this->addr_lat      = $addr['lat'] ?? null;
            $this->addr_lng      = $addr['lng'] ?? null;
            $this->businessAddress = $addr['formatted'] ?? '';
        }

        $this->vatRate        = 0;
        $this->serviceCharge  = SystemSetting::get('service_charge', 0.00);
        $this->currency       = SystemSetting::get('currency', 'PHP');
        $this->currencySymbol = SystemSetting::get('currency_symbol', '₱');
        $this->taxDestination = 'BUSINESS';

        $this->receiptLogoEnabled   = SystemSetting::get('receipt_logo_enabled', true);
        $this->receiptShowVat       = SystemSetting::get('receipt_show_vat', true);
        $this->receiptFooterMessage = SystemSetting::get('receipt_footer_message', 'Thank you for your visit!');
        $this->receiptReturnPolicy  = SystemSetting::get('receipt_return_policy', 'No return, no exchange.');
        $this->receiptCopies        = SystemSetting::get('receipt_copies', 1);
        $this->receiptQrUrl         = SystemSetting::get('receipt_qr_url', '');

        $this->lowStockThreshold      = SystemSetting::get('low_stock_threshold', 10);
        $this->criticalStockThreshold = SystemSetting::get('critical_stock_threshold', 5);
        $this->expiryAlertDays        = SystemSetting::get('expiry_alert_days', 7);
        $this->autoReorderEnabled     = SystemSetting::get('auto_reorder_enabled', false);

        $this->discountRate      = SystemSetting::get('discount_rate', 0.10);
        $this->seniorDiscountRate = SystemSetting::get('senior_discount_rate', 0.20);
        $this->posBusinessName   = SystemSetting::get('pos_business_name', 'Mister Takoyaki');
        $this->posOrderTypes     = SystemSetting::get('pos_order_types', ['Dine-in', 'Take-out']);
        $this->posPaymentMethods = SystemSetting::get('pos_payment_methods', ['Cash', 'GCash']);
        $this->gcashAccountName   = SystemSetting::get('gcash_account_name', 'Mister Takoyaki Cafe');
        $this->gcashAccountNumber  = SystemSetting::get('gcash_account_number', '');
        $this->existingGcashQrImage = SystemSetting::get('gcash_qr_image', '');

        // Per-user preference for module visibility (isolates settings between super admins)
        $this->hideOperationalModules = auth()->user()->hide_modules;

        $this->reviewFormTitle   = SystemSetting::get('review_form_title', 'How was your experience?');
        $this->reviewFormSubtitle = SystemSetting::get('review_form_subtitle', 'Thank you for your feedback!');
        $defaultQuestions = [
            ['text' => 'How would you rate our food quality?', 'type' => 'rating', 'required' => true],
            ['text' => 'How would you rate our service?', 'type' => 'rating', 'required' => true],
            ['text' => 'Any suggestions for improvement?', 'type' => 'text', 'required' => false],
        ];
        $reviewQuestionsJson = SystemSetting::get('review_questions', json_encode($defaultQuestions));
        $this->reviewQuestions = is_string($reviewQuestionsJson) 
            ? json_decode($reviewQuestionsJson, true) 
            : $reviewQuestionsJson;
    }

    public function updatedBusinessName()
    {
        $this->validateFieldLive('businessName', ['required', 'string', 'min:3', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME_BASIC], ValidationHelper::commonMessages());
    }

    public function updatedBusinessEmail()
    {
        $this->validateFieldLive('businessEmail', ValidationHelper::rulesEmail(), ValidationHelper::commonMessages());
    }

    public function updatedBusinessPhone()
    {
        $this->validateFieldLive('businessPhone', ['nullable', 'string', 'regex:/^[0-9]{10}$/'], ['businessPhone.regex' => 'Enter 10-digit mobile number.']);
    }

    public function updatedAddrStreet() { $this->validateFieldLive('addr_street', ['nullable', 'string', 'max:255'], ValidationHelper::commonMessages()); }
    public function updatedAddrBarangay() { $this->validateFieldLive('addr_barangay', ['nullable', 'string'], ValidationHelper::commonMessages()); }
    public function updatedAddrCity() { $this->validateFieldLive('addr_city', ['nullable', 'string'], ValidationHelper::commonMessages()); }
    public function updatedAddrProvince() { $this->validateFieldLive('addr_province', ['nullable', 'string'], ValidationHelper::commonMessages()); }
    public function updatedAddrRegion() { $this->validateFieldLive('addr_region', ['nullable', 'string'], ValidationHelper::commonMessages()); }

    public function updatedPosBusinessName()
    {
        $this->validateFieldLive('posBusinessName', ['required', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME], ValidationHelper::commonMessages());
    }

    public function updatedLowStockThreshold()
    {
        $this->validateFieldLive('lowStockThreshold', ['required', 'integer', 'min:1', 'max:10000'], ValidationHelper::commonMessages());
    }


    /**
     * Magic method to handle all updating* methods that reset pagination.
     */
    public function __call($method, $parameters)
    {
        if (str_starts_with($method, 'updating') && !str_ends_with($method, 'Page')) {
            $this->resetPage();
            return;
        }

        // Handle other potential missing methods if needed
        return parent::__call($method, $parameters);
    }

    public function selectTab($tab)
    {
        // Guard against non-super admins trying to access restricted tabs
        $operationalTabs = ['inventory', 'pos', 'reviews'];
        if (!auth()->user()->isSuperAdmin() && !in_array($tab, $operationalTabs)) {
            return;
        }

        $this->tab = $tab;
        $this->resetPage(); // Reset pagination when switching tabs
        $this->updateHeader();
    }

    public function addOrderType()
    {
        $type = trim($this->normalizeString($this->newOrderType));
        if ($type && !in_array($type, $this->posOrderTypes)) {
            $this->posOrderTypes[] = $type;
        }
        $this->newOrderType = '';
    }

    public function removeOrderType($type)
    {
        $this->posOrderTypes = array_filter($this->posOrderTypes, fn($t) => $t !== $type);
    }

    public function addPaymentMethod()
    {
        $method = trim($this->normalizeString($this->newPaymentMethod));
        if ($method && !in_array($method, $this->posPaymentMethods)) {
            $this->posPaymentMethods[] = $method;
        }
        $this->newPaymentMethod = '';
    }

    public function removePaymentMethod($method)
    {
        $this->posPaymentMethods = array_filter($this->posPaymentMethods, fn($m) => $m !== $method);
    }

    public function addReviewQuestion()
    {
        $this->reviewQuestions[] = [
            'text' => 'New Question',
            'type' => 'rating',
            'required' => true,
        ];
    }

    public function removeReviewQuestion($index)
    {
        unset($this->reviewQuestions[$index]);
        $this->reviewQuestions = array_values($this->reviewQuestions); // Reindex array
    }

    public function updatedHideOperationalModules($value)
    {
        if ($value === true) {
            $this->opBranchId = '';
        }
    }

    public function updatedOpBranchId($value)
    {
        // If "General Headquarters (No Branch)" is selected, automatically hide modules
        if (empty($value)) {
            $this->hideOperationalModules = true;
        } else {
            // If a specific branch is selected, unhide modules automatically
            $this->hideOperationalModules = false;
        }
    }

    public function validateBeforeSave()
    {
        // Authorize based on tab and role
        $isOperationsTab = in_array($this->tab, ['inventory', 'pos', 'reviews']);
        
        if (!$this->isSuperAdmin() && !$isOperationsTab) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error', 
                'message' => 'Unauthorized operation. You only have permission to modify operational settings.'
            ]);
            return;
        }

        // Normalize data for the current tab before validation
        $this->normalizeCurrentTabData();

        // Get rules specifically for the active tab
        $rules = $this->getRulesForTab($this->tab);

        $this->validateBeforeModal($rules, ValidationHelper::commonMessages(), 'confirm-update-settings');
    }

    private function normalizeCurrentTabData()
    {
        if ($this->tab === 'general') {
            $this->businessName    = $this->normalizeString($this->businessName);
            $this->businessEmail   = trim(strtolower($this->businessEmail));
            $this->businessPhone   = trim(preg_replace('/\s+/', '', $this->businessPhone));
            $this->businessTin     = trim($this->businessTin);
            $this->businessAddress = $this->normalizeString($this->businessAddress);
        }
    }

    private function getRulesForTab($tab)
    {
        $allRules = [
            'general' => [
                'businessName'    => ['required', 'string', 'min:3', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME_BASIC],
                'businessEmail'   => ValidationHelper::rulesEmail(),
                'businessPhone'   => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
                'businessTin'     => ['nullable', 'regex:/^\d{3}-\d{3}-\d{3}-\d{3}$/'],
                'addr_region'     => ['nullable', 'string'],
                'addr_province'   => ['nullable', 'string'],
                'addr_city'       => ['nullable', 'string'],
                'addr_barangay'   => ['nullable', 'string'],
                'addr_street'     => ['nullable', 'string', 'max:255'],
                'businessLogo'    => ['nullable', 'image', 'max:1024'],
            ],

            'receipts' => [
                'receiptFooterMessage' => ['nullable', 'string', 'max:255'],
                'receiptReturnPolicy'  => ['nullable', 'string', 'max:500'],
                'receiptCopies'        => ['required', 'integer', 'min:1', 'max:5'],
                'receiptQrUrl'         => ['nullable', 'url', 'max:500'],
            ],
            'inventory' => [
                'lowStockThreshold'      => ['required', 'integer', 'min:1', 'max:10000'],
                'criticalStockThreshold' => ['required', 'integer', 'min:1', 'lt:lowStockThreshold'],
                'expiryAlertDays'        => ['required', 'integer', 'min:1', 'max:365'],
            ],
            'pos' => [
                'serviceCharge'      => ['required', 'numeric', 'min:0', 'max:1'],
                'discountRate'       => ['required', 'numeric', 'min:0', 'max:1'],
                'seniorDiscountRate' => ['required', 'numeric', 'min:0', 'max:1'],
                'posBusinessName'    => ['nullable', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME_BASIC],
                'posOrderTypes'      => ['required', 'array', 'min:1'],
                'posPaymentMethods'  => ['required', 'array', 'min:1'],
                'gcashAccountName'   => ['nullable', 'string', 'max:255'],
                'gcashAccountNumber' => ['nullable', 'string', 'max:20'],
                'gcashQrImage'       => ['nullable', 'image', 'max:1024'],
            ],
            'system' => [
                'opBranchId' => [
                    auth()->user()->role_id === 1 && !$this->hideOperationalModules ? 'required' : 'nullable',
                ],
            ],
            'reviews' => [
                'reviewFormTitle' => ['required', 'string', 'max:255'],
                'reviewFormSubtitle' => ['nullable', 'string', 'max:255'],
            ]
        ];

        return $allRules[$tab] ?? [];
    }

    protected function validateSettingsData()
    {
        $this->normalizeCurrentTabData();
        $rules = $this->getRulesForTab($this->tab);
        
        $this->validateSecure($rules, [
            'businessName.regex' => 'Business name has invalid characters.',
            'businessPhone.regex' => 'Enter 10-digit mobile number (e.g. 9123456789).',
            'posBusinessName.regex' => 'Terminal name has invalid characters.',
            'opBranchId.required' => 'An Operating Branch is required when operational modules are visible.',
        ]);
    }

    public function updateSettings()
    {
        // Authorize based on tab and role
        $isOperationsTab = in_array($this->tab, ['inventory', 'pos', 'reviews']);
        
        if (!$this->isSuperAdmin() && !$isOperationsTab) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error', 
                'message' => 'Unauthorized: You only have permission to modify operational settings.'
            ]);
            return;
        }

        $this->validateSettingsData();

        // Handle Logo Upload
        if ($this->businessLogo) {
            if ($this->existingLogo) {
                Storage::disk('public')->delete($this->existingLogo);
            }
            $this->existingLogo = $this->businessLogo->store('branding', 'public');
            $this->businessLogo = null;
        }

        // Handle GCash QR Upload
        if ($this->gcashQrImage) {
            if ($this->existingGcashQrImage) {
                Storage::disk('public')->delete($this->existingGcashQrImage);
            }
            $this->existingGcashQrImage = $this->gcashQrImage->store('branding', 'public');
            $this->gcashQrImage = null;
        }

        // Save All to DB
        // Compose address JSON from PSGC sub-fields
        $parts = array_filter([
            $this->addr_street,
            $this->addr_barangay,
            $this->addr_city,
            $this->addr_province,
            $this->addr_region,
        ]);
        $formattedAddress = implode(', ', $parts);

        $addressJson = json_encode([
            'region'    => $this->addr_region,
            'province'  => $this->addr_province,
            'city'      => $this->addr_city,
            'barangay'  => $this->addr_barangay,
            'street'    => $this->addr_street,
            'lat'       => $this->addr_lat,
            'lng'       => $this->addr_lng,
            'formatted' => $formattedAddress,
        ]);

        SystemSetting::set('business_name', $this->businessName);
        SystemSetting::set('business_email', $this->businessEmail);
        SystemSetting::set('business_phone', $this->businessPhone ? '+63' . trim($this->businessPhone) : '');
        SystemSetting::set('business_tin', $this->businessTin);
        SystemSetting::set('business_address', $addressJson);
        SystemSetting::set('business_logo', $this->existingLogo);

        SystemSetting::set('vat_rate', 0);
        SystemSetting::set('service_charge', (float)$this->serviceCharge);
        SystemSetting::set('currency', $this->currency);
        SystemSetting::set('currency_symbol', $this->currencySymbol);
        SystemSetting::set('tax_destination', 'BUSINESS');

        SystemSetting::set('receipt_logo_enabled', (bool)$this->receiptLogoEnabled);
        SystemSetting::set('receipt_show_vat', (bool)$this->receiptShowVat);
        SystemSetting::set('receipt_footer_message', $this->receiptFooterMessage);
        SystemSetting::set('receipt_return_policy', $this->receiptReturnPolicy);
        SystemSetting::set('receipt_copies', (int)$this->receiptCopies);
        SystemSetting::set('receipt_qr_url', $this->receiptQrUrl);

        SystemSetting::set('low_stock_threshold', (int)$this->lowStockThreshold);
        SystemSetting::set('critical_stock_threshold', (int)$this->criticalStockThreshold);
        SystemSetting::set('expiry_alert_days', (int)$this->expiryAlertDays);
        SystemSetting::set('auto_reorder_enabled', (bool)$this->autoReorderEnabled);

        SystemSetting::set('discount_rate', (float)$this->discountRate);
        SystemSetting::set('senior_discount_rate', (float)$this->seniorDiscountRate);
        SystemSetting::set('pos_business_name', $this->posBusinessName);
        SystemSetting::set('pos_order_types', $this->posOrderTypes);
        SystemSetting::set('pos_payment_methods', $this->posPaymentMethods);
        SystemSetting::set('gcash_account_name', $this->gcashAccountName);
        SystemSetting::set('gcash_account_number', $this->gcashAccountNumber);
        SystemSetting::set('gcash_qr_image', $this->existingGcashQrImage);

        // Save per-user preferences (isolates settings from other super admins)
        $user = auth()->user();
        $user->update([
            'hide_modules' => (bool)$this->hideOperationalModules,
            'branch_id'    => $this->opBranchId ? (int)$this->opBranchId : null
        ]);

        if ($user->role_id === 1) {
            \App\Services\BranchContext::setActiveBranch($this->opBranchId);
        }


        if ($this->tab === 'reviews') {
            SystemSetting::set('review_form_title', $this->reviewFormTitle);
            SystemSetting::set('review_form_subtitle', $this->reviewFormSubtitle);
            SystemSetting::set('review_questions', json_encode($this->reviewQuestions));
        }

        // Invalidate all cached configurations so other modules get fresh data
        ConfigurationService::invalidateCache();

        // Broadcast update events so other Livewire components can react
        $this->emit('settingsUpdated', [
            'type' => 'all',
            'business' => ConfigurationService::getBusinessConfig(),
            'financial' => ConfigurationService::getFinancialConfig(),
            'inventory' => ConfigurationService::getInventoryConfig(),
            'pos' => ConfigurationService::getPosConfig(),
        ]);

        // Broadcast to specific modules
        $this->emit('businessSettingsUpdated', ConfigurationService::getBusinessConfig());
        $this->emit('financialSettingsUpdated', ConfigurationService::getFinancialConfig());
        $this->emit('inventorySettingsUpdated', ConfigurationService::getInventoryConfig());
        $this->emit('posSettingsUpdated', ConfigurationService::getPosConfig());

        $this->dispatchBrowserEvent('notify', [
            'type'    => 'success',
            'message' => 'System configuration persisted successfully. All modules reloading...'
        ]);

        $this->dispatchBrowserEvent('close-modal', 'confirm-update-settings');
        
        // Dispatch to browser to refresh sidebar logo and business name
        $this->dispatchBrowserEvent('businessConfigUpdated', [
            'logo_url' => ConfigurationService::getBusinessLogoUrl(),
            'business_name' => ConfigurationService::getBusinessName(),
        ]);

        $this->dispatchBrowserEvent('accessibility-config-updated', [
            'hide_modules' => (bool)$this->hideOperationalModules
        ]);

        $this->emit('refreshTopbar');
        $this->emit('branchContextUpdated');
        $this->updateHeader();
        $this->regenerateQrCode();
    }

    public function regenerateQrCode()
    {
        $this->sampleQrCode = QrCodeHelper::generateReviewQrCode('SAMPLE-' . uniqid());
    }

    public function downloadDatabaseBackup()
    {
        // Authorize: Only super admins can download backups
        if (!auth()->user()->isSuperAdmin()) {
            $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'Unauthorized: Only administrators can download database backups.']);
            return;
        }

        $filename = "backup-" . now()->format('Y-m-d-H-i') . ".sql";
        $path = storage_path("app/backups/" . $filename);
        
        if (!File::exists(storage_path('app/backups'))) {
            File::makeDirectory(storage_path('app/backups'), 0755, true);
        }

        $mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $command = "\"$mysqldumpPath\" --user=$dbUser " . ($dbPass ? "--password=$dbPass " : "") . "$dbName > \"$path\"";
        
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            return response()->download($path)->deleteFileAfterSend(true);
        }

        $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'Database backup failed. Check server permissions.']);
    }

    public $backupFile;
    public function restoreDatabaseBackup()
    {
        // Authorize: Only super admins can restore backups
        if (!auth()->user()->isSuperAdmin()) {
            $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'Unauthorized: Only administrators can restore database backups.']);
            return;
        }

        $this->validate([
            'backupFile' => 'required|file|max:50120', // 50MB max
        ]);

        $path = $this->backupFile->store('temp_restores');
        $fullPath = storage_path('app/' . $path);

        $mysqlPath = 'C:\\xampp\\mysql\\bin\\mysql.exe';
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $command = "\"$mysqlPath\" --user=$dbUser " . ($dbPass ? "--password=$dbPass " : "") . "$dbName < \"$fullPath\"";
        
        exec($command, $output, $returnVar);

        Storage::delete($path);

        if ($returnVar === 0) {
            $this->dispatchBrowserEvent('notify', ['type' => 'success', 'message' => 'System state restored successfully. Initializing...']);
            return redirect()->route('settings.index');
        }

        $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'Recovery failed. SQL syntax error or connection drop.']);
    }

    public function downloadMediaBackup()
    {
        $filename = "media-backup-" . now()->format('Y-m-d-H-i') . ".zip";
        $path = storage_path("app/backups/" . $filename);

        if (!File::exists(storage_path('app/backups'))) {
            File::makeDirectory(storage_path('app/backups'), 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            $files = File::allFiles(storage_path('app/public'));
            foreach ($files as $file) {
                $zip->addFile($file->getRealPath(), 'public/' . $file->getRelativePathname());
            }
            $zip->close();
            return response()->download($path)->deleteFileAfterSend(true);
        }

        $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'Media backup failed. Zip extension might be missing.']);
    }

    private function updateHeader()
    {
        $this->emit('setHeader', [
            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            'title' => 'System Settings',
            'breadcrumbs' => [
                ['label' => 'Configuration', 'url' => '#'],
                ['label' => 'System Settings', 'url' => route('settings.index')],
                ['label' => ucwords(str_replace('_', ' ', $this->tab)), 'url' => '#'],
            ]
        ]);
    }

    public function render()
    {
        return view('livewire.system-settings', [
            'logs' => $this->getRealLogs()
        ])->layout('layouts.app');
    }

    private function getRealLogs()
    {
        // 1. Get Recent Orders
        $orderLogs = \App\Models\Order::with('user')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn($o) => [
                'id' => 'ord-' . $o->id,
                'user' => $o->user->name ?? 'System',
                'action' => 'Order ' . $o->status,
                'target' => '#' . $o->reference_no,
                'time' => $o->created_at->diffForHumans(),
                'timestamp' => $o->created_at,
                'status' => match($o->status) {
                    'Completed' => 'success',
                    'Cancelled', 'Void' => 'rose',
                    default => 'info'
                }
            ]);

        // 2. Get Recent Stock Movements
        $stockLogs = \App\Models\StockMovement::with(['user', 'ingredient'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn($m) => [
                'id' => 'stk-' . $m->id,
                'user' => $m->user->name ?? 'System',
                'action' => 'Stock ' . ucfirst($m->type),
                'target' => ($m->ingredient->name ?? 'Item') . ' (' . ($m->quantity > 0 ? '+' : '') . $m->quantity . ')',
                'time' => $m->created_at->diffForHumans(),
                'timestamp' => $m->created_at,
                'status' => 'info'
            ]);

        // 3. Get Recent User Activities
        $userLogs = \App\Models\User::latest()
            ->limit(10)
            ->get()
            ->map(fn($u) => [
                'id' => 'usr-' . $u->id,
                'user' => 'System',
                'action' => 'Account Created',
                'target' => $u->name,
                'time' => $u->created_at->diffForHumans(),
                'timestamp' => $u->created_at,
                'status' => 'warning'
            ]);

        // Combine and Sort by timestamp
        $allLogs = $orderLogs->concat($stockLogs)->concat($userLogs)
            ->sortByDesc('timestamp')
            ->values();

        $page = $this->page ?: 1;
        
        return new LengthAwarePaginator(
            $allLogs->forPage($page, $this->perPage),
            $allLogs->count(),
            $this->perPage,
            $page
        );
    }

    public function isSuperAdmin()
    {
        return auth()->check() && auth()->user()->role_id === 1;
    }

    public function isAdmin()
    {
        return auth()->check() && in_array(auth()->user()->role_id, [1, 2]);
    }

    public function isStaff()
    {
        return auth()->check() && in_array(auth()->user()->role_id, [1, 2, 3]);
    }
}
