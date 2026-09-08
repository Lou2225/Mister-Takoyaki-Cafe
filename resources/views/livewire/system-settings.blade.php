<div class="space-y-6" x-data="systemSettingsData({ 

        tab: @entangle('tab').live,
        addr_region: @entangle('addr_region').live,
        addr_province: @entangle('addr_province').live,
        addr_city: @entangle('addr_city').live,
        addr_barangay: @entangle('addr_barangay').live,
        receipt: {
                            logo_enabled: @js($receiptLogoEnabled),
                            show_vat: @entangle('receiptShowVat').live,
                            footer: @js($receiptFooterMessage),
            policy: @js($receiptReturnPolicy),
            copies: @js($receiptCopies),
            qr_url: @js($receiptQrUrl)
        }
    })">

    <div class="flex flex-col lg:flex-row gap-6 lg:gap-10 min-h-[700px] w-full">

        {{-- Mobile / Tablet Nav: horizontal scrollable pills --}}
        <div class="lg:hidden -mx-1 px-1 overflow-x-auto no-scrollbar scroll-smooth">
            <div class="flex items-center gap-2 w-max pb-1">
                @if($this->isSuperAdmin())
                    <button @click="tab = 'general'; $wire.selectTab('general')" :class="tab === 'general' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200'" class="flex items-center gap-2 px-3.5 h-10 rounded-xl text-[12px] font-bold border shadow-sm transition-colors shrink-0 whitespace-nowrap">
                        <svg :class="tab === 'general' ? 'text-white' : 'text-gray-400'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Business Identity
                    </button>
                    <button @click="tab = 'receipts'; $wire.selectTab('receipts')" :class="tab === 'receipts' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200'" class="flex items-center gap-2 px-3.5 h-10 rounded-xl text-[12px] font-bold border shadow-sm transition-colors shrink-0 whitespace-nowrap">
                        <svg :class="tab === 'receipts' ? 'text-white' : 'text-gray-400'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Receipt Design
                    </button>
                    <div class="w-px h-6 bg-gray-200 shrink-0"></div>
                @endif

                <button @click="tab = 'inventory'; $wire.selectTab('inventory')" :class="tab === 'inventory' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200'" class="flex items-center gap-2 px-3.5 h-10 rounded-xl text-[12px] font-bold border shadow-sm transition-colors shrink-0 whitespace-nowrap">
                    <svg :class="tab === 'inventory' ? 'text-white' : 'text-gray-400'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m-8-4V7m8 4v10M4 7l8 4" /></svg>
                    Inventory Controls
                </button>
                <button @click="tab = 'pos'; $wire.selectTab('pos')" :class="tab === 'pos' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200'" class="flex items-center gap-2 px-3.5 h-10 rounded-xl text-[12px] font-bold border shadow-sm transition-colors shrink-0 whitespace-nowrap">
                    <svg :class="tab === 'pos' ? 'text-white' : 'text-gray-400'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                    POS Platform
                </button>
                <button @click="tab = 'reviews'; $wire.selectTab('reviews')" :class="tab === 'reviews' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200'" class="flex items-center gap-2 px-3.5 h-10 rounded-xl text-[12px] font-bold border shadow-sm transition-colors shrink-0 whitespace-nowrap">
                    <svg :class="tab === 'reviews' ? 'text-white' : 'text-gray-400'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 11l3 3L20 4m-9 10a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Customer Feedback
                </button>

                @if($this->isSuperAdmin())
                    <div class="w-px h-6 bg-gray-200 shrink-0"></div>
                    <button @click="tab = 'printer'; $wire.selectTab('printer')" :class="tab === 'printer' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200'" class="flex items-center gap-2 px-3.5 h-10 rounded-xl text-[12px] font-bold border shadow-sm transition-colors shrink-0 whitespace-nowrap">
                        <svg :class="tab === 'printer' ? 'text-white' : 'text-gray-400'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        Thermal Printer
                    </button>
                    <button @click="tab = 'system'; $wire.selectTab('system')" :class="tab === 'system' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200'" class="flex items-center gap-2 px-3.5 h-10 rounded-xl text-[12px] font-bold border shadow-sm transition-colors shrink-0 whitespace-nowrap">
                        <svg :class="tab === 'system' ? 'text-white' : 'text-gray-400'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Access Control
                    </button>
                    <button @click="tab = 'logs'; $wire.selectTab('logs')" :class="tab === 'logs' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200'" class="flex items-center gap-2 px-3.5 h-10 rounded-xl text-[12px] font-bold border shadow-sm transition-colors shrink-0 whitespace-nowrap">
                        <svg :class="tab === 'logs' ? 'text-white' : 'text-gray-400'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Activity Audit
                    </button>
                @endif
            </div>
        </div>

        {{-- Side Navigation (Desktop) --}}
        <div class="hidden lg:flex flex-col gap-6 w-[260px] flex-shrink-0">
            @if($this->isSuperAdmin())
            <div class="space-y-0.5">
                <h3 class="px-3 mb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider transition-opacity duration-300">General</h3>
                <button @click="tab = 'general'; $wire.selectTab('general')" :class="tab === 'general' ? 'bg-[#F3F4F6] text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'" class="flex items-center gap-3 w-full px-3 py-1.5 rounded-lg text-[13px] font-medium transition-colors group focus:outline-none">
                    <svg :class="tab === 'general' ? 'text-gray-900' : 'text-gray-400 group-hover:text-gray-900'" class="w-[18px] h-[18px] opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Business Identity</span>
                </button>

                <button @click="tab = 'receipts'; $wire.selectTab('receipts')" :class="tab === 'receipts' ? 'bg-[#F3F4F6] text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'" class="flex items-center gap-3 w-full px-3 py-1.5 mt-0.5 rounded-lg text-[13px] font-medium transition-colors group focus:outline-none">
                    <svg :class="tab === 'receipts' ? 'text-gray-900' : 'text-gray-400 group-hover:text-gray-900'" class="w-[18px] h-[18px] opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Receipt Design</span>
                </button>
            </div>
            @endif

            <div class="space-y-0.5">
                <h3 class="px-3 mb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Operations</h3>
                <button @click="tab = 'inventory'; $wire.selectTab('inventory')" :class="tab === 'inventory' ? 'bg-[#F3F4F6] text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'" class="flex items-center gap-3 w-full px-3 py-1.5 rounded-lg text-[13px] font-medium transition-colors group focus:outline-none">
                    <svg :class="tab === 'inventory' ? 'text-gray-900' : 'text-gray-400 group-hover:text-gray-900'" class="w-[18px] h-[18px] opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m-8-4V7m8 4v10M4 7l8 4" />
                    </svg>
                    <span>Inventory Controls</span>
                </button>
                <button @click="tab = 'pos'; $wire.selectTab('pos')" :class="tab === 'pos' ? 'bg-[#F3F4F6] text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'" class="flex items-center gap-3 w-full px-3 py-1.5 mt-0.5 rounded-lg text-[13px] font-medium transition-colors group focus:outline-none">
                    <svg :class="tab === 'pos' ? 'text-gray-900' : 'text-gray-400 group-hover:text-gray-900'" class="w-[18px] h-[18px] opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span>POS Platform</span>
                </button>
                <button @click="tab = 'reviews'; $wire.selectTab('reviews')" :class="tab === 'reviews' ? 'bg-[#F3F4F6] text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'" class="flex items-center gap-3 w-full px-3 py-1.5 mt-0.5 rounded-lg text-[13px] font-medium transition-colors group focus:outline-none">
                    <svg :class="tab === 'reviews' ? 'text-gray-900' : 'text-gray-400 group-hover:text-gray-900'" class="w-[18px] h-[18px] opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 11l3 3L20 4m-9 10a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Customer Feedback</span>
                </button>
            </div>

            @if($this->isSuperAdmin())
            <div class="space-y-0.5">
                <h3 class="px-3 mb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Hardware</h3>
                <button @click="tab = 'printer'; $wire.selectTab('printer')" :class="tab === 'printer' ? 'bg-[#F3F4F6] text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'" class="flex items-center gap-3 w-full px-3 py-1.5 rounded-lg text-[13px] font-medium transition-colors group focus:outline-none">
                    <svg :class="tab === 'printer' ? 'text-gray-900' : 'text-gray-400 group-hover:text-gray-900'" class="w-[18px] h-[18px] opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Thermal Printer</span>
                </button>
            </div>

            <div class="space-y-0.5">
                <h3 class="px-3 mb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Security</h3>
                <button @click="tab = 'system'; $wire.selectTab('system')" :class="tab === 'system' ? 'bg-[#F3F4F6] text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'" class="flex items-center gap-3 w-full px-3 py-1.5 rounded-lg text-[13px] font-medium transition-colors group focus:outline-none">
                    <svg :class="tab === 'system' ? 'text-gray-900' : 'text-gray-400 group-hover:text-gray-900'" class="w-[18px] h-[18px] opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>Access Control</span>
                </button>
                {{-- <button @click="tab = 'maintenance'; $wire.selectTab('maintenance')" :class="tab === 'maintenance' ? 'bg-[#F3F4F6] text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'" class="flex items-center gap-3 w-full px-3 py-1.5 mt-0.5 rounded-lg text-[13px] font-medium transition-colors group focus:outline-none">
                    <svg :class="tab === 'maintenance' ? 'text-gray-900' : 'text-gray-400 group-hover:text-gray-900'" class="w-[18px] h-[18px] opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>
                    <span>Maintenance</span>
                </button> --}}
                <button @click="tab = 'logs'; $wire.selectTab('logs')" :class="tab === 'logs' ? 'bg-[#F3F4F6] text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'" class="flex items-center gap-3 w-full px-3 py-1.5 mt-0.5 rounded-lg text-[13px] font-medium transition-colors group focus:outline-none">
                    <svg :class="tab === 'logs' ? 'text-gray-900' : 'text-gray-400 group-hover:text-gray-900'" class="w-[18px] h-[18px] opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Activity Audit</span>
                </button>
            </div>
            @endif
        </div>

        {{-- Content Area --}}
        <div class="space-y-6" style="flex-grow: 1; min-width: 0;">

            {{-- Business Identity --}}
            @if($this->isSuperAdmin())
            <div x-show="tab === 'general'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-[13px] font-bold text-gray-900 uppercase tracking-widest mb-6 border-b border-gray-50 pb-2">Business Identity</h2>
                    
                    <div class="flex flex-col md:flex-row gap-8 mb-8 pb-8 border-b border-gray-50">
                        <div class="w-48 flex-shrink-0">
                            <x-input-label value="Business Logo" class="mb-2" />
                            <div class="relative group">
                                <div class="w-40 h-40 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden transition-all group-hover:border-indigo-300 shadow-inner">
                                    @if($businessLogo)
                                        <img src="{{ $businessLogo->temporaryUrl() }}" class="w-full h-full object-cover">
                                    @elseif($existingLogo)
                                        <img src="{{ Storage::url($existingLogo) }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="text-center p-4">
                                            <svg class="w-8 h-8 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span class="text-[10px] font-black text-gray-300 uppercase tracking-widest leading-tight">Upload<br>Logo</span>
                                        </div>
                                    @endif
                                    <input type="file" wire:model.live="businessLogo" class="absolute inset-0 opacity-0 cursor-pointer" accept=".jpg,.jpeg,.png,.webp,image/*">
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('businessLogo')" class="mt-2" />
                            <p class="mt-2 text-[10px] text-gray-400 font-medium italic">PNG, JPG up to 1MB</p>
                        </div>

                        <div class="flex-grow grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                            <div class="sm:col-span-2">
                                <x-input-label for="businessName" value="Legal Entity Name *" />
                                <x-text-input id="businessName" wire:model.blur="businessName" class="mt-1 block w-full h-10" placeholder="e.g. Acme Corp" inputFilter="name" :hasError="$errors->has('businessName')" />
                                <x-input-error :messages="$errors->get('businessName')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="businessEmail" value="Primary Support Email *" />
                                <x-text-input id="businessEmail" wire:model.blur="businessEmail" type="email" class="mt-1 block w-full h-10" placeholder="contact@example.com" inputFilter="email" :hasError="$errors->has('businessEmail')" />
                                <x-input-error :messages="$errors->get('businessEmail')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="businessPhone" value="Contact Hotline" />
                                <div class="flex items-center mt-1">
                                    <div class="flex-shrink-0 inline-flex items-center px-3 h-10 rounded-l-lg border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-[13px] font-bold">
                                        +63
                                    </div>
                                    <x-text-input id="businessPhone" wire:model.blur="businessPhone" type="text"
                                        class="block w-full rounded-l-none" placeholder="912 345 6789" autocomplete="tel"
                                        inputFilter="number" maxlength="10"
                                        @keydown="FormFilters.numberKeydown($event)" @paste="FormFilters.numberPaste($event)"
                                        :hasError="$errors->has('businessPhone')" />
                                </div>
                                <x-input-error :messages="$errors->get('businessPhone')" class="mt-1" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="businessTin" value="Registered TIN" />
                                <x-text-input id="businessTin" wire:model.blur="businessTin" class="mt-1 block w-full h-10" placeholder="000-000-000-000" />
                                <x-input-error :messages="$errors->get('businessTin')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    {{-- Section: Address --}}
                    <div class="mt-8 pt-8 border-t border-gray-50">
                        <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h3 class="text-[14px] font-bold text-gray-900 mb-1">Business Location</h3>
                                <p class="text-[12px] text-gray-500">Provide the geographic location of your headquarters.</p>
                            </div>
                            <button type="button" @click="$dispatch('open-modal', 'map-modal'); initMap();" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-[11px] font-bold hover:bg-indigo-100 transition-all shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Open Map Picker
                            </button>
                        </div>

                        <div class="flex flex-col gap-5">
                            {{-- Row 1: Region + Province --}}
                            <div class="flex flex-col sm:flex-row gap-5">
                                <div class="flex-1 w-full">
                                    <x-input-label value="Region" />
                                    <div x-on:click.capture="loc.region.search = ''; loadRegions();">
                                        <x-dropdown align="left" width="full" containerClasses="block w-full mt-1">
                                            <x-slot name="trigger">
                                                <button type="button" class="w-full flex items-center justify-between px-3 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm hover:border-indigo-300 focus:outline-none transition-all h-10">
                                                    <span class="truncate" :class="addr_region ? 'text-gray-900 font-medium' : 'text-gray-400'" x-text="addr_region || 'Select Region...'"></span>
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                            </x-slot>
                                            <x-slot name="content">
                                                <div class="p-2 border-b border-gray-100 bg-gray-50/50">
                                                    <input x-model="loc.region.search" type="text" placeholder="Search region..." class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-[12px] focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                                                </div>
                                                <div class="max-h-48 overflow-y-auto">
                                                    <template x-for="r in filtered('region')" :key="r.code">
                                                        <x-dropdown-link href="#" @click.prevent="dropdownOpen = false; selectRegion(r)">
                                                            <span x-text="r.name"></span>
                                                        </x-dropdown-link>
                                                    </template>
                                                    <template x-if="filtered('region').length === 0">
                                                        <div class="px-4 py-2 text-[12px] text-gray-400 italic font-medium">No regions found...</div>
                                                    </template>
                                                </div>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                    <x-input-error :messages="$errors->get('addr_region')" class="mt-1" />
                                </div>

                                <div class="flex-1 w-full">
                                    <x-input-label value="Province" />
                                    <div x-on:click.capture="if(!addr_region || loc.noProvince) { $event.stopPropagation(); } else { loc.province.search = ''; }">
                                        <x-dropdown align="left" width="full" containerClasses="block w-full mt-1">
                                            <x-slot name="trigger">
                                                <button type="button" :disabled="!addr_region || loc.noProvince" class="w-full flex items-center justify-between px-3 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm disabled:bg-gray-50 disabled:opacity-75 disabled:cursor-not-allowed h-10">
                                                    <span class="truncate" :class="addr_province ? 'text-gray-900 font-medium' : 'text-gray-400'">
                                                        <template x-if="loc.noProvince"><span>N/A (Direct to City)</span></template>
                                                        <template x-if="!loc.noProvince"><span x-text="addr_province || 'Select Province...'"></span></template>
                                                    </span>
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                            </x-slot>
                                            <x-slot name="content">
                                                <div class="p-2 border-b border-gray-100 bg-gray-50/50">
                                                    <input x-model="loc.province.search" type="text" placeholder="Search province..." class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-[12px] focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                                                </div>
                                                <div class="max-h-48 overflow-y-auto">
                                                    <template x-for="p in filtered('province')" :key="p.code">
                                                        <x-dropdown-link href="#" @click.prevent="dropdownOpen = false; selectProvince(p)">
                                                            <span x-text="p.name"></span>
                                                        </x-dropdown-link>
                                                    </template>
                                                </div>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                    <x-input-error :messages="$errors->get('addr_province')" class="mt-1" />
                                </div>
                            </div>

                            {{-- Row 2: City + Barangay --}}
                            <div class="flex flex-col sm:flex-row gap-5">
                                <div class="flex-1 w-full">
                                    <x-input-label value="City / Municipality" />
                                    <div x-on:click.capture="if(!addr_region || (!addr_province && !loc.noProvince)) { $event.stopPropagation(); } else { loc.city.search = ''; }">
                                        <x-dropdown align="left" width="full" containerClasses="block w-full mt-1">
                                            <x-slot name="trigger">
                                                <button type="button" :disabled="!addr_region || (!addr_province && !loc.noProvince)" class="w-full flex items-center justify-between px-3 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm disabled:bg-gray-50 disabled:opacity-75 disabled:cursor-not-allowed h-10">
                                                    <span class="truncate" :class="addr_city ? 'text-gray-900 font-medium' : 'text-gray-400'" x-text="addr_city || 'Select City...'"></span>
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                            </x-slot>
                                            <x-slot name="content">
                                                <div class="p-2 border-b border-gray-100 bg-gray-50/50">
                                                    <input x-model="loc.city.search" type="text" placeholder="Search city..." class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-[12px] focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                                                </div>
                                                <div class="max-h-48 overflow-y-auto">
                                                    <template x-for="c in filtered('city')" :key="c.code">
                                                        <x-dropdown-link href="#" @click.prevent="dropdownOpen = false; selectCity(c)">
                                                            <span x-text="c.name"></span>
                                                        </x-dropdown-link>
                                                    </template>
                                                </div>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                    <x-input-error :messages="$errors->get('addr_city')" class="mt-1" />
                                </div>

                                <div class="flex-1 w-full">
                                    <x-input-label value="Barangay" />
                                    <div x-on:click.capture="if(!addr_city) { $event.stopPropagation(); } else { loc.barangay.search = ''; }">
                                        <x-dropdown align="left" width="full" containerClasses="block w-full mt-1">
                                            <x-slot name="trigger">
                                                <button type="button" :disabled="!addr_city" class="w-full flex items-center justify-between px-3 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm disabled:bg-gray-50 disabled:opacity-75 disabled:cursor-not-allowed h-10">
                                                    <span class="truncate" :class="addr_barangay ? 'text-gray-900 font-medium' : 'text-gray-400'" x-text="addr_barangay || 'Select Barangay...'"></span>
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                            </x-slot>
                                            <x-slot name="content">
                                                <div class="p-2 border-b border-gray-100 bg-gray-50/50">
                                                    <input x-model="loc.barangay.search" type="text" placeholder="Search barangay..." class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-[12px] focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                                                </div>
                                                <div class="max-h-48 overflow-y-auto">
                                                    <template x-for="b in filtered('barangay')" :key="b.code">
                                                        <x-dropdown-link href="#" @click.prevent="dropdownOpen = false; selectBarangay(b)">
                                                            <span x-text="b.name"></span>
                                                        </x-dropdown-link>
                                                    </template>
                                                </div>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                    <x-input-error :messages="$errors->get('addr_barangay')" class="mt-1" />
                                </div>
                            </div>

                            {{-- Row 3: Street --}}
                            <div>
                                <x-input-label value="House # / Street / Subdivision" />
                                <x-text-input wire:model.blur="addr_street" class="w-full mt-1 h-10" placeholder="e.g. Unit 123, Rosewood Ave, Phase 1" :hasError="$errors->has('addr_street')" />
                                <x-input-error :messages="$errors->get('addr_street')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-2 pt-6 border-t border-gray-50">
                        <x-primary-button wire:click="validateBeforeSave" wire:loading.attr="disabled" wire:target="validateBeforeSave" class="h-11">Save Identity</x-primary-button>
                    </div>
                </div>
            </div>
            @endif



            {{-- Receipt Design --}}
            @if($this->isSuperAdmin())
            <div x-show="tab === 'receipts'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 space-y-6">
                        <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                            <h2 class="text-[13px] font-bold text-gray-900 uppercase tracking-widest mb-6 border-b border-gray-50 pb-2">Design Controls</h2>

                            <div class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <label class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-100 rounded-xl cursor-pointer hover:bg-white transition-all shadow-sm">
                                        <input type="checkbox" wire:model.live="receiptLogoEnabled" class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900 h-4 w-4">
                                        <span class="text-[13px] font-semibold text-gray-800">Show Logo on Receipts</span>
                                    </label>
                                    <label class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-100 rounded-xl cursor-pointer hover:bg-white transition-all shadow-sm">
                                        <input type="checkbox" wire:model.live="receiptShowVat" class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900 h-4 w-4">
                                        <span class="text-[13px] font-semibold text-gray-800">Show VAT on Receipts</span>
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <label class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-100 rounded-xl cursor-pointer hover:bg-white transition-all shadow-sm">
                                        <input type="checkbox" wire:model.live="showReceiptQrCode" class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900 h-4 w-4">
                                        <span class="text-[13px] font-semibold text-gray-800">Show Receipt QR Code</span>
                                    </label>
                                    <label class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-100 rounded-xl cursor-pointer hover:bg-white transition-all shadow-sm">
                                        <input type="checkbox" wire:model.live="showReceiptFooter" class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900 h-4 w-4">
                                        <span class="text-[13px] font-semibold text-gray-800">Show Receipt Footer</span>
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <label class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-100 rounded-xl cursor-pointer hover:bg-white transition-all shadow-sm">
                                        <input type="checkbox" wire:model.live="showReceiptTendered" class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900 h-4 w-4">
                                        <span class="text-[13px] font-semibold text-gray-800">Show Tendered Amount</span>
                                    </label>
                                    <label class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-100 rounded-xl cursor-pointer hover:bg-white transition-all shadow-sm">
                                        <input type="checkbox" wire:model.live="showReceiptChange" class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900 h-4 w-4">
                                        <span class="text-[13px] font-semibold text-gray-800">Show Change</span>
                                    </label>
                                </div>

                                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                                        <div>
                                        <x-input-label for="receiptCopies" value="Print Copies (1–3)" />
                                        <div class="mt-1 flex items-center h-10 border border-gray-200 rounded-lg overflow-hidden shadow-sm bg-white">
                                            <button type="button" wire:click="decrementReceiptCopies"
                                                @if((int)$receiptCopies <= 1) disabled @endif
                                                class="w-10 h-full flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-900 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-white transition-colors border-r border-gray-200 shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                            </button>
                                            <input id="receiptCopies" type="number" readonly tabindex="-1"
                                                wire:model="receiptCopies"
                                                class="flex-1 w-full h-full text-center text-[13px] font-bold text-gray-900 border-0 focus:ring-0 bg-transparent cursor-default [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                            <button type="button" wire:click="incrementReceiptCopies"
                                                @if((int)$receiptCopies >= 3) disabled @endif
                                                class="w-10 h-full flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-900 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-white transition-colors border-l border-gray-200 shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                        </div>
                                        <x-input-error :messages="$errors->get('receiptCopies')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="receiptQrUrl" value="Review QR URL" />
                                        <x-text-input id="receiptQrUrl" wire:model.blur="receiptQrUrl" class="mt-1 block w-full h-10" placeholder="https://..." />
                                        <x-input-error :messages="$errors->get('receiptQrUrl')" class="mt-1" />
                                    </div>
                                </div>

                                <div>
                                    <x-input-label for="customerReceiptTitle" value="Customer Receipt Title" />
                                    <x-text-input id="customerReceiptTitle" wire:model.blur="customerReceiptTitle" class="mt-1 block w-full h-10" placeholder="Customer Receipt & Invoice" />
                                    <x-input-error :messages="$errors->get('customerReceiptTitle')" class="mt-1" />
                                </div>

                                <div class="border-t border-gray-100 pt-4 mt-4 space-y-4">
                                    <h3 class="text-[12px] font-bold text-gray-700 uppercase tracking-wider">Production Slips Configuration</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label for="kitchenSlipTitle" value="Kitchen Slip Title" />
                                            <x-text-input id="kitchenSlipTitle" wire:model.blur="kitchenSlipTitle" class="mt-1 block w-full h-10" placeholder="🍳 KITCHEN SLIP" />
                                            <x-input-error :messages="$errors->get('kitchenSlipTitle')" class="mt-1" />
                                        </div>
                                        <div>
                                            <x-input-label for="kitchenSlipSubtitle" value="Kitchen Slip Subtitle" />
                                            <x-text-input id="kitchenSlipSubtitle" wire:model.blur="kitchenSlipSubtitle" class="mt-1 block w-full h-10" placeholder="Food Preparation Order" />
                                            <x-input-error :messages="$errors->get('kitchenSlipSubtitle')" class="mt-1" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label for="baristaSlipTitle" value="Barista Slip Title" />
                                            <x-text-input id="baristaSlipTitle" wire:model.blur="baristaSlipTitle" class="mt-1 block w-full h-10" placeholder="☕ BARISTA SLIP" />
                                            <x-input-error :messages="$errors->get('baristaSlipTitle')" class="mt-1" />
                                        </div>
                                        <div>
                                            <x-input-label for="baristaSlipSubtitle" value="Barista Slip Subtitle" />
                                            <x-text-input id="baristaSlipSubtitle" wire:model.blur="baristaSlipSubtitle" class="mt-1 block w-full h-10" placeholder="Beverage Preparation Order" />
                                            <x-input-error :messages="$errors->get('baristaSlipSubtitle')" class="mt-1" />
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-gray-100 pt-4 mt-4 space-y-4">
                                    <div>
                                        <x-input-label for="receiptFooterMessage" value="Footer Greeting" />
                                        <x-text-input id="receiptFooterMessage" wire:model.blur="receiptFooterMessage" class="mt-1 block w-full h-10" placeholder="Thank you for your visit!" />
                                        <x-input-error :messages="$errors->get('receiptFooterMessage')" class="mt-1" />
                                    </div>

                                    <div>
                                        <x-input-label for="receiptReturnPolicy" value="Terms & Policy" />
                                        <x-text-input id="receiptReturnPolicy" wire:model.blur="receiptReturnPolicy" class="mt-1 block w-full h-10" placeholder="No return, no exchange." />
                                        <x-input-error :messages="$errors->get('receiptReturnPolicy')" class="mt-1" />
                                    </div>
                                </div>

                                <div class="mt-8 flex justify-end gap-2 pt-6 border-t border-gray-50">
                                    <x-primary-button wire:click="validateBeforeSave" wire:loading.attr="disabled" wire:target="validateBeforeSave" class="h-11">Save Design</x-primary-button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Virtual Receipt Preview --}}
                    <div class="xl:col-span-1">
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100 flex flex-col items-center">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-4">Live Preview</span>
                            <div class="w-full max-w-[240px] min-w-0 overflow-hidden bg-white shadow-md p-4 pt-6 pb-8 font-mono text-[10px] text-gray-800 relative receipt-paper border border-gray-100"
                                 x-data="{ 
                                     subtotal: 320.00, 
                                     vatRate: 0,
                                     currency: '₱',
                                     serviceCharge: 16.00,
                                     businessName: @entangle('businessName').live,
                                     businessEmail: @entangle('businessEmail').live,
                                     businessPhone: @entangle('businessPhone').live,
                                     logoEnabled: @entangle('receiptLogoEnabled').live,
                                     showVat: @entangle('receiptShowVat').live,
                                     logoUrl: @js($businessLogo ? $businessLogo->temporaryUrl() : ($existingLogo ? Storage::url($existingLogo) : null)),
                                                                          footer: @entangle('receiptFooterMessage').live,
                                     policy: @entangle('receiptReturnPolicy').live,
                                     showFooter: @entangle('showReceiptFooter').live,
                                     showQrCode: @entangle('showReceiptQrCode').live,
                                     showTendered: @entangle('showReceiptTendered').live,
                                     showChange: @entangle('showReceiptChange').live,
                                     customerTitle: @entangle('customerReceiptTitle').live
                                 }"
                                 @businessconfigupdated.window="logoUrl = $event.detail.logo_url; businessName = $event.detail.business_name">
                                <div class="text-center mb-4">
                                    <div x-show="logoEnabled" class="mb-2 flex justify-center">
                                        <img x-show="logoUrl" :src="logoUrl" class="w-10 h-10 object-contain">
                                        <div x-show="!logoUrl" class="w-8 h-8 bg-black rounded flex items-center justify-center text-white font-bold text-sm">
                                            <span>MTC</span>
                                        </div>
                                    </div>
                                    <p class="font-bold text-[11px]" x-text="businessName || 'Your Business Name'"></p>
                                    <p class="text-[8px] text-gray-500 mt-0.5" x-text="'+63 ' + (businessPhone || '912 345 6789')"></p>
                                    <p class="w-full min-w-0 break-all text-[8px] text-gray-500 leading-tight" x-text="businessEmail || 'contact@mistertakoyaki.com'"></p>
                                </div>

                                <div class="border-y border-dashed border-gray-200 py-1.5 mb-3 space-y-0.5 text-[9px]">
                                    <div class="flex justify-between"><span>#10425</span><span>{{ now()->format('d/m/y H:i') }}</span></div>
                                    <div class="flex justify-between font-bold" style="font-size:9px; margin-top:4px;">
                                        <span style="text-transform:uppercase; letter-spacing:0.05em;">⬛ DINE-IN</span>
                                    </div>
                                    <div class="text-center font-bold text-[8px] text-gray-500 uppercase tracking-wider pt-0.5" x-text="customerTitle || 'Customer Receipt & Invoice'"></div>
                                </div>

                                <div class="space-y-1 mb-3">
                                    <div class="flex justify-between"><span>1x Original Takoyaki</span><span x-text="currency + '120.00'"></span></div>
                                    <div class="flex justify-between"><span>2x Cheese Takoyaki</span><span x-text="currency + '200.00'"></span></div>
                                </div>

                                <div class="border-t border-dashed border-gray-200 pt-1.5 mb-3 space-y-0.5">
                                    <div class="flex justify-between"><span>Subtotal</span><span x-text="currency + subtotal.toFixed(2)"></span></div>

                                    <div class="flex justify-between text-[9px] text-gray-500">
                                        <span>Service Charge (5%)</span>
                                        <span x-text="currency + serviceCharge.toFixed(2)"></span>
                                    </div>

                                    <div class="flex justify-between font-bold text-[10px] pt-1 mt-1 border-t border-gray-100">
                                        <span>TOTAL</span>
                                        <span x-text="currency + (subtotal + serviceCharge).toFixed(2)"></span>
                                    </div>

                                    <div x-show="showTendered" class="flex justify-between text-[9px] text-gray-500">
                                        <span>Cash Tendered</span>
                                        <span x-text="currency + '500.00'"></span>
                                    </div>
                                    <div x-show="showChange" class="flex justify-between text-[9px] text-gray-500">
                                        <span>Change</span>
                                        <span x-text="currency + '164.00'"></span>
                                    </div>
                                </div>

                                                                <div class="text-center mt-4">
                                    <div x-show="showFooter">
                                        <p class="font-bold italic text-gray-700 text-[8px]" x-text="footer || 'Thank you for your visit!'"></p>
                                        <p class="text-[7px] text-gray-600 mt-1" x-text="policy || 'No return, no exchange.'"></p>
                                    </div>

                                    <div x-show="showQrCode" class="mt-2 pt-2 border-t border-gray-200">
                                        <p class="text-[7px] text-gray-600 mb-1">Scan to Review:</p>
                                        @if($sampleQrCode)
                                            <div class="bg-white p-1 inline-block border border-gray-300 mx-auto">
                                                <img src="{{ $sampleQrCode }}" alt="Review QR Code" class="w-14 h-14 block">
                                            </div>
                                        @else
                                            <svg class="w-12 h-12 mx-auto border border-gray-300" viewBox="0 0 100 100">
                                                <!-- Fallback QR placeholder -->
                                                <rect width="100" height="100" fill="white"/>
                                                <rect x="10" y="10" width="30" height="30" fill="black"/>
                                                <rect x="60" y="10" width="30" height="30" fill="black"/>
                                                <rect x="10" y="60" width="30" height="30" fill="black"/>
                                                <rect x="40" y="40" width="20" height="20" fill="black"/>
                                                <circle cx="50" cy="50" r="5" fill="white"/>
                                            </svg>
                                        @endif
                                        <p class="text-[6.5px] text-gray-600 mt-1 break-all">Review us: {{ url('/review') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Inventory Controls --}}
            <div x-show="tab === 'inventory'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-[13px] font-bold text-gray-900 uppercase tracking-widest mb-6 border-b border-gray-50 pb-2">Inventory Operations</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div>
                            <x-input-label for="lowStockThreshold" value="Low Stock Warning" />
                            <x-text-input id="lowStockThreshold" type="number" min="1" wire:model.blur="lowStockThreshold" class="mt-1 block w-full h-10" placeholder="e.g. 10" :hasError="$errors->has('lowStockThreshold')" />
                            <x-input-error :messages="$errors->get('lowStockThreshold')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="criticalStockThreshold" value="Emergency Level" />
                            <x-text-input id="criticalStockThreshold" type="number" min="1" wire:model.blur="criticalStockThreshold" class="mt-1 block w-full h-10" placeholder="e.g. 5" />
                            <x-input-error :messages="$errors->get('criticalStockThreshold')" class="mt-1" />
                            <p class="mt-2 text-[10px] text-gray-400 font-medium italic">Must be less than Low Stock Warning</p>
                        </div>
                        <div>
                            <x-input-label for="expiryAlertDays" value="Expiry Warning (Days)" />
                            <x-text-input id="expiryAlertDays" type="number" min="1" max="365" wire:model.blur="expiryAlertDays" class="mt-1 block w-full h-10" placeholder="e.g. 7" />
                            <x-input-error :messages="$errors->get('expiryAlertDays')" class="mt-1" />
                        </div>
                    </div>
                    <div class="mt-6">
                        <label class="flex items-center gap-3 cursor-pointer select-none group">
                            <input type="checkbox" wire:model.live="autoReorderEnabled" class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900 h-4 w-4">
                            <div>
                                <span class="block text-[13px] font-bold text-gray-800 group-hover:text-indigo-600 transition-colors">Auto-Notifications</span>
                                <span class="block text-[11px] text-gray-500">Alert managers when stock is critical.</span>
                            </div>
                        </label>
                    </div>

                    <div class="mt-8 flex justify-end gap-2 pt-6 border-t border-gray-50">
                        <x-primary-button wire:click="validateBeforeSave" wire:loading.attr="disabled" wire:target="validateBeforeSave" class="h-11">Save Controls</x-primary-button>
                    </div>
                </div>
            </div>

            {{-- POS Platform --}}
            {{-- POS Platform --}}
            <div x-show="tab === 'pos'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                        <h2 class="text-[13px] font-bold text-gray-900 uppercase tracking-widest mb-6 border-b border-gray-50 pb-2">POS Configuration</h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="discountRate" value="Standard Discount" />
                                    <x-text-input id="discountRate" type="number" step="0.01" min="0" max="1" wire:model.blur="discountRate" class="mt-1 block w-full h-10" placeholder="0.10" />
                                    <x-input-error :messages="$errors->get('discountRate')" class="mt-1" />
                                    <p class="mt-1 text-[10px] text-gray-400 font-medium italic">Decimal format: 10% → 0.10</p>
                                </div>
                                <div>
                                    <x-input-label for="seniorDiscountRate" value="Senior/PWD Discount" />
                                    <x-text-input id="seniorDiscountRate" type="number" step="0.01" min="0" max="1" wire:model.blur="seniorDiscountRate" class="mt-1 block w-full h-10" placeholder="0.10" />
                                    <x-input-error :messages="$errors->get('seniorDiscountRate')" class="mt-1" />
                                    <p class="mt-1 text-[10px] text-gray-400 font-medium italic">Decimal format: 20% → 0.20</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-1 gap-4">
                                <div>
                                    <x-input-label for="serviceCharge" value="Service Charge Rate" />
                                    <x-text-input id="serviceCharge" type="text" wire:model.blur="serviceCharge" class="mt-1 block w-full h-10" placeholder="0.00" x-on:input="restrictInput($event)" :hasError="$errors->has('serviceCharge')" />
                                    <x-input-error :messages="$errors->get('serviceCharge')" class="mt-1" />
                                    <p class="mt-1 text-[10px] text-gray-400 font-medium italic">e.g. 0.05 for 5%</p>
                                </div>
                            </div>


                            <div class="sm:col-span-2 space-y-6 pt-4 border-t border-gray-50">
                               <div>
                                    <x-input-label value="Active Order Modes" />
                                    <p class="text-[11px] text-gray-400 font-medium mt-0.5 mb-2">Toggle which order types are available at checkout.</p>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                        @foreach(\App\Livewire\SystemSettings::ORDER_TYPE_OPTIONS as $type)
                                            @php $isOn = in_array($type, $posOrderTypes); @endphp
                                            <button type="button" wire:click="toggleOrderType('{{ $type }}')" wire:key="order-type-{{ $type }}"
                                                class="flex items-center justify-between gap-2 h-11 px-3.5 rounded-xl border text-[12px] font-bold transition-all
                                                    {{ $isOn ? 'bg-gray-900 border-gray-900 text-white shadow-sm' : 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-gray-100' }}">
                                                <span>{{ $type }}</span>
                                                <span class="w-4 h-4 rounded-full flex items-center justify-center shrink-0 {{ $isOn ? 'bg-white/20' : 'bg-gray-200' }}">
                                                    @if($isOn)
                                                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    @endif
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                    <x-input-error :messages="$errors->get('posOrderTypes')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label value="Accepted Payment Methods" />
                                    <p class="text-[11px] text-gray-400 font-medium mt-0.5 mb-2">Toggle which payment methods are accepted in POS.</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach(\App\Livewire\SystemSettings::PAYMENT_METHOD_OPTIONS as $method)
                                            @php $isOn = in_array($method, $posPaymentMethods); @endphp
                                            <button type="button" wire:click="togglePaymentMethod('{{ $method }}')" wire:key="payment-method-{{ $method }}"
                                                class="flex items-center justify-between gap-2 h-11 px-3.5 rounded-xl border text-[12px] font-bold transition-all
                                                    {{ $isOn ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm' : 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-gray-100' }}">
                                                <span>{{ $method }}</span>
                                                <span class="w-4 h-4 rounded-full flex items-center justify-center shrink-0 {{ $isOn ? 'bg-white/20' : 'bg-gray-200' }}">
                                                    @if($isOn)
                                                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    @endif
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                    <x-input-error :messages="$errors->get('posPaymentMethods')" class="mt-1" />
                                </div>
                            </div>

                            {{-- GCash Integrated Payment --}}
                            <div class="sm:col-span-2 mt-8 pt-6 border-t border-gray-100">
                                <div class="flex items-center gap-2 mb-6">
                                    <div class="p-1.5 bg-blue-100 rounded-lg text-blue-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    </div>
                                    <h3 class="text-[11px] font-black text-gray-900 uppercase tracking-widest">Integrated QR Payments</h3>
                                </div>
                                <div class="flex flex-col sm:flex-row gap-8 items-start">
                                    {{-- QR Upload Box --}}
                                    <div class="flex-shrink-0 flex flex-col items-center">
                                        <div class="relative group">
                                            <div class="w-32 h-32 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden transition-all group-hover:border-blue-400 shadow-inner">
                                                @if($gcashQrImage)
                                                    <img src="{{ $gcashQrImage->temporaryUrl() }}" class="w-full h-full object-contain p-2">
                                                @elseif($existingGcashQrImage)
                                                    <img src="{{ Storage::url($existingGcashQrImage) }}" class="w-full h-full object-contain p-2">
                                                @else
                                                    <div class="text-center p-2">
                                                        <svg class="w-6 h-6 text-gray-200 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m0 11v1m0-6h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        <span class="text-[9px] font-black text-gray-300 uppercase tracking-widest leading-tight">Upload<br>Payment QR</span>
                                                    </div>
                                                @endif
                                                <input type="file" wire:model.live="gcashQrImage" class="absolute inset-0 opacity-0 cursor-pointer" accept=".jpg,.jpeg,.png,.webp,image/*">
                                            </div>
                                        </div>
                                        <x-input-error :messages="$errors->get('gcashQrImage')" class="mt-2" />
                                    </div>

                                    {{-- Fields --}}
                                    <div class="flex-grow grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label value="GCash Account Name" />
                                            <x-text-input wire:model.blur="gcashAccountName" class="w-full mt-1 h-10" placeholder="e.g. Acme Corp" />
                                            <x-input-error :messages="$errors->get('gcashAccountName')" class="mt-1" />
                                        </div>
                                        <div>
                                            <x-input-label value="Mobile Wallet Number" />
                                            <div class="flex items-center mt-1">
                                                <div class="flex-shrink-0 inline-flex items-center px-3 h-10 rounded-l-lg border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-[13px] font-bold">
                                                    +63
                                                </div>
                                                <x-text-input wire:model.blur="gcashAccountNumber" type="text"
                                                    class="block w-full rounded-l-none" placeholder="912 345 6789" autocomplete="tel"
                                                    inputFilter="number" maxlength="10"
                                                    @keydown="FormFilters.numberKeydown($event)" @paste="FormFilters.numberPaste($event)"
                                                    :hasError="$errors->has('gcashAccountNumber')" />
                                            </div>
                                            <x-input-error :messages="$errors->get('gcashAccountNumber')" class="mt-1" />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <p class="text-[10px] text-gray-400 italic font-medium">If provided, this QR will be displayed at checkout for digital payments.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end gap-2 pt-6 border-t border-gray-50">
                            <x-primary-button wire:click="validateBeforeSave" wire:loading.attr="disabled" wire:target="validateBeforeSave" class="h-11">Update POS Platform</x-primary-button>
                        </div>
                    </div>
                </div>

            {{-- Customer Feedback --}}
            <div x-show="tab === 'reviews'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <div class="xl:col-span-2 space-y-6">
                        <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                            <h2 class="text-[13px] font-bold text-gray-900 uppercase tracking-widest mb-6 border-b border-gray-50 pb-2">Feedback Questionnaire</h2>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 pb-6 border-b border-gray-50">
                                <div>
                                    <x-input-label for="reviewFormTitle" value="Feedback Header" />
                                    <x-text-input id="reviewFormTitle" wire:model.live="reviewFormTitle" class="mt-1 block w-full h-10" placeholder="How was your experience?" />
                                    <x-input-error :messages="$errors->get('reviewFormTitle')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="reviewFormSubtitle" value="Sub-header / Thank You" />
                                    <x-text-input id="reviewFormSubtitle" wire:model.live="reviewFormSubtitle" class="mt-1 block w-full h-10" placeholder="Thank you for your feedback!" />
                                    <x-input-error :messages="$errors->get('reviewFormSubtitle')" class="mt-1" />
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Question Stack</h3>
                                    <x-primary-button wire:click="addReviewQuestion" class="h-8 px-3 text-[10px] font-black uppercase tracking-widest">+ Add Field</x-primary-button>
                                </div>

                                <div class="space-y-3 overflow-y-auto pr-1 custom-scrollbar-slate" style="max-height: 480px;">
@forelse($reviewQuestions as $index => $question)
                                        <div wire:key="review-question-{{ $index }}" class="p-4 bg-gray-50 border border-gray-100 rounded-xl space-y-3 group hover:bg-white transition-all shadow-sm">
                                            <div class="flex items-start gap-4">
                                                <div class="flex-grow space-y-2">
                                                    <x-input-label value="Question Prompt" />
                                                    <x-text-input wire:model.live="reviewQuestions.{{ $index }}.text" class="block w-full h-10 bg-white" placeholder="e.g. Rate our service quality" />
                                                </div>
                                                <button wire:click="removeReviewQuestion({{ $index }})" class="mt-8 text-gray-400 hover:text-rose-500 transition-colors">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <x-input-label value="Response Type" />
                                                    <div class="relative mt-1"
                                                        x-data="{
                                                            open: false,
                                                            openUpward: false,
                                                            position() {
                                                                const trigger = this.$refs.responseTypeTrigger;
                                                                const panel = this.$refs.responseTypePanel;
                                                                if (!trigger || !panel) return;

                                                                const r = trigger.getBoundingClientRect();
                                                                const gap = 6;
                                                                const panelHeight = panel.offsetHeight;
                                                                const spaceBelow = window.innerHeight - r.bottom;
                                                                const spaceAbove = r.top;

                                                                this.openUpward = spaceBelow < (panelHeight + gap) && spaceAbove > spaceBelow;
                                                            },
                                                            async openDropdown() {
                                                                this.open = true;
                                                                await this.$nextTick();
                                                                this.position();
                                                            },
                                                            closeDropdown() {
                                                                this.open = false;
                                                            }
                                                        }"
                                                        @click.outside="closeDropdown()"
                                                        @keydown.escape.window="closeDropdown()">

                                                        <button type="button" x-ref="responseTypeTrigger"
                                                            @click="open ? closeDropdown() : openDropdown()"
                                                            class="w-full flex items-center justify-between px-3 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm hover:border-indigo-300 focus:outline-none transition-all h-10">
                                                            <span class="truncate text-gray-900 font-medium">
                                                                @if(($question['type'] ?? 'rating') === 'rating') Star Rating (1-5)
                                                                @elseif(($question['type'] ?? '') === 'text') Open Text Comment
                                                                @else Multiple Choice
                                                                @endif
                                                            </span>
                                                            <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                        </button>

                                                        {{-- Stays in normal DOM flow, inside the Question Stack's
                                                             scrollable container — no teleport. Absolutely positioned
                                                             relative to this wrapper so it overlays nearby content
                                                             without pushing other fields, and flips to open above
                                                             the trigger when there isn't enough room below. --}}
                                                        <div x-show="open" x-cloak x-ref="responseTypePanel"
                                                            x-transition:enter="transition ease-out duration-100"
                                                            x-transition:enter-start="opacity-0"
                                                            x-transition:enter-end="opacity-100"
                                                            x-transition:leave="transition ease-in duration-75"
                                                            x-transition:leave-start="opacity-100"
                                                            x-transition:leave-end="opacity-0"
                                                            :class="openUpward ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                                            class="absolute left-0 w-full z-50 bg-white rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 p-1.5 max-h-40 overflow-y-auto custom-scrollbar">
                                                            <a href="#" wire:click.prevent="$set('reviewQuestions.{{ $index }}.type', 'rating')" @click="closeDropdown()"
                                                                class="block px-4 py-2 text-[13px] rounded-lg hover:bg-slate-50 transition-colors text-slate-700">
                                                                Star Rating (1-5)
                                                            </a>
                                                            <a href="#" wire:click.prevent="$set('reviewQuestions.{{ $index }}.type', 'text')" @click="closeDropdown()"
                                                                class="block px-4 py-2 text-[13px] rounded-lg hover:bg-slate-50 transition-colors text-slate-700">
                                                                Open Text Comment
                                                            </a>
                                                            <a href="#" wire:click.prevent="$set('reviewQuestions.{{ $index }}.type', 'multiple')" @click="closeDropdown()"
                                                                class="block px-4 py-2 text-[13px] rounded-lg hover:bg-slate-50 transition-colors text-slate-700">
                                                                Multiple Choice
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center pt-6">
                                                    <label class="flex items-center gap-3 cursor-pointer group">
                                                        <input type="checkbox" wire:model.live="reviewQuestions.{{ $index }}.required" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-4 w-4">
                                                        <span class="text-[12px] font-bold text-gray-600 group-hover:text-indigo-600 transition-colors">Required Field</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <x-empty-state compact title="No questions defined" description="Click add field to start building your form." />
                                    @endforelse
                                </div>
                            </div>

                            <div class="mt-8 flex justify-end gap-2 pt-6 border-t border-gray-50">
                                <x-primary-button wire:click="validateBeforeSave" wire:loading.attr="disabled" wire:target="validateBeforeSave" class="h-11">Save Feedback Config</x-primary-button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 text-white shadow-2xl relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-4 opacity-10">
                                <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                            </div>
                            <h3 class="text-[11px] font-black text-gray-500 uppercase tracking-widest mb-6">Device Preview</h3>
                            
                            <div class="bg-white rounded-xl p-5 text-gray-900 space-y-4 shadow-xl border border-gray-100">
                                <div class="text-center">
                                    <h4 class="text-[15px] font-black tracking-tight leading-tight">{{ $reviewFormTitle ?: 'How was your experience?' }}</h4>
                                    <p class="text-[11px] text-gray-500 mt-1 font-medium">{{ $reviewFormSubtitle ?: 'Thank you for your feedback!' }}</p>
                                </div>

                                <div class="space-y-5 py-2 overflow-y-auto pr-1 custom-scrollbar-slate" style="max-height: 340px;">
                                    @foreach($reviewQuestions as $q)
                                        <div class="space-y-2">
                                            <p class="text-[12px] font-bold text-gray-800 leading-tight">{{ $q['text'] ?: 'New Question Prompt' }}</p>
                                            @if(($q['type'] ?? 'rating') === 'rating')
                                                <div class="flex gap-1.5">
                                                    @for($i=0; $i<5; $i++)
                                                        <svg class="w-5 h-5 text-amber-400 drop-shadow-sm" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                    @endfor
                                                </div>
                                            @else
                                                <div class="w-full h-10 bg-gray-50 border border-gray-100 rounded-lg"></div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <button disabled class="w-full py-2.5 bg-indigo-600 text-white rounded-lg text-[11px] font-black uppercase tracking-widest opacity-50 cursor-not-allowed shadow-lg">Submit Feedback</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Thermal Printer Settings --}}
            @if($this->isSuperAdmin())
            <div x-show="tab === 'printer'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                                        <h2 class="text-[13px] font-bold text-gray-900 uppercase tracking-widest mb-6 border-b border-gray-50 pb-2">Thermal Printer</h2>

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 gap-4">
                            <label class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-100 rounded-xl cursor-pointer hover:bg-emerald-100 transition-all">
                                <input type="checkbox" wire:model.live="printerEnabled" class="rounded border-emerald-300 text-emerald-600 shadow-sm focus:ring-emerald-600 h-5 w-5">
                                <div class="flex-1">
                                    <p class="text-[13px] font-semibold text-gray-900">Use a receipt printer</p>
                                    <p class="text-[12px] text-gray-600 mt-1">Receipts print through the connected POS device after payment.</p>
                                </div>
                            </label>
                        </div>

                        @if($printerEnabled)
                        <div>
                            <label class="block text-[12px] font-semibold text-gray-700 mb-3">Add a device</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="relative flex items-center gap-3 p-3 border rounded-lg cursor-pointer {{ $printerType === 'bluetooth' ? 'border-gray-900 bg-gray-50' : 'border-gray-200 hover:border-gray-300' }}">
                                    <input type="radio" wire:model.live="printerType" value="bluetooth" class="text-gray-900">
                                    <div>
                                        <p class="text-[12px] font-semibold text-gray-900">Bluetooth</p>
                                        <p class="text-[11px] text-gray-500">Pair this POS terminal with a printer</p>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 p-3 border rounded-lg {{ $printerType === 'wired' ? 'border-gray-900 bg-gray-50' : 'border-gray-200 hover:border-gray-300' }}">
                                    <input type="radio" wire:model.live="printerType" value="wired" class="text-gray-900">
                                    <div>
                                        <p class="text-[12px] font-semibold text-gray-900">Wired</p>
                                        <p class="text-[11px] text-gray-500">USB printer connected to this Windows PC</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        @if($printerType === 'wired')
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-[12px] font-semibold text-gray-700">Available wired printers</label>
                                <button type="button" wire:click="refreshAvailablePrinters" wire:loading.attr="disabled" wire:target="refreshAvailablePrinters" class="text-[11px] font-semibold text-blue-600 hover:text-blue-700 px-2 py-1 rounded hover:bg-blue-50 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="refreshAvailablePrinters">Refresh</span>
                                    <span wire:loading wire:target="refreshAvailablePrinters">Checking...</span>
                                </button>
                            </div>
                            @if(!empty($availablePrinters))
                                <div class="space-y-2">
                                    @foreach($availablePrinters as $printer)
                                        <button type="button" wire:click="$set('printerName', '{{ addslashes($printer) }}')" class="w-full flex items-center justify-between text-left px-3 py-2 text-[12px] border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-all {{ $printerName === $printer ? 'border-gray-900 bg-gray-50' : '' }}">
                                            <span>{{ $printer }}</span>
                                            @if($printerName === $printer)
                                                <svg class="w-4 h-4 text-gray-900 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                                                        @else
                                <p class="text-[12px] text-gray-500 italic">No wired printer was detected on this Windows PC.</p>
                            @endif
                        </div>
                        @error('printerName') <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p> @enderror
                        @endif

                        @if($printerType === 'bluetooth')
                        <div x-data="btPrinterCard()" x-init="init()">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                                <p class="text-[11px] text-blue-900 leading-relaxed">
                                    <strong>Per-device pairing:</strong> this only pairs the printer with <em>this browser, on this device</em>. Repeat "Add Device" on every POS terminal that needs to print.
                                </p>
                            </div>

                            <label class="block text-[12px] font-semibold text-gray-700 mb-3">This Device's Paired Printer</label>

                            <template x-if="!btSupported">
                                <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-[11px] text-rose-700 mb-3">
                                    Web Bluetooth isn't supported in this browser. Use Chrome or Edge on Windows or Android.
                                </div>
                            </template>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <template x-if="deviceName">
                                    <div class="flex flex-col items-center text-center gap-2 p-5 border rounded-xl" :class="btConnected ? 'border-emerald-300 bg-emerald-50' : 'border-gray-200 bg-white'">
                                        <div class="w-12 h-12 rounded-full flex items-center justify-center" :class="btConnected ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-400'">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                        </div>
                                        <p class="text-[13px] font-bold text-gray-900" x-text="deviceName"></p>
                                        <p class="text-[11px]" :class="btConnected ? 'text-emerald-600' : 'text-gray-400'" x-text="btStatusLabel"></p>
                                        <div class="flex flex-wrap justify-center gap-2 mt-1">
                                            <button type="button" @click="btConnected ? btDisconnect() : btAddDevice()" :disabled="btBusy || !btSupported"
                                                class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition-colors disabled:opacity-50"
                                                :class="btConnected ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-gray-900 text-white hover:bg-gray-800'">
                                                <span x-show="!btBusy" x-text="btConnected ? 'Disconnect' : 'Connect'"></span>
                                                <span x-show="btBusy">Working…</span>
                                            </button>
                                            <button type="button" @click="btTestPrint()" :disabled="btBusy || !btConnected" class="px-3 py-1.5 rounded-lg text-[11px] font-bold text-blue-600 hover:bg-blue-50 disabled:opacity-30 disabled:cursor-not-allowed">
                                                Test Print
                                            </button>
                                            <button type="button" @click="btForget()" :disabled="btBusy" class="px-3 py-1.5 rounded-lg text-[11px] font-bold text-rose-600 hover:bg-rose-50 disabled:opacity-50">
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <button type="button" @click="btAddDevice()" :disabled="btBusy || !btSupported"
                                    class="flex flex-col items-center justify-center gap-2 p-5 border-2 border-dashed border-gray-200 rounded-xl text-gray-400 hover:border-blue-300 hover:text-blue-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" /></svg>
                                    <span class="text-[11px] font-bold uppercase tracking-wide" x-text="btBusy ? 'Scanning…' : 'Add Device'"></span>
                                </button>
                            </div>

                            <template x-if="btError">
                                <p class="mt-3 text-[11px] text-rose-600 font-medium" x-text="btError"></p>
                            </template>
                        </div>
                        @endif

                        @if($printerType !== 'bluetooth')
                        {{-- Test Printer --}}
                        <div class="flex gap-3">
                            <button type="button" wire:click="testPrinterConnection"
                                    @if($printerType === 'wired' && !$windowsPrintingAvailable) disabled @endif
                                    class="flex-1 bg-amber-500 hover:bg-amber-600 text-white text-[12px] font-semibold py-2.5 px-4 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                🧪 Test Printer Connection
                            </button>
                        </div>

                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <p class="text-[11px] text-amber-900">
                                <strong>Note:</strong> After configuring your printer, click "Test Printer Connection" to verify it's working correctly. A test receipt will be printed.
                            </p>
                        </div>
                        @endif
                        @endif
                    </div>

                    {{-- Save Button --}}
                    <div class="mt-8 flex justify-end gap-2 pt-6 border-t border-gray-50">
                        <x-primary-button wire:click="validateBeforeSave" wire:loading.attr="disabled" wire:target="validateBeforeSave" class="h-11">Save Printer Settings</x-primary-button>
                    </div>
                </div>
            </div>
            @endif
            {{-- Access Control --}}
            @if($this->isSuperAdmin())
            <div x-show="tab === 'system'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                        <h2 class="text-[13px] font-bold text-gray-900 uppercase tracking-widest mb-6 border-b border-gray-50 pb-2">Module Visibility</h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="flex flex-col gap-2 p-4 bg-gray-50 border border-gray-100 rounded-xl cursor-pointer hover:bg-white transition-all shadow-sm">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" wire:model.live="hideOperationalModules" class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900 h-4 w-4">
                                    <span class="text-[13px] font-bold text-gray-800">Hide Operational View</span>
                                </div>
                                <span class="text-[11px] text-gray-500 ml-7">Simplify your sidebar by hiding POS, orders, and kitchen displays. This only affects your view.</span>
                            </label>

                            @if(auth()->user()->role_id === 1)
                                <div class="flex flex-col gap-2 p-4 bg-gray-50 border border-gray-100 rounded-xl transition-all shadow-sm">
                                    <x-input-label value="Super Admin Operating Branch" class="!text-[13px] !font-bold !text-gray-800" />
                                    <p class="text-[11px] text-gray-500 mb-2 mt-[-4px]">Select which branch context you are operating in.</p>
                                    
                                    <x-dropdown align="left" width="full" containerClasses="block w-full">
                                        <x-slot name="trigger">
                                            <button type="button" class="mt-1 flex items-center justify-between w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-[13px] text-gray-700 shadow-sm hover:border-gray-300 focus:outline-none transition-all h-10">
                                                <span>{{ $opBranchId ? $branches->firstWhere('id', $opBranchId)?->branch_name : 'General Headquarters (No Branch)' }}</span>
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                        </x-slot>
                                        <x-slot name="content">
                                            <x-dropdown-link href="#" wire:click.prevent="$set('opBranchId', '')">General Headquarters (No Branch)</x-dropdown-link>
                                            <hr class="border-gray-100">
                                            @forelse($branches as $branch)
                                                <x-dropdown-link href="#" wire:click.prevent="$set('opBranchId', {{ $branch->id }})">{{ $branch->branch_name }}</x-dropdown-link>
                                            @empty
                                                <x-empty-state compact title="No branches" description="" />
                                            @endforelse
                                        </x-slot>
                                    </x-dropdown>
                                    <x-input-error :messages="$errors->get('opBranchId')" class="mt-1" />
                                </div>
                            @endif
                        </div>

                        <div class="mt-8 flex justify-end gap-2 pt-6 border-t border-gray-50">
                            <x-primary-button wire:click="validateBeforeSave" wire:loading.attr="disabled" wire:target="validateBeforeSave" class="h-11">Save System State</x-primary-button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Activity Audit --}}
            @if($this->isSuperAdmin())
            <div x-show="tab === 'logs'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mt-2">
                        <div class="px-5 py-4 border-b border-gray-100 bg-white">
                            <h2 class="text-[13px] font-bold text-gray-900 uppercase tracking-widest leading-none">Activity Audit Log</h2>
                        </div>

                        <div class="overflow-x-auto min-h-[400px]">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-white">
                                        <th class="py-3 px-4 border-r border-gray-100 text-[12px] font-medium text-gray-500 tracking-wide">User Participant</th>
                                        <th class="py-3 px-4 border-r border-gray-100 text-[12px] font-medium text-gray-500 tracking-wide text-center">Operation</th>
                                        <th class="py-3 px-4 border-r border-gray-100 text-[12px] font-medium text-gray-500 tracking-wide">Resource</th>
                                        <th class="py-3 px-4 text-right text-[12px] font-medium text-gray-500 tracking-wide">Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 border-b border-gray-200 relative"
                                    wire:loading.class="opacity-50 pointer-events-none">
                                    @foreach($logs as $log)
                                        <tr wire:key="audit-log-{{ $log['id'] }}-{{ $logs->currentPage() }}" 
                                            class="hover:bg-gray-50/50 transition-colors">
                                            <td class="py-3 px-4 border-r border-gray-100 whitespace-nowrap">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-[26px] h-[26px] rounded-full bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-500 border border-gray-200">{{ strtoupper(substr($log['user'], 0, 1)) }}</div>
                                                    <span class="text-[13px] font-bold text-gray-900">{{ $log['user'] }}</span>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4 border-r border-gray-100 whitespace-nowrap text-center">
                                                @php
                                                    $colors = [
                                                        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                        'info' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                        'warning' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                    ];
                                                    $cls = $colors[$log['status']] ?? $colors['info'];
                                                @endphp
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase border {{ $cls }} tracking-tight">{{ $log['action'] }}</span>
                                            </td>
                                            <td class="py-3 px-4 border-r border-gray-100 whitespace-nowrap text-[13px] text-gray-500 font-medium italic">{{ $log['target'] }}</td>
                                            <td class="py-3 px-4 text-right text-[12px] text-gray-400 font-black tabular-nums">{{ $log['time'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <x-pagination :paginator="$logs" />
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Save Confirmation Modal ── --}}
    <x-modal name="confirm-update-settings" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Update System Settings</h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">Apply changes to the <span class="font-bold text-indigo-600 uppercase tracking-tight">{{ $tab }}</span> configuration?</p>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-update-settings')" class="h-10">Cancel</x-secondary-button>
                <x-primary-button 
                    @click="$dispatch('close-modal', 'confirm-update-settings')"
                    wire:click="updateSettings" 
                    class="h-10">
                    Confirm & Save
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- ── Map Selection Modal ── --}}
    <x-modal name="map-modal" maxWidth="2xl" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Geographic Locator</h3>
                    <p class="text-[11px] text-gray-500 mt-0.5 uppercase tracking-wider font-bold">Laguna Province Boundary</p>
                </div>
                <button @click="$dispatch('close-modal', 'map-modal')" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18" /></svg>
                </button>
            </div>
            
            <div id="userMap" class="w-full h-[400px] rounded-xl border border-gray-200 shadow-inner z-10" wire:ignore></div>
            
            <div class="mt-5 p-4 bg-indigo-50 border border-indigo-100 rounded-xl flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-indigo-600 shrink-0 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <h4 class="text-[12px] font-bold text-indigo-900">How to use</h4>
                    <p class="text-[11px] text-indigo-700/80 leading-relaxed">Click anywhere on the map to drop a pin. The system will automatically resolve the address components (Region, City, Street) using reverse-geocoding.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 mt-6">
                <x-primary-button @click="$dispatch('close-modal', 'map-modal')" class="h-10 px-8">Confirm Pin Location</x-primary-button>
            </div>
        </div>
    </x-modal>
    <style>
        .custom-scrollbar-slate::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar-slate::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar-slate::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
    <script>
    (function() {
        const registerSettingsData = () => { if (Alpine.data('systemSettingsData')) return; Alpine.data('systemSettingsData', (initialData) => ({ ...initialData,
                // ── Location state ───────────────────────────────────────
                loc: {
                    region:   { items: [], search: '', loading: false },
                    province: { items: [], search: '', loading: false },
                    city:     { items: [], search: '', loading: false },
                    barangay: { items: [], search: '', loading: false },
                    noProvince: false
                },

                getCustomPinIcon() {
                    return L.divIcon({
                        html: `
                            \x3cdiv class="relative flex flex-col items-center justify-end w-10 h-10"\x3e
                                \x3cspan class="absolute w-4 h-2 bg-indigo-500/40 rounded-full blur-[2px] animate-ping bottom-[-2px] left-1/2 -translate-x-1/2"\x3e\x3c/span\x3e
                                \x3cdiv class="relative w-8 h-8 bg-indigo-600 rounded-t-full rounded-bl-full rotate-45 border-2 border-white shadow-lg flex items-center justify-center transition-all duration-300"\x3e
                                    \x3cdiv class="w-3.5 h-3.5 bg-white rounded-full -rotate-45 flex items-center justify-center shadow-inner"\x3e
                                        \x3cdiv class="w-1.5 h-1.5 bg-indigo-600 rounded-full"\x3e\x3c/div\x3e
                                    \x3c/div\x3e
                                \x3c/div\x3e
                            \x3c/div\x3e
                        `,
                        className: 'custom-leaflet-icon',
                        iconSize: [40, 40],
                        iconAnchor: [20, 40]
                    });
                },

                filtered(type) {
                    const s = this.loc[type];
                    const q = s.search.toLowerCase();
                    return q ? s.items.filter(i => i.name.toLowerCase().includes(q)) : s.items;
                },

                restrictInput(event) {
                    let value = event.target.value.replace(/[^0-9.]/g, '');
                    const parts = value.split('.');
                    if (parts.length > 2) {
                        value = parts[0] + '.' + parts.slice(1).join('');
                    }
                    event.target.value = value;
                    event.target.dispatchEvent(new Event('input'));
                },

                // ── PSGC loaders ─────────────────────────────────────────
                async fetchWithRetry(url, retries = 2, delay = 1000) {
                    try {
                        const cached = sessionStorage.getItem(url);
                        if (cached) return JSON.parse(cached);
                    } catch (e) { console.error('Cache read failed:', e); }

                    for (let i = 0; i <= retries; i++) {
                        try {
                            const res = await fetch(url);
                            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                            const data = await res.json();
                            try {
                                sessionStorage.setItem(url, JSON.stringify(data));
                            } catch (e) { console.error('Cache write failed:', e); }
                            return data;
                        } catch (e) {
                            if (i === retries) throw e;
                            console.warn(`Fetch failed for ${url}, retrying (${i + 1}/${retries})...`, e);
                            await new Promise(resolve => setTimeout(resolve, delay * (i + 1)));
                        }
                    }
                },

                async loadRegions() {
                    if (this.loc.region.items.length > 0) return;
                    this.loc.region.loading = true;
                    try {
                        const data = await this.fetchWithRetry('https://psgc.cloud/api/regions');
                        this.loc.region.items = data.sort((a, b) => a.name.localeCompare(b.name));
                    } catch (e) { 
                        console.error('Regions fetch failed after retries', e);
                        this.dispatchNotification('error', 'Failed to load regions. Please check your internet connection.');
                    }
                    finally { this.loc.region.loading = false; }
                },

                async loadProvinces(regionCode) {
                    this.loc.province.items = []; this.loc.city.items = []; this.loc.barangay.items = [];
                    this.loc.noProvince = false;
                    if (!regionCode) return;
                    this.loc.province.loading = true;
                    try {
                        const data = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${regionCode}/provinces`);
                        this.loc.province.items = data.sort((a, b) => a.name.localeCompare(b.name));
                        
                        if (this.loc.province.items.length === 0) {
                            this.loc.noProvince = true;
                            this.loc.province.loading = false;
                            this.loc.city.loading = true;
                            const data2 = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${regionCode}/cities-municipalities`);
                            this.loc.city.items = data2.sort((a, b) => a.name.localeCompare(b.name));
                            this.loc.city.loading = false;
                        }
                    } catch (e) { 
                        console.error('Provinces fetch failed', e);
                        this.dispatchNotification('error', 'Failed to load provinces.');
                    }
                    finally { this.loc.province.loading = false; }
                },

                async loadCities(provinceCode) {
                    this.loc.city.items = []; this.loc.barangay.items = [];
                    if (!provinceCode) return;
                    this.loc.city.loading = true;
                    try {
                        const data = await this.fetchWithRetry(`https://psgc.cloud/api/provinces/${provinceCode}/cities-municipalities`);
                        this.loc.city.items = data.sort((a, b) => a.name.localeCompare(b.name));
                    } catch (e) { 
                        console.error('Cities fetch failed', e);
                        this.dispatchNotification('error', 'Failed to load cities.');
                    }
                    finally { this.loc.city.loading = false; }
                },

                async loadBarangays(cityCode) {
                    this.loc.barangay.items = [];
                    if (!cityCode) return;
                    this.loc.barangay.loading = true;
                    try {
                        const data = await this.fetchWithRetry(`https://psgc.cloud/api/cities-municipalities/${cityCode}/barangays`);
                        this.loc.barangay.items = data.sort((a, b) => a.name.localeCompare(b.name));
                    } catch (e) { 
                        console.error('Barangays fetch failed', e);
                        this.dispatchNotification('error', 'Failed to load barangays.');
                    }
                    finally { this.loc.barangay.loading = false; }
                },

                dispatchNotification(type, message) {
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { type, message }
                    }));
                },

                // ── Cascade handlers ─────────────────────────────────────
                async selectRegion(region, fromMap = false) {
                    this.addr_region = region.name;
                    this.addr_province = ''; this.addr_city = ''; this.addr_barangay = '';
                    this.loc.province.items = []; this.loc.city.items = []; this.loc.barangay.items = [];
                    this.loc.noProvince = false;
                    
                    if (!fromMap) this.geocodeAddress();
                    
                    this.loc.province.loading = true;
                    try {
                        const data = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${region.code}/provinces`);
                        this.loc.province.items = data.sort((a, b) => a.name.localeCompare(b.name));
                        if (this.loc.province.items.length === 0) {
                            this.loc.noProvince = true;
                            this.loc.province.loading = false;
                            this.loc.city.loading = true;
                            const data2 = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${region.code}/cities-municipalities`);
                            this.loc.city.items = data2.sort((a, b) => a.name.localeCompare(b.name));
                            this.loc.city.loading = false;
                        }
                    } catch (e) { console.error(e); }
                    finally { this.loc.province.loading = false; }
                },
                
                async selectProvince(province, fromMap = false) {
                    this.addr_province = province.name;
                    this.addr_city = ''; this.addr_barangay = '';
                    
                    if (!fromMap) this.geocodeAddress();
                    
                    this.loc.city.loading = true;
                    try {
                        const data = await this.fetchWithRetry(`https://psgc.cloud/api/provinces/${province.code}/cities-municipalities`);
                        this.loc.city.items = data.sort((a, b) => a.name.localeCompare(b.name));
                    } catch (e) { console.error(e); }
                    finally { this.loc.city.loading = false; }
                },
                
                async selectCity(city, fromMap = false) {
                    this.addr_city = city.name;
                    this.addr_barangay = '';
                    
                    if (!fromMap) this.geocodeAddress();
                    
                    this.loc.barangay.loading = true;
                    try {
                        const data = await this.fetchWithRetry(`https://psgc.cloud/api/cities-municipalities/${city.code}/barangays`);
                        this.loc.barangay.items = data.sort((a, b) => a.name.localeCompare(b.name));
                    } catch (e) { console.error(e); }
                    finally { this.loc.barangay.loading = false; }
                },
                
                selectBarangay(brgy, fromMap = false) {
                    this.addr_barangay = brgy.name;
                    if (!fromMap) this.geocodeAddress();
                },

                async geocodeAddress() {
                    const parts = [];
                    if (this.addr_barangay) parts.push(this.addr_barangay);
                    if (this.addr_city) parts.push(this.addr_city);
                    if (this.addr_province) parts.push(this.addr_province);
                    if (this.addr_region) parts.push(this.addr_region);
                    
                    if (parts.length === 0) return;
                    
                    const query = parts.join(', ') + ', Philippines';
                    try {
                        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=ph`);
                        const data = await response.json();
                        
                        if (data && data.length > 0) {
                            const lat = parseFloat(data[0].lat);
                            const lng = parseFloat(data[0].lon);
                            
                            this.$wire.set('addr_lat', lat);
                            this.$wire.set('addr_lng', lng);
                            
                            if (this.map) {
                                let zoom = 11;
                                if (this.addr_barangay) zoom = 16;
                                else if (this.addr_city) zoom = 14;
                                else if (this.addr_province) zoom = 12;
                                
                                this.map.setView([lat, lng], zoom);
                                if (this.marker) {
                                    this.marker.setLatLng([lat, lng]);
                                } else {
                                    this.marker = L.marker([lat, lng], { icon: this.getCustomPinIcon() }).addTo(this.map);
                                }
                            }
                        }
                    } catch (e) {
                        console.error('Forward geocoding failed:', e);
                    }
                },

                async autoMatchLocation(addr) {
                    const clean = (str) => {
                        if (!str) return '';
                        return str.toLowerCase()
                            .replace(/city of|province of|region|district|barangay|brgy\.?|municipality of/g, '')
                            .replace(/[^a-z0-9]/g, '')
                            .trim();
                    };

                    let rName = addr.region || '';
                    let pName = addr.state || addr.province || addr.county || '';
                    let cName = addr.city || addr.town || addr.municipality || '';
                    let bName = addr.quarter || addr.village || addr.suburb || addr.neighbourhood || '';

                    if (this.loc.region.items.length === 0) await this.loadRegions();

                    let crName = clean(rName);
                    let cpName = clean(pName);
                    let matchedR = this.loc.region.items.find(r => {
                        const target = clean(r.name);
                        return target === crName || 
                               (crName.includes('manila') && target.includes('ncr')) ||
                               (cpName.includes('manila') && target.includes('ncr'));
                    });

                    if (!matchedR && crName) {
                        matchedR = this.loc.region.items.find(r => clean(r.name).includes(crName) || crName.includes(clean(r.name)));
                    }

                    if (!matchedR) return;
                    await this.selectRegion(matchedR, true);

                    if (pName && this.loc.province.items.length > 0) {
                        let cppName = clean(pName);
                        let matchedP = this.loc.province.items.find(p => clean(p.name) === cppName);
                        if (!matchedP) {
                            matchedP = this.loc.province.items.find(p => {
                                const target = clean(p.name).replace('province', '');
                                const search = cppName.replace('province', '');
                                return target && search && (target === search || target.includes(search) || search.includes(target));
                            });
                        }
                        if (matchedP) await this.selectProvince(matchedP, true);
                    }

                    if (cName && this.loc.city.items.length > 0) {
                        let ccName = clean(cName);
                        let matchedC = this.loc.city.items.find(c => clean(c.name) === ccName);
                        if (!matchedC) {
                            matchedC = this.loc.city.items.find(c => {
                                const target = clean(c.name).replace('city', '').replace('municipality', '').replace('city of', '');
                                const search = ccName.replace('city', '').replace('municipality', '').replace('city of', '');
                                return target && search && (target === search || target.includes(search) || search.includes(target));
                            });
                        }
                        if (matchedC) await this.selectCity(matchedC, true);
                    }

                    if (bName && this.loc.barangay.items.length > 0) {
                        let cbName = clean(bName);
                        let matchedB = this.loc.barangay.items.find(b => clean(b.name) === cbName);
                        if (!matchedB) {
                            matchedB = this.loc.barangay.items.find(b => {
                                const target = clean(b.name).replace('barangay', '').replace('brgy', '').replace('poblacion', '').replace('pob', '');
                                const search = cbName.replace('barangay', '').replace('brgy', '').replace('poblacion', '').replace('pob', '');
                                return target && search && (target === search || target.includes(search) || search.includes(target));
                            });
                        }
                        if (matchedB) this.selectBarangay(matchedB, true);
                    }
                },

                map: null,
                marker: null,
                initMap() {
                    if (typeof L === 'undefined') {
                        setTimeout(() => this.initMap(), 100);
                        return;
                    }
                    if (this.map) {
                        setTimeout(() => {
                            const container = document.getElementById('userMap');
                            if (!container) return;
                            this.map.invalidateSize(); 
                            let component = Livewire.find('{{ $this->getId() }}');
                            let lat = component.addr_lat;
                            let lng = component.addr_lng;
                            if (lat && lng) {
                                this.map.setView([lat, lng], 16);
                                if (this.marker) this.marker.setLatLng([lat, lng]);
                            }
                        }, 250);
                        return;
                    }
                    setTimeout(() => {
                        const container = document.getElementById('userMap');
                        if (!container) return;

                        let component = Livewire.find('{{ $this->getId() }}');
                        let lat = component.addr_lat;
                        let lng = component.addr_lng;
                        let startLat = lat || 14.2189;
                        let startLng = lng || 121.1672;
                        let startZoom = lat ? 15 : 11;
                        
                        const lagunaBounds = L.latLngBounds([13.9, 120.9], [14.5, 121.6]);
                        
                        const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            minZoom: 10,
                            attribution: '© OpenStreetMap'
                        });
                        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
                        });

                        this.map = L.map('userMap', {
                            maxBounds: lagunaBounds,
                            maxBoundsViscosity: 1.0,
                            layers: [street]
                        }).setView([startLat, startLng], startZoom);

                        L.control.layers({ "Street": street, "Satellite": satellite }).addTo(this.map);
                        
                        if (lat && lng) {
                            this.marker = L.marker([lat, lng], { icon: this.getCustomPinIcon() }).addTo(this.map);
                        }

                        this.map.on('click', async (e) => {
                            const lat = e.latlng.lat;
                            const lng = e.latlng.lng;
                            if (this.marker) this.marker.setLatLng(e.latlng);
                            else this.marker = L.marker(e.latlng, { icon: this.getCustomPinIcon() }).addTo(this.map);
                            
                            Livewire.find('{{ $this->getId() }}').set('addr_lat', lat);
                            Livewire.find('{{ $this->getId() }}').set('addr_lng', lng);
                            
                            try {
                                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&countrycodes=ph`);
                                const data = await response.json();
                                if (data && data.address) {
                                    let st = data.address.road || data.address.pedestrian || '';
                                    let num = data.address.house_number || '';
                                    let fst = (num + ' ' + st).trim();
                                    if(fst) Livewire.find('{{ $this->getId() }}').set('addr_street', fst);
                                    
                                    await this.autoMatchLocation(data.address);
                                }

                            } catch (error) { console.error(error); }
                        });
                    }, 350);
                },

                async initializeExistingAddress() {
                    if (this.loc.region.items.length === 0) await this.loadRegions();
                    if (!this.addr_region) return;

                    const region = this.loc.region.items.find(r => r.name === this.addr_region);
                    if (!region) return;

                    try {
                        const data = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${region.code}/provinces`);
                        this.loc.province.items = data.sort((a, b) => a.name.localeCompare(b.name));
                        if (this.loc.province.items.length === 0) {
                            this.loc.noProvince = true;
                            const data2 = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${region.code}/cities-municipalities`);
                            this.loc.city.items = data2.sort((a, b) => a.name.localeCompare(b.name));
                        }
                    } catch (e) { console.error('Preload provinces failed', e); }

                    if (this.addr_province && this.loc.province.items.length > 0) {
                        const province = this.loc.province.items.find(p => p.name === this.addr_province);
                        if (province) {
                            try {
                                const data = await this.fetchWithRetry(`https://psgc.cloud/api/provinces/${province.code}/cities-municipalities`);
                                this.loc.city.items = data.sort((a, b) => a.name.localeCompare(b.name));
                            } catch (e) { console.error('Preload cities failed', e); }
                        }
                    }

                    if (this.addr_city && this.loc.city.items.length > 0) {
                        const city = this.loc.city.items.find(c => c.name === this.addr_city);
                        if (city) {
                            try {
                                const data = await this.fetchWithRetry(`https://psgc.cloud/api/cities-municipalities/${city.code}/barangays`);
                                this.loc.barangay.items = data.sort((a, b) => a.name.localeCompare(b.name));
                            } catch (e) { console.error('Preload barangays failed', e); }
                        }
                    }
                },

                init() {
                    this.initializeExistingAddress();
                }
            }));
        };
        if (window.Alpine) registerSettingsData();
        else document.addEventListener('alpine:init', registerSettingsData);
    })();
    </script>

    <script>
    (function() {
        const registerBtPrinterCard = () => { if (Alpine.data('btPrinterCard')) return; Alpine.data('btPrinterCard', () => ({
            btSupported: false,
            btConnected: false,
            btBusy: false,
            btError: null,
            deviceName: null,
            supportCheck: null,
            btStatusLabel() {
                if (this.btBusy) return 'Working…';
                if (this.btConnected) return 'Connected';
                return this.deviceName ? 'Not connected' : '';
            },
            refreshSupport() {
                this.btSupported = 'bluetooth' in navigator;
            },
            init() {
                this.refreshSupport();
                this.deviceName = localStorage.getItem('thermal_printer_name');
                this.btConnected = !!(window.thermalBluetoothPrinter?.characteristic);
                window.addEventListener('thermal-bt-client-ready', () => this.refreshSupport());
                window.addEventListener('thermal-bt-connected', (e) => {
                    this.btConnected = true;
                    this.deviceName = e.detail?.name || this.deviceName;
                    this.btError = null;
                });
                window.addEventListener('thermal-bt-disconnected', () => { this.btConnected = false; });
                window.addEventListener('thermal-bt-error', (e) => {
                    this.btConnected = false;
                    this.btError = e.detail?.message || 'Could not connect to the printer.';
                });
                this.supportCheck = setInterval(() => {
                    if (!window.thermalBluetoothPrinter) return;
                    this.refreshSupport();
                    clearInterval(this.supportCheck);
                }, 250);
            },
            async btAddDevice() {
                this.btBusy = true; this.btError = null;
                try {
                    if (!window.thermalBluetoothPrinter) throw new Error('Bluetooth printer service is still loading. Please try again.');
                    const name = await window.thermalBluetoothPrinter.connect();
                    this.deviceName = name || 'Thermal Printer';
                    this.btConnected = true;
                    window.dispatchEvent(new CustomEvent('thermal-bt-connected', { detail: { name: this.deviceName } }));
                } catch (e) {
                    if (e?.name !== 'NotFoundError') this.btError = e?.message || 'Could not connect to the printer.';
                } finally { this.btBusy = false; }
            },
            async btDisconnect() {
                this.btBusy = true;
                try { await window.thermalBluetoothPrinter.disconnect(); this.btConnected = false; }
                finally { this.btBusy = false; }
            },
            async btTestPrint() {
                this.btBusy = true; this.btError = null;
                try { await window.thermalBluetoothPrinter.printTestPage(); }
                catch (e) { this.btError = e?.message || 'Test print failed.'; }
                finally { this.btBusy = false; }
            },
            btForget() {
                localStorage.removeItem('thermal_printer_name');
                this.deviceName = null; this.btConnected = false; this.btError = null;
                if (window.thermalBluetoothPrinter?.device?.gatt?.connected) {
                    window.thermalBluetoothPrinter.disconnect();
                }
            },
            destroy() {
                clearInterval(this.supportCheck);
            }
        })); };
        if (window.Alpine) registerBtPrinterCard();
        else document.addEventListener('alpine:init', registerBtPrinterCard);
    })();
    </script>
</div>

