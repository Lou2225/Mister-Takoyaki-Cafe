<div>
<div class="w-full" x-data="{
    strength: 0,
    step: 1,
    countdown: 0,
    timer: null,
    expireCountdown: 600,
    expireTimer: null,
    otp: '',
    digits: ['','','','','',''],
    email: '',
    password: '',
    passwordConfirmation: '',
    
    checkStrength(pw) {
        let s = 0;
        if (pw.length >= 8) s++;
        if (/[A-Z]/.test(pw)) s++;
        if (/[a-z]/.test(pw)) s++;
        if (/\d/.test(pw)) s++;
        if (/([^A-Za-z0-9])/.test(pw)) s++;
        this.strength = s;
    },
    formatTime(seconds) {
        let m = Math.floor(seconds / 60);
        let s = seconds % 60;
        return m + ':' + (s < 10 ? '0' : '') + s;
    },
    startCountdown() {
        clearInterval(this.timer);
        this.timer = setInterval(() => {
            if (this.countdown > 0) this.countdown--;
            else clearInterval(this.timer);
        }, 1000);
    },
    startExpireTimer() {
        if (this.expireTimer) return; // Prevent multiple intervals
        this.expireCountdown = 600;
        this.expireTimer = setInterval(() => {
            if (this.expireCountdown > 0) this.expireCountdown--;
            else {
                clearInterval(this.expireTimer);
                this.expireTimer = null;
            }
        }, 1000);
    },
    focusNext(i, e) {
        if (e.target.value.length >= 1 && i < 5) {
            let inputs = e.target.parentElement.querySelectorAll('input');
            if (inputs[i + 1]) inputs[i + 1].focus();
        }
    },
    handlePaste(e) {
        let paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        let arr = ['','','','','',''];
        paste.split('').forEach((c, index) => arr[index] = c);
        this.digits = arr;
        if (paste.length === 6) {
            let inputs = e.target.parentElement.querySelectorAll('input');
            if (inputs[5]) inputs[5].focus();
        }
        e.preventDefault();
    },
    handleBackspace(i, e) {
        if (e.key === 'Backspace' && this.digits[i] === '' && i > 0) {
            let inputs = e.target.parentElement.querySelectorAll('input');
            if (inputs[i - 1]) inputs[i - 1].focus();
        }
    }
}" x-init="
    const setup = () => {
        let el = $el.closest('[wire\\:id]');
        if (!el) return;
        let id = el.getAttribute('wire:id');
        let component = null;
        try {
            component = window.Livewire && window.Livewire.find(id);
        } catch (e) {
            // Livewire component not fully booted yet in the registry
        }
        
        if (component) {
            // Initial sync
            step = component.get('step');
            countdown = component.get('resendCooldown');
            otp = component.get('otp') || '';
            let arr = [...digits];
            for (let i = 0; i < 6; i++) {
                arr[i] = otp[i] || '';
            }
            digits = arr;
            
            email = component.get('email') || '';
            password = component.get('password') || '';
            passwordConfirmation = component.get('passwordConfirmation') || '';
            
            // Watch server updates safely
            if (!el.__lwSync) {
                window.Livewire.hook('message.processed', (msg, comp) => {
                    if (comp.id === id) {
                        step = comp.get('step');
                        countdown = comp.get('resendCooldown');
                        let newOtp = comp.get('otp') || '';
                        if (otp !== newOtp) {
                            otp = newOtp;
                            let newArr = [...digits];
                            for (let i = 0; i < 6; i++) {
                                newArr[i] = otp[i] || '';
                            }
                            digits = newArr;
                        }
                    }
                });
                el.__lwSync = true;
            }

            // Watch local changes and push to server safely
            $watch('digits', (value) => {
                let currentOtp = value.join('');
                if (otp !== currentOtp) {
                    otp = currentOtp;
                    try { window.Livewire?.find(id)?.set('otp', otp, true); } catch(e) {}
                }
            });
            $watch('email', (value) => {
                try { window.Livewire?.find(id)?.set('email', value, true); } catch(e) {}
            });
            $watch('password', (value) => {
                try { window.Livewire?.find(id)?.set('password', value, true); } catch(e) {}
            });
            $watch('passwordConfirmation', (value) => {
                try { window.Livewire?.find(id)?.set('passwordConfirmation', value, true); } catch(e) {}
            });
        } else {
            setTimeout(setup, 50);
        }
    };
    setup();
    $watch('countdown', v => { if (v > 0 && !timer) startCountdown(); });
" x-effect="if (step === 2) startExpireTimer()">

    {{-- ═══════════════════════════════════════ STEP 1: Email ═══ --}}
    <div x-cloak x-show="step === 1" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-2">FORGOT PASSWORD</h1>
            <p class="text-[14px] text-gray-500 font-medium">Enter your registered email and we'll send a 6-digit code.</p>
        </div>

        {{-- Session flash --}}
        @if (session('status'))
            <div class="mb-6 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-[13px] text-emerald-700 font-bold flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit.prevent="sendOtp" class="space-y-6">
            <div>
                <label for="fp_email" class="block text-[12px] font-bold text-gray-700 uppercase tracking-wider mb-2">Email Address</label>
                <input id="fp_email" type="email" x-model="email" autocomplete="email"
                       placeholder="name@domain.com"
                       class="block w-full px-4 py-3 bg-gray-50 border-gray-100 rounded-xl focus:bg-white focus:border-indigo-600 transition-all duration-200" />
                @error('email')
                    <p class="mt-2 text-[12px] font-bold text-red-500 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="w-full justify-center py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-200 transition-all duration-200 transform active:scale-[0.98] font-bold tracking-wide uppercase text-[13px] flex items-center gap-2"
                        wire:loading.attr="disabled" wire:target="sendOtp">
                    <span wire:loading.remove wire:target="sendOtp">Send Verification Code</span>
                    <span wire:loading.flex wire:target="sendOtp" class="items-center justify-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        Sending…
                    </span>
                </button>
            </div>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
            <p class="text-[13px] text-gray-500 font-medium">
                Remember your password?
                <button type="button" x-on:click.prevent="$dispatch('switchtologin')" class="text-indigo-600 font-bold hover:text-indigo-700 transition-colors ml-1 uppercase tracking-wide text-[12px]">Back to Login</button>
            </p>
        </div>
    </div>

    {{-- ═══════════════════════════════════════ STEP 2: OTP ═════ --}}
    <div x-show="step === 2" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-2">CHECK YOUR INBOX</h1>
            <p class="text-[14px] text-gray-500 font-medium mb-1">
                We sent a 6-digit code to
            </p>
            <p class="text-[14px] font-extrabold text-indigo-600 mb-4">{{ $email }}</p>

            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 border border-amber-100 rounded-lg"
                 :class="expireCountdown <= 60 ? 'bg-red-50 border-red-100 text-red-600' : 'text-amber-700'">
                <svg class="w-4 h-4 animate-pulse flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="text-[12px] font-bold tracking-wide">
                    Code expires in <span x-text="formatTime(expireCountdown)"></span>
                </span>
            </div>
        </div>

        <form wire:submit.prevent="verifyOtp" class="space-y-6">
            {{-- 6-digit OTP input --}}
            <div class="flex gap-3 justify-center" @paste.window="handlePaste">
                <template x-for="(d, i) in digits" :key="i">
                    <input
                        type="text" inputmode="numeric" maxlength="1"
                        x-model="digits[i]"
                        @input="focusNext(i, $event)"
                        @keydown="handleBackspace(i, $event)"
                        class="w-12 h-16 text-center text-[24px] font-extrabold text-indigo-900 bg-gray-50 border border-gray-100 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition shadow-sm tracking-widest"
                    />
                </template>
            </div>

            @error('otp')
                <p class="mt-2 text-center text-[12px] font-bold text-red-500 flex items-center justify-center gap-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror

            <div class="pt-2">
                <button type="submit"
                        class="w-full justify-center py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-200 transition-all duration-200 transform active:scale-[0.98] font-bold tracking-wide uppercase text-[13px] flex items-center gap-2"
                        wire:loading.attr="disabled" wire:target="verifyOtp">
                    <span wire:loading.remove wire:target="verifyOtp">Verify Code</span>
                    <span wire:loading.flex wire:target="verifyOtp" class="items-center justify-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        Verifying…
                    </span>
                </button>
            </div>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 text-center space-y-3">
            <p class="text-[13px] text-gray-500 font-medium">
                Didn't receive it?
                <button x-show="countdown === 0" wire:click="resendOtp"
                        class="text-indigo-600 font-bold hover:text-indigo-700 transition-colors ml-1 uppercase tracking-wide text-[12px]">Resend Code</button>
                <span x-show="countdown > 0" class="text-gray-400 ml-1 font-bold">
                    Resend in <span x-text="countdown" class="text-indigo-600"></span>s
                </span>
            </p>
            <p class="text-[13px] text-gray-500 font-medium">
                Wrong email?
                <button wire:click="$set('step', 1)" class="text-indigo-600 font-bold hover:text-indigo-700 transition-colors ml-1 uppercase tracking-wide text-[12px]">Go back</button>
            </p>
        </div>
    </div>

    {{-- ════════════════════════════════ STEP 3: New Password ════ --}}
    <div x-show="step === 3" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-2">SET NEW PASSWORD</h1>
            <p class="text-[14px] text-gray-500 font-medium">Requires at least 8 characters, an uppercase letter, and a number.</p>
        </div>

        <form wire:submit.prevent="resetPassword" class="space-y-6">
            <div x-data="{ show: false }">
                <label for="fp_password" class="block text-[12px] font-bold text-gray-700 uppercase tracking-wider mb-2">New Password</label>
                <div class="relative">
                    <input id="fp_password" name="password" :type="show ? 'text' : 'password'" x-model="password"
                           placeholder="Min. 8 chars, A–Z, a–z, 0–9"
                           autocomplete="new-password"
                           x-on:input="checkStrength($event.target.value)"
                           class="block w-full px-4 py-3 bg-gray-50 border-gray-100 rounded-xl focus:bg-white focus:border-indigo-600 transition-all duration-200 pr-12" />
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-4 flex items-center justify-center text-gray-400 hover:text-indigo-600 transition-colors">
                        <svg x-show="!show" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        <svg x-show="show" x-cloak class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                    </button>
                </div>

                {{-- Strength bar --}}
                <div class="mt-3 flex gap-1 h-1.5">
                    <div class="flex-1 rounded-full transition-colors duration-300"
                         :class="strength >= 1 ? (strength <= 2 ? 'bg-red-400 shadow-[0_0_8px_rgba(248,113,113,0.5)]' : (strength <= 3 ? 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.5)]' : 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.5)]')) : 'bg-gray-200'"></div>
                    <div class="flex-1 rounded-full transition-colors duration-300"
                         :class="strength >= 2 ? (strength <= 2 ? 'bg-red-400 shadow-[0_0_8px_rgba(248,113,113,0.5)]' : (strength <= 3 ? 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.5)]' : 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.5)]')) : 'bg-gray-200'"></div>
                    <div class="flex-1 rounded-full transition-colors duration-300"
                         :class="strength >= 3 ? (strength <= 3 ? 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.5)]' : 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.5)]') : 'bg-gray-200'"></div>
                    <div class="flex-1 rounded-full transition-colors duration-300"
                         :class="strength >= 4 ? 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.5)]' : 'bg-gray-200'"></div>
                    <div class="flex-1 rounded-full transition-colors duration-300"
                         :class="strength >= 5 ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-gray-200'"></div>
                </div>
                <p class="mt-2 text-[11px] font-bold uppercase tracking-wider transition-colors"
                   :class="strength <= 2 ? 'text-red-500' : (strength <= 3 ? 'text-amber-500' : 'text-emerald-600')"
                   x-text="strength === 0 ? '' : (strength <= 2 ? 'Weak' : (strength <= 3 ? 'Fair' : 'Strong'))"></p>

                @error('password')
                    <p class="mt-2 text-[12px] font-bold text-red-500 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div x-data="{ show: false }">
                <label for="fp_password_confirmation" class="block text-[12px] font-bold text-gray-700 uppercase tracking-wider mb-2">Confirm Password</label>
                <div class="relative">
                    <input id="fp_password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" x-model="passwordConfirmation"
                           placeholder="Re-enter your new password"
                           autocomplete="new-password"
                           class="block w-full px-4 py-3 bg-gray-50 border-gray-100 rounded-xl focus:bg-white focus:border-indigo-600 transition-all duration-200 pr-12" />
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-4 flex items-center justify-center text-gray-400 hover:text-indigo-600 transition-colors">
                        <svg x-show="!show" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        <svg x-show="show" x-cloak class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                    </button>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="w-full justify-center py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-200 transition-all duration-200 transform active:scale-[0.98] font-bold tracking-wide uppercase text-[13px] flex items-center gap-2"
                        wire:loading.attr="disabled" wire:target="resetPassword">
                    <span wire:loading.remove wire:target="resetPassword">Reset Password</span>
                    <span wire:loading.flex wire:target="resetPassword" class="items-center justify-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        Saving…
                    </span>
                </button>
            </div>
        </form>
    </div>

</div>
</div>
