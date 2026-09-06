<div x-data="{ view: 'login' }" @switchtologin.window="view = 'login'" class="relative w-full">

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

                             <form wire:submit="login" class="space-y-5 sm:space-y-6"
              x-on:login-success.window="
                  window.dispatchEvent(new CustomEvent('auth-split'));
                  let dashboardUrl = $event.detail.url;
                  setTimeout(() => { Livewire.navigate(dashboardUrl); }, 1250);
              ">

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email Address')" class="text-[12px] font-semibold text-slate-900 uppercase tracking-[0.24em] mb-2" />
                <x-text-input id="email" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-indigo-500 focus:ring-indigo-100 transition-all duration-200"
                    type="email" wire:model="email" required autofocus autocomplete="username" placeholder="name@domain.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div x-data="{ show: false }">
                <div class="mb-2">
                    <x-input-label for="password" :value="__('Password')" class="text-[12px] font-semibold text-slate-900 uppercase tracking-[0.24em]" />
                </div>

                <div class="relative">
                    <x-text-input id="password" class="block w-full px-4 py-3 bg-gray-50 border-gray-100 rounded-xl focus:bg-white focus:border-indigo-600 transition-all duration-200 pr-12"
                                    x-bind:type="show ? 'text' : 'password'"
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
                        wire:loading.attr="disabled" wire:target="login"
                        @if($authenticated) disabled @endif
                        class="w-full inline-flex justify-center items-center px-4 py-4 bg-black hover:bg-slate-900 text-white rounded-xl font-black text-[12px] shadow-lg shadow-slate-900/10 uppercase tracking-widest transition-all duration-200 transform active:scale-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 disabled:opacity-60">
                    @if($authenticated)
                        {{-- Success state: rendered exclusively once auth succeeds, so this
                             never coexists in the DOM with the default "Log In" span below —
                             wire:loading.remove matching two elements at once was what caused
                             the brief two-row flash. --}}
                        <span wire:key="login-btn-success" class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" class="auth-success-circle"/>
                                <path d="M7 12.5l3 3 7-7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" fill="none" class="auth-success-check"/>
                            </svg>
                            Welcome back
                        </span>
                    @else
                        <span wire:key="login-btn-idle" wire:loading.remove wire:target="login">{{ __('Log In') }}</span>
                                                <span wire:key="login-btn-loading" wire:loading wire:target="login">
                            Signing in...
                        </span>
                    @endif
                </button>
                        </div>
        </form>

        <style>
            .auth-success-circle {
                stroke-dasharray: 63; stroke-dashoffset: 63;
                animation: authCircle 0.35s cubic-bezier(0.65, 0, 0.45, 1) forwards;
            }
            .auth-success-check {
                stroke-dasharray: 16; stroke-dashoffset: 16;
                animation: authCheck 0.25s ease-out 0.3s forwards;
            }
            @keyframes authCircle { to { stroke-dashoffset: 0; } }
            @keyframes authCheck { to { stroke-dashoffset: 0; } }
        </style>
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

</div>