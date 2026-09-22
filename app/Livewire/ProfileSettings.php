<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use App\Traits\HandlesValidations;
use App\Helpers\ValidationHelper;

class ProfileSettings extends Component
{
    use HandlesValidations;

    public $firstName = '';
    public $middleName = '';
    public $lastName = '';
    public $email = '';
    public $phone = '';
    
    public $emailPassword = '';
    public $currentPassword = '';
    public $password = '';
    public $passwordConfirmation = '';
    
    public $sessionsPassword = '';

    public $addr_region   = '';
    public $addr_province = '';
    public $addr_city     = '';
    public $addr_barangay = '';
    public $addr_street   = '';
    public $addr_lat      = null;
    public $addr_lng      = null;

    public $selectedAvatar = '';
    
    public $tab = 'profile';

    // Avatar collection definition
    public static function avatarCollection(): array
    {
        return [
            'foods' => [
                ['id' => 'food-takoyaki',    'emoji' => '🐙', 'label' => 'Takoyaki',   'style' => 'background: linear-gradient(135deg, #fb923c, #ef4444)'],
                ['id' => 'food-ramen',       'emoji' => '🍜', 'label' => 'Ramen',       'style' => 'background: linear-gradient(135deg, #facc15, #f97316)'],
                ['id' => 'food-sushi',       'emoji' => '🍣', 'label' => 'Sushi',       'style' => 'background: linear-gradient(135deg, #f472b6, #f43f5e)'],
                ['id' => 'food-onigiri',     'emoji' => '🍙', 'label' => 'Onigiri',     'style' => 'background: linear-gradient(135deg, #94a3b8, #475569)'],
                ['id' => 'food-bento',       'emoji' => '🍱', 'label' => 'Bento',       'style' => 'background: linear-gradient(135deg, #34d399, #0d9488)'],
                ['id' => 'food-dumpling',    'emoji' => '🥟', 'label' => 'Dumpling',    'style' => 'background: linear-gradient(135deg, #fbbf24, #ca8a04)'],
                ['id' => 'food-donut',       'emoji' => '🍩', 'label' => 'Donut',       'style' => 'background: linear-gradient(135deg, #ec4899, #c026d3)'],
                ['id' => 'food-pizza',       'emoji' => '🍕', 'label' => 'Pizza',       'style' => 'background: linear-gradient(135deg, #f87171, #ea580c)'],
                ['id' => 'food-burger',      'emoji' => '🍔', 'label' => 'Burger',      'style' => 'background: linear-gradient(135deg, #eab308, #d97706)'],
                ['id' => 'food-icecream',    'emoji' => '🍦', 'label' => 'Ice Cream',   'style' => 'background: linear-gradient(135deg, #7dd3fc, #3b82f6)'],
            ],
            'animals' => [
                ['id' => 'animal-fox',       'emoji' => '🦊', 'label' => 'Fox',         'style' => 'background: linear-gradient(135deg, #f97316, #dc2626)'],
                ['id' => 'animal-panda',     'emoji' => '🐼', 'label' => 'Panda',       'style' => 'background: linear-gradient(135deg, #475569, #1e293b)'],
                ['id' => 'animal-lion',      'emoji' => '🦁', 'label' => 'Lion',        'style' => 'background: linear-gradient(135deg, #eab308, #d97706)'],
                ['id' => 'animal-tiger',     'emoji' => '🐯', 'label' => 'Tiger',       'style' => 'background: linear-gradient(135deg, #fb923c, #eab308)'],
                ['id' => 'animal-rabbit',    'emoji' => '🐰', 'label' => 'Rabbit',      'style' => 'background: linear-gradient(135deg, #f9a8d4, #fb7185)'],
                ['id' => 'animal-frog',      'emoji' => '🐸', 'label' => 'Frog',        'style' => 'background: linear-gradient(135deg, #10b981, #16a34a)'],
                ['id' => 'animal-cat',       'emoji' => '🐱', 'label' => 'Cat',         'style' => 'background: linear-gradient(135deg, #a78bfa, #9333ea)'],
                ['id' => 'animal-dog',       'emoji' => '🐶', 'label' => 'Dog',         'style' => 'background: linear-gradient(135deg, #fbbf24, #92400e)'],
                ['id' => 'animal-penguin',   'emoji' => '🐧', 'label' => 'Penguin',     'style' => 'background: linear-gradient(135deg, #3b82f6, #4338ca)'],
                ['id' => 'animal-bear',      'emoji' => '🐻', 'label' => 'Bear',        'style' => 'background: linear-gradient(135deg, #78716c, #b45309)'],
            ],
        ];
    }

    public function mount()
    {
        $user = auth()->user();
        $this->firstName = $user->first_name;
        $this->middleName = $user->middle_name ?? '';
        $this->lastName = $user->last_name;
        $this->email = $user->email;
        $this->phone = $user->phone ? str_replace('+63', '', $user->phone) : '';
        $this->selectedAvatar = $user->avatar ?? '';

        $addr = json_decode((string) $user->address, true);
        if (is_array($addr)) {
            $this->addr_region   = $addr['region']   ?? '';
            $this->addr_province = $addr['province'] ?? '';
            $this->addr_city     = $addr['city']     ?? '';
            $this->addr_barangay = $addr['barangay'] ?? '';
            $this->addr_street   = $addr['street']   ?? '';
            $this->addr_lat      = $user->latitude  ?? $addr['lat'] ?? null;
            $this->addr_lng      = $user->longitude ?? $addr['lng'] ?? null;
        } elseif (! empty($user->address)) {
            $this->addr_street = (string) $user->address; // older plain-text address
            $this->addr_lat    = $user->latitude;
            $this->addr_lng    = $user->longitude;
        }

        $this->dispatch('setHeader',
            icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            title: 'Profile Settings',
            breadcrumbs: [
                ['label' => 'Settings', 'url' => '#'],
                ['label' => 'Profile', 'url' => route('profile.edit')],
            ]
        );
    }

    // ── Real-time validation hooks ───────────────────────────────
    public function updatedFirstName() { 
        $this->firstName = $this->titleCaseName($this->firstName);
        $this->validateFieldLive('firstName', ValidationHelper::rulesName(), ValidationHelper::nameMessages()); 
    }
    public function updatedMiddleName() { 
        $this->middleName = $this->titleCaseName($this->middleName);
        $this->validateFieldLive('middleName', ValidationHelper::rulesOptionalName(), ValidationHelper::nameMessages()); 
    }
    public function updatedLastName() { 
        $this->lastName = $this->titleCaseName($this->lastName);
        $this->validateFieldLive('lastName', ValidationHelper::rulesName(), ValidationHelper::nameMessages()); 
    }
    
    public function updatedEmail()
    {
        // The live check skips the DNS lookup; the full check still runs on save
        $liveEmailRules = array_map(fn ($r) => $r === 'email:rfc,dns' ? 'email:rfc' : $r, ValidationHelper::rulesEmail());
        $rules = array_merge($liveEmailRules, [
            Rule::unique('users', 'email')->ignore(auth()->id())
        ]);
        $this->validateFieldLive('email', $rules, ValidationHelper::commonMessages());
    }

    public function updatedPhone()
    {
        $this->validateFieldLive('phone', ['nullable', 'string', 'regex:' . ValidationHelper::REGEX_PH_MOBILE], ['phone.regex' => 'Enter a valid PH mobile number.']);
    }

    /**
     * Capitalizes the first letter after a space, hyphen or apostrophe (multibyte-safe).
     * "mary-jane" becomes "Mary-Jane", "o'brien" becomes "O'Brien", "ñoño" becomes "Ñoño".
     * Existing capitals are kept. It does NOT trim, so it is safe to call while the user is typing.
     */
    private function titleCaseName(?string $value): string
    {
        return preg_replace_callback(
            '/(^|[\s\-\'])(\p{Ll})/u',
            fn ($m) => $m[1] . mb_strtoupper($m[2], 'UTF-8'),
            (string) $value
        ) ?? (string) $value;
    }

    /**
     * Checks the current password, limited to 5 tries per 5 minutes.
     * Adds an error to $field and returns false on failure.
     */
    private function confirmCurrentPassword(string $field, ?string $password): bool
    {
        if ($password === null || $password === '') {
            $this->addError($field, 'Please enter your current password.');
            return false;
        }

        $key = 'profile-password:' . auth()->id();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError($field, 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.');
            return false;
        }

        if (! Hash::check($password, auth()->user()->password)) {
            RateLimiter::hit($key, 300);
            $this->addError($field, 'The provided password does not match your current password.');
            return false;
        }

        RateLimiter::clear($key);
        return true;
    }

    /**
     * Lists this user's active sessions (needs the "database" session
     * driver), newest activity first, flagging which row is this browser.
     */
    public function getActiveSessionsProperty(): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        $rows = \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
            ->where('user_id', auth()->id())
            ->orderByDesc('last_activity')
            ->get();

        return $rows->map(function ($row) {
            $agent = $this->parseUserAgent($row->user_agent ?? '');

            return [
                'id'            => $row->id,
                'is_current'    => $row->id === session()->getId(),
                'ip_address'    => $row->ip_address,
                'device'        => $agent['device'],
                'browser'       => $agent['browser'],
                'platform'      => $agent['platform'],
                'last_activity' => \Carbon\Carbon::createFromTimestamp($row->last_activity)->timezone('Asia/Manila'),
            ];
        })->toArray();
    }

    /**
     * Small dependency-free user-agent breakdown, good enough to label a
     * row "Chrome on Windows" / "Safari on iPhone". Swap for a package
     * like jenssegers/agent if you need more accurate detection.
     */
    private function parseUserAgent(?string $ua): array
    {
        $ua = (string) $ua;

        $platform = match (true) {
            (bool) preg_match('/windows/i', $ua)          => 'Windows',
            (bool) preg_match('/iphone/i', $ua)           => 'iPhone',
            (bool) preg_match('/ipad/i', $ua)              => 'iPad',
            (bool) preg_match('/macintosh|mac os/i', $ua) => 'Mac',
            (bool) preg_match('/android/i', $ua)          => 'Android',
            (bool) preg_match('/linux/i', $ua)            => 'Linux',
            default                                        => 'Unknown device',
        };

        $browser = match (true) {
            (bool) preg_match('/edg\//i', $ua)   => 'Edge',
            (bool) preg_match('/chrome/i', $ua)  => 'Chrome',
            (bool) preg_match('/firefox/i', $ua) => 'Firefox',
            (bool) preg_match('/safari/i', $ua)  => 'Safari',
            default                               => 'Unknown browser',
        };

        $device = in_array($platform, ['iPhone', 'iPad', 'Android']) ? 'Mobile' : 'Desktop';

        return compact('device', 'browser', 'platform');
    }

    /**
     * Signs out one specific session (picked from the device list).
     */
    public function logoutSession(string $sessionId)
    {
        if (! $this->confirmCurrentPassword('sessionsPassword', $this->sessionsPassword)) {
            return;
        }

        if ($sessionId === session()->getId()) {
            $this->addError('sessionsPassword', "You can't sign out your current device from here.");
            return;
        }

        if (config('session.driver') === 'database') {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', auth()->id())
                ->where('id', $sessionId)
                ->delete();
        }

        $this->reset('sessionsPassword');
        $this->dispatch('notify', type: 'success', message: 'Device signed out.');
    }

    /**
     * Signs the account out everywhere except this browser.
     */
    public function signOutOtherDevices()
    {
        if (! $this->confirmCurrentPassword('sessionsPassword', $this->sessionsPassword)) {
            return;
        }

        // Database session driver: delete this user's other sessions immediately
        if (config('session.driver') === 'database') {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', auth()->id())
                ->where('id', '!=', session()->getId())
                ->delete();
        }

        // Also rotates remember-me tokens, and with AuthenticateSession ends other sessions on any driver
        \Illuminate\Support\Facades\Auth::logoutOtherDevices($this->sessionsPassword);

        $this->reset('sessionsPassword');
        $this->dispatch('sessions-signed-out');
        $this->dispatch('notify', type: 'success', message: 'Signed out of all other devices.');
    }

    public function selectAvatar(string $avatarId)
    {
        if ($avatarId === '') {
            $this->removeAvatar();
            return;
        }
        // Flatten all avatars and validate ID exists
        $all = collect(self::avatarCollection())->flatten(1);
        $valid = $all->pluck('id')->contains($avatarId);
        
        if ($valid) {
            $this->selectedAvatar = $avatarId;
            $user = auth()->user();
            $user->avatar = $avatarId;
            $user->save();
            
            $avatarData = $all->firstWhere('id', $avatarId);
            
            $this->dispatch('notify', type: 'success', message: 'Avatar updated!');
            // Tell Topbar and other components to refresh without a page reload
            $this->dispatch('avatar-updated', emoji: $avatarData['emoji'], style: $avatarData['style']);
        }
    }

    public function removeAvatar()
    {
        $this->selectedAvatar = '';
        $user = auth()->user();
        $user->avatar = null;
        $user->save();
        $this->dispatch('notify', type: 'success', message: 'Avatar removed.');
        $this->dispatch('avatar-updated', emoji: null, style: null);
    }

    public function updateProfile()
    {
        $this->firstName = $this->titleCaseName($this->normalizeString($this->firstName));
        $this->middleName = $this->titleCaseName($this->normalizeString($this->middleName));
        $this->lastName = $this->titleCaseName($this->normalizeString($this->lastName));
        $this->email = trim(strtolower($this->email));
        $this->phone = trim($this->phone);
        $this->addr_street = $this->normalizeString($this->addr_street);

        $rules = [
            'firstName'     => ValidationHelper::rulesName(),
            'middleName'    => ValidationHelper::rulesOptionalName(),
            'lastName'      => ValidationHelper::rulesName(),
            'email'         => array_merge(ValidationHelper::rulesEmail(), [Rule::unique('users', 'email')->ignore(auth()->id())]),
            'phone'         => ['nullable', 'string', 'regex:' . ValidationHelper::REGEX_PH_MOBILE],
            'addr_region'   => ['nullable', 'string', 'max:150'],
            'addr_province' => ['nullable', 'string', 'max:150'],
            'addr_city'     => ['nullable', 'string', 'max:150'],
            'addr_barangay' => ['nullable', 'string', 'max:150'],
            'addr_street'   => ['nullable', 'string', 'max:255', 'regex:~^[\pL\pN\s\-\.\,\#\/\'\(\)]+$~u'],
        ];

        // Once the user touches any address field, region/city/barangay become required
        $addressStarted = $this->addr_region !== '' || $this->addr_province !== '' || $this->addr_city !== ''
            || $this->addr_barangay !== '' || $this->addr_street !== '';

        if ($addressStarted) {
            $rules['addr_region']   = ['required', 'string', 'max:150'];
            $rules['addr_city']     = ['required', 'string', 'max:150'];
            $rules['addr_barangay'] = ['required', 'string', 'max:150'];
        }

        $this->validate($rules, array_merge(ValidationHelper::commonMessages(), ValidationHelper::nameMessages(), [
            'phone.regex'             => 'Enter a valid PH mobile number (e.g. 9123456789).',
            'addr_region.required'    => 'Please select a region.',
            'addr_city.required'      => 'Please select a city or municipality.',
            'addr_barangay.required'  => 'Please select a barangay.',
            'addr_street.regex'       => 'Street contains invalid characters.',
        ]));

        $user = auth()->user();
        if ($user->email !== $this->email && ! $this->confirmCurrentPassword('emailPassword', $this->emailPassword)) {
            return;
        }

        $user->first_name = $this->firstName;
        $user->middle_name = $this->middleName ?: null;
        $user->last_name = $this->lastName;

        if ($user->email !== $this->email) {
            $user->email = $this->email;
            $user->email_verified_at = null;
        }

        $user->phone = $this->phone ? '+63' . $this->phone : null;

        // ── Address (same JSON shape UserManagement uses) ─────────────
        if (! $addressStarted) {
            $user->address   = null;
            $user->latitude  = null;
            $user->longitude = null;
        } else {
            $old = json_decode((string) $user->address, true);
            $old = is_array($old) ? $old : [];

            $sameArea = ($old['region'] ?? '')   === $this->addr_region
                     && ($old['province'] ?? '') === $this->addr_province
                     && ($old['city'] ?? '')     === $this->addr_city
                     && ($old['barangay'] ?? '') === $this->addr_barangay;

            $lat = $this->addr_lat;
            $lng = $this->addr_lng;
            $geocodeOk = is_numeric($lat) && is_numeric($lng) && abs((float) $lat) <= 90 && abs((float) $lng) <= 180;

            if ($sameArea && $user->latitude !== null && $user->longitude !== null && ! $geocodeOk) {
                $lat = $user->latitude;
                $lng = $user->longitude;
            } elseif ($geocodeOk) {
                $lat = round((float) $lat, 7);
                $lng = round((float) $lng, 7);
            } else {
                $lat = null;
                $lng = null;
            }

            $this->addr_lat = $lat;
            $this->addr_lng = $lng;

            $user->address = json_encode([
                'region'    => $this->addr_region,
                'province'  => $this->addr_province,
                'city'      => $this->addr_city,
                'barangay'  => $this->addr_barangay,
                'street'    => $this->addr_street,
                'lat'       => $lat,
                'lng'       => $lng,
                'formatted' => implode(', ', array_filter([
                    $this->addr_street, $this->addr_barangay, $this->addr_city, $this->addr_province, $this->addr_region,
                ])),
            ]);
            $user->latitude  = $lat;
            $user->longitude = $lng;
        }

        $user->save();

        $this->reset('emailPassword');
        $this->dispatch('notify', type: 'success', message: 'Profile information updated successfully.');
    }

    public function updatePassword()
    {
        $this->validate([
            'currentPassword'      => ['required'],
            'password'             => ['required', 'string', 'min:8', 'max:128', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', 'different:currentPassword'],
            'passwordConfirmation' => ['required', 'same:password'],
        ], [
            'currentPassword.required'      => 'Please enter your current password.',
            'password.required'             => 'Please enter a new password.',
            'password.min'                  => 'Password must be at least 8 characters.',
            'password.regex'                => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
            'password.different'            => 'Your new password must be different from the current one.',
            'passwordConfirmation.required' => 'Please confirm your new password.',
            'passwordConfirmation.same'     => 'Password confirmation does not match.',
        ]);

        if (! $this->confirmCurrentPassword('currentPassword', $this->currentPassword)) {
            return;
        }

        $user = auth()->user();
        $user->password = Hash::make($this->password);
        $user->save();

        // Ends the user's sessions on other devices (needs AuthenticateSession middleware)
        Auth::logoutOtherDevices($this->password);

        $this->reset(['currentPassword', 'password', 'passwordConfirmation']);
        $this->resetValidation();

        $this->dispatch('password-updated');
        $this->dispatch('notify', type: 'success', message: 'Password updated successfully.');
    }

    public function getAvatarDisplayProperty(): ?array
    {
        if (!$this->selectedAvatar) return null;
        $all = collect(self::avatarCollection())->flatten(1);
        return $all->firstWhere('id', $this->selectedAvatar);
    }

    public function render()
    {
        auth()->user()->loadMissing(['role', 'branch']);
        
        return view('livewire.profile-settings', [
            'avatarCollection' => self::avatarCollection(),
            'avatarDisplay'    => $this->avatarDisplay,
        ])->layout('layouts.app');
    }
}
