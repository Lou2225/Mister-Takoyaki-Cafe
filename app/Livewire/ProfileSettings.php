<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
    
    public $currentPassword = '';
    public $password = '';
    public $passwordConfirmation = '';
    
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
    }

    // ── Real-time validation hooks ───────────────────────────────
    public function updatedFirstName() { $this->validateFieldLive('firstName', ValidationHelper::rulesName(), ValidationHelper::nameMessages()); }
    public function updatedMiddleName() { $this->validateFieldLive('middleName', ValidationHelper::rulesOptionalName(), ValidationHelper::nameMessages()); }
    public function updatedLastName() { $this->validateFieldLive('lastName', ValidationHelper::rulesName(), ValidationHelper::nameMessages()); }
    
    public function updatedEmail()
    {
        $rules = array_merge(ValidationHelper::rulesEmail(), [
            Rule::unique('users', 'email')->ignore(auth()->id())
        ]);
        $this->validateFieldLive('email', $rules, ValidationHelper::commonMessages());
    }

    public function updatedPhone()
    {
        $this->validateFieldLive('phone', ['nullable', 'string', 'regex:/^[0-9]{10}$/'], ['phone.regex' => 'Enter 10-digit mobile number.']);
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
            $this->dispatch('notify', type: 'success', message: 'Avatar updated!');
            // Tell Topbar and other components to refresh without a page reload
            $this->dispatch('refreshTopbar');
        }
    }

    public function removeAvatar()
    {
        $this->selectedAvatar = '';
        $user = auth()->user();
        $user->avatar = null;
        $user->save();
        $this->dispatch('notify', type: 'success', message: 'Avatar removed.');
        $this->dispatch('refreshTopbar');
    }

    public function updateProfile()
    {
        $this->firstName = $this->normalizeString($this->firstName);
        $this->middleName = $this->normalizeString($this->middleName);
        $this->lastName = $this->normalizeString($this->lastName);
        $this->email = trim(strtolower($this->email));
        $this->phone = trim($this->phone);

        $this->validate([
            'firstName'  => ValidationHelper::rulesName(),
            'middleName' => ValidationHelper::rulesOptionalName(),
            'lastName'   => ValidationHelper::rulesName(),
            'email'      => array_merge(ValidationHelper::rulesEmail(), [Rule::unique('users', 'email')->ignore(auth()->id())]),
            'phone'      => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
        ], array_merge(ValidationHelper::commonMessages(), ValidationHelper::nameMessages(), [
            'phone.regex' => 'Enter 10-digit mobile number (e.g. 9123456789).',
        ]));

        $user = auth()->user();
        $user->first_name = $this->firstName;
        $user->middle_name = $this->middleName ?: null;
        $user->last_name = $this->lastName;
        
        if ($user->email !== $this->email) {
            $user->email = $this->email;
            $user->email_verified_at = null; 
        }
        
        $user->phone = $this->phone ? '+63' . $this->phone : null;
        $user->save();

        $this->dispatch('notify', type: 'success', message: 'Profile information updated successfully.');
    }

    public function updatePassword()
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'password' => [
                'required', 'string', 'min:8', 'max:128',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                'same:passwordConfirmation',
            ],
        ], [
            'currentPassword.current_password' => 'The provided password does not match your current password.',
            'password.required' => 'Please enter a new password.',
            'password.min'      => 'Password must be at least 8 characters.',
            'password.regex'    => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
            'password.same'     => 'Password confirmation does not match.',
        ]);

        $user = auth()->user();
        $user->password = Hash::make($this->password);
        $user->save();

        $this->reset(['currentPassword', 'password', 'passwordConfirmation']);
        $this->resetValidation();
        
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
        $this->dispatch('setHeader', 
            icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            title: 'Profile Settings',
            breadcrumbs: [
                ['label' => 'Settings', 'url' => '#'],
                ['label' => 'Profile', 'url' => route('profile.edit')],
            ]
        );

        return view('livewire.profile-settings', [
            'avatarCollection' => self::avatarCollection(),
            'avatarDisplay'    => $this->avatarDisplay,
        ])->layout('layouts.app');
    }
}
