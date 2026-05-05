<x-auth-layout>
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

            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-2">LOGIN</h1>
                <p class="text-[14px] text-gray-500 font-medium">Enter your credentials to access your administrative workspace.</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Email Address -->
                <div>
                    <x-input-label for="email" :value="__('Email Address')" class="text-[12px] font-bold text-gray-700 uppercase tracking-wider mb-2" />
                    <x-text-input id="email" class="block w-full px-4 py-3 bg-gray-50 border-gray-100 rounded-xl focus:bg-white focus:border-indigo-600 transition-all duration-200" 
                        type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@domain.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Password -->
                <div x-data="{ show: false }">
                    <div class="flex items-center justify-between mb-2">
                        <x-input-label for="password" :value="__('Password')" class="text-[12px] font-bold text-gray-700 uppercase tracking-wider" />
                        @if (Route::has('password.request'))
                            <button type="button" @click.prevent="view = 'forgot'" class="text-[12px] font-bold text-indigo-600 hover:text-indigo-700 transition-colors">
                                {{ __('Forgot Password?') }}
                            </button>
                        @endif
                    </div>

                    <div class="relative">
                        <x-text-input id="password" class="block w-full px-4 py-3 bg-gray-50 border-gray-100 rounded-xl focus:bg-white focus:border-indigo-600 transition-all duration-200 pr-12"
                                        x-bind:type="show ? 'text' : 'password'"
                                        name="password"
                                        required autocomplete="current-password" placeholder="••••••••" />
                        
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-4 flex items-center justify-center text-gray-400 hover:text-indigo-600 transition-colors">
                            {{-- Eye Icon --}}
                            <svg x-show="!show" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            {{-- Eye Slash Icon --}}
                            <svg x-show="show" x-cloak class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                        </button>
                    </div>

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <label for="remember_me" class="inline-flex items-center group cursor-pointer">
                        <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" name="remember">
                        <span class="ml-2 text-sm text-gray-500 group-hover:text-gray-700 transition-colors font-medium">{{ __('Keep me logged in') }}</span>
                    </label>
                </div>

                <div class="pt-2">
                    <x-primary-button class="w-full justify-center py-4 shadow-lg shadow-indigo-200 uppercase tracking-widest">
                        {{ __('Log In') }}
                    </x-primary-button>
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

    </div>
</x-auth-layout>
