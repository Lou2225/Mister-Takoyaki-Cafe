{{-- ═══════════ DIRECTORY TAB ═══════════ --}}
<div x-show="activeTab === 'directory'"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-cloak wire:ignore.self>

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
                
                <svg x-cloak x-show="tableView === 'table'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                
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
                <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Full name</th>
                <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Email</th>
                <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Role</th>
                <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Work Location</th>
                <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Joined date</th>
                <th class="py-3 px-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
            </x-slot>

            @php
                $avatarCollection = collect(\App\Livewire\ProfileSettings::avatarCollection())->flatten(1);
            @endphp

            <tbody class="divide-y divide-slate-100/80" wire:key="directory-body-{{ $users->currentPage() }}">
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
                    <td class="py-3 px-4 border-r border-slate-100/50 whitespace-nowrap">
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
                    <td class="py-3 px-4 border-r border-slate-100/50 whitespace-nowrap text-[13px] text-slate-500">{{ $user->email }}</td>
                    <td class="py-3 px-4 border-r border-slate-100/50 whitespace-nowrap text-[13px] text-slate-600">{{ optional($user->role)->name ?? 'No Role' }}</td>
                    <td class="py-3 px-4 border-r border-slate-100/50 whitespace-nowrap text-[13px] text-slate-600">{{ optional($user->branch)->branch_name ?? '—' }}</td>
                    <td class="py-3 px-4 border-r border-slate-100/50 whitespace-nowrap">
                        <button wire:click="confirmToggleStatus({{ $user->id }}, '{{ addslashes($user->first_name . ' ' . $user->last_name) }}', {{ $user->is_active ? 'true' : 'false' }})"
                            class="flex items-center gap-2 hover:opacity-85 transition-opacity focus:outline-none">
                            <span class="h-1.5 w-1.5 rounded-full {{ $user->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                            <span class="text-[11px] font-bold {{ $user->is_active ? 'text-emerald-600' : 'text-rose-600' }} uppercase">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                        </button>
                    </td>
                    <td class="py-3 px-4 border-r border-slate-100/50 whitespace-nowrap text-[13px] text-slate-600">
                        {{ $user->date_hired ? date('d M Y', strtotime($user->date_hired)) : date('d M Y', strtotime($user->created_at)) }}
                    </td>
                    <td class="py-3 px-4 whitespace-nowrap text-right">
                        <x-secondary-button @click="openViewProfile({{ $user->id }})" class="h-8 px-3 inline-flex items-center gap-1.5 text-xs shadow-none border-slate-200" x-bind:class="loadingProfileId !== null ? 'opacity-60 pointer-events-none' : ''">
                            <svg x-show="loadingProfileId == {{ $user->id }}" class="w-3.5 h-3.5 text-indigo-500 animate-spin shrink-0" fill="none" viewBox="0 0 24 24" x-cloak>
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"></path>
                            </svg>
                            <svg x-show="loadingProfileId != {{ $user->id }}" class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span x-text="loadingProfileId == {{ $user->id }} ? 'Loading…' : 'View Profile'">View Profile</span>
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
            </tbody>
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
                    <h3 class="font-bold text-gray-900 text-sm mb-0.5">{{ $user->first_name }} {{ $user->last_name }}</h3>
                    <p class="text-xs text-gray-500 mb-3">{{ $user->email }}</p>
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-[11px] font-bold text-gray-600">{{ optional($user->role)->name ?? 'No Role' }}</span>
                            <span class="text-[10px] text-gray-400 font-medium">{{ optional($user->branch)->branch_name ?? 'Unassigned' }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button @click="openViewProfile({{ $user->id }})"
                                title="View Details"
                                :disabled="loadingProfileId !== null"
                                class="w-7 h-7 flex items-center justify-center rounded-lg transition-all focus:outline-none"
                                :class="loadingProfileId == {{ $user->id }} ? 'text-indigo-500 bg-indigo-50' : 'text-gray-400 hover:text-indigo-600 hover:bg-indigo-50'">
                                <svg x-show="loadingProfileId == {{ $user->id }}" class="w-3.5 h-3.5 animate-spin text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" x-cloak>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"></path>
                                </svg>
                                <svg x-show="loadingProfileId != {{ $user->id }}" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                            <button wire:click.prevent="showEdit({{ $user->id }})"
                                title="Edit Profile"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-all focus:outline-none">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
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
</div>

