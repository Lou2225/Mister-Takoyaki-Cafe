{{-- ── Archive Confirmation Modal ── --}}
<x-modal name="archive-user" maxWidth="sm" focusable>
    <div class="h-1 w-full bg-gradient-to-r from-red-400 to-rose-500 rounded-t-lg"></div>
    <div class="p-6">
        <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-50 border border-red-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 8h14M5 8a2 2 0 01-2-2V4a2 2 0 012-2h14a2 2 0 012 2v2a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
            </div>
            <div>
                <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Archive User</h3>
                <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">
                    This account will be removed from the active directory and lose sign-in access. Their order and sales history stays intact, and you can restore them later from the Archived tab.
                </p>
            </div>
        </div>
        <div class="px-3 py-2 bg-white border border-gray-100 rounded-lg text-[13px] text-gray-600 mb-4">
            <span class="font-semibold text-gray-800">{{ $archiveTargetName }}</span>
        </div>
        <div>
            <x-input-label for="archive_reason" value="Reason (optional)" />
            <x-text-input id="archive_reason" wire:model="archiveReason" type="text" class="mt-1 block w-full h-10" placeholder="e.g. Resigned, End of contract" maxlength="150" />
        </div>
        <div class="flex items-center justify-end gap-2 mt-5">
            <x-secondary-button @click="$dispatch('close-modal', 'archive-user')">Cancel</x-secondary-button>
            <x-danger-button wire:click="archiveUser" @click="$dispatch('close-modal', 'archive-user')">Archive</x-danger-button>
        </div>
    </div>
</x-modal>

{{-- ── Restore Confirmation Modal ── --}}
<x-modal name="restore-user" maxWidth="sm" focusable>
    <div class="h-1 w-full bg-gradient-to-r from-emerald-400 to-teal-500 rounded-t-lg"></div>
    <div class="p-6" x-data="{ restoring: false }" @open-modal.window="if ($event.detail === 'restore-user') restoring = false">
        <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
            <div>
                <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Restore User</h3>
                <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">
                    This account will reappear in the Team Directory. They will still need to be manually reactivated before they can sign in again.
                </p>
            </div>
        </div>
        <div class="px-3 py-2 bg-white border border-gray-100 rounded-lg text-[13px] text-gray-600 mb-5">
            <span class="font-semibold text-gray-800">{{ $restoreTargetName }}</span>
        </div>
        <div class="flex items-center justify-end gap-2">
            <x-secondary-button @click="$dispatch('close-modal', 'restore-user'); restoring = false">Cancel</x-secondary-button>
            <x-primary-button
                type="button"
                x-bind:disabled="restoring"
                @click="restoring = true; $wire.restoreUser().then(() => { restoring = false; $dispatch('close-modal', 'restore-user'); })"
                class="bg-emerald-600 hover:bg-emerald-700 min-w-[100px] flex justify-center">
                <span x-show="!restoring">Restore</span>
                <span x-show="restoring" x-cloak>
                    <svg class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Restoring...
                </span>
            </x-primary-button>
        </div>
    </div>
</x-modal>

{{-- ── Status Toggle Confirmation Modal ── --}}
<x-modal name="toggle-status" maxWidth="sm" focusable>
    <div class="h-1 w-full bg-gradient-to-r {{ $statusTargetActive ? 'from-rose-400 to-red-500' : 'from-emerald-400 to-teal-500' }} rounded-t-lg"></div>
    <div class="p-6">
        <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-full border flex items-center justify-center {{ $statusTargetActive ? 'bg-rose-50 border-rose-100' : 'bg-emerald-50 border-emerald-100' }}">
                @if($statusTargetActive)
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                @else
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                @endif
            </div>
            <div>
                <h3 class="text-[15px] font-bold text-gray-900 leading-tight">
                    {{ $statusTargetActive ? 'Deactivate Account' : 'Activate Account' }}
                </h3>
                <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">
                    @if($statusTargetActive)
                        This user will immediately lose sign-in access and be signed out of any active sessions.
                    @else
                        This user will regain sign-in access to the system.
                    @endif
                </p>
            </div>
        </div>
        <div class="px-3 py-2 bg-white border border-gray-100 rounded-lg text-[13px] text-gray-600 mb-5">
            <span class="font-semibold text-gray-800">{{ $statusTargetName }}</span>
        </div>
        <div class="flex items-center justify-end gap-2">
            <x-secondary-button @click="$dispatch('close-modal', 'toggle-status')">Cancel</x-secondary-button>
            @if($statusTargetActive)
                <x-danger-button wire:click="toggleStatus" @click="$dispatch('close-modal', 'toggle-status')">Deactivate</x-danger-button>
            @else
                <x-primary-button wire:click="toggleStatus" @click="$dispatch('close-modal', 'toggle-status')">Activate</x-primary-button>
            @endif
        </div>
    </div>
</x-modal>

{{-- ── Manager Conflict Warning Modal ── --}}
<x-modal name="confirm-manager-replace" maxWidth="sm" focusable>
    <div class="h-1 w-full bg-gradient-to-r from-amber-400 to-orange-500 rounded-t-lg"></div>
    <div class="p-6" x-data="{ saving: false }" @open-modal.window="if ($event.detail === 'confirm-manager-replace') saving = false">
        <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-50 border border-amber-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Replace Branch Manager?</h3>
                <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">
                    The selected branch already has a manager: <br>
                    <span class="font-bold text-gray-800">{{ $conflictingManagerName }}</span>
                </p>
                <p class="mt-2 text-[12px] text-gray-400 leading-relaxed italic">
                    Confirming will reassign this user as the new branch manager and clear the previous assignment.
                </p>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2">
            <x-secondary-button @click="$dispatch('close-modal', 'confirm-manager-replace'); saving = false">Cancel</x-secondary-button>
            <x-primary-button
                type="button"
                x-bind:disabled="saving"
                @click="saving = true; $wire.replaceManager().then(() => { saving = false })"
                class="bg-amber-600 hover:bg-amber-700 min-w-[140px] flex justify-center">
                <span x-show="!saving">Replace & Save</span>
                <span x-show="saving" x-cloak>
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                </span>
            </x-primary-button>
        </div>
    </div>
</x-modal>

{{-- ── Save Confirmation Modal ── --}}
<x-modal name="confirm-save-user" maxWidth="sm" focusable>
    <div class="h-1 w-full bg-gradient-to-r from-emerald-400 to-teal-500 rounded-t-lg"></div>
    <div class="p-6" x-data="{ saving: false }" @open-modal.window="if ($event.detail === 'confirm-save-user') saving = false">
        <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div>
                <h3 class="text-[15px] font-bold text-gray-900 leading-tight" x-text="mode === 'edit' ? 'Update User' : 'Register User'"></h3>
                <p class="mt-1 text-[13px] text-gray-500 leading-relaxed" x-text="mode === 'edit' ? 'Apply changes to this profile?' : 'Register this new user into the system?'"></p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 mt-6">
            <x-secondary-button @click="$dispatch('close-modal', 'confirm-save-user'); saving = false" class="h-10">Cancel</x-secondary-button>
            <x-primary-button
                type="button"
                x-bind:disabled="saving"
                @click="saving = true; $wire.{{ $editUserId ? 'updateUser' : 'saveUser' }}().then(() => { saving = false })"
                class="h-10 min-w-[150px] flex justify-center">
                <span x-show="!saving">Confirm & Save</span>
                <span x-show="saving" x-cloak>
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                </span>
            </x-primary-button>
        </div>
    </div>
</x-modal>

{{-- ── Order Detail Side Panel ── --}}
<x-side-panel name="view-order-detail" width="max-w-md">
    @if($viewingOrder)
        <div class="flex flex-col h-full bg-white">
            {{-- Premium Header --}}
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/40">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white shadow-lg shadow-slate-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-black text-slate-900 tracking-tight leading-none">Order Details</h3>
                        <span class="text-[11px] text-indigo-600 font-bold uppercase tracking-wider mt-1 block">Ref: #{{ $viewingOrder->reference_no }}</span>
                    </div>
                </div>
                <button @click="$dispatch('close-modal', 'view-order-detail')" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-slate-600 hover:shadow-sm transition-all border border-transparent hover:border-slate-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Content Area --}}
            <div class="flex-1 overflow-y-auto custom-scrollbar">
                {{-- Transaction Context Section --}}
                <div class="p-6 bg-gradient-to-b from-slate-50/80 to-white border-b border-slate-50">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1.5">Branch Context</span>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <span class="text-[12px] font-bold text-slate-700">{{ $viewingOrder->branch->branch_name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1.5">Status</span>
                            <div class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full {{ $viewingOrder->status === 'Completed' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                <span class="text-[11px] font-bold {{ $viewingOrder->status === 'Completed' ? 'text-emerald-600' : 'text-slate-600' }} uppercase">{{ $viewingOrder->status }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1.5">Cashier</span>
                            <span class="text-[12px] font-bold text-slate-700">{{ $viewingOrder->user->first_name ?? 'System' }} {{ $viewingOrder->user->last_name ?? '' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1.5">Timestamp</span>
                            <span class="text-[12px] font-bold text-slate-700">{{ $viewingOrder->created_at->format('M d, h:i A') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Itemized Breakdown Section --}}
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h5 class="text-[11px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-900"></span>
                            Order Summary
                        </h5>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">{{ $viewingOrder->items->count() }} Items</span>
                    </div>

                    <div class="space-y-6 relative">
                        {{-- Vertical line --}}
                        <div class="absolute left-[7px] top-2 bottom-2 w-[2px] bg-slate-50 rounded-full"></div>

                        @foreach($viewingOrder->items as $item)
                            <div class="relative pl-7 group">
                                <div class="absolute left-0 top-[6px] w-[16px] h-[16px] rounded-full border-4 border-white bg-slate-100 group-hover:bg-slate-900 transition-colors z-10"></div>
                                
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="text-[13px] font-bold text-slate-900 leading-tight">{{ $item->product->name ?? 'Unknown Item' }}</p>
                                        @if($item->options->isNotEmpty())
                                            <div class="flex flex-wrap gap-x-2 gap-y-0.5 mt-1">
                                                @foreach($item->options as $opt)
                                                    <span class="text-[11px] text-slate-400 italic">{{ $opt->option->name ?? 'N/A' }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        <p class="text-[11px] font-bold text-slate-400 mt-1">₱{{ number_format($item->price, 2) }} × {{ $item->quantity }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[13px] font-black text-slate-900">₱{{ number_format($item->subtotal, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Financial Totals Section --}}
                <div class="p-6 bg-slate-50 border-t border-slate-100">
                    <div class="space-y-2.5">
                        <div class="flex justify-between text-[12px] font-medium text-slate-500">
                            <span>Subtotal</span>
                            <span class="font-bold text-slate-700">₱{{ number_format($viewingOrder->total_amount + $viewingOrder->discount_amount - $viewingOrder->tax_amount, 2) }}</span>
                        </div>
                        @if($viewingOrder->tax_amount > 0)
                            <div class="flex justify-between text-[12px] font-medium text-slate-500">
                                <span>Tax (Included)</span>
                                <span class="font-bold text-slate-700">₱{{ number_format($viewingOrder->tax_amount, 2) }}</span>
                            </div>
                        @endif
                        @if($viewingOrder->discount_amount > 0)
                            <div class="flex justify-between text-[12px] font-bold text-rose-500">
                                <span>Applied Discount</span>
                                <span>-₱{{ number_format($viewingOrder->discount_amount, 2) }}</span>
                            </div>
                        @endif
                        <div class="pt-3 mt-3 border-t border-slate-200 flex justify-between items-baseline">
                            <span class="text-[13px] font-black text-slate-900 uppercase tracking-tight">Grand Total</span>
                            <span class="text-[24px] font-black text-slate-900 tracking-tighter">₱{{ number_format($viewingOrder->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Footer --}}
            <div class="p-5 border-t border-slate-100 bg-white">
                <button @click="$dispatch('close-modal', 'view-order-detail')" class="w-full h-10 flex items-center justify-center gap-2 bg-slate-900 text-white text-[13px] font-bold rounded-xl hover:bg-slate-800 transition-all shadow-lg shadow-slate-200">
                    <span>Done Reviewing</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
            </div>
        </div>
    @endif
</x-side-panel>

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

