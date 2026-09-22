{{-- ════════════════ PANEL 2 — FORM (CREATE/EDIT) ════════════════ --}}
<div x-show="panel === 'form'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h2 class="text-[17px] font-bold text-gray-900 tracking-tight" x-text="mode === 'create' ? 'Register New User' : (mode === 'edit' ? 'Update Profile' : 'View Profile')"></h2>
            <p class="text-[12px] text-gray-500 font-medium" x-text="mode === 'create' ? 'New team member entry' : (mode === 'edit' ? 'Employee reference configuration' : 'Read-only profile view')"></p>
        </div>
        <x-secondary-button @click="panel = 'list'; mode = 'list'; $wire.backToList()" class="h-10">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to List
        </x-secondary-button>
    </div>

    <div x-show="mode !== 'view'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: profile + work records --}}
            <div class="lg:col-span-2 space-y-6">

                <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                    <h2 class="text-[13px] font-semibold text-gray-700 uppercase tracking-wider mb-4">Profile Details</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="f_first_name" value="First Name *" />
                            <x-text-input id="f_first_name" name="first_name" wire:model="firstName"
                                type="text" class="mt-1 block w-full" placeholder="Juan"
                                autocomplete="given-name" inputFilter="nameStrict" maxlength="100"
                                @keydown="FormFilters.nameStrictKeydown($event)" @paste="FormFilters.nameStrictPaste($event)"
                                :hasError="$errors->has('firstName')" />
                            <x-input-error :messages="$errors->get('firstName')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="f_middle_name" value="Middle Name" />
                            <x-text-input id="f_middle_name" name="middle_name" wire:model="middleName"
                                type="text" class="mt-1 block w-full" placeholder="Dela"
                                autocomplete="additional-name" inputFilter="nameStrict" maxlength="100"
                                @keydown="FormFilters.nameStrictKeydown($event)" @paste="FormFilters.nameStrictPaste($event)"
                                :hasError="$errors->has('middleName')" />
                            <x-input-error :messages="$errors->get('middleName')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="f_last_name" value="Last Name *" />
                            <x-text-input id="f_last_name" name="last_name" wire:model="lastName" type="text"
                                class="mt-1 block w-full" placeholder="Cruz" autocomplete="family-name" 
                                inputFilter="nameStrict" maxlength="100"
                                @keydown="FormFilters.nameStrictKeydown($event)" @paste="FormFilters.nameStrictPaste($event)"
                                :hasError="$errors->has('lastName')" />
                            <x-input-error :messages="$errors->get('lastName')" class="mt-1" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <x-input-label for="f_email" value="Email Address *" />
                            <x-text-input id="f_email" name="email" wire:model="email" type="email"
                                class="mt-1 block w-full" placeholder="juan.cruz@company.com"
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
                                <x-text-input id="f_phone" name="phone" wire:model="phone" type="text"
                                    class="block w-full rounded-l-none" placeholder="912 345 6789" autocomplete="tel"
                                    inputFilter="number" maxlength="10"
                                    @keydown="FormFilters.numberKeydown($event)" @paste="FormFilters.numberPaste($event)"
                                    :hasError="$errors->has('phone')" />
                            </div>
                            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                    <h2 class="text-[13px] font-semibold text-gray-700 uppercase tracking-wider mb-4">Work Records</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="f_employee_id" value="Employee ID" />
                            <div class="relative mt-1">
                                <input type="text" id="f_employee_id" readonly 
                                    value="{{ $editUserId ? $employeeId : 'Auto-generated' }}"
                                    class="block w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-gray-400 text-[13px] shadow-sm cursor-not-allowed focus:ring-0 focus:border-gray-200 h-10 select-none pl-8">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div x-show="formRoleId == 3" x-cloak>
                            <x-input-label for="f_position">Staff Role <span class="text-red-500">*</span></x-input-label>
                            <div wire:key="role-dropdown-container">
                                <x-dropdown align="left" width="full" containerClasses="block w-full">
                                    <x-slot name="trigger">
                                        <button id="f_position" type="button" 
                                            class="mt-1 flex items-center justify-between w-full px-3 py-2 bg-white border rounded-lg text-[13px] text-gray-700 shadow-sm hover:border-gray-300 focus:outline-none transition-all h-10 {{ $errors->has('position') ? 'border-red-400 bg-red-50/30' : 'border-gray-200' }}">
                                            <span x-text="position || 'Select role...'">{{ $position ?: 'Select role...' }}</span>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content" class="max-h-60 overflow-y-auto">
                                        @forelse($availablePositions as $pos)
                                            <x-dropdown-link href="#" @click.prevent="position = '{{ $pos }}'; dropdownOpen = false;">
                                                {{ $pos }}
                                            </x-dropdown-link>
                                        @empty
                                            <div class="px-4 py-2 text-[12px] text-gray-400 italic font-medium">No roles configured...</div>
                                        @endforelse
                                    </x-slot>
                                </x-dropdown>
                                <div x-show="!position">
                                    <x-input-error :messages="$errors->get('position')" class="mt-1" />
                                </div>
                            </div>
                        </div>
                        <div>
                            <x-input-label for="f_date_hired" value="Date Joined" />
                            <x-text-input id="f_date_hired" name="date_hired" wire:model="dateHired"
                                type="date" class="mt-1 block w-full" inputFilter="date"
                                @keydown="FormFilters.dateKeydown($event)" @paste="FormFilters.datePaste($event)"
                                :hasError="$errors->has('dateHired')" />
                            <x-input-error :messages="$errors->get('dateHired')" class="mt-1" />
                        </div>
                    </div>
                </div>

                {{-- Section: Address --}}
                <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                    <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-[14px] font-bold text-gray-900 mb-1">Residential Address</h3>
                            <p class="text-[12px] text-gray-500">Provide the geographic location of this team member.</p>
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
                                <div class="relative mt-1" wire:ignore
                                    x-data="{
                                        open: false,
                                        dropUp: false,
                                        checkFlip() {
                                            const rect = this.$el.getBoundingClientRect();
                                            const spaceBelow = window.innerHeight - rect.bottom;
                                            this.dropUp = spaceBelow < 250 && rect.top > 250;
                                        },
                                        openDropdown() {
                                            loadRegions();
                                            this.checkFlip();
                                            this.open = true;
                                            loc.region.search = '';
                                        },
                                        closeDropdown() {
                                            this.open = false;
                                            loc.region.search = '';
                                        }
                                    }"
                                    @click.outside="closeDropdown()"
                                    @keydown.escape.window="closeDropdown()">
                                    <div class="relative flex items-center">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        </div>
                                        <input type="text"
                                            :value="open ? loc.region.search : (addr_region || '')"
                                            @input="loc.region.search = $event.target.value; open = true"
                                            @focus="openDropdown()"
                                            @click="openDropdown()"
                                            placeholder="Search or select region..."
                                            class="w-full pl-10 pr-10 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-10"
                                            :class="addr_region && !open ? 'font-medium text-gray-900' : 'text-gray-800'"
                                            autocomplete="off">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <button type="button"
                                                x-show="addr_region || loc.region.search"
                                                x-cloak
                                                @click.stop="addr_region = ''; loc.region.search = ''; open = false;"
                                                title="Clear selection"
                                                class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div x-show="open" x-cloak
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        :class="dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                        class="absolute left-0 right-0 z-50 bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-48 overflow-y-auto custom-scrollbar">
                                        <template x-for="r in filtered('region')" :key="r.code">
                                            <button type="button"
                                                @click="selectRegion(r); open = false;"
                                                class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors"
                                                :class="addr_region === r.name ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                <span class="text-[13px] font-medium truncate" x-text="r.name"></span>
                                            </button>
                                        </template>
                                        <template x-if="filtered('region').length === 0">
                                            <div class="px-4 py-3 text-[12px] text-slate-400 italic text-center">No regions found</div>
                                        </template>
                                    </div>
                                </div>
                                <x-input-error :messages="$errors->get('addr_region')" class="mt-1" />
                            </div>

                            <div class="flex-1 w-full">
                                <x-input-label value="Province" />
                                <div class="relative mt-1" wire:ignore
                                    x-data="{
                                        open: false,
                                        dropUp: false,
                                        get isDisabled() { return !addr_region || loc.noProvince; },
                                        checkFlip() {
                                            const rect = this.$el.getBoundingClientRect();
                                            const spaceBelow = window.innerHeight - rect.bottom;
                                            this.dropUp = spaceBelow < 250 && rect.top > 250;
                                        },
                                        openDropdown() {
                                            if (this.isDisabled) return;
                                            this.checkFlip();
                                            this.open = true;
                                            loc.province.search = '';
                                        },
                                        closeDropdown() {
                                            this.open = false;
                                            loc.province.search = '';
                                        }
                                    }"
                                    @click.outside="closeDropdown()"
                                    @keydown.escape.window="closeDropdown()">
                                    <div class="relative flex items-center">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        </div>
                                        <input type="text"
                                            :disabled="isDisabled"
                                            :value="loc.noProvince ? 'N/A (Direct to City)' : (open ? loc.province.search : (addr_province || ''))"
                                            @input="loc.province.search = $event.target.value; open = true"
                                            @focus="openDropdown()"
                                            @click="openDropdown()"
                                            placeholder="Search or select province..."
                                            class="w-full pl-10 pr-10 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-10 disabled:bg-gray-50 disabled:opacity-75 disabled:cursor-not-allowed"
                                            :class="addr_province && !open ? 'font-medium text-gray-900' : 'text-gray-800'"
                                            autocomplete="off">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <button type="button"
                                                x-show="!isDisabled && (addr_province || loc.province.search)"
                                                x-cloak
                                                @click.stop="addr_province = ''; loc.province.search = ''; open = false;"
                                                title="Clear selection"
                                                class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div x-show="open" x-cloak
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        :class="dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                        class="absolute left-0 right-0 z-50 bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-48 overflow-y-auto custom-scrollbar">
                                        <template x-for="p in filtered('province')" :key="p.code">
                                            <button type="button"
                                                @click="selectProvince(p); open = false;"
                                                class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors"
                                                :class="addr_province === p.name ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                <span class="text-[13px] font-medium truncate" x-text="p.name"></span>
                                            </button>
                                        </template>
                                        <template x-if="filtered('province').length === 0">
                                            <div class="px-4 py-3 text-[12px] text-slate-400 italic text-center">No provinces found</div>
                                        </template>
                                    </div>
                                </div>
                                <x-input-error :messages="$errors->get('addr_province')" class="mt-1" />
                            </div>
                        </div>

                        {{-- Row 2: City + Barangay --}}
                        <div class="flex flex-col sm:flex-row gap-5">
                            <div class="flex-1 w-full">
                                <x-input-label value="City / Municipality *" />
                                <div class="relative mt-1" wire:ignore
                                    x-data="{
                                        open: false,
                                        dropUp: false,
                                        get isDisabled() { return !addr_region || (!addr_province && !loc.noProvince); },
                                        checkFlip() {
                                            const rect = this.$el.getBoundingClientRect();
                                            const spaceBelow = window.innerHeight - rect.bottom;
                                            this.dropUp = spaceBelow < 250 && rect.top > 250;
                                        },
                                        openDropdown() {
                                            if (this.isDisabled) return;
                                            this.checkFlip();
                                            this.open = true;
                                            loc.city.search = '';
                                        },
                                        closeDropdown() {
                                            this.open = false;
                                            loc.city.search = '';
                                        }
                                    }"
                                    @click.outside="closeDropdown()"
                                    @keydown.escape.window="closeDropdown()">
                                    <div class="relative flex items-center">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        </div>
                                        <input type="text"
                                            :disabled="isDisabled"
                                            :value="open ? loc.city.search : (addr_city || '')"
                                            @input="loc.city.search = $event.target.value; open = true"
                                            @focus="openDropdown()"
                                            @click="openDropdown()"
                                            placeholder="Search or select city..."
                                            class="w-full pl-10 pr-10 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-10 disabled:bg-gray-50 disabled:opacity-75 disabled:cursor-not-allowed"
                                            :class="addr_city && !open ? 'font-medium text-gray-900' : 'text-gray-800'"
                                            autocomplete="off">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <button type="button"
                                                x-show="!isDisabled && (addr_city || loc.city.search)"
                                                x-cloak
                                                @click.stop="addr_city = ''; loc.city.search = ''; open = false;"
                                                title="Clear selection"
                                                class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div x-show="open" x-cloak
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        :class="dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                        class="absolute left-0 right-0 z-50 bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-48 overflow-y-auto custom-scrollbar">
                                        <template x-for="c in filtered('city')" :key="c.code">
                                            <button type="button"
                                                @click="selectCity(c); open = false;"
                                                class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors"
                                                :class="addr_city === c.name ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                <span class="text-[13px] font-medium truncate" x-text="c.name"></span>
                                            </button>
                                        </template>
                                        <template x-if="filtered('city').length === 0">
                                            <div class="px-4 py-3 text-[12px] text-slate-400 italic text-center">No cities found</div>
                                        </template>
                                    </div>
                                </div>
                                <x-input-error :messages="$errors->get('addr_city')" class="mt-1" />
                            </div>

                            <div class="flex-1 w-full">
                                <x-input-label value="Barangay *" />
                                <div class="relative mt-1" wire:ignore
                                    x-data="{
                                        open: false,
                                        dropUp: false,
                                        checkFlip() {
                                            const rect = this.$el.getBoundingClientRect();
                                            const spaceBelow = window.innerHeight - rect.bottom;
                                            this.dropUp = spaceBelow < 250 && rect.top > 250;
                                        },
                                        openDropdown() {
                                            if (!addr_city) return;
                                            this.checkFlip();
                                            this.open = true;
                                            loc.barangay.search = '';
                                        },
                                        closeDropdown() {
                                            this.open = false;
                                            loc.barangay.search = '';
                                        }
                                    }"
                                    @click.outside="closeDropdown()"
                                    @keydown.escape.window="closeDropdown()">
                                    <div class="relative flex items-center">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        </div>
                                        <input type="text"
                                            :disabled="!addr_city"
                                            :value="open ? loc.barangay.search : (addr_barangay || '')"
                                            @input="loc.barangay.search = $event.target.value; open = true"
                                            @focus="openDropdown()"
                                            @click="openDropdown()"
                                            placeholder="Search or select barangay..."
                                            class="w-full pl-10 pr-10 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-10 disabled:bg-gray-50 disabled:opacity-75 disabled:cursor-not-allowed"
                                            :class="addr_barangay && !open ? 'font-medium text-gray-900' : 'text-gray-800'"
                                            autocomplete="off">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <button type="button"
                                                x-show="addr_city && (addr_barangay || loc.barangay.search)"
                                                x-cloak
                                                @click.stop="addr_barangay = ''; loc.barangay.search = ''; open = false;"
                                                title="Clear selection"
                                                class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div x-show="open" x-cloak
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        :class="dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                        class="absolute left-0 right-0 z-50 bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-48 overflow-y-auto custom-scrollbar">
                                        <template x-for="b in filtered('barangay')" :key="b.code">
                                            <button type="button"
                                                @click="selectBarangay(b); open = false;"
                                                class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors"
                                                :class="addr_barangay === b.name ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                <span class="text-[13px] font-medium truncate" x-text="b.name"></span>
                                            </button>
                                        </template>
                                        <template x-if="filtered('barangay').length === 0">
                                            <div class="px-4 py-3 text-[12px] text-slate-400 italic text-center">No barangays found</div>
                                        </template>
                                    </div>
                                </div>
                                <x-input-error :messages="$errors->get('addr_barangay')" class="mt-1" />
                            </div>
                        </div>

                        {{-- Row 3: Street --}}
                        <div>
                            <x-input-label value="House # / Street / Subdivision" />
                            <x-text-input wire:model="addr_street" x-model="addr_street" class="w-full mt-1 h-10" placeholder="e.g. Unit 123, Rosewood Ave, Phase 1" :hasError="$errors->has('addr_street')" />
                            <x-input-error :messages="$errors->get('addr_street')" class="mt-1" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: access + status + actions --}}
            <div class="space-y-6">
                <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                    <h2 class="text-[13px] font-semibold text-gray-700 uppercase tracking-wider mb-4">Access Rights</h2>
                    @if(auth()->user()->role_id === 1)
                        <div class="mb-4">
                            <x-input-label for="f_role" value="Account Type *" />
                            <x-dropdown align="left" width="full" containerClasses="block w-full">
                                <x-slot name="trigger">
                                    <button id="f_role" type="button"
                                        class="mt-1 flex items-center justify-between w-full px-3 py-2 bg-white border rounded-lg text-[13px] text-gray-700 shadow-sm hover:border-gray-300 focus:outline-none transition-all h-10 {{ $errors->has('formRoleId') ? 'border-red-400 bg-red-50/30' : 'border-gray-200' }}">
                                        <span x-text="rolesMap[formRoleId] ? (rolesMap[formRoleId].charAt(0).toUpperCase() + rolesMap[formRoleId].slice(1)) : 'Select account type...'">{{ $formRoleId ? ucfirst($roles->firstWhere('id', $formRoleId)?->name) : 'Select account type...' }}</span>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @forelse($roles as $r)
                                        <x-dropdown-link href="#" @click.prevent="formRoleId = '{{ $r->id }}'; if (formRoleId != 3) position = ''; dropdownOpen = false;">
                                            {{ ucfirst($r->name) }}
                                        </x-dropdown-link>
                                    @empty
                                        <div class="px-4 py-2 text-[12px] text-gray-400 italic font-medium">No roles available...</div>
                                    @endforelse
                                </x-slot>
                            </x-dropdown>
                            <div x-show="!formRoleId">
                                <x-input-error :messages="$errors->get('formRoleId')" class="mt-1" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="f_branch_id" value="Work Location *" />
                            <x-dropdown align="left" width="full" containerClasses="block w-full">
                                <x-slot name="trigger">
                                    <button id="f_branch_id" type="button"
                                        class="mt-1 flex items-center justify-between w-full px-3 py-2 bg-white border rounded-lg text-[13px] text-gray-700 shadow-sm hover:border-gray-300 focus:outline-none transition-all h-10 {{ $errors->has('formBranchId') ? 'border-red-400 bg-red-50/30' : 'border-gray-200' }}">
                                        <span x-text="branchesMap[formBranchId] || 'Select work location...'">{{ $formBranchId ? ($branches->firstWhere('id', $formBranchId)?->branch_name ?? 'Select work location...') : 'Select work location...' }}</span>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content" class="max-h-60 overflow-y-auto">
                                    @forelse($branches as $branch)
                                        <x-dropdown-link href="#" @click.prevent="formBranchId = '{{ $branch->id }}'; dropdownOpen = false;">
                                            {{ $branch->branch_name }}
                                        </x-dropdown-link>
                                    @empty
                                        <div class="px-4 py-2 text-[12px] text-gray-400 italic font-medium">No branches defined...</div>
                                    @endforelse
                                </x-slot>
                            </x-dropdown>
                            <div x-show="!formBranchId">
                                <x-input-error :messages="$errors->get('formBranchId')" class="mt-1" />
                            </div>
                        </div>
                    @else
                        <div class="space-y-3">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="text-[12px] text-gray-500 font-medium">Role</span>
                                <span class="text-[12px] font-bold text-gray-800 bg-gray-100 px-2 py-0.5 rounded-full">Staff</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-[12px] text-gray-500 font-medium">Branch</span>
                                <span class="text-[12px] font-bold text-gray-800">{{ auth()->user()->branch?->branch_name ?? '—' }}</span>
                            </div>
                            @if(!auth()->user()->branch_id)
                                <div class="mt-2 p-3 bg-red-50 border border-red-100 rounded-lg text-[11px] text-red-600 font-medium leading-relaxed">
                                    Your account has no branch assigned, so new staff can't be registered. Contact a Super Admin to assign your account a branch.
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                    <h2 class="text-[13px] font-semibold text-gray-700 uppercase tracking-wider mb-4">Account Status</h2>
                    <label for="f_is_active" class="flex items-center gap-3 cursor-pointer select-none">
                        <input type="checkbox" id="f_is_active" name="is_active" wire:model="formIsActive"
                            class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900 h-4 w-4">
                        <div>
                            <span class="block text-[13px] font-semibold text-gray-800">Active Access</span>
                            <span class="block text-[12px] text-gray-500">Allow user to sign in.</span>
                        </div>
                    </label>
                </div>

                <div class="flex flex-col gap-2">
                    <x-primary-button type="button" wire:click="runPreSaveValidation"
                        :disabled="auth()->user()->isAdmin() && !auth()->user()->branch_id"
                        class="w-full justify-center" x-text="mode === 'edit' ? 'Save Changes' : 'Register User'"></x-primary-button>
                    <x-secondary-button @click="if(mode === 'edit') { mode = 'view'; $wire.showEdit($wire.get('editUserId'), 'view') } else { panel = 'list'; mode = 'list'; $wire.backToList() }" class="w-full justify-center">
                        <span>Cancel</span>
                    </x-secondary-button>

                    @if($editUserId && !$editUserArchived)
                        <div class="bg-red-50 border border-red-100 rounded-2xl p-6 shadow-sm mt-4" wire:key="danger-zone-{{ $editUserId }}">
                            <h2 class="text-[13px] font-bold text-red-600 uppercase tracking-wider mb-2">Danger Zone</h2>
                            <p class="text-[12px] text-gray-500 mb-4 leading-relaxed">Archiving removes this person from the active team directory and revokes sign-in access, but keeps their order and sales history intact. This can be undone later.</p>
                            <x-danger-button 
                                wire:click="confirmArchive({{ $editUserId }}, '{{ addslashes($firstName . ' ' . $lastName) }}')"
                                class="w-full justify-center h-11">
                                Archive Account
                            </x-danger-button>
                        </div>
                    @else
                        <div wire:key="danger-zone-empty"></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

