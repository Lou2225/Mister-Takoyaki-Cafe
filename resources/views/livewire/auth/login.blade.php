<div x-data="{
        view: 'login',
        submitting: false,
        preloadingDashboard: false,
        init() {
            // If Livewire finishes and credentials failed (validation error), reset submitting
            const hookCommit = () => {
                if (window.Livewire && typeof window.Livewire.hook === 'function') {
                    window.Livewire.hook('commit', ({ succeed, fail }) => {
                        succeed(() => {
                            if (!this.preloadingDashboard) {
                                this.submitting = false;
                            }
                        });
                        fail(() => {
                            this.submitting = false;
                        });
                    });
                }
            };
            if (window.Livewire) {
                hookCommit();
            } else {
                document.addEventListener('livewire:initialized', hookCommit);
            }
        }
    }"
    @switchtologin.window="view = 'login'"
    @login-success.window="submitting = true; preloadingDashboard = true; window.mtcLoginCurtain ? window.mtcLoginCurtain.run($event.detail.url) : window.Livewire.navigate($event.detail.url)"
    @login-transition-failed.window="submitting = false; preloadingDashboard = false"
    class="relative w-full">

    <!-- ======================= -->
    <!--      LOGIN VIEW         -->
    <!-- ======================= -->
    <div x-show="view === 'login'"
         x-transition:enter="transition ease-out duration-300 delay-150"
         x-transition:enter-start="opacity-0 translate-x-4" 
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-150 absolute inset-x-0 top-0"
         x-transition:leave-start="opacity-100 translate-x-0" 
         x-transition:leave-end="opacity-0 -translate-x-4">
         
        <!-- Session Status -->
        <x-auth-session-status class="mb-6" :status="session('status')" />

        <div class="mb-6 sm:mb-8">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-2">LOGIN</h1>
            <p class="text-[13px] sm:text-[14px] text-slate-500 font-medium">Enter your credentials to access your administrative workspace.</p>
        </div>

<form wire:submit="login" @submit="submitting = true; window.mtcLoginCurtain && window.mtcLoginCurtain.unlockAudio()" class="space-y-5 sm:space-y-6">
            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email Address')" class="text-[12px] font-semibold text-slate-900 uppercase tracking-[0.24em] mb-2" />
                <x-text-input id="email" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-indigo-500 focus:ring-indigo-100 transition-all duration-200" 
                    type="email" name="email" wire:model="email" required autofocus autocomplete="username" placeholder="name@domain.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div x-data="{ show: false }">
                <div class="mb-2">
                    <x-input-label for="password" :value="__('Password')" class="text-[12px] font-semibold text-slate-900 uppercase tracking-[0.24em]" />
                </div>

                <div class="relative">
                    <x-text-input id="password" class="block w-full px-4 py-3 bg-gray-50 border-gray-100 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-indigo-100 transition-all duration-200 pr-12"
                                    x-bind:type="show ? 'text' : 'password'"
                                    name="password"
                                    wire:model="password"
                                    required autocomplete="current-password" placeholder="••••••••" />
                    
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-4 flex items-center justify-center text-gray-400 hover:text-black transition-colors">
                        {{-- Eye Icon --}}
                        <svg x-show="!show" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        {{-- Eye Slash Icon --}}
                        <svg x-show="show" x-cloak class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                    </button>
                </div>

                <div class="flex items-center justify-between mt-2">
                    <x-input-error :messages="$errors->get('password')" />
                    @if (Route::has('password.request'))
                        <button type="button" @click.prevent="view = 'forgot'" class="text-[12px] font-semibold text-indigo-600 hover:text-indigo-700 transition-colors ml-auto">
                            {{ __('Forgot your password?') }}
                        </button>
                    @endif
                </div>
            </div>

            <div class="pt-2">
                <button type="submit"
                    :disabled="submitting || preloadingDashboard"
                    class="auth-submit-btn">
                    
                    {{-- Exactly ONE Single Spinner: centered alongside text --}}
                    <span x-show="submitting || preloadingDashboard" x-cloak class="auth-btn-content">
                        <svg class="auth-btn-spinner" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Signing in...</span>
                    </span>

                    {{-- Default idle state --}}
                    <span x-show="!submitting && !preloadingDashboard" class="auth-btn-content">
                        {{ __('Log In') }}
                    </span>
                </button>
            </div>
        </form>
    </div>

    <!-- ======================= -->
    <!--  FORGOT PASSWORD VIEW   -->
    <!-- ======================= -->
    <div x-cloak x-show="view === 'forgot'" style="display: none;"
         x-transition:enter="transition ease-out duration-300 delay-150"
         x-transition:enter-start="opacity-0 translate-x-4" 
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-150 absolute inset-x-0 top-0"
         x-transition:leave-start="opacity-100 translate-x-0" 
         x-transition:leave-end="opacity-0 -translate-x-4">
         
        @livewire('auth.forgot-password')
    </div>

    <style>
        .auth-submit-btn {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: relative !important;
            width: 100% !important;
            padding: 1rem !important;
            background-color: #000000 !important;
            color: #ffffff !important;
            border-radius: 0.75rem !important;
            font-weight: 900 !important;
            font-size: 12px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.1em !important;
            min-height: 52px !important;
            transition: all 0.2s ease !important;
            border: none !important;
            cursor: pointer !important;
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.1) !important;
        }
        .auth-submit-btn:hover {
            background-color: #0f172a !important;
        }
        .auth-submit-btn:active {
            transform: scale(0.98) !important;
        }
        .auth-submit-btn:disabled {
            opacity: 0.6 !important;
            cursor: not-allowed !important;
        }
        .auth-btn-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            white-space: nowrap;
            line-height: 1;
        }
        .auth-btn-content[style*="display: none"] {
            display: none !important;
        }
        .auth-btn-spinner {
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
            flex-shrink: 0;
            color: #ffffff;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</div>