<div
    x-data="userManagementData($wire, @js($panel), @js($mode), @js($view))"
    @send-email-bg.window="$wire.sendUserEmail($event.detail.userId, $event.detail.password)"
    @trigger-edit.window="$wire.showEdit($event.detail.id, $event.detail.mode || 'edit')"
    @trigger-set-formrole.window="$wire.setFormRoleId($event.detail)"
    @trigger-set-formbranch.window="$wire.setFormBranchId($event.detail)"
    @trigger-set-position.window="$wire.set('position', $event.detail)"
    class="relative">






    <div class="relative min-h-[600px]">
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
                                <x-text-input id="f_first_name" name="first_name" wire:model.blur="firstName"
                                    type="text" class="mt-1 block w-full" placeholder="Juan"
                                    autocomplete="given-name" inputFilter="nameStrict" maxlength="100"
                                    @keydown="FormFilters.nameStrictKeydown($event)" @paste="FormFilters.nameStrictPaste($event)"
                                    :hasError="$errors->has('firstName')" />
                                <x-input-error :messages="$errors->get('firstName')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="f_middle_name" value="Middle Name" />
                                <x-text-input id="f_middle_name" name="middle_name" wire:model.blur="middleName"
                                    type="text" class="mt-1 block w-full" placeholder="Dela"
                                    autocomplete="additional-name" inputFilter="nameStrict" maxlength="100"
                                    @keydown="FormFilters.nameStrictKeydown($event)" @paste="FormFilters.nameStrictPaste($event)"
                                    :hasError="$errors->has('middleName')" />
                                <x-input-error :messages="$errors->get('middleName')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="f_last_name" value="Last Name *" />
                                <x-text-input id="f_last_name" name="last_name" wire:model.blur="lastName" type="text"
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
                                <x-text-input id="f_email" name="email" wire:model.blur="email" type="email"
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
                                    <x-text-input id="f_phone" name="phone" wire:model.blur="phone" type="text"
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
                            <div x-show="$wire.formRoleId == 3" x-cloak>
    <x-input-label for="f_position">Staff Role <span class="text-red-500">*</span></x-input-label>
                                <div wire:key="role-dropdown-container">
                                    <x-dropdown align="left" width="full" containerClasses="block w-full">
                                        <x-slot name="trigger">
                                            <button id="f_position" type="button" 
                                                class="mt-1 flex items-center justify-between w-full px-3 py-2 bg-white border rounded-lg text-[13px] text-gray-700 shadow-sm hover:border-gray-300 focus:outline-none transition-all h-10 {{ $errors->has('position') ? 'border-red-400 bg-red-50/30' : 'border-gray-200' }}">
                                                <span>{{ $position ?: 'Select role...' }}</span>
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </x-slot>
                                        <x-slot name="content" class="max-h-60 overflow-y-auto">
                                            @forelse($availablePositions as $pos)
    <x-dropdown-link href="#" @click.prevent="dropdownOpen = false; $dispatch('trigger-set-position', '{{ $pos }}')">
        {{ $pos }}
    </x-dropdown-link>
@empty
                                                <div class="px-4 py-2 text-[12px] text-gray-400 italic font-medium">No roles configured...</div>
                                            @endforelse
                                        </x-slot>
                                    </x-dropdown>
                                    <x-input-error :messages="$errors->get('position')" class="mt-1" />
                                </div>
                            </div>
                            <div>
                                <x-input-label for="f_date_hired" value="Date Joined" />
                                <x-text-input id="f_date_hired" name="date_hired" wire:model.blur="dateHired"
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
                                            <span>{{ $formRoleId ? $roles->firstWhere('id', $formRoleId)?->name : 'Select account type...' }}</span>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        @forelse($roles as $r)
    <x-dropdown-link href="#" @click.prevent="dropdownOpen = false; $dispatch('trigger-set-formrole', '{{ $r->id }}')">
        {{ ucfirst($r->name) }}
    </x-dropdown-link>
@empty
                                            <div class="px-4 py-2 text-[12px] text-gray-400 italic font-medium">No roles available...</div>
                                        @endforelse
                                    </x-slot>
                                </x-dropdown>
                                <x-input-error :messages="$errors->get('formRoleId')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="f_branch_id" value="Work Location *" />
                                <x-dropdown align="left" width="full" containerClasses="block w-full">
                                    <x-slot name="trigger">
                                        <button id="f_branch_id" type="button"
                                            class="mt-1 flex items-center justify-between w-full px-3 py-2 bg-white border rounded-lg text-[13px] text-gray-700 shadow-sm hover:border-gray-300 focus:outline-none transition-all h-10 {{ $errors->has('formBranchId') ? 'border-red-400 bg-red-50/30' : 'border-gray-200' }}">
                                            <span>{{ $formBranchId ? ($branches->firstWhere('id', $formBranchId)?->branch_name ?? 'Select work location...') : 'Select work location...' }}</span>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content" class="max-h-60 overflow-y-auto">
                                        @forelse($branches as $branch)
    <x-dropdown-link href="#" @click.prevent="dropdownOpen = false; $dispatch('trigger-set-formbranch', '{{ $branch->id }}')">
        {{ $branch->branch_name }}
    </x-dropdown-link>
@empty
                                            <div class="px-4 py-2 text-[12px] text-gray-400 italic font-medium">No branches defined...</div>
                                        @endforelse
                                    </x-slot>
                                </x-dropdown>
                                <x-input-error :messages="$errors->get('formBranchId')" class="mt-1" />
                            </div>
                        @else
                            <div class="space-y-3">
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span class="text-[12px] text-gray-500 font-medium">Role</span>
                                    <span
                                        class="text-[12px] font-bold text-gray-800 bg-gray-100 px-2 py-0.5 rounded-full">Staff</span>
                                </div>
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-[12px] text-gray-500 font-medium">Branch</span>
                                    <span
                                        class="text-[12px] font-bold text-gray-800">{{ auth()->user()->branch?->branch_name ?? '—' }}</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                        <h2 class="text-[13px] font-semibold text-gray-700 uppercase tracking-wider mb-4">Account Status</h2>
                        <label for="f_is_active" class="flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox" id="f_is_active" name="is_active" wire:model.blur="formIsActive"
                                class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900 h-4 w-4">
                            <div>
                                <span class="block text-[13px] font-semibold text-gray-800">Active Access</span>
                                <span class="block text-[12px] text-gray-500">Allow user to sign in.</span>
                            </div>
                        </label>
                    </div>

                    <div class="flex flex-col gap-2">
                <x-primary-button type="button" wire:click="runPreSaveValidation"
                    class="w-full justify-center" x-text="mode === 'edit' ? 'Save Changes' : 'Register User'"></x-primary-button>
                        <x-secondary-button @click="if(mode === 'edit') { mode = 'view'; $wire.showEdit($wire.get('editUserId'), 'view') } else { panel = 'list'; mode = 'list'; $wire.backToList() }" class="w-full justify-center">
                            <span>Cancel</span>
                        </x-secondary-button>

                        @if($editUserId)
                            <div class="bg-red-50 border border-red-100 rounded-2xl p-6 shadow-sm mt-4" wire:key="danger-zone-{{ $editUserId }}">
                                <h2 class="text-[13px] font-bold text-red-600 uppercase tracking-wider mb-2">Danger Zone</h2>
                                <p class="text-[12px] text-gray-500 mb-4 leading-relaxed">Permanently deactivate this user account. This will restrict their access to the system immediately.</p>
                                <x-danger-button 
                                    wire:click="confirmDelete({{ $editUserId }}, '{{ addslashes($firstName . ' ' . $lastName) }}')"
                                    class="w-full justify-center h-11">
                                    Delete Account
                                </x-danger-button>
                            </div>
                        @else
                            <div wire:key="danger-zone-empty"></div>
                        @endif
                    </div>
                </div>
            </div>
            </div>
{{-- ════════════════ DASHBOARD (VIEW MODE) — SKELETON PRELOAD ════════════════ --}}
            <div x-show="mode === 'view' && viewProfileLoading" x-cloak class="animate-pulse">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div class="col-span-1 space-y-6">
                        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-[0_8px_30px_-12px_rgba(0,0,0,0.05)] p-8">
                            <div class="w-24 h-24 rounded-2xl bg-slate-200 mx-auto mb-6"></div>
                            <div class="h-4 bg-slate-200 rounded-full w-2/3 mx-auto mb-2"></div>
                            <div class="h-3 bg-slate-100 rounded-full w-1/2 mx-auto mb-8"></div>
                            <div class="pt-8 border-t border-slate-100 space-y-5">
                                @for ($i = 0; $i < 6; $i++)
                                    <div class="flex items-center justify-between">
                                        <div class="h-3 bg-slate-100 rounded-full w-1/3"></div>
                                        <div class="h-4 bg-slate-200 rounded-lg w-1/4"></div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                    <div class="col-span-1 lg:col-span-3">
                        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6">
                            <div class="flex items-center justify-between mb-6">
                                <div class="h-4 bg-slate-200 rounded-full w-1/4"></div>
                                <div class="h-8 bg-slate-100 rounded-lg w-32"></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                                @for ($i = 0; $i < 4; $i++)
                                    <div class="p-5 rounded-2xl bg-slate-100 h-28"></div>
                                @endfor
                            </div>
                            <div class="h-40 bg-slate-50 rounded-2xl"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ════════════════ DASHBOARD (VIEW MODE) ════════════════ --}}
            {{-- No enter transition here on purpose: once the skeleton above
                 clears, the real content should appear instantly rather than
                 fading in — a second animation right after the skeleton reads
                 as a duplicate "loading" moment. --}}
            <div x-show="mode === 'view' && !viewProfileLoading" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    {{-- Profile Card (Left side) --}}
                    <div class="col-span-1 space-y-6">
                        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-[0_8px_30px_-12px_rgba(0,0,0,0.05)] p-8 text-center relative overflow-hidden group">
                            {{-- Decorative background element --}}
                            <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-50 rounded-full blur-3xl opacity-50 group-hover:bg-indigo-100 transition-colors duration-500"></div>
                            
                            <div class="relative">
                                @if($editUserAvatar)
                                    <div class="w-24 h-24 rounded-2xl flex items-center justify-center text-5xl mx-auto mb-6 shadow-xl rotate-3 group-hover:rotate-0 transition-transform duration-500" style="{{ $editUserAvatar['style'] }}">
                                        {{ $editUserAvatar['emoji'] }}
                                    </div>
                                @else
                                    <div class="w-24 h-24 bg-gradient-to-tr from-indigo-600 to-violet-500 text-white rounded-2xl flex items-center justify-center text-3xl font-black mx-auto mb-6 shadow-xl shadow-indigo-200 rotate-3 group-hover:rotate-0 transition-transform duration-500">
                                        {{ strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)) }}
                                    </div>
                                @endif
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ $firstName }} {{ $lastName }}</h3>
                                <p class="text-[13px] text-slate-500 font-medium mt-1">{{ $email }}</p>
                                
                                <div class="mt-8 pt-8 border-t border-slate-100 text-left space-y-5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Account ID</span>
                                        <span class="text-[12px] font-black text-slate-700 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-100">#{{ $employeeId ?: 'PENDING' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">System Role</span>
                                        <span class="text-[12px] font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100">{{ $formRoleId ? $roles->firstWhere('id', $formRoleId)?->name : 'N/A' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Work Location</span>
                                        <span class="text-[12px] font-bold text-slate-700">{{ $formBranchId ? $branches->firstWhere('id', $formBranchId)?->branch_name : 'N/A' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Status</span>
                                        @if($formIsActive)
                                            <span class="flex items-center gap-1.5 text-[10px] font-black text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-100">
                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                                ACTIVE
                                            </span>
                                        @else
                                            <span class="text-[10px] font-black text-red-600 bg-red-50 px-2.5 py-1 rounded-full border border-red-100">INACTIVE</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center justify-between pt-2">
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Hired Since</span>
                                        <span class="text-[12px] font-bold text-slate-700">{{ $dateHired ? \Carbon\Carbon::parse($dateHired)->format('M d, Y') : 'N/A' }}</span>
                                    </div>
                                    
                                    <div class="pt-5 border-t border-slate-50">
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest block mb-1.5">Residential Address</span>
                                        <p class="text-[12px] font-bold text-slate-700 leading-relaxed">
                                            @php
                                                // Building each line by filtering out empty parts and imploding
                                                // avoids a stray leading ", " whenever street (or another
                                                // upstream part) is blank — the old version always prefixed
                                                // barangay/province with a separator regardless of whether the
                                                // field before it actually had a value.
                                                $line1 = implode(', ', array_filter([$addr_street, $addr_barangay]));
                                                $line2 = implode(', ', array_filter([$addr_city, $addr_province]));
                                            @endphp
                                            @if($line1 || $line2 || $addr_region)
                                                @if($line1)
                                                    {{ $line1 }}<br>
                                                @endif
                                                @if($line2)
                                                    {{ $line2 }}<br>
                                                @endif
                                                @if($addr_region)
                                                    <span class="text-[11px] text-slate-500">{{ $addr_region }}</span>
                                                @endif
                                            @else
                                                <span class="text-slate-400 font-medium italic">No address provided</span>
                                            @endif
                                        </p>
                                    </div>
                                    
                                    {{-- Profile Actions --}}
                                    <div class="pt-6 mt-6 border-t border-slate-100 flex flex-col gap-2">
                                        <button wire:click.prevent="showEdit({{ $editUserId }})" wire:loading.attr="disabled"
                                            class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-xl transition-colors focus:outline-none">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            Edit Profile
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- History Area (Right side) --}}
                    <div class="col-span-1 lg:col-span-3">
                        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm">

                            {{-- Header: title + date filter --}}
                            <div class="flex items-center justify-between px-6 pt-6 pb-0">
                                <div>
                                    <h4 class="text-[15px] font-bold text-gray-900 tracking-tight">Activity Overview</h4>
                                    <p class="text-[12px] text-gray-400 font-medium mt-0.5">Historical performance data</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-date-filter startModel="historyStartDate" endModel="historyEndDate" activeModel="activeFilter" />
                                </div>
                            </div>

                            {{-- BI-style Tab Nav with sliding indicator --}}
                            <x-sliding-tabs model="historyTab" class="mt-4 px-5" ref="historyTabList" wire:ignore>
                                <x-sliding-tab model="historyTab" value="overview">
                                    <x-slot name="icon">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    </x-slot>
                                    Overview
                                </x-sliding-tab>
                                <x-sliding-tab model="historyTab" value="orders">
                                    <x-slot name="icon">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    </x-slot>
                                    Order History
                                </x-sliding-tab>
                            </x-sliding-tabs>

                            {{-- Tab Contents --}}
                            <div class="p-6">
                            <div x-cloak x-show="historyTab === 'overview'" class="animate-fadeIn">
                                @php
                                    $stats = $this->historyStats;
                                @endphp
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                                    <div class="p-3 sm:p-5 rounded-2xl bg-indigo-600 shadow-[0_12px_24px_-8px_rgba(79,70,229,0.3)] relative overflow-hidden group">
                                        <div class="absolute -bottom-6 -right-6 w-20 h-20 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                                        <div class="relative">
                                            <div class="w-8 h-8 sm:w-9 sm:h-9 bg-white/20 rounded-xl flex items-center justify-center mb-2 sm:mb-3 backdrop-blur-md">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </div>
                                            <p class="text-[8px] sm:text-[9px] text-indigo-100 uppercase font-black tracking-widest opacity-80">Total Revenue</p>
                                            <p class="text-[16px] sm:text-[19px] font-black text-white mt-0.5">₱{{ number_format($stats['total_sales'] ?? 0, 2) }}</p>
                                        </div>
                                    </div>

                                    <div class="p-3 sm:p-5 rounded-2xl bg-white border border-slate-100 shadow-sm relative overflow-hidden group">
                                        <div class="absolute -bottom-6 -right-6 w-20 h-20 bg-slate-50 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                                        <div class="relative">
                                            <div class="w-8 h-8 sm:w-9 sm:h-9 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center mb-2 sm:mb-3">
                                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                            </div>
                                            <p class="text-[8px] sm:text-[9px] text-slate-400 uppercase font-black tracking-widest">Total Orders</p>
                                            <p class="text-[16px] sm:text-[19px] font-black text-slate-900 mt-0.5">{{ number_format($stats['total_orders'] ?? 0) }}</p>
                                        </div>
                                    </div>

                                    <div class="p-3 sm:p-5 rounded-2xl bg-white border border-slate-100 shadow-sm relative overflow-hidden group">
                                        <div class="absolute -bottom-6 -right-6 w-20 h-20 bg-slate-50 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                                        <div class="relative">
                                            <div class="w-8 h-8 sm:w-9 sm:h-9 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center mb-2 sm:mb-3">
                                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                            </div>
                                            <p class="text-[8px] sm:text-[9px] text-slate-400 uppercase font-black tracking-widest">Avg. Order Value</p>
                                            <p class="text-[16px] sm:text-[19px] font-black text-slate-900 mt-0.5">₱{{ number_format($stats['avg_order_value'] ?? 0, 2) }}</p>
                                        </div>
                                    </div>

                                    <div class="p-3 sm:p-5 rounded-2xl bg-white border border-slate-100 shadow-sm relative overflow-hidden group">
                                        <div class="absolute -bottom-6 -right-6 w-20 h-20 bg-slate-50 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                                        <div class="relative">
                                            <div class="w-8 h-8 sm:w-9 sm:h-9 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center mb-2 sm:mb-3">
                                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </div>
                                            <p class="text-[8px] sm:text-[9px] text-slate-400 uppercase font-black tracking-widest">Completion Rate</p>
                                            <p class="text-[16px] sm:text-[19px] font-black text-slate-900 mt-0.5">{{ number_format($stats['completion_rate'] ?? 0, 1) }}%</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 p-4 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-white border border-slate-100 flex items-center justify-center shadow-sm">
                                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-[11px] font-bold text-slate-700">Recent Activity Volume</p>
                                            <p class="text-[10px] text-slate-500 font-medium tracking-wide italic">Last 7 days performance metrics</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[18px] font-black text-slate-900 leading-none">{{ $stats['recent_activity'] ?? 0 }}</p>
                                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-1">Actions</p>
                                    </div>
                                </div>
                            </div>

                            <div x-cloak x-show="historyTab === 'orders'" class="animate-fadeIn">
                                <x-data-table>
                                    <x-slot name="header">
                                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Date & Time</th>
                                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Reference No.</th>
                                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                                        <th class="py-3 px-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Total Amount</th>
                                    </x-slot>

                                    @forelse($this->historyOrders as $order)
                                        <tr class="hover:bg-slate-50/50 transition-colors cursor-pointer group" 
                                            wire:click="viewOrder({{ $order->id }})"
                                            title="Click to view details">
                                            <td class="px-4 py-3 text-[13px] text-slate-600 font-medium">{{ $order->created_at->format('M d, Y h:i A') }}</td>
                                            <td class="px-4 py-3">
                                                <button type="button" 
                                                    wire:click.stop="viewOrder({{ $order->id }})"
                                                    class="text-[13px] font-bold text-indigo-600 hover:text-indigo-800 underline decoration-indigo-200 decoration-2 underline-offset-2 transition-colors">
                                                    {{ $order->reference_no }}
                                                </button>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="h-1.5 w-1.5 rounded-full {{ $order->status === 'Completed' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                                    <span class="text-[11px] font-bold {{ $order->status === 'Completed' ? 'text-emerald-600' : 'text-slate-600' }} uppercase">{{ $order->status }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-right text-[13px] font-black text-slate-900">₱{{ number_format($order->total_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-12 text-center text-slate-400">
                                                <x-empty-state 
                                                    compact
                                                    title="No orders found" 
                                                    description="No records match the selected date range."
                                                />
                                            </td>
                                        </tr>
                                    @endforelse
                                </x-data-table>
                                <div class="mt-4">
                                    <x-pagination :paginator="$this->historyOrders" />
                                </div>
                            </div>
                            </div>{{-- end p-6 tab-content wrapper --}}
                        </div>
                    </div>
                </div>
            </div>
            {{-- ════════════════ END DASHBOARD ════════════════ --}}
        </div>{{-- end panel 2 --}}

        {{-- ════════════════ PANEL 1 — LIST ════════════════ --}}
        <div x-show="panel === 'list'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">

            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">{{ auth()->user()->isSuperAdmin() ? 'User Management' : 'Staff Management' }}</h2>
                    <p class="text-[12px] text-gray-500 font-medium">System Overview: <span class="text-indigo-600 font-bold">{{ $totalUsers }} members</span></p>
                </div>
                <x-primary-button @click="panel = 'form'; mode = 'create'; $wire.showCreate()" class="h-10 !px-2.5 sm:!px-4">
                    <svg class="w-4 h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span class="hidden sm:inline">Register New User</span>
                </x-primary-button>
            </div>

            {{-- System Metrics Grid (Matching Dashboard Premium Aesthetic - Compact Footprint) --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
                @php
                    $sysStats = $this->systemStats;
                    $primaryColor = auth()->user()->getRoleTheme()['primary'];
                @endphp
                
                {{-- Total Users --}}
                <div class="p-3 sm:p-4 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-white border border-indigo-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Users</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ number_format($sysStats['total']) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Registered accounts in database</p>
                </div>

                {{-- Active Status --}}
                <div class="p-3 sm:p-4 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Now</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-emerald-600 tracking-tight leading-none">{{ number_format($sysStats['active']) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Accounts with active access</p>
                </div>

                {{-- Inactive Accounts --}}
                <div class="p-3 sm:p-4 bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-white border border-rose-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Inactive</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-rose-600 tracking-tight leading-none">{{ number_format($sysStats['inactive']) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Deactivated user profiles</p>
                </div>

                {{-- Workforce --}}
                <div class="p-3 sm:p-4 bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-white border border-amber-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Staff Members</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-amber-100 flex items-center justify-center text-amber-600 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ number_format($sysStats['staff']) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Operational store employees</p>
                </div>
            </div>

            {{-- macOS Style Unified Toolbar --}}
            <div class="relative z-20 flex flex-row items-center justify-between mb-6 gap-2 sm:gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
                
                {{-- Left: Search Bar --}}
                <div class="flex flex-1 min-w-0 lg:flex-initial">
                    <x-search-bar wireModel="search" placeholder="Find records..." width="w-full lg:w-72" />
                </div>

                {{-- Right: Filters & View Toggle --}}
                <div class="flex flex-nowrap items-center justify-end gap-1.5 sm:gap-2 shrink-0">

                    {{-- Role Filter (Super Admin only) --}}
                    @if(auth()->user()->role_id === 1)
                        <x-dropdown align="right" width="48" wire:key="filter-role">
                            <x-slot name="trigger">
                                <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                                    <svg class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <circle cx="12" cy="7" r="4" stroke-width="1.5" />
                                        <path d="M4 21v-2a4 4 0 014-4h8a4 4 0 014 4v2" stroke-width="1.5" />
                                    </svg>
                                    <span class="hidden sm:inline text-[12px] whitespace-nowrap">{{ $role_id ? ucfirst($roles->firstWhere('id', $role_id)?->name) : 'All Roles' }}</span>
                                    <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </x-secondary-button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link href="#" wire:click.prevent="$set('role_id', '')">All Roles</x-dropdown-link>
                                @forelse($roles as $role)
                                    <x-dropdown-link href="#" wire:click.prevent="$set('role_id', {{ $role->id }})">
                                        {{ ucfirst($role->name) }}
                                    </x-dropdown-link>
                                @empty
                                    <div class="px-4 py-2 text-[12px] text-gray-400 italic">No roles...</div>
                                @endforelse
                            </x-slot>
                        </x-dropdown>
                    @endif

                    {{-- Branch Filter (Super Admin only) --}}
                    @if(auth()->user()->role_id === 1)
                        <x-dropdown align="right" width="48" wire:key="filter-branch">
                            <x-slot name="trigger">
                                <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="hidden sm:inline text-[12px] whitespace-nowrap">{{ $branch_id ? $branches->firstWhere('id', $branch_id)?->branch_name : 'All Branches' }}</span>
                                    <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </x-secondary-button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link href="#" wire:click.prevent="$set('branch_id', '')">All Branches</x-dropdown-link>
                                @forelse($branches as $branch)
                                    <x-dropdown-link href="#" wire:click.prevent="$set('branch_id', {{ $branch->id }})">
                                        {{ $branch->branch_name }}
                                    </x-dropdown-link>
                                @empty
                                    <x-empty-state compact title="No branches" description="" />
                                @endforelse
                            </x-slot>
                        </x-dropdown>
                    @endif

                    {{-- Status Filter --}}
                    <x-dropdown align="right" width="48" wire:key="filter-status">
                        <x-slot name="trigger">
                            <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span class="hidden sm:inline text-[12px] whitespace-nowrap">{{ $is_active === '1' ? 'Active Only' : ($is_active === '0' ? 'Inactive Only' : 'All Status') }}</span>
                                <svg class="hidden sm:block w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </x-secondary-button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link href="#" wire:click.prevent="$set('is_active', '')">All Status</x-dropdown-link>
                            <x-dropdown-link href="#" wire:click.prevent="$set('is_active', '1')">Active Only</x-dropdown-link>
                            <x-dropdown-link href="#" wire:click.prevent="$set('is_active', '0')">Inactive Only</x-dropdown-link>
                        </x-slot>
                    </x-dropdown>

                    {{-- macOS Divider --}}
                    <div class="hidden lg:block w-px h-6 bg-slate-200 mx-2"></div>

                    {{-- View Toggle (Seamless Single Icon) --}}
                    <button type="button" @click="tableView = (tableView === 'table' ? 'board' : 'table')"
                        class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors focus:outline-none shrink-0"
                        :title="tableView === 'table' ? 'Switch to Board View' : 'Switch to Table View'">
                        
                        {{-- Show Board Icon (since clicking it will switch to Board) --}}
                        <svg x-cloak x-show="tableView === 'table'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        
                        {{-- Show Table Icon (since clicking it will switch to Table) --}}
                        <svg x-cloak x-show="tableView === 'board'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- ── Table View ── --}}
            <div x-show="tableView === 'table'" x-transition:enter="transition ease-out duration-400"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="w-full">
                <x-data-table>
                    <x-slot name="header">
                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Full name</th>
                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Email</th>
                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Role</th>
                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Work Location</th>
                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Joined date</th>
                        <th class="py-3 px-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                    </x-slot>

                    @php
                        $avatarCollection = collect(\App\Livewire\ProfileSettings::avatarCollection())->flatten(1);
                    @endphp

                    @forelse($users as $user)
                        @php
                            $colors = ['from-purple-400 to-indigo-500', 'from-pink-400 to-rose-500', 'from-blue-400 to-sky-500', 'from-emerald-400 to-teal-500', 'from-amber-400 to-orange-500'];
                            $grad = $colors[$user->id % count($colors)];
                            $initials = strtoupper(substr($user->first_name ?? '', 0, 1) . substr($user->last_name ?? '', 0, 1));
                            
                            $userAvatar = null;
                            if ($user->avatar) {
                                $userAvatar = $avatarCollection->firstWhere('id', $user->avatar);
                            }
                        @endphp
                        <tr wire:key="user-row-{{ $user->id }}-{{ $users->currentPage() }}"
                            class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    @if($userAvatar)
                                        <div class="w-[26px] h-[26px] rounded-full flex items-center justify-center text-[14px] shadow-sm" style="{{ $userAvatar['style'] }}">
                                            {{ $userAvatar['emoji'] }}
                                        </div>
                                    @else
                                        <div class="w-[26px] h-[26px] rounded-full bg-gradient-to-br {{ $grad }} flex items-center justify-center text-white text-[11px] font-bold shadow-sm">
                                            {{ $initials ?: '?' }}
                                        </div>
                                    @endif
                                    <span class="font-medium text-[13px] text-slate-900">{{ $user->first_name }} {{ $user->last_name }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-[13px] text-slate-500">{{ $user->email }}</td>
                            <td class="py-3 px-4 whitespace-nowrap text-[13px] text-slate-600">{{ optional($user->role)->name ?? 'No Role' }}</td>
                            <td class="py-3 px-4 whitespace-nowrap text-[13px] text-slate-600">{{ optional($user->branch)->branch_name ?? '—' }}</td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <button wire:click="toggleStatus({{ $user->id }})"
                                    class="flex items-center gap-2 hover:opacity-85 transition-opacity focus:outline-none">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $user->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span class="text-[11px] font-bold {{ $user->is_active ? 'text-emerald-600' : 'text-rose-600' }} uppercase">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                                </button>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-[13px] text-slate-600">
                                {{ $user->date_hired ? date('d M Y', strtotime($user->date_hired)) : date('d M Y', strtotime($user->created_at)) }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-right">
                                <x-secondary-button @click="openViewProfile({{ $user->id }})" class="h-8 px-3 inline-flex items-center gap-1.5 text-xs shadow-none border-slate-200">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    View Profile
                                </x-secondary-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-0">
                                <x-empty-state 
                                    title="No users identified" 
                                    description="No accounts match your current filter or search criteria. Try broadening your scope."
                                />
                            </td>
                        </tr>
                    @endforelse
                </x-data-table>
                <x-pagination :paginator="$users" />
            </div>

            {{-- ── Board View ── --}}
            <div x-show="tableView === 'board'" x-transition:enter="transition ease-out duration-400"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @forelse($users as $user)
                        @php
                            $colors = ['from-purple-400 to-indigo-500', 'from-pink-400 to-rose-500', 'from-blue-400 to-sky-500', 'from-emerald-400 to-teal-500', 'from-amber-400 to-orange-500'];
                            $grad = $colors[$user->id % count($colors)];
                            
                            $userAvatar = null;
                            if ($user->avatar) {
                                $userAvatar = $avatarCollection->firstWhere('id', $user->avatar);
                            }
                        @endphp
                        <div class="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_30px_-6px_rgba(0,0,0,0.1)] hover:-translate-y-0.5 transition-all duration-300">
                            <div class="flex items-start justify-between mb-3">
                                @if($userAvatar)
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-2xl shadow-sm" style="{{ $userAvatar['style'] }}">
                                        {{ $userAvatar['emoji'] }}
                                    </div>
                                @else
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br {{ $grad }} flex items-center justify-center text-white font-bold shadow-sm">
                                        {{ strtoupper(substr($user->first_name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="flex items-center gap-2">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $user->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span class="text-[11px] font-bold {{ $user->is_active ? 'text-emerald-600' : 'text-rose-600' }} uppercase">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                                </div>
                            </div>
                            <h3 class="font-bold text-gray-900 text-sm mb-0.5">{{ $user->first_name }}
                                {{ $user->last_name }}</h3>
                            <p class="text-xs text-gray-500 mb-3">{{ $user->email }}</p>
                            <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                                <div class="flex flex-col">
                                    <span
                                        class="text-[11px] font-bold text-gray-600">{{ optional($user->role)->name ?? 'No Role' }}</span>
                                    <span
                                        class="text-[10px] text-gray-400 font-medium">{{ optional($user->branch)->branch_name ?? 'Unassigned' }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button @click="openViewProfile({{ $user->id }})"
                                        title="View Details"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all focus:outline-none">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button wire:click.prevent="showEdit({{ $user->id }})"
                                        title="Edit Profile"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-all focus:outline-none">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full">
                            <x-empty-state 
                                title="No employee profiles" 
                                description="Your search yielded no results. Consider checking the spelling or removing filters."
                            />
                        </div>
                    @endforelse
                </div>
                <div class="mt-4">
                    <x-pagination :paginator="$users" />
                </div>
            </div>

        </div>{{-- end panel 1 --}}


    </div>{{-- end relative wrapper --}}

    {{-- ── List-page delete confirmation modal ── --}}
    <x-modal name="delete-user" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-red-400 to-rose-500 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div
                    class="flex-shrink-0 w-10 h-10 rounded-full bg-red-50 border border-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Delete User</h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">The user account and permissions will be
                        removed. This cannot be undone.</p>
                </div>
            </div>
            <div class="px-3 py-2 bg-white border border-gray-100 rounded-lg text-[13px] text-gray-600 mb-5">
                <span class="font-semibold text-gray-800">{{ $deleteTargetName }}</span>
            </div>
            <div class="flex items-center justify-end gap-2">
                <x-secondary-button @click="$dispatch('close-modal', 'delete-user')">Cancel</x-secondary-button>
                <x-danger-button wire:click="deleteUser" @click="$dispatch('close-modal', 'delete-user')">Delete</x-danger-button>
            </div>
        </div>
    </x-modal>

    {{-- ── Manager Conflict Warning Modal ── --}}
    <x-modal name="confirm-manager-replace" maxWidth="sm" focusable>
            <div class="h-1 w-full bg-gradient-to-r from-amber-400 to-orange-500 rounded-t-lg"></div>
            <div class="p-6" x-data="{ saving: false }" @open-modal.window="if ($event.detail === 'confirm-manager-replace') saving = false">
                <div class="flex items-start gap-4 mb-4">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-50 border border-amber-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
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
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
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
                                    {{-- Dot --}}
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
    <script>
    (function() {
        const registerUserData = () => {
            if (!window.Alpine) return;
            if (Alpine.data('userManagementData')) return;
            Alpine.data('userManagementData', ($wire, initialPanel, initialMode, initialView) => ({
                ...slidingTabs(@js($historyTab), 'historyTab', 'historyTabList'),
                panel: $wire.entangle('panel').live,
                mode: $wire.entangle('mode').live,
                tableView: initialView || 'table',
                deleteTargetId: null,
                deleteTargetName: '',

                // ── View Profile preload state ───────────────────────────
                // The skeleton shows once per account, the first time that
                // account's profile is opened in this session. Every open
                // after that (for that same account) swaps instantly with
                // no skeleton. viewProfileWarmedIds tracks which user IDs
                // have already been loaded once.
                viewProfileLoading: false,
                viewProfileWarmedIds: new Set(),

                // ── Entangled Form State ─────────────────────────────────
                addr_region: @entangle('addr_region'),
                addr_province: @entangle('addr_province'),
                addr_city: @entangle('addr_city'),
                addr_barangay: @entangle('addr_barangay'),
                addr_lat: @entangle('addr_lat'),
                addr_lng: @entangle('addr_lng'),

                // ── Location state ───────────────────────────────────────
                loc: {
                    noProvince: false,
                    region:   { items: [], search: '', loading: false },
                    province: { items: [], search: '', loading: false },
                    city:     { items: [], search: '', loading: false },
                    barangay: { items: [], search: '', loading: false }
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

                openViewProfile(userId) {
                    this.panel = 'form';
                    this.mode = 'view';

                    // Already showing this exact profile — nothing to refetch or re-render.
                    if (this.$wire.editUserId === userId && this.$wire.mode === 'view') {
                        return;
                    }

                    // This account was already loaded once this session —
                    // skip the skeleton, swap instantly.
                    if (this.viewProfileWarmedIds.has(userId)) {
                        this.$wire.showEdit(userId, 'view');
                        return;
                    }

                    this.viewProfileLoading = true;
                    this.$wire.showEdit(userId, 'view').finally(() => {
                        this.viewProfileLoading = false;
                        this.viewProfileWarmedIds.add(userId);
                    });
                },

                filtered(type) {
                    const s = this.loc[type];
                    const q = s.search.toLowerCase();
                    return q ? s.items.filter(i => i.name.toLowerCase().includes(q)) : s.items;
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
                        console.error('Regions fetch failed', e);
                        this.dispatchNotification('error', 'Failed to load regions. Please check your connection.');
                    }
                    finally { this.loc.region.loading = false; }
                },

                async loadProvinces(regionCode) {
                    this.loc.province.items = []; this.loc.city.items = []; this.loc.barangay.items = [];
                    if (!regionCode) return;
                    this.loc.province.loading = true;
                    try {
                        const data = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${regionCode}/provinces`);
                        this.loc.province.items = data.sort((a, b) => a.name.localeCompare(b.name));
                        
                        this.loc.noProvince = this.loc.province.items.length === 0;

                        if (this.loc.noProvince) {
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
                // ── Cascade handlers ─────────────────────────────────────
                async selectRegion(region, fromMap = false) {
                    this.addr_region = region.name;
                    this.addr_province = ''; this.addr_city = ''; this.addr_barangay = '';
                    if (!fromMap) this.geocodeAddress();
                    await this.loadProvinces(region.code);
                },
                async selectProvince(province, fromMap = false) {
                    this.addr_province = province.name;
                    this.addr_city = ''; this.addr_barangay = '';
                    if (!fromMap) this.geocodeAddress();
                    await this.loadCities(province.code);
                },
                async selectCity(city, fromMap = false) {
                    this.addr_city = city.name;
                    this.addr_barangay = '';
                    if (!fromMap) this.geocodeAddress();
                    await this.loadBarangays(city.code);
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
                            
                            this.addr_lat = lat;
                            this.addr_lng = lng;
                            
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
                mapTimeout: null,

                patchLeaflet() {
                    if (typeof L === 'undefined' || L._patched) return;
                    L._patched = true;

                    const originalResetGrid = L.GridLayer.prototype._resetGrid;
                    if (originalResetGrid) {
                        L.GridLayer.prototype._resetGrid = function() {
                            if (!this._map) return;
                            return originalResetGrid.apply(this, arguments);
                        };
                    }

                    const originalSetView = L.GridLayer.prototype._setView;
                    if (originalSetView) {
                        L.GridLayer.prototype._setView = function() {
                            if (!this._map) return;
                            return originalSetView.apply(this, arguments);
                        };
                    }

                    const originalUpdate = L.GridLayer.prototype._update;
                    if (originalUpdate) {
                        L.GridLayer.prototype._update = function() {
                            if (!this._map) return;
                            return originalUpdate.apply(this, arguments);
                        };
                    }

                    const originalResetView = L.GridLayer.prototype._resetView;
                    if (originalResetView) {
                        L.GridLayer.prototype._resetView = function() {
                            if (!this._map) return;
                            return originalResetView.apply(this, arguments);
                        };
                    }
                },

                initMap() {
                    if (typeof L === 'undefined') {
                        if (this.mapTimeout) clearTimeout(this.mapTimeout);
                        this.mapTimeout = setTimeout(() => this.initMap(), 100);
                        return;
                    }
                    this.patchLeaflet();
                    if (this.map) {
                        if (this.mapTimeout) clearTimeout(this.mapTimeout);
                        this.mapTimeout = setTimeout(() => {
                            const container = document.getElementById('userMap');
                            if (!container) return;
                            this.map.invalidateSize(); 
                            let lat = this.addr_lat;
                            let lng = this.addr_lng;
                            if (lat && lng) {
                                this.map.setView([lat, lng], 16);
                                if (this.marker) this.marker.setLatLng([lat, lng]);
                            }
                            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 250);
                            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 500);
                        }, 50);
                        return;
                    }
                    if (this.mapTimeout) clearTimeout(this.mapTimeout);
                    this.mapTimeout = setTimeout(() => {
                        let container = document.getElementById('userMap');
                        if (!container) return;

                        if (container._leaflet_id) {
                            const clone = container.cloneNode(false);
                            clone.removeAttribute('class');
                            container.parentNode.replaceChild(clone, container);
                            container = clone;
                        }

                        let lat = this.addr_lat;
                        let lng = this.addr_lng;
                        let startLat = lat || 14.2189;
                        let startLng = lng || 121.1672;
                        let startZoom = lat ? 15 : 11;
                        
                        const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            minZoom: 10,
                            attribution: '© OpenStreetMap'
                        });
                        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
                        });

                        const lagunaBounds = L.latLngBounds([13.9, 120.9], [14.5, 121.6]);

                        this.map = L.map(container, {
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
                            
                            this.addr_lat = lat;
                            this.addr_lng = lng;
                            
                            try {
                                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&countrycodes=ph`);
                                const data = await response.json();
                                if (data && data.address) {
                                    let st = data.address.road || data.address.pedestrian || '';
                                    let num = data.address.house_number || '';
                                    let fst = (num + ' ' + st).trim();
                                    if(fst) this.$wire.set('addr_street', fst);
                                    
                                    await this.autoMatchLocation(data.address);
                                }
                            } catch (error) { console.error(error); }
                        });

                        // The modal's show transition may still be in flight when the
                        // map is constructed above, which locks Leaflet into a 0x0 size.
                        // Force a couple of recalculations after the transition settles.
                        setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 250);
                        setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 500);
                    }, 50);
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
                    this.$watch('mode', (val) => {
        if (val === 'create') {
            this.loc.province.items = [];
            this.loc.city.items     = [];
            this.loc.barangay.items = [];
            this.loc.noProvince     = false;
        } else if (val === 'edit' || val === 'view') {
            // Every edit/view can target a different user, so the cascade
            // lists must be reloaded for that user's saved address — they
            // don't refresh automatically just because addr_region/
            // addr_province/etc changed via the wire entangle.
            this.loc.province.items = [];
            this.loc.city.items     = [];
            this.loc.barangay.items = [];
            this.loc.noProvince     = false;
            this.initializeExistingAddress();
        }
    });
                    this.$watch('panel', (val) => {
                        // The map picker only lives inside its modal and is
                        // initialized when that modal is explicitly opened
                        // (see the "Open Map Picker" button's @click). Calling
                        // initMap() here too — while the modal is still closed
                        // and its container has zero size — locks Leaflet
                        // into a 0x0 box that never recovers on next open.
                        if (val === 'form') {
                            this.$nextTick(() => {
                                if (typeof this.recalculateHistoryTab === 'function') {
                                    this.recalculateHistoryTab();
                                }
                            });
                        } else {
                            if (this.marker && this.map && this.mode === 'create') {
                                this.map.removeLayer(this.marker);
                                this.marker = null;
                            }
                        }
                    });

                    this.$watch('tableView', (val) => {
                        if (this.$wire && this.$wire.get('view') !== val) {
                            this.$wire.set('view', val);
                        }
                    });
                }
            }));
        };
        if (window.Alpine) registerUserData();
        else document.addEventListener('alpine:init', registerUserData);
    })();
    </script>
</div>
