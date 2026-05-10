<div class="relative min-h-[600px]" x-data="{ 
    tab: @entangle('tab'),
    strength: 0,
    avatarModal: false,
    activeCategory: 'foods',
    tabWidth: 0,
    tabLeft: 0,
    activeCategoryWidth: 0,
    activeCategoryLeft: 0,
    checkStrength(pw) {
        let s = 0;
        if (pw.length >= 8) s++;
        if (/[A-Z]/.test(pw)) s++;
        if (/[a-z]/.test(pw)) s++;
        if (/\d/.test(pw)) s++;
        if (/([^A-Za-z0-9])/.test(pw)) s++;
        this.strength = s;
    },
    recalculateTab() {
        this.$nextTick(() => {
            const activeTab = this.$el.querySelector(`[data-tab='${this.tab}']`);
            if (activeTab) {
                this.tabWidth = activeTab.offsetWidth;
                this.tabLeft = activeTab.offsetLeft;
            }
        });
    },
    recalculateActiveCategoryTab() {
        this.$nextTick(() => {
            const activeTab = this.$el.querySelector(`[data-tab='${this.activeCategory}']`);
            if (activeTab) {
                this.activeCategoryWidth = activeTab.offsetWidth;
                this.activeCategoryLeft = activeTab.offsetLeft;
            }
        });
    },
    init() {
        this.recalculateTab();
        this.$watch('tab', () => this.recalculateTab());
        this.$watch('activeCategory', () => this.recalculateActiveCategoryTab());
        this.$watch('avatarModal', (val) => {
            if (val) setTimeout(() => this.recalculateActiveCategoryTab(), 100);
        });
        window.addEventListener('resize', () => {
            this.recalculateTab();
            this.recalculateActiveCategoryTab();
        });
    }
}">

    <div class="mb-5 flex items-center justify-between">
        <div>
            <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Profile Settings</h2>
            <p class="text-[12px] text-gray-500 font-medium">Manage your personal information and security settings</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Left: Profile Card --}}
        <div class="col-span-1 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-[0_8px_30px_-12px_rgba(0,0,0,0.05)] p-8 text-center relative overflow-hidden group">
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-50 rounded-full blur-3xl opacity-50 group-hover:bg-indigo-100 transition-colors duration-500"></div>
                
                <div class="relative">
                    {{-- Avatar Display --}}
                    <div class="relative mx-auto mb-6 w-24 h-24">
                        @if($avatarDisplay)
                            <div class="w-24 h-24 rounded-2xl flex items-center justify-center text-5xl shadow-xl rotate-3 group-hover:rotate-0 transition-transform duration-500" style="{{ $avatarDisplay['style'] }}">
                                {{ $avatarDisplay['emoji'] }}
                            </div>
                        @else
                            <div class="w-24 h-24 bg-gradient-to-tr from-indigo-600 to-violet-500 text-white rounded-2xl flex items-center justify-center text-3xl font-black shadow-xl shadow-indigo-200 rotate-3 group-hover:rotate-0 transition-transform duration-500">
                                {{ strtoupper(substr(auth()->user()->first_name, 0, 1) . substr(auth()->user()->last_name, 0, 1)) }}
                            </div>
                        @endif
                        {{-- Edit avatar button --}}
                        <button @click="avatarModal = true" type="button"
                            class="absolute -bottom-2 -right-2 w-8 h-8 bg-white border border-slate-200 rounded-full flex items-center justify-center text-slate-500 hover:text-indigo-600 hover:border-indigo-300 shadow-sm transition-all hover:scale-110 focus:outline-none"
                            title="Change avatar">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </button>
                    </div>

                    <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</h3>
                    <p class="text-[13px] text-slate-500 font-medium mt-1">{{ auth()->user()->email }}</p>
                    
                    @if($avatarDisplay)
                        <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-widest">{{ $avatarDisplay['label'] }}</p>
                    @endif

                    <div class="mt-8 pt-8 border-t border-slate-100 text-left space-y-5">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Account ID</span>
                            <span class="text-[12px] font-black text-slate-700 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-100">#{{ auth()->user()->employee_id ?: 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">System Role</span>
                            <span class="text-[12px] font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100">{{ auth()->user()->role->name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Work Location</span>
                            <span class="text-[12px] font-bold text-slate-700">{{ auth()->user()->branch->branch_name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Status</span>
                            @if(auth()->user()->is_active)
                                <span class="flex items-center gap-1.5 text-[10px] font-black text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-100">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                    ACTIVE
                                </span>
                            @else
                                <span class="text-[10px] font-black text-red-600 bg-red-50 px-2.5 py-1 rounded-full border border-red-100">INACTIVE</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Tabs and Content --}}
        <div class="col-span-1 lg:col-span-3">
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm">
                
                <div class="px-6 pt-6 border-b border-slate-100">
                    <x-sliding-tabs model="tab" class="px-0">
                        <x-sliding-tab model="tab" value="profile">
                            <x-slot name="icon">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </x-slot>
                            Personal Information
                        </x-sliding-tab>
                        <x-sliding-tab model="tab" value="security">
                            <x-slot name="icon">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </x-slot>
                            Security & Password
                        </x-sliding-tab>
                    </x-sliding-tabs>
                </div>

                {{-- Personal Information Tab --}}
                <div x-cloak x-show="tab === 'profile'" class="p-6 animate-fadeIn">
                    <form wire:submit="updateProfile">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div>
                                <x-input-label for="f_first_name" value="First Name *" />
                                <x-text-input id="f_first_name" wire:model.live.debounce.500ms="firstName"
                                    type="text" class="mt-1 block w-full" placeholder="Juan"
                                    autocomplete="given-name" inputFilter="nameStrict" maxlength="100"
                                    @keydown="FormFilters.nameStrictKeydown($event)" @paste="FormFilters.nameStrictPaste($event)"
                                    :hasError="$errors->has('firstName')" />
                                <x-input-error :messages="$errors->get('firstName')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="f_middle_name" value="Middle Name" />
                                <x-text-input id="f_middle_name" wire:model.live.debounce.500ms="middleName"
                                    type="text" class="mt-1 block w-full" placeholder="Dela"
                                    autocomplete="additional-name" inputFilter="nameStrict" maxlength="100"
                                    @keydown="FormFilters.nameStrictKeydown($event)" @paste="FormFilters.nameStrictPaste($event)"
                                    :hasError="$errors->has('middleName')" />
                                <x-input-error :messages="$errors->get('middleName')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="f_last_name" value="Last Name *" />
                                <x-text-input id="f_last_name" wire:model.live.debounce.500ms="lastName" type="text"
                                    class="mt-1 block w-full" placeholder="Cruz" autocomplete="family-name" 
                                    inputFilter="nameStrict" maxlength="100"
                                    @keydown="FormFilters.nameStrictKeydown($event)" @paste="FormFilters.nameStrictPaste($event)"
                                    :hasError="$errors->has('lastName')" />
                                <x-input-error :messages="$errors->get('lastName')" class="mt-1" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
                            <div>
                                <x-input-label for="f_email" value="Email Address *" />
                                <x-text-input id="f_email" wire:model.live.debounce.500ms="email" type="email"
                                    class="mt-1 block w-full" placeholder="juan.cruz@gmail.com"
                                    autocomplete="email" inputFilter="email" maxlength="255"
                                    @keydown="FormFilters.emailKeydown($event)" @paste="FormFilters.emailPaste($event)"
                                    :hasError="$errors->has('email')" />
                                <x-input-error :messages="$errors->get('email')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="f_phone" value="Phone Number" />
                                <div class="flex items-center mt-1">
                                    <div class="flex-shrink-0 inline-flex items-center px-3 h-10 rounded-l-lg border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-[13px] font-bold">
                                        +63
                                    </div>
                                    <x-text-input id="f_phone" wire:model.live.debounce.500ms="phone" type="text"
                                        class="block w-full rounded-l-none" placeholder="912 345 6789" autocomplete="tel"
                                        inputFilter="number" maxlength="10"
                                        @keydown="FormFilters.numberKeydown($event)" @paste="FormFilters.numberPaste($event)"
                                        :hasError="$errors->has('phone')" />
                                </div>
                                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                            </div>
                        </div>
                        
                        <div class="mt-8 flex justify-end">
                            <x-primary-button type="submit">
                                Save Profile Changes
                            </x-primary-button>
                        </div>
                    </form>
                </div>

                {{-- Security Tab --}}
                <div x-cloak x-show="tab === 'security'" class="p-6 animate-fadeIn">
                    <form wire:submit="updatePassword" class="max-w-xl">
                        <div class="mb-6">
                            <h3 class="text-sm font-bold text-gray-900">Update Password</h3>
                            <p class="text-xs text-gray-500 mt-1">Ensure your account is using a long, random password to stay secure.</p>
                        </div>

                        <div class="space-y-5" x-data="{ showPasswords: false }">
                            
                            {{-- Current Password --}}
                            <div>
                                <x-input-label for="current_password" value="Current Password" />
                                <div class="relative mt-1">
                                    <x-text-input id="current_password" x-bind:type="showPasswords ? 'text' : 'password'" wire:model="currentPassword"
                                        class="block w-full" autocomplete="current-password" :hasError="$errors->has('currentPassword')" />
                                </div>
                                <x-input-error :messages="$errors->get('currentPassword')" class="mt-1" />
                            </div>

                            {{-- New Password --}}
                            <div>
                                <x-input-label for="new_password" value="New Password" />
                                <div class="relative mt-1">
                                    <x-text-input id="new_password" x-bind:type="showPasswords ? 'text' : 'password'" wire:model="password"
                                        class="block w-full" autocomplete="new-password"
                                        x-on:input="checkStrength($event.target.value)"
                                        :hasError="$errors->has('password')" />
                                </div>
                                
                                {{-- Password Strength Meter --}}
                                <div class="mt-2 flex gap-1 h-1.5">
                                    <div class="flex-1 rounded-full transition-colors duration-300" :class="strength >= 1 ? (strength <= 2 ? 'bg-red-400' : (strength <= 3 ? 'bg-amber-400' : 'bg-emerald-400')) : 'bg-gray-100'"></div>
                                    <div class="flex-1 rounded-full transition-colors duration-300" :class="strength >= 2 ? (strength <= 2 ? 'bg-red-400' : (strength <= 3 ? 'bg-amber-400' : 'bg-emerald-400')) : 'bg-gray-100'"></div>
                                    <div class="flex-1 rounded-full transition-colors duration-300" :class="strength >= 3 ? (strength <= 3 ? 'bg-amber-400' : 'bg-emerald-400') : 'bg-gray-100'"></div>
                                    <div class="flex-1 rounded-full transition-colors duration-300" :class="strength >= 4 ? 'bg-emerald-400' : 'bg-gray-100'"></div>
                                    <div class="flex-1 rounded-full transition-colors duration-300" :class="strength >= 5 ? 'bg-emerald-500' : 'bg-gray-100'"></div>
                                </div>
                                <p class="mt-1.5 text-[10px] font-bold uppercase tracking-wider transition-colors"
                                   :class="strength <= 2 ? 'text-red-500' : (strength <= 3 ? 'text-amber-500' : 'text-emerald-600')"
                                   x-text="strength === 0 ? '' : (strength <= 2 ? 'Weak' : (strength <= 3 ? 'Fair' : 'Strong'))"></p>

                                <x-input-error :messages="$errors->get('password')" class="mt-1" />
                            </div>

                            {{-- Confirm Password --}}
                            <div>
                                <x-input-label for="confirm_password" value="Confirm Password" />
                                <div class="relative mt-1">
                                    <x-text-input id="confirm_password" x-bind:type="showPasswords ? 'text' : 'password'" wire:model="passwordConfirmation"
                                        class="block w-full" autocomplete="new-password" :hasError="$errors->has('passwordConfirmation')" />
                                </div>
                                <x-input-error :messages="$errors->get('passwordConfirmation')" class="mt-1" />
                            </div>

                            {{-- Show Password Checkbox --}}
                            <div class="block mt-6">
                                <label for="show_passwords" class="flex items-center gap-3 cursor-pointer select-none">
                                    <input id="show_passwords" type="checkbox" x-model="showPasswords" name="show_passwords"
                                        class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900 h-4 w-4">
                                    <div>
                                        <span class="block text-[13px] font-semibold text-gray-800">Show Passwords</span>
                                        <span class="block text-[12px] text-gray-500">Display password characters as plain text.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-start">
                            <x-primary-button type="submit">
                                Update Password
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════ AVATAR SELECTION MODAL ══════════════ --}}
    <div x-show="avatarModal" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[500] flex items-center justify-center p-4"
        @keydown.escape.window="avatarModal = false">

        {{-- Backdrop --}}
        <div class="absolute inset-0" style="background: rgba(15,23,42,0.6); backdrop-filter: blur(4px);" @click="avatarModal = false"></div>

        {{-- Modal Panel --}}
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-7 pt-7 pb-5">
                <div>
                    <h3 class="text-[17px] font-black text-slate-900">Choose Your Avatar</h3>
                    <p class="text-[12px] text-slate-500 mt-0.5">Pick a fun avatar to represent you across the system</p>
                </div>
                <button @click="avatarModal = false" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 transition-colors focus:outline-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Category Tabs --}}
            <div class="px-7 mb-6">
                <x-sliding-tabs model="activeCategory" class="px-0" ref="avatarTabList">
                    <x-sliding-tab model="activeCategory" value="foods">
                        <span class="flex items-center gap-2">🍜 Cartoon Foods</span>
                    </x-sliding-tab>
                    <x-sliding-tab model="activeCategory" value="animals">
                        <span class="flex items-center gap-2">🦊 Animals</span>
                    </x-sliding-tab>
                </x-sliding-tabs>
            </div>

            {{-- Avatar Grid --}}
            <div class="px-7 pb-7">
                {{-- Foods --}}
                <div x-show="activeCategory === 'foods'" x-cloak class="animate-fadeIn">
                    <div style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.75rem;">
                    @foreach($avatarCollection['foods'] as $av)
                        <button wire:click="selectAvatar('{{ $av['id'] }}')" @click="avatarModal = false" type="button"
                            class="group flex flex-col items-center gap-2 p-3 rounded-2xl transition-all focus:outline-none hover:scale-105
                                {{ $selectedAvatar === $av['id'] ? 'ring-2 ring-indigo-500 ring-offset-2 bg-indigo-50' : 'hover:bg-slate-50' }}">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl shadow-lg transition-shadow" style="{{ $av['style'] }}">
                                {{ $av['emoji'] }}
                            </div>
                            <span class="text-[10px] font-bold text-slate-600 text-center leading-tight">{{ $av['label'] }}</span>
                            @if($selectedAvatar === $av['id'])
                                <div class="w-4 h-4 bg-indigo-500 rounded-full flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            @endif
                        </button>
                    @endforeach
                    </div>
                </div>
                {{-- Animals --}}
                <div x-show="activeCategory === 'animals'" x-cloak class="animate-fadeIn">
                    <div style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.75rem;">
                    @foreach($avatarCollection['animals'] as $av)
                        <button wire:click="selectAvatar('{{ $av['id'] }}')" @click="avatarModal = false" type="button"
                            class="group flex flex-col items-center gap-2 p-3 rounded-2xl transition-all focus:outline-none hover:scale-105
                                {{ $selectedAvatar === $av['id'] ? 'ring-2 ring-indigo-500 ring-offset-2 bg-indigo-50' : 'hover:bg-slate-50' }}">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl shadow-lg transition-shadow" style="{{ $av['style'] }}">
                                {{ $av['emoji'] }}
                            </div>
                            <span class="text-[10px] font-bold text-slate-600 text-center leading-tight">{{ $av['label'] }}</span>
                            @if($selectedAvatar === $av['id'])
                                <div class="w-4 h-4 bg-indigo-500 rounded-full flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            @endif
                        </button>
                    @endforeach
                    </div>
                </div>

                {{-- Remove Avatar Option --}}
                @if($selectedAvatar)
                    <div class="mt-5 pt-5 border-t border-slate-100 flex justify-center">
                        <button wire:click="selectAvatar('')" @click="avatarModal = false" type="button"
                            class="text-[11px] font-bold text-slate-400 hover:text-red-500 transition-colors focus:outline-none underline underline-offset-2">
                            Remove avatar (use initials instead)
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
