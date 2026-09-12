<div x-data="deviceReviewManager()" x-init="initDevice()" @device-id-synced.window="syncDeviceId($event.detail.deviceId)">
    @if($isSubmitted)
        <div class="bg-white rounded-3xl shadow-xl shadow-rose-100/50 p-8 text-center border border-gray-100 relative overflow-hidden transform transition-all duration-500 scale-100 opacity-100">
            <div class="w-20 h-20 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-2">Thank You!</h2>
            <p class="text-[14px] text-gray-500 font-medium leading-relaxed mb-6">Your feedback has been submitted successfully. We appreciate your time!</p>
            
            <div class="absolute -right-10 -bottom-10 opacity-5 pointer-events-none">
                <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path></svg>
            </div>
        </div>
    @elseif($alreadyReviewed)
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8 text-center border border-gray-100 relative overflow-hidden">
            <div class="w-20 h-20 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-5 border border-amber-100">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-2">Feedback Already Submitted</h2>
            <p class="text-[14px] text-gray-500 font-medium leading-relaxed mb-4">
                You have already shared your review for 
                @if($order)<span class="font-bold text-gray-800">Order #{{ $order->reference_no }}</span>@endif
                {{ $existingReviewDate ? 'on ' . $existingReviewDate : '' }}.
            </p>
            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 inline-block text-[12px] text-gray-500 font-medium">
                Thank you for helping us improve our food and service!
            </div>
        </div>
    @elseif($maxDevicesReached)
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8 text-center border border-gray-100 relative overflow-hidden">
            <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-5 border border-rose-100">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-2">Review Limit Reached</h2>
            <p class="text-[14px] text-gray-500 font-medium leading-relaxed mb-4">
                The maximum number of reviews for 
                @if($order)<span class="font-bold text-gray-800">Order #{{ $order->reference_no }}</span>@endif
                has already been received. Thank you to everyone at your table for your feedback!
            </p>
        </div>
    @elseif($isExpired)
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8 text-center border border-gray-100 relative overflow-hidden">
            <div class="w-20 h-20 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-2">Review Period Ended</h2>
            <p class="text-[14px] text-gray-500 font-medium leading-relaxed mb-4">
                This receipt review link has expired. Receipt reviews must be submitted within {{ $expiryDays }} days of purchase.
            </p>
        </div>
    @elseif($orderNotFound)
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8 text-center border border-gray-100 relative overflow-hidden">
            <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-5 border border-rose-100">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-2">Invalid Review Link</h2>
            <p class="text-[14px] text-gray-500 font-medium leading-relaxed">
                We couldn't locate this receipt. Please scan the QR code printed directly on your physical receipt.
            </p>
        </div>
    @elseif($isCoolingDown)
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8 text-center border border-gray-100 relative overflow-hidden">
            <div class="w-20 h-20 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-5 border border-indigo-100">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 mb-3">
                ⏱️ Review Cooldown Active
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-2">Please Wait a Moment</h2>
            <p class="text-[14px] text-gray-500 font-medium leading-relaxed mb-4">
                You recently submitted a review from this device. To prevent spam and duplicate feedback, please wait 
                <span class="font-bold text-gray-900">{{ $cooldownRemainingMinutes }} minute{{ $cooldownRemainingMinutes > 1 ? 's' : '' }}</span> 
                before reviewing another receipt.
            </p>
            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 inline-block text-[12px] text-gray-500 font-medium">
                Thank you for your patience and for dining with us!
            </div>
        </div>
    @else
        <form wire:submit="submit" action="javascript:void(0);" onsubmit="return false;" class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-6 sm:p-8 border border-gray-100 transition-all">
            
            {{-- Hidden Anti-Bot Honeypot Field --}}
            <div style="position: absolute; left: -9999px; top: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">
                <input type="text" wire:model="honeypot" name="website_url_check" tabindex="-1" autocomplete="off">
            </div>

            {{-- Hidden Device Binding Inputs --}}
            <input type="hidden" wire:model="device_id" :value="deviceId">
            <input type="hidden" wire:model="device_fingerprint" :value="fingerprint">

            <div class="mb-6 text-center pb-5 border-b border-gray-50">
                <h1 class="text-xl font-black tracking-tight text-gray-900 mb-1">{{ tap($title) ? $title : 'How was your experience?' }}</h1>
                <p class="text-[13px] text-gray-500 font-medium">{{ tap($subtitle) ? $subtitle : 'Thank you for your feedback!' }}</p>
            </div>

            {{-- Branch & Order Details Badges --}}
            @if($branch || $order)
                <div class="mb-6 flex flex-wrap items-center justify-center gap-2">
                    @if($branch)
                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 rounded-xl border border-rose-100 text-rose-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span class="text-[11px] font-black uppercase tracking-wide">{{ $branch->branch_name }}</span>
                        </div>
                    @endif
                    @if($order)
                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 rounded-xl border border-indigo-100 text-indigo-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span class="text-[11px] font-black uppercase tracking-wide">Order #{{ $order->reference_no }}</span>
                        </div>
                        @if($order->table_number)
                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 rounded-xl border border-amber-100 text-amber-700">
                                <span class="text-[11px] font-black uppercase tracking-wide">Table {{ $order->table_number }}</span>
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            {{-- General Submission Error / Cooldown Alert --}}
            @error('submission')
                <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-[13px] font-medium flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ $message }}</span>
                </div>
            @enderror

            <!-- Dynamic Questions Section -->
            <div class="space-y-8 mb-8">
                @foreach($questions as $index => $question)
                    <div wire:key="question-item-{{ $index }}">
                        <label class="block text-[13px] font-bold text-gray-700 mb-3 text-center sm:text-left">
                            {{ $question['text'] }}
                            @if($question['required'] ?? false) <span class="text-rose-500">*</span> @endif
                        </label>

                        @if(($question['type'] ?? '') === 'rating')
                            <div class="flex items-center justify-center sm:justify-start gap-2 flex-row-reverse" 
                                 x-data="{ 
                                     rating: @entangle('answers.' . $index).live, 
                                     hoverRating: 0 
                                 }" 
                                 @mouseleave="hoverRating = 0">
                                @for($i = 5; $i >= 1; $i--)
                                    <button type="button" 
                                            @click="rating = {{ $i }}"
                                            wire:click="setRating({{ $index }}, {{ $i }})"
                                            @mouseover="hoverRating = {{ $i }}"
                                            aria-label="Rate {{ $i }} out of 5"
                                            class="transform transition-transform active:scale-90 focus:outline-none p-1"
                                    >
                                        <svg class="w-10 h-10 sm:w-12 sm:h-12 transition-colors duration-200" 
                                             :class="(hoverRating >= {{ $i }} || (hoverRating == 0 && (rating || 0) >= {{ $i }})) ? 'text-amber-400 drop-shadow-md' : 'text-gray-200'"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </button>
                                @endfor
                            </div>
                        @else
                            <textarea wire:model.live.debounce.300ms="answers.{{ $index }}" rows="3" 
                                class="w-full bg-gray-50 border {{ $errors->has('answers.'.$index) ? 'border-red-500 bg-red-50' : 'border-gray-200' }} text-gray-900 text-[14px] rounded-xl focus:ring-rose-500 focus:border-rose-500 block p-3.5 transition-all shadow-sm placeholder-gray-400" 
                                placeholder="Type your answer here..."></textarea>
                        @endif

                        @error("answers.$index") <span class="text-xs text-rose-500 font-bold mt-2 block">{{ $message }}</span> @enderror
                    </div>
                @endforeach
            </div>

            <!-- Optional Details -->
            <div class="space-y-5">
                <div class="pt-4 border-t border-gray-100">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-4">Optional Details</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="customer_name" class="block text-[13px] font-bold text-gray-700 mb-1.5">Name</label>
                            <input type="text" wire:model.live.debounce.400ms="customer_name" id="customer_name" 
                                class="w-full bg-gray-50 border {{ $errors->has('customer_name') ? 'border-red-500 bg-red-50' : 'border-gray-200' }} text-gray-900 text-[14px] rounded-xl focus:ring-rose-500 focus:border-rose-500 block px-3.5 py-2.5 transition-all shadow-sm placeholder-gray-400" 
                                placeholder="Juan Dela Cruz" x-on:input="restrictInput($event, 'name')">
                            @error('customer_name') <span class="text-xs text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="contact_number" class="block text-[13px] font-bold text-gray-700 mb-1.5">Contact Number</label>
                            <input type="tel" wire:model.live.debounce.400ms="contact_number" id="contact_number" 
                                class="w-full bg-gray-50 border {{ $errors->has('contact_number') ? 'border-red-500 bg-red-50' : 'border-gray-200' }} text-gray-900 text-[14px] rounded-xl focus:ring-rose-500 focus:border-rose-500 block px-3.5 py-2.5 transition-all shadow-sm placeholder-gray-400" 
                                placeholder="09xxxxxxxxx" x-on:input="restrictInput($event, 'phone')">
                            @error('contact_number') <span class="text-xs text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-8">
                <button type="submit" class="w-full relative inline-flex items-center justify-center px-6 py-4 overflow-hidden font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 transition-all shadow-md active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-rose-200 group">
                    <span wire:loading.remove wire:target="submit" class="flex items-center gap-2">
                        Submit Feedback
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </span>
                    <span wire:loading wire:target="submit" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Sending...
                    </span>
                </button>
            </div>
        </form>
    @endif

    <script>
        function deviceReviewManager() {
            return {
                deviceId: '',
                fingerprint: '',
                initDevice() {
                    const COOKIE_NAME = 'mtc_device_id';
                    let deviceId = '';

                    // 1. Try localStorage
                    try {
                        deviceId = localStorage.getItem(COOKIE_NAME);
                    } catch(e) {}

                    // 2. Try sessionStorage
                    if (!deviceId) {
                        try {
                            deviceId = sessionStorage.getItem(COOKIE_NAME);
                        } catch(e) {}
                    }

                    // 3. Try document.cookie
                    if (!deviceId) {
                        try {
                            const match = document.cookie.match(new RegExp('(^| )' + COOKIE_NAME + '=([^;]+)'));
                            if (match) {
                                deviceId = decodeURIComponent(match[2]);
                            }
                        } catch(e) {}
                    }

                    // 4. Generate cryptographic UUID if still absent
                    if (!deviceId || typeof deviceId !== 'string' || deviceId.trim().length < 10) {
                        if (window.crypto && window.crypto.randomUUID) {
                            deviceId = window.crypto.randomUUID();
                        } else {
                            deviceId = 'dev_' + Math.random().toString(36).substring(2, 15) + Date.now().toString(36);
                        }
                    }

                    deviceId = deviceId.trim();

                    // 5. Persist across all client storage tiers (1-year lifetime)
                    this.persistDeviceId(deviceId);

                    // 6. Build high-fidelity canvas & hardware fingerprint
                    const fingerprint = this.computeFingerprint();

                    this.deviceId = deviceId;
                    this.fingerprint = fingerprint;

                    // 7. Dispatch to Livewire component
                    if (this.$wire && typeof this.$wire.setDeviceId === 'function') {
                        this.$wire.setDeviceId(deviceId, fingerprint);
                    }
                },

                persistDeviceId(id) {
                    if (!id) return;
                    const COOKIE_NAME = 'mtc_device_id';
                    try { localStorage.setItem(COOKIE_NAME, id); } catch(e) {}
                    try { sessionStorage.setItem(COOKIE_NAME, id); } catch(e) {}
                    try {
                        const expires = new Date(Date.now() + 365 * 864e5).toUTCString();
                        const isSecure = window.location.protocol === 'https:';
                        document.cookie = COOKIE_NAME + '=' + encodeURIComponent(id) + '; expires=' + expires + '; path=/; SameSite=Lax' + (isSecure ? '; Secure' : '');
                    } catch(e) {}
                },

                syncDeviceId(newId) {
                    if (newId && newId !== this.deviceId) {
                        this.deviceId = newId;
                        this.persistDeviceId(newId);
                    }
                },

                computeFingerprint() {
                    try {
                        // Off-screen Canvas 2D render hash (captures GPU font-rasterization micro-signatures)
                        let canvasHash = '';
                        try {
                            const canvas = document.createElement('canvas');
                            canvas.width = 160;
                            canvas.height = 40;
                            const ctx = canvas.getContext('2d');
                            if (ctx) {
                                ctx.textBaseline = 'alphabetic';
                                ctx.font = '14px Arial';
                                ctx.fillStyle = '#f43f5e';
                                ctx.fillRect(10, 5, 80, 25);
                                ctx.fillStyle = '#0284c7';
                                ctx.fillText('MTC-Review-2026', 15, 22);
                                ctx.fillStyle = 'rgba(234, 88, 12, 0.7)';
                                ctx.fillText('Takoyaki!✨', 20, 24);
                                const dataUrl = canvas.toDataURL();
                                let h = 0;
                                for (let i = 0; i < dataUrl.length; i++) {
                                    h = Math.imul(31, h) + dataUrl.charCodeAt(i) | 0;
                                }
                                canvasHash = (h >>> 0).toString(16);
                            }
                        } catch(e) {}

                        // WebGL GPU Renderer (e.g. Apple GPU, Adreno, Mali)
                        let glInfo = '';
                        try {
                            const glCanvas = document.createElement('canvas');
                            const gl = glCanvas.getContext('webgl') || glCanvas.getContext('experimental-webgl');
                            if (gl) {
                                const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                                if (debugInfo) {
                                    glInfo = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) || '';
                                }
                            }
                        } catch(e) {}

                        const raw = [
                            screen.width + 'x' + screen.height,
                            window.devicePixelRatio || 1,
                            screen.colorDepth || '',
                            Intl.DateTimeFormat().resolvedOptions().timeZone || '',
                            navigator.language || '',
                            navigator.platform || '',
                            navigator.hardwareConcurrency || '',
                            navigator.maxTouchPoints || '',
                            canvasHash,
                            glInfo
                        ].join('|');

                        // Produce deterministic 32-character hex hash
                        let h1 = 0x12345678, h2 = 0x87654321, h3 = 0xdeadbeef, h4 = 0x41c6ce57;
                        for (let i = 0; i < raw.length; i++) {
                            const c = raw.charCodeAt(i);
                            h1 = Math.imul(h1 ^ c, 2654435761);
                            h2 = Math.imul(h2 ^ c, 1597334677);
                            h3 = Math.imul(h3 ^ c, 2246822507);
                            h4 = Math.imul(h4 ^ c, 3266489909);
                        }
                        return [h1, h2, h3, h4].map(h => (h >>> 0).toString(16).padStart(8, '0')).join('');
                    } catch(e) {
                        return 'default_fp';
                    }
                }
            };
        }
    </script>
</div>

