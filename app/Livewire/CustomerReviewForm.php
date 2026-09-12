<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Branch;
use App\Models\Order;
use App\Models\CustomerReview;
use App\Models\SystemSetting;
use App\Traits\HandlesValidations;
use App\Helpers\ValidationHelper;
use Illuminate\Support\Facades\Cookie;

class CustomerReviewForm extends Component
{
    use HandlesValidations;

    public $branchId;
    public $branch;
    public $orderId;
    public $token;

    public $title = 'How was your experience?';
    public $subtitle = 'Thank you for your feedback!';
    public $questions = [];
    public $answers = [];

    public $customer_name = '';
    public $contact_number = '';

    // Device identification & Anti-spam
    public $device_id = '';
    public $device_fingerprint = '';
    public $honeypot = '';
    public $mountedAt = null;

    // State flags
    public $isSubmitted = false;
    public $alreadyReviewed = false;
    public $existingReviewDate = null;
    public $maxDevicesReached = false;
    public $isExpired = false;
    public $expiryDays = 7;
    public $orderNotFound = false;
    public $isCoolingDown = false;
    public $cooldownRemainingMinutes = 0;
    public $cooldownMinutes = 10;

    // In-memory order cache
    protected ?Order $cachedOrder = null;

    public function mount($branch = null, $token = null)
    {
        $this->mountedAt = time();

        // 1. Resolve device_id from cookie if available
        $cookieDeviceId = request()->cookie('mtc_device_id') ?? ($_COOKIE['mtc_device_id'] ?? null);
        if (!empty($cookieDeviceId)) {
            $this->device_id = substr(trim($cookieDeviceId), 0, 64);
            $this->queueDeviceCookie($this->device_id);
        }

        // 2. Resolve token from param, property, query parameter, or route parameter
        $resolvedToken = $token ?: $this->token ?: request()->query('token');
        if (!$resolvedToken && $branch && strlen($branch) >= 20 && !is_numeric($branch)) {
            $resolvedToken = $branch;
            $branch = null;
        }
        $this->token = $resolvedToken;

        // 3. If token is present, resolve order and enforce limits
        if ($this->token) {
            $order = $this->getOrder();

            if ($order) {
                $this->orderId = $order->id;
                $this->branch = $order->branch;
                $this->branchId = $order->branch_id;

                // Track scan count and last scanned time
                try {
                    $order->increment('review_scan_count');
                    $order->updateQuietly(['review_last_scanned_at' => now()]);
                } catch (\Throwable $e) {
                    // Suppress scan tracking failure to avoid blocking customer review
                }

                // Check receipt expiry window (default: 7 days, max 30)
                $this->expiryDays = max(1, min(30, (int) SystemSetting::get('review_expiry_days', 7, $this->branchId)));
                if ($this->expiryDays > 0 && $order->created_at && $order->created_at->addDays($this->expiryDays)->isPast()) {
                    $this->isExpired = true;
                }

                // Check if this device already reviewed this order (checks device_id, IP/subnet + User-Agent)
                $this->checkIfDeviceAlreadyReviewed();
                if (!$this->alreadyReviewed) {
                    $this->checkIfDeviceIsCoolingDown();
                }

                // Check if maximum devices cap per receipt has been reached
                $maxDevices = max(1, min(10, (int) SystemSetting::get('review_max_devices_per_receipt', 5, $this->branchId)));
                if ($maxDevices > 0 && $order->customerReviews->count() >= $maxDevices) {
                    $this->maxDevicesReached = true;
                }
            } else {
                $this->orderNotFound = true;
            }
        }

        // 4. Fallback branch resolution if not already resolved via order
        if (!$this->branch) {
            $branchParam = $branch ?: request()->query('branch');
            if ($branchParam) {
                $this->branch = Branch::where('id', $branchParam)->orWhere('branch_code', $branchParam)->first();
                if ($this->branch) {
                    $this->branchId = $this->branch->id;
                }
            }
        }

        // Check cooldown if device_id is present and not already marked as reviewed
        if (!empty($this->device_id) && !$this->alreadyReviewed && !$this->isCoolingDown) {
            $this->checkIfDeviceIsCoolingDown();
        }

        // 5. Load configuration from System Settings
        $this->title = SystemSetting::get('review_form_title', 'How was your experience?', $this->branchId);
        $this->subtitle = SystemSetting::get('review_form_subtitle', 'Thank you for your feedback!', $this->branchId);

        $questionsJson = SystemSetting::get('review_questions', '[]', $this->branchId);
        $this->questions = is_string($questionsJson) ? json_decode($questionsJson, true) : $questionsJson;

        // Initialize empty answers based on questions
        if (is_array($this->questions)) {
            foreach ($this->questions as $index => $q) {
                $this->answers[$index] = ($q['type'] ?? '') === 'rating' ? 0 : '';
            }
        } else {
            $this->questions = [];
        }
    }

    /**
     * Reliable order getter that survives Livewire rehydrations.
     */
    public function getOrder(): ?Order
    {
        if ($this->cachedOrder) {
            return $this->cachedOrder;
        }

        if ($this->orderId) {
            $this->cachedOrder = Order::with(['branch', 'customerReviews'])->find($this->orderId);
            return $this->cachedOrder;
        }

        if ($this->token) {
            $this->cachedOrder = Order::with(['branch', 'customerReviews'])->where('review_token', $this->token)->first();
            if ($this->cachedOrder) {
                $this->orderId = $this->cachedOrder->id;
            }
            return $this->cachedOrder;
        }

        return null;
    }

    /**
     * Called by Alpine.js on page load once client device UUID and fingerprint are resolved.
     */
    /**
     * Queue persistent first-party cookie in HTTP response headers (1-year duration).
     */
    protected function queueDeviceCookie(?string $deviceId): void
    {
        if (empty($deviceId)) {
            return;
        }
        try {
            Cookie::queue(Cookie::make(
                'mtc_device_id',
                $deviceId,
                525600, // 1 year in minutes
                '/',
                null,
                request()->secure(),
                false, // httpOnly = false so Alpine / JS can also read it
                false,
                'Lax'
            ));
        } catch (\Throwable $e) {
            // Suppress cookie queue failures
        }
    }

    /**
     * Extract network subnet prefix for dynamic carrier IP and local proxy tolerance.
     */
    protected function getIpSubnet(?string $ip): ?string
    {
        if (empty($ip)) {
            return null;
        }
        // IPv4: match /24 subnet (e.g., "112.198.170.")
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.';
            }
        }
        // IPv6: match /64 prefix (first 4 segments)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            if (count($parts) >= 4) {
                return implode(':', array_slice($parts, 0, 4)) . ':';
            }
        }
        return null;
    }

    /**
     * Flag device as already reviewed for this receipt and prioritize state.
     */
    protected function markAsAlreadyReviewed(CustomerReview $existing): void
    {
        $this->alreadyReviewed = true;
        $this->isCoolingDown = false;
        $this->existingReviewDate = $existing->created_at ? $existing->created_at->format('M d, Y h:i A') : null;
    }

    /**
     * Called by Alpine.js on page load once client device UUID and fingerprint are resolved.
     */
    public function setDeviceId(string $deviceId, string $fingerprint = '')
    {
        $cleanId = substr(trim($deviceId), 0, 64);
        if (!empty($cleanId)) {
            $this->device_id = $cleanId;
            $this->queueDeviceCookie($this->device_id);
        }

        $cleanFingerprint = substr(trim($fingerprint), 0, 64);
        if (!empty($cleanFingerprint)) {
            $this->device_fingerprint = $cleanFingerprint;
        }

        $this->checkIfDeviceAlreadyReviewed();
        if (!$this->alreadyReviewed) {
            $this->checkIfDeviceIsCoolingDown();
        }
    }

    public function updatedDeviceId()
    {
        if (!empty($this->device_id)) {
            $this->queueDeviceCookie($this->device_id);
        }
        $this->checkIfDeviceAlreadyReviewed();
        if (!$this->alreadyReviewed) {
            $this->checkIfDeviceIsCoolingDown();
        }
    }

    /**
     * Robust Multi-Signal Duplicate Check for the specific order.
     * Prevents re-submitting on the same receipt even if client storage was cleared or IP rotated.
     */
    public function checkIfDeviceAlreadyReviewed(): void
    {
        $order = $this->getOrder();
        if (!$order) {
            return;
        }

        // 1. Direct device_id match
        if (!empty($this->device_id)) {
            $existing = CustomerReview::where('order_id', $order->id)
                ->where('device_id', $this->device_id)
                ->first();

            if ($existing) {
                $this->markAsAlreadyReviewed($existing);
                return;
            }
        }

        // 2. High-fidelity hardware/canvas device_fingerprint match on this order
        if (!empty($this->device_fingerprint) && !in_array($this->device_fingerprint, ['default', 'default_fp'])) {
            $existing = CustomerReview::where('order_id', $order->id)
                ->where('device_fingerprint', $this->device_fingerprint)
                ->first();

            if ($existing) {
                // Synchronize device_id back if existing review had one
                if (!empty($existing->device_id) && $existing->device_id !== $this->device_id) {
                    $this->device_id = $existing->device_id;
                    $this->queueDeviceCookie($this->device_id);
                    $this->dispatch('device-id-synced', deviceId: $this->device_id);
                }
                $this->markAsAlreadyReviewed($existing);
                return;
            }
        }

        // 3. User-Agent + IP / IP Subnet Match on this order
        $clientIp = request()->ip();
        $userAgent = request()->userAgent();
        if (!empty($clientIp) && !empty($userAgent) && strlen($userAgent) > 15) {
            $ipSubnet = $this->getIpSubnet($clientIp);
            $existing = CustomerReview::where('order_id', $order->id)
                ->where('user_agent', substr($userAgent, 0, 500))
                ->where(function ($q) use ($clientIp, $ipSubnet) {
                    $q->where('ip_address', $clientIp);
                    if ($ipSubnet) {
                        $q->orWhere('ip_address', 'LIKE', $ipSubnet . '%');
                    }
                })
                ->first();

            if ($existing) {
                if (!empty($existing->device_id) && $existing->device_id !== $this->device_id) {
                    $this->device_id = $existing->device_id;
                    $this->queueDeviceCookie($this->device_id);
                    $this->dispatch('device-id-synced', deviceId: $this->device_id);
                }
                $this->markAsAlreadyReviewed($existing);
                return;
            }
        }

        // 4. Contact Number match on this order (if entered by customer)
        if (!empty($this->contact_number)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $this->contact_number);
            if (strlen($cleanPhone) >= 7) {
                $existing = CustomerReview::where('order_id', $order->id)
                    ->whereNotNull('contact_number')
                    ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(contact_number, ' ', ''), '-', ''), '(', ''), ')', '') LIKE ?", ["%{$cleanPhone}%"])
                    ->first();

                if ($existing) {
                    if (!empty($existing->device_id) && $existing->device_id !== $this->device_id) {
                        $this->device_id = $existing->device_id;
                        $this->queueDeviceCookie($this->device_id);
                        $this->dispatch('device-id-synced', deviceId: $this->device_id);
                    }
                    $this->markAsAlreadyReviewed($existing);
                    return;
                }
            }
        }
    }

    /**
     * Check if this device is within the anti-spam cooldown window across receipts.
     */
    public function checkIfDeviceIsCoolingDown(): bool
    {
        // If already reviewed this order, the permanent already-reviewed screen takes priority
        if ($this->alreadyReviewed) {
            $this->isCoolingDown = false;
            return false;
        }

        $cooldownMinutes = max(0, min(120, (int) SystemSetting::get('review_device_cooldown_minutes', 10, $this->branchId)));
        $this->cooldownMinutes = $cooldownMinutes;

        if ($cooldownMinutes <= 0) {
            $this->isCoolingDown = false;
            return false;
        }

        if (empty($this->device_id) && empty($this->device_fingerprint)) {
            return false;
        }

        $cutoff = now()->subMinutes($cooldownMinutes);
        $clientIp = request()->ip();
        $userAgent = request()->userAgent();
        $ipSubnet = $this->getIpSubnet($clientIp);

        $recentReview = CustomerReview::where('created_at', '>=', $cutoff)
            ->where(function ($q) use ($clientIp, $userAgent, $ipSubnet) {
                if (!empty($this->device_id)) {
                    $q->where('device_id', $this->device_id);
                }
                if (!empty($this->device_fingerprint) && !in_array($this->device_fingerprint, ['default', 'default_fp'])) {
                    $q->orWhere('device_fingerprint', $this->device_fingerprint);
                }
                if (!empty($clientIp) && !empty($userAgent) && strlen($userAgent) > 15) {
                    $q->orWhere(function ($sub) use ($clientIp, $userAgent, $ipSubnet) {
                        $sub->where('user_agent', substr($userAgent, 0, 500))
                            ->where(function ($ipQ) use ($clientIp, $ipSubnet) {
                                $ipQ->where('ip_address', $clientIp);
                                if ($ipSubnet) {
                                    $ipQ->orWhere('ip_address', 'LIKE', $ipSubnet . '%');
                                }
                            });
                    });
                }
            })
            ->latest('created_at')
            ->first();

        if ($recentReview && $recentReview->created_at) {
            $cooldownEnd = $recentReview->created_at->copy()->addMinutes($cooldownMinutes);
            if ($cooldownEnd->isFuture()) {
                $this->isCoolingDown = true;
                $secondsRemaining = now()->diffInSeconds($cooldownEnd);
                $this->cooldownRemainingMinutes = max(1, (int) ceil($secondsRemaining / 60));
                return true;
            }
        }

        $this->isCoolingDown = false;
        return false;
    }

    public function updatedCustomerName()
    {
        $this->validateFieldLive('customer_name', ['nullable', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME], ValidationHelper::commonMessages());
    }

    public function updatedContactNumber()
    {
        $this->validateFieldLive('contact_number', ['nullable', 'string', 'max:50', 'regex:' . ValidationHelper::REGEX_PHONE], ValidationHelper::commonMessages());
        $this->checkIfDeviceAlreadyReviewed();
    }

    public function updatedAnswers($value, $key)
    {
        $index = explode('.', $key)[1] ?? null;
        if ($index !== null && isset($this->questions[$index])) {
            $question = $this->questions[$index];
            $rules = [];
            if (($question['type'] ?? '') === 'text') {
                $rules = ['string', 'max:1000', 'regex:' . ValidationHelper::REGEX_NAME_BASIC];
            }
            if (!empty($rules)) {
                $this->validateFieldLive($key, $rules, ValidationHelper::commonMessages());
            }
        }
    }

    public function setRating($questionIndex, $value)
    {
        $this->answers[$questionIndex] = $value;
    }

    public function submit()
    {
        $this->resetErrorBag();

        // 1. Anti-Bot: Honeypot check
        if (!empty($this->honeypot)) {
            $this->isSubmitted = true;
            return;
        }

        // 2. Anti-Bot: Time-on-page check (minimum 2 seconds)
        if ($this->mountedAt && (time() - $this->mountedAt) < 2) {
            $this->addError('submission', 'Submission completed too quickly. Please take a moment to review.');
            return;
        }

        // 3. Receipt Expiry Check
        if ($this->isExpired) {
            $this->addError('submission', 'This receipt review link has expired.');
            return;
        }

        // 4. Ensure device_id is never empty and lock into persistent cookie
        if (empty($this->device_id)) {
            $this->device_id = request()->cookie('mtc_device_id') 
                ?? ($_COOKIE['mtc_device_id'] ?? null) 
                ?? 'dev_' . substr(hash('sha256', request()->ip() . '|' . (request()->userAgent() ?? '')), 0, 28);
        }
        $this->queueDeviceCookie($this->device_id);

        // 5. Order & Receipt-Level Limits
        $order = $this->getOrder();
        if ($order) {
            // Check if this device or contact number already reviewed this order
            $this->checkIfDeviceAlreadyReviewed();
            if ($this->alreadyReviewed) {
                return;
            }

            // Check if maximum devices cap per receipt has been reached
            $maxDevices = max(1, min(10, (int) SystemSetting::get('review_max_devices_per_receipt', 5, $this->branchId)));
            if ($maxDevices > 0) {
                $currentDeviceCount = CustomerReview::where('order_id', $order->id)->count();
                if ($currentDeviceCount >= $maxDevices) {
                    $this->maxDevicesReached = true;
                    return;
                }
            }
        }

        // 6. Anti-Spam: Device Cooldown across receipts (default 10 minutes, max 120)
        if ($this->checkIfDeviceIsCoolingDown()) {
            $this->addError('submission', "You have recently submitted a review. Please wait {$this->cooldownRemainingMinutes} minute(s) before submitting another.");
            return;
        }

        // 7. Form Field Validations
        $rules = [
            'customer_name' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:50',
        ];

        foreach ($this->questions as $index => $question) {
            if ($question['required'] ?? false) {
                if (($question['type'] ?? '') === 'rating') {
                    $rules["answers.$index"] = 'required|integer|min:1|max:5';
                } else {
                    $rules["answers.$index"] = 'required|string|max:1000';
                }
            } else {
                if (($question['type'] ?? '') === 'rating') {
                    $rules["answers.$index"] = 'nullable|integer|min:1|max:5';
                } else {
                    $rules["answers.$index"] = 'nullable|string|max:1000';
                }
            }
        }

        $this->validate($rules, ValidationHelper::commonMessages());

        // Transform answers to structured format
        $structuredAnswers = collect($this->questions)->map(function ($q, $idx) {
            return [
                'question' => $q['text'] ?? '',
                'type' => $q['type'] ?? 'text',
                'answer' => $this->answers[$idx] ?? null,
            ];
        })->toArray();

        // 8. Atomic Review Creation with Compound Unique Constraint Safety
        try {
            $review = CustomerReview::create([
                'branch_id'          => $this->branchId,
                'order_id'           => $order?->id,
                'device_id'          => substr(trim($this->device_id), 0, 64),
                'device_fingerprint' => $this->device_fingerprint ? substr(trim($this->device_fingerprint), 0, 64) : null,
                'answers'            => $structuredAnswers,
                'customer_name'      => $this->customer_name,
                'contact_number'     => $this->contact_number,
                'ip_address'         => request()->ip(),
                'user_agent'         => substr(request()->userAgent() ?? '', 0, 500),
            ]);

            $this->queueDeviceCookie($this->device_id);
            $this->dispatch('device-id-synced', deviceId: $this->device_id);
            $this->isSubmitted = true;
        } catch (\Illuminate\Database\QueryException $e) {
            // Catch duplicate key collision on (order_id, device_id)
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'unique_order_device_review')) {
                $this->alreadyReviewed = true;
                $this->isCoolingDown = false;
                return;
            }
            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.customer-review-form', [
            'order' => $this->getOrder(),
        ])->layout('layouts.mobile');
    }
}
