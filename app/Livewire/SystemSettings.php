<?php

namespace App\Livewire;

use Livewire\Component;

use Livewire\Attributes\On;
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

class SystemSettings extends Component
{
    use WithPagination, WithFileUploads, HandlesValidations;

    // Fixed, non-extensible option sets — POS order modes and payment
    // methods are now toggled on/off rather than freely typed, so staff
    // can't introduce typos, duplicates, or unsupported gateways.
    public const ORDER_TYPE_OPTIONS = ['Dine-in', 'Take-out', 'Delivery', 'Pick-up'];
    public const PAYMENT_METHOD_OPTIONS = ['Cash', 'GCash'];

    public $tab = 'general';
    public $perPage = 5;

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
    public $serviceCharge;
    public $currency;
    public $currencySymbol;

    // Receipt Settings
    public $receiptLogoEnabled;
    public $receiptFooterMessage;
    public $receiptReturnPolicy;
    public $receiptCopies;
    public $receiptQrUrl;
    public $kitchenSlipTitle;
    public $kitchenSlipSubtitle;
    public $baristaSlipTitle;
    public $baristaSlipSubtitle;
    public $customerReceiptTitle;
    public $showReceiptQrCode;
    public $showReceiptFooter;
    public $showReceiptTendered;
    public $showReceiptChange;

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
    public $gcashAccountName;
    public $gcashAccountNumber;
    public $gcashQrImage;
    public $existingGcashQrImage;

    // System Settings
    public $hideOperationalModules = false;
    public $opBranchId;
    public $branches = [];

    // Which branch's settings are currently loaded/edited. Super admins
    // follow whichever branch is active in BranchContext (the same
    // switcher used for stock ordering elsewhere); everyone else is
    // locked to their own assigned branch. Null = editing the global
    // default that branches fall back to when they have no override.
    public $settingsBranchId = null;
    public $settingsBranchName = 'All Branches (Global Default)';
    public $missingBranchAssignment = false;

    // Tabs whose settings are per-branch. A user with no branch_id and
    // no super-admin privileges must not be able to view or save these —
    // there is no "their branch" to scope the edit to.
    private const BRANCH_SCOPED_TABS = ['receipts', 'inventory', 'pos', 'reviews', 'printer'];

    // Review Settings
    public $reviewFormTitle = 'How was your experience?';
    public $reviewFormSubtitle = 'Thank you for your feedback!';
    public $reviewQuestions = [];
    public $sampleQrCode = ''; // Data URI for sample QR code in receipt preview

    // Thermal Printer Settings
    public $printerEnabled = false;
    public $printerType = 'bluetooth';
    public $printerName = '';
    public $printerAutoCut = true;
    public array $availablePrinters = [];
    public bool $windowsPrintingAvailable = false;

    protected $queryString = [
        'perPage' => ['except' => 5, 'as' => 'ss_pp'],
    ];

    public bool $isSavingSystemState = false;

    #[On('branchContextUpdated')]
    #[On('branch-switched')]
    public function handleBranchSwitch()
    {
        if ($this->isSavingSystemState) {
            return;
        }
        $this->opBranchId = \App\Services\BranchContext::getActiveBranchId() ?: auth()->user()->branch_id;
        $this->loadSettings();
    }

    #[On('refresh')]
    public function refreshSettings(): void
    {
        //
    }

    public function mount()
    {
        // Authorize: Only Super Admin and Admin can access system settings
        if (!auth()->user() || (!auth()->user()->isSuperAdmin() && !auth()->user()->isAdmin())) {
            abort(403, 'Unauthorized access to system settings.');
        }

        $this->branches = Branch::all();
        $this->opBranchId = auth()->user()->branch_id;
        $this->loadSettings();

        // Set default tab for non-super admins. If they have no branch
        // assignment, every operational tab is branch-scoped and blocked,
        // so there is nothing safe to default into.
        if (!auth()->user()->isSuperAdmin()) {
            $this->tab = $this->missingBranchAssignment ? '' : 'inventory';
        }
        $this->updateHeader();
        // Generate a sample QR code for the receipt preview
        $this->sampleQrCode = QrCodeHelper::generateReviewQrCode('SAMPLE-' . uniqid());
    }


        /**
     * Strips any punctuation and a leading '63' country code (with or
     * without a '+'), returning a clean 10-digit local number. Handles
     * both correctly-prefixed values ('+639989282479') and legacy/bad
     * data that was saved without the '+' ('639989282479') — a plain
     * str_starts_with(...,'+63') check only catches the first case and
     * silently leaves stale country-code digits in the field otherwise.
     */
    private function normalizeMobileDisplay(?string $raw): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $raw);
        if (strlen($digits) > 10) {
            $digits = str_starts_with($digits, '63')
                ? substr($digits, 2)
                : substr($digits, -10);
        }
        return $digits;
    }
    private function resolveSettingsBranchId(): ?int
    {
        if ($this->isSuperAdmin()) {
            return \App\Services\BranchContext::getActiveBranchId() ?: null;
        }

        $branchId = auth()->user()->branch_id;
        $this->missingBranchAssignment = empty($branchId);

        return $branchId;
    }

    private function loadSettings()
    {
        $this->settingsBranchId = $this->resolveSettingsBranchId();
        $this->settingsBranchName = $this->settingsBranchId
            ? (Branch::find($this->settingsBranchId)?->branch_name ?? 'Unknown Branch')
            : 'All Branches (Global Default)';

        $this->businessName    = SystemSetting::get('business_name', 'Mister Takoyaki Cafe');
        $this->businessEmail   = SystemSetting::get('business_email', 'contact@mistertakoyaki.com');
        $this->businessPhone   = $this->normalizeMobileDisplay(SystemSetting::get('business_phone', ''));

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

        $this->serviceCharge  = SystemSetting::get('service_charge', 0.00, $this->settingsBranchId);
        $this->currency       = SystemSetting::get('currency', 'PHP');
        $this->currencySymbol = SystemSetting::get('currency_symbol', '₱');

        $this->receiptLogoEnabled   = SystemSetting::get('receipt_logo_enabled', true, $this->settingsBranchId);
        $this->receiptFooterMessage = SystemSetting::get('receipt_footer_message', 'Thank you for your visit!', $this->settingsBranchId);
        $this->receiptReturnPolicy  = SystemSetting::get('receipt_return_policy', 'No return, no exchange.', $this->settingsBranchId);
        $this->receiptCopies        = SystemSetting::get('receipt_copies', 1, $this->settingsBranchId);
        $this->receiptQrUrl         = SystemSetting::get('receipt_qr_url', '', $this->settingsBranchId);
        
        // Thermal printer receipt customization
        $this->kitchenSlipTitle     = SystemSetting::get('kitchen_slip_title', '🍳 KITCHEN SLIP', $this->settingsBranchId);
        $this->kitchenSlipSubtitle  = SystemSetting::get('kitchen_slip_subtitle', 'Food Preparation Order', $this->settingsBranchId);
        $this->baristaSlipTitle     = SystemSetting::get('barista_slip_title', '☕ BARISTA SLIP', $this->settingsBranchId);
        $this->baristaSlipSubtitle  = SystemSetting::get('barista_slip_subtitle', 'Beverage Preparation Order', $this->settingsBranchId);
        $this->customerReceiptTitle = SystemSetting::get('customer_receipt_title', 'Customer Receipt & Invoice', $this->settingsBranchId);
        $this->showReceiptQrCode    = SystemSetting::get('show_receipt_qr_code', true, $this->settingsBranchId);
        $this->showReceiptFooter    = SystemSetting::get('show_receipt_footer', true, $this->settingsBranchId);
        $this->showReceiptTendered = SystemSetting::get('show_receipt_tendered', true, $this->settingsBranchId);
        $this->showReceiptChange   = SystemSetting::get('show_receipt_change', true, $this->settingsBranchId);

        $this->lowStockThreshold      = SystemSetting::get('low_stock_threshold', 10, $this->settingsBranchId);
        $this->criticalStockThreshold = SystemSetting::get('critical_stock_threshold', 5, $this->settingsBranchId);
        $this->expiryAlertDays        = SystemSetting::get('expiry_alert_days', 7, $this->settingsBranchId);
        $this->autoReorderEnabled     = SystemSetting::get('auto_reorder_enabled', false, $this->settingsBranchId);

        $this->discountRate      = SystemSetting::get('discount_rate', 0.10, $this->settingsBranchId);
        $this->seniorDiscountRate = SystemSetting::get('senior_discount_rate', 0.20, $this->settingsBranchId);
        $this->posBusinessName   = SystemSetting::get('pos_business_name', 'Mister Takoyaki', $this->settingsBranchId);
        $this->posOrderTypes = array_values(array_intersect(
            SystemSetting::get('pos_order_types', ['Dine-in', 'Take-out'], $this->settingsBranchId),
            self::ORDER_TYPE_OPTIONS
        ));
        $this->posPaymentMethods = array_values(array_intersect(
            SystemSetting::get('pos_payment_methods', ['Cash', 'GCash'], $this->settingsBranchId),
            self::PAYMENT_METHOD_OPTIONS
        ));
        $this->gcashAccountName   = SystemSetting::get('gcash_account_name', 'Mister Takoyaki Cafe', $this->settingsBranchId);
        $this->gcashAccountNumber = $this->normalizeMobileDisplay(SystemSetting::get('gcash_account_number', '', $this->settingsBranchId));
        $this->existingGcashQrImage = SystemSetting::get('gcash_qr_image', '', $this->settingsBranchId);

        // Per-user preference for module visibility (isolates settings between super admins)
        $this->hideOperationalModules = auth()->user()->hide_modules;

        $this->reviewFormTitle   = SystemSetting::get('review_form_title', 'How was your experience?', $this->settingsBranchId);
        $this->reviewFormSubtitle = SystemSetting::get('review_form_subtitle', 'Thank you for your feedback!', $this->settingsBranchId);
        $defaultQuestions = [
            ['text' => 'How would you rate our food quality?', 'type' => 'rating', 'required' => true],
            ['text' => 'How would you rate our service?', 'type' => 'rating', 'required' => true],
            ['text' => 'Any suggestions for improvement?', 'type' => 'text', 'required' => false],
        ];
        $reviewQuestionsRaw = SystemSetting::get('review_questions', json_encode($defaultQuestions), $this->settingsBranchId);
        $this->reviewQuestions = is_string($reviewQuestionsRaw)
            ? json_decode($reviewQuestionsRaw, true)
            : ($reviewQuestionsRaw ?? $defaultQuestions);
            // Thermal Printer Settings
        $printerConfig = SystemSetting::get('thermal_printer_config', [], $this->settingsBranchId);
        if (is_string($printerConfig)) {
            $printerConfig = json_decode($printerConfig, true) ?? [];
        }
                $this->printerEnabled = $printerConfig['enabled'] ?? false;
        $storedPrinterType = $printerConfig['type'] ?? 'bluetooth';
        $this->printerType = in_array($storedPrinterType, ['windows', 'usb'], true)
            ? 'wired'
            : ($storedPrinterType === 'file' || $storedPrinterType === 'network' ? 'bluetooth' : $storedPrinterType);
        $this->printerName = $printerConfig['name'] ?? '';
        $this->printerAutoCut = $printerConfig['auto_cut'] ?? true;
        // A hosted Linux server cannot access a printer attached to the
        // cashier's Windows PC. Avoid loading the legacy Windows-only printer
        // service here so a problem in that optional service cannot prevent
        // the Settings page from opening on Hostinger.
        $this->windowsPrintingAvailable = PHP_OS_FAMILY === 'Windows' && function_exists('proc_open');
        $this->availablePrinters = $this->windowsPrintingAvailable
            ? \App\Services\ThermalPrinterService::getWindowsPrinters()
            : [];

        if (empty($this->printerName) && !empty($this->availablePrinters)) {
            $this->printerName = $this->availablePrinters[0];
        }
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
        $this->validateFieldLive('businessPhone', ['nullable', 'string', 'regex:~^[0-9]{10}$~'], ['businessPhone.regex' => 'Enter 10-digit mobile number.']);
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

    public function updatedGcashAccountNumber()
    {
        $this->validateFieldLive('gcashAccountNumber', ['nullable', 'string', 'regex:~^[0-9]{10}$~'], ['gcashAccountNumber.regex' => 'Enter 10-digit mobile number.']);
    }

    public function updatedLowStockThreshold()
    {
        $this->validateFieldLive('lowStockThreshold', ['required', 'integer', 'min:1', 'max:10000'], ValidationHelper::commonMessages());
    }

    public function updatedCriticalStockThreshold()
    {
        $this->validateFieldLive('criticalStockThreshold', ['required', 'integer', 'min:1', 'lt:lowStockThreshold'], ValidationHelper::commonMessages());
    }

    public function updatedExpiryAlertDays()
    {
        $this->validateFieldLive('expiryAlertDays', ['required', 'integer', 'min:1', 'max:365'], ValidationHelper::commonMessages());
    }

    public function updatedDiscountRate()
    {
        $this->validateFieldLive('discountRate', ['required', 'numeric', 'min:0', 'max:1'], ValidationHelper::commonMessages());
    }

    public function updatedSeniorDiscountRate()
    {
        $this->validateFieldLive('seniorDiscountRate', ['required', 'numeric', 'min:0', 'max:1'], ValidationHelper::commonMessages());
    }

    public function updatedServiceCharge()
    {
        $this->validateFieldLive('serviceCharge', ['required', 'numeric', 'min:0', 'max:1'], ValidationHelper::commonMessages());
    }

    public function updatedReviewFormTitle()
    {
        $this->validateFieldLive('reviewFormTitle', ['required', 'string', 'max:255'], ValidationHelper::commonMessages());
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
    }

    public function selectTab($tab)
    {
        $superAdminTabs = ['general', 'receipts', 'inventory', 'pos', 'reviews', 'system', 'logs', 'printer'];
        $operationalTabs = ['inventory', 'pos', 'reviews', 'printer'];

        // Validate tab is a known value
        if (!in_array($tab, $superAdminTabs)) {
            return;
        }

        // Non-super admins can only access operational tabs
        if (!auth()->user()->isSuperAdmin() && !in_array($tab, $operationalTabs)) {
            return;
        }

        // A non-super-admin with no assigned branch has no branch to scope
        // branch-specific settings to — block the tab rather than silently
        // falling back to editing the global default.
        if ($this->missingBranchAssignment && in_array($tab, self::BRANCH_SCOPED_TABS)) {
            $this->dispatch('notify',
                type: 'error',
                message: 'You are not assigned to a branch, so branch-specific settings are unavailable. Contact a super admin.'
            );
            return;
        }

        $this->tab = $tab;
        $this->resetPage();
        $this->updateHeader();
    }

    public function toggleOrderType(string $type)
    {
        if (!in_array($type, self::ORDER_TYPE_OPTIONS, true)) return;

        if (in_array($type, $this->posOrderTypes, true)) {
            $this->posOrderTypes = array_values(array_diff($this->posOrderTypes, [$type]));
        } else {
            $this->posOrderTypes[] = $type;
        }
    }

    public function togglePaymentMethod(string $method)
    {
        if (!in_array($method, self::PAYMENT_METHOD_OPTIONS, true)) return;

        if (in_array($method, $this->posPaymentMethods, true)) {
            $this->posPaymentMethods = array_values(array_diff($this->posPaymentMethods, [$method]));
        } else {
            $this->posPaymentMethods[] = $method;
        }
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
        if (!$this->authorizeTabAccess()) {
            $this->dispatch('notify', 
                type: 'error', 
                message: 'Unauthorized operation. You only have permission to modify operational settings.'
            );
            return;
        }

        // Normalize data for the current tab before validation
        $this->normalizeCurrentTabData();

        // Get rules specifically for the active tab
        $rules = $this->getRulesForTab($this->tab);

        $this->validateBeforeModal($rules, ValidationHelper::commonMessages(), 'confirm-update-settings');
    }

    private function authorizeTabAccess(): bool
    {
        if ($this->missingBranchAssignment && in_array($this->tab, self::BRANCH_SCOPED_TABS)) {
            return false;
        }

        $isOperationsTab = in_array($this->tab, ['inventory', 'pos', 'reviews', 'printer']);
        return $this->isSuperAdmin() || $isOperationsTab;
    }

        private function normalizeCurrentTabData()
    {
        if ($this->tab === 'general') {
            $this->businessName    = $this->normalizeString($this->businessName);
            $this->businessEmail   = trim(strtolower($this->businessEmail));
            $this->businessPhone   = $this->normalizeMobileDisplay($this->businessPhone);
            $this->businessTin     = trim($this->businessTin);
            $this->businessAddress = $this->normalizeString($this->businessAddress);
        } elseif ($this->tab === 'pos') {
            $this->gcashAccountNumber = $this->normalizeMobileDisplay($this->gcashAccountNumber);
        }
    }

    private function getRulesForTab($tab)
    {
        $allRules = [
            'general' => [
                'businessName'    => ['required', 'string', 'min:3', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME_BASIC],
                'businessEmail'   => ValidationHelper::rulesEmail(),
                'businessPhone'   => ['nullable', 'string', 'regex:~^[0-9]{10}$~'],
                'businessTin'     => ['nullable', 'regex:~^\d{3}-\d{3}-\d{3}-\d{3}$~'],
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
                'receiptCopies'        => ['required', 'integer', 'min:1', 'max:3'],
                'receiptQrUrl'         => ['nullable', 'url', 'max:500'],
                'kitchenSlipTitle'     => ['required', 'string', 'max:255'],
                'kitchenSlipSubtitle'  => ['nullable', 'string', 'max:255'],
                'baristaSlipTitle'     => ['required', 'string', 'max:255'],
                'baristaSlipSubtitle'  => ['nullable', 'string', 'max:255'],
                'customerReceiptTitle' => ['required', 'string', 'max:255'],
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
                'gcashAccountNumber' => ['nullable', 'string', 'regex:~^[0-9]{10}$~'],
                'gcashQrImage'       => ['nullable', 'image', 'max:1024'],
            ],
                        'system' => [
                'opBranchId' => [
                    auth()->user()->role_id === 1 && !$this->hideOperationalModules ? 'required' : 'nullable',
                ],
            ],
                        'printer' => [
                'printerType' => ['required', 'in:wired,bluetooth'],
                'printerName' => $this->printerEnabled && $this->printerType === 'wired'
                    ? ['required', 'string', 'max:255']
                    : ['nullable', 'string', 'max:255'],
                'printerAutoCut' => ['boolean'],
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
            'gcashAccountNumber.regex' => 'Enter 10-digit mobile number (e.g. 9123456789).',
            'printerName.required' => 'Printer name/address is required while the printer is enabled.',
            'printerName.regex' => 'Enter a network address in the format IP:PORT (e.g. 192.168.1.100:9100) — not a printer name.',
        ]);
    }

    public function updateSettings()
    {
        if (!$this->authorizeTabAccess()) {
            $this->dispatch('notify',
                type: 'error',
                message: 'Unauthorized: You only have permission to modify operational settings.'
            );
            return;
        }

        $this->validateSettingsData();

        switch ($this->tab) {
            case 'general':
                $this->saveGeneralTab();
                break;
            case 'receipts':
                $this->saveReceiptsTab();
                break;
            case 'inventory':
                $this->saveInventoryTab();
                break;
            case 'pos':
                $this->savePosTab();
                break;
            case 'system':
                $this->saveSystemTab();
                break;
            case 'reviews':
                $this->saveReviewsTab();
                break;
                case 'printer':
                $this->savePrinterTab();
                break;
        }
        // Invalidate all cached configurations so other modules get fresh data
        ConfigurationService::invalidateCache();

        // Broadcast only what's relevant to the tab just saved — the System
        // tab already dispatches its own accessibility/branch events inside
        // saveSystemTab(), so it doesn't need business/financial/inventory/
        // pos payloads rebuilt and pushed on every save.
        if ($this->tab !== 'system') {
            $this->dispatch('settingsUpdated',
                type: 'all',
                business: ConfigurationService::getBusinessConfig(),
                financial: ConfigurationService::getFinancialConfig(),
                inventory: ConfigurationService::getInventoryConfig(),
                pos: ConfigurationService::getPosConfig(),
            );
            $this->dispatch('businessSettingsUpdated', ConfigurationService::getBusinessConfig());
            $this->dispatch('financialSettingsUpdated', ConfigurationService::getFinancialConfig());
            $this->dispatch('inventorySettingsUpdated', ConfigurationService::getInventoryConfig());
            $this->dispatch('posSettingsUpdated', ConfigurationService::getPosConfig());
        }
 
        $this->dispatch('notify',
            type: 'success',
            message: ucwords(str_replace('_', ' ', $this->tab)) . ' settings saved successfully.'
        );

        $this->dispatch('close-modal', name: 'confirm-update-settings');

        $this->dispatch('businessconfigupdated',
            logo_url: ConfigurationService::getBusinessLogoUrl(),
            business_name: ConfigurationService::getBusinessName(),
        );

        $this->dispatch('refreshTopbar');
        $this->updateHeader();
    }

    private function saveGeneralTab(): void
    {
        if ($this->businessLogo) {
            if ($this->existingLogo) {
                Storage::disk('public')->delete($this->existingLogo);
            }
            $this->existingLogo = $this->businessLogo->store('branding', 'public');
            $this->businessLogo = null;
        }

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
    }

    private function saveReceiptsTab(): void
    {
        SystemSetting::set('receipt_logo_enabled', (bool) $this->receiptLogoEnabled, $this->settingsBranchId);
        SystemSetting::set('receipt_footer_message', $this->receiptFooterMessage, $this->settingsBranchId);
        SystemSetting::set('receipt_return_policy', $this->receiptReturnPolicy, $this->settingsBranchId);
        SystemSetting::set('receipt_copies', (int) $this->receiptCopies, $this->settingsBranchId);
        SystemSetting::set('receipt_qr_url', $this->receiptQrUrl, $this->settingsBranchId);
        
        // Thermal printer receipt customization
        SystemSetting::set('kitchen_slip_title', $this->kitchenSlipTitle, $this->settingsBranchId);
        SystemSetting::set('kitchen_slip_subtitle', $this->kitchenSlipSubtitle, $this->settingsBranchId);
        SystemSetting::set('barista_slip_title', $this->baristaSlipTitle, $this->settingsBranchId);
        SystemSetting::set('barista_slip_subtitle', $this->baristaSlipSubtitle, $this->settingsBranchId);
        SystemSetting::set('customer_receipt_title', $this->customerReceiptTitle, $this->settingsBranchId);
        SystemSetting::set('show_receipt_qr_code', (bool) $this->showReceiptQrCode, $this->settingsBranchId);
        SystemSetting::set('show_receipt_footer', (bool) $this->showReceiptFooter, $this->settingsBranchId);
        SystemSetting::set('show_receipt_tendered', (bool) $this->showReceiptTendered, $this->settingsBranchId);
        SystemSetting::set('show_receipt_change', (bool) $this->showReceiptChange, $this->settingsBranchId);
        $this->regenerateQrCode();
    }

    private function saveInventoryTab(): void
    {
        SystemSetting::set('low_stock_threshold', (int) $this->lowStockThreshold, $this->settingsBranchId);
        SystemSetting::set('critical_stock_threshold', (int) $this->criticalStockThreshold, $this->settingsBranchId);
        SystemSetting::set('expiry_alert_days', (int) $this->expiryAlertDays, $this->settingsBranchId);
        SystemSetting::set('auto_reorder_enabled', (bool) $this->autoReorderEnabled, $this->settingsBranchId);
    }

    private function savePosTab(): void
    {
        if ($this->gcashQrImage) {
            if ($this->existingGcashQrImage) {
                Storage::disk('public')->delete($this->existingGcashQrImage);
            }
            $this->existingGcashQrImage = $this->gcashQrImage->store('branding', 'public');
            $this->gcashQrImage = null;
        }

        SystemSetting::set('service_charge', (float) $this->serviceCharge, $this->settingsBranchId);
        SystemSetting::set('discount_rate', (float) $this->discountRate, $this->settingsBranchId);
        SystemSetting::set('senior_discount_rate', (float) $this->seniorDiscountRate, $this->settingsBranchId);
        SystemSetting::set('pos_business_name', $this->posBusinessName, $this->settingsBranchId);
        SystemSetting::set('pos_order_types', $this->posOrderTypes, $this->settingsBranchId);
        SystemSetting::set('pos_payment_methods', $this->posPaymentMethods, $this->settingsBranchId);
        SystemSetting::set('gcash_account_name', $this->gcashAccountName, $this->settingsBranchId);
        SystemSetting::set('gcash_account_number', $this->gcashAccountNumber ? '+63' . trim($this->gcashAccountNumber) : '', $this->settingsBranchId);
        SystemSetting::set('gcash_qr_image', $this->existingGcashQrImage, $this->settingsBranchId);
    }

    private function saveSystemTab(): void
    {
        $user = auth()->user();
        $user->update([
            'hide_modules' => (bool) $this->hideOperationalModules,
            'branch_id'    => $this->opBranchId ? (int) $this->opBranchId : null,
        ]);

        if ($user->role_id === 1) {
            \App\Services\BranchContext::setActiveBranch($this->opBranchId);
        }

        $branch = $this->opBranchId ? \App\Models\Branch::find($this->opBranchId) : null;
        $hasBranch = !empty($this->opBranchId);
        $isMain = $branch ? (bool) $branch->is_main : false;
        $isSub = $hasBranch && !$isMain;
        $effectiveHide = (bool) $this->hideOperationalModules || ($user->role_id === 1 && !$hasBranch);
        $branchName = $branch?->branch_name ?? ($user->role_id === 1 ? 'General Headquarters' : 'No Branch Assigned');

        $this->isSavingSystemState = true;
        try {
            $this->dispatch('accessibility-config-updated', 
                hide_modules: $effectiveHide,
                has_branch: $hasBranch,
                is_main: $isMain,
                is_sub: $isSub,
                branch_id: $this->opBranchId ? (int) $this->opBranchId : null,
                branch_name: $branchName,
                branchName: $branchName
            );

            $this->dispatch('branchContextUpdated', 
                branchName: $branchName,
                branch_name: $branchName,
                branchId: $this->opBranchId ? (int) $this->opBranchId : null,
                isMain: $isMain,
                hasBranch: $hasBranch
            );
        } finally {
            $this->isSavingSystemState = false;
        }
    }

    private function saveReviewsTab(): void
    {
        SystemSetting::set('review_form_title', $this->reviewFormTitle, $this->settingsBranchId);
        SystemSetting::set('review_form_subtitle', $this->reviewFormSubtitle, $this->settingsBranchId);
        SystemSetting::set('review_questions', json_encode($this->reviewQuestions), $this->settingsBranchId);
    }

        public function regenerateQrCode()
    {
        $this->sampleQrCode = QrCodeHelper::generateReviewQrCode('SAMPLE-' . uniqid());
    }

    public function incrementReceiptCopies(): void
    {
        $this->receiptCopies = min(3, ((int) $this->receiptCopies) + 1);
    }

    public function decrementReceiptCopies(): void
    {
        $this->receiptCopies = max(1, ((int) $this->receiptCopies) - 1);
    }


    private function updateHeader()
    {
        $this->dispatch('setHeader', 
            icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            title: 'System Settings',
            breadcrumbs: [
                ['label' => 'Configuration', 'url' => '#'],
                ['label' => 'System Settings', 'url' => route('settings.index')],
                ['label' => ucwords(str_replace('_', ' ', $this->tab)), 'url' => '#'],
            ]
        );
    }

    public function render()
    {
        return view('livewire.system-settings', [
            'logs' => $this->tab === 'logs' ? $this->getRealLogs() : $this->emptyLogsPaginator(),
        ])->layout('layouts.app');
    }

    private function emptyLogsPaginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            collect(),
            0,
            $this->perPage,
            $this->getPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );
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
                'user' => $o->user ? $o->user->first_name . ' ' . $o->user->last_name : 'System',
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
                'user' => $m->user ? $m->user->first_name . ' ' . $m->user->last_name : 'System',
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
                'target' => $u->first_name . ' ' . $u->last_name,
                'time' => $u->created_at->diffForHumans(),
                'timestamp' => $u->created_at,
                'status' => 'warning'
            ]);

        // Combine and Sort by timestamp
        $allLogs = $orderLogs->concat($stockLogs)->concat($userLogs)
            ->sortByDesc('timestamp')
            ->values();

        $page = $this->getPage();
        
        return new LengthAwarePaginator(
            $allLogs->forPage($page, $this->perPage),
            $allLogs->count(),
            $this->perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
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
    
    private function savePrinterTab(): void
    {
        $printerConfig = [
            'enabled' => (bool) $this->printerEnabled,
            'type' => $this->printerType === 'wired' ? 'usb' : 'bluetooth',
            'name' => $this->printerName,
            'auto_cut' => (bool) $this->printerAutoCut,
        ];
        SystemSetting::set('thermal_printer_config', json_encode($printerConfig), $this->settingsBranchId);
    }

    public function refreshAvailablePrinters(): void
    {
        $this->availablePrinters = \App\Services\ThermalPrinterService::getWindowsPrinters();

        if (empty($this->printerName) && !empty($this->availablePrinters)) {
            $this->printerName = $this->availablePrinters[0];
        }

        $this->dispatch('notify', type: 'info', message: empty($this->availablePrinters)
            ? 'No local printers were detected on this Windows machine.'
            : 'Available printers refreshed.'
        );
    }

        public function testPrinterConnection(): void
    {
        $printerConfig = [
            'enabled' => (bool) $this->printerEnabled,
            'type' => $this->printerType === 'wired' ? 'usb' : 'bluetooth',
            'name' => $this->printerName,
            'auto_cut' => (bool) $this->printerAutoCut,
        ];

        $result = \App\Services\ThermalPrinterService::testPrinter($printerConfig);

        if ($result['success']) {
            $this->dispatch('notify', type: 'success', message: 'Printer test successful! Check your printer.');
        } else {
            $this->dispatch('notify', type: 'error', message: 'Printer test failed: ' . $result['message']);
        }
    }
}
