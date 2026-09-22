{{-- ═══════════ ARCHIVED TAB ═══════════ --}}
<div x-show="activeTab === 'archived'"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-cloak wire:ignore.self>

    {{-- Archived KPI Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
        @php $archStats = $this->archivedStats; @endphp

        <div class="p-3 sm:p-4 bg-gradient-to-br from-slate-500/10 via-slate-500/5 to-white border border-slate-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
                <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Archived</span>
                <div class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-500 shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 8h14M5 8a2 2 0 01-2-2V4a2 2 0 012-2h14a2 2 0 012 2v2a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                </div>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ number_format($archStats['total']) }}</h3>
            <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Former team members on file</p>
        </div>

        <div class="p-3 sm:p-4 bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-white border border-amber-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
                <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Archived Staff</span>
                <div class="w-7 h-7 rounded-lg bg-white border border-amber-100 flex items-center justify-center text-amber-600 shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-amber-600 tracking-tight leading-none">{{ number_format($archStats['staff']) }}</h3>
            <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Cashiers &amp; riders no longer active</p>
        </div>

        <div class="p-3 sm:p-4 bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-white border border-rose-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
                <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Archived Managers</span>
                <div class="w-7 h-7 rounded-lg bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-rose-600 tracking-tight leading-none">{{ number_format($archStats['admins']) }}</h3>
            <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Branch admins no longer active</p>
        </div>

        <div class="p-3 sm:p-4 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-white border border-indigo-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
                <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Last 30 Days</span>
                <div class="w-7 h-7 rounded-lg bg-white border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ number_format($archStats['recent']) }}</h3>
            <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Newly archived this month</p>
        </div>
    </div>

    <div class="mb-6 p-4 bg-slate-50 border border-slate-200 rounded-2xl flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-500 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 01-2-2V4a2 2 0 012-2h14a2 2 0 012 2v2a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
        </div>
        <div>
            <p class="text-[13px] font-bold text-slate-800">Archived accounts</p>
            <p class="text-[11px] text-slate-500 font-medium">Former team members. Their records and order history remain intact but they cannot sign in.</p>
        </div>
    </div>

    <div class="relative z-20 flex flex-row items-center justify-between mb-6 gap-2 sm:gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
        <div class="flex flex-1 min-w-0 lg:flex-initial">
            <x-search-bar wireModel="search" placeholder="Find records..." width="w-full lg:w-72" />
        </div>
        <div class="flex flex-nowrap items-center justify-end gap-1.5 sm:gap-2 shrink-0">
            @if(auth()->user()->role_id === 1)
                <x-dropdown align="right" width="48" wire:key="filter-role-archived">
                    <x-slot name="trigger">
                        <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                            <svg class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <circle cx="12" cy="7" r="4" stroke-width="1.5" />
                                <path d="M4 21v-2a4 4 0 014-4h8a4 4 0 014 4v2" stroke-width="1.5" />
                            </svg>
                            <span class="hidden sm:inline text-[12px] whitespace-nowrap">{{ $role_id ? ucfirst($roles->firstWhere('id', $role_id)?->name) : 'All Roles' }}</span>
                            <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </x-secondary-button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link href="#" wire:click.prevent="$set('role_id', '')">All Roles</x-dropdown-link>
                        @forelse($roles as $role)
                            <x-dropdown-link href="#" wire:click.prevent="$set('role_id', {{ $role->id }})">{{ ucfirst($role->name) }}</x-dropdown-link>
                        @empty
                            <div class="px-4 py-2 text-[12px] text-gray-400 italic">No roles...</div>
                        @endforelse
                    </x-slot>
                </x-dropdown>
            @endif
            @if(auth()->user()->role_id === 1)
                <x-dropdown align="right" width="48" wire:key="filter-branch-archived">
                    <x-slot name="trigger">
                        <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            <span class="hidden sm:inline text-[12px] whitespace-nowrap">{{ $branch_id ? $branches->firstWhere('id', $branch_id)?->branch_name : 'All Branches' }}</span>
                            <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </x-secondary-button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link href="#" wire:click.prevent="$set('branch_id', '')">All Branches</x-dropdown-link>
                        @forelse($branches as $branch)
                            <x-dropdown-link href="#" wire:click.prevent="$set('branch_id', {{ $branch->id }})">{{ $branch->branch_name }}</x-dropdown-link>
                        @empty
                            <x-empty-state compact title="No branches" description="" />
                        @endforelse
                    </x-slot>
                </x-dropdown>
            @endif

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

    {{-- ── Archived Table View ── --}}
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
                <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Archived On</th>
                <th class="py-3 px-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
            </x-slot>

            @php $avatarCollection = collect(\App\Livewire\ProfileSettings::avatarCollection())->flatten(1); @endphp

            <tbody class="divide-y divide-slate-100/80" wire:key="archived-body-{{ $archivedUsers->currentPage() }}">
            @forelse($archivedUsers as $user)
                @php
                    $colors = ['from-purple-400 to-indigo-500', 'from-pink-400 to-rose-500', 'from-blue-400 to-sky-500', 'from-emerald-400 to-teal-500', 'from-amber-400 to-orange-500'];
                    $grad = $colors[$user->id % count($colors)];
                    $initials = strtoupper(substr($user->first_name ?? '', 0, 1) . substr($user->last_name ?? '', 0, 1));
                    $userAvatar = $user->avatar ? $avatarCollection->firstWhere('id', $user->avatar) : null;
                @endphp
                <tr wire:key="archived-row-{{ $user->id }}-{{ $archivedUsers->currentPage() }}" class="hover:bg-slate-50/50 transition-colors opacity-80">
                    <td class="py-3 px-4 border-r border-slate-100/50 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            @if($userAvatar)
                                <div class="w-[26px] h-[26px] rounded-full flex items-center justify-center text-[14px] shadow-sm grayscale" style="{{ $userAvatar['style'] }}">{{ $userAvatar['emoji'] }}</div>
                            @else
                                <div class="w-[26px] h-[26px] rounded-full bg-gradient-to-br {{ $grad }} grayscale flex items-center justify-center text-white text-[11px] font-bold shadow-sm">{{ $initials ?: '?' }}</div>
                            @endif
                            <span class="font-medium text-[13px] text-slate-900">{{ $user->first_name }} {{ $user->last_name }}</span>
                        </div>
                    </td>
                    <td class="py-3 px-4 border-r border-slate-100/50 whitespace-nowrap text-[13px] text-slate-500">{{ $user->email }}</td>
                    <td class="py-3 px-4 border-r border-slate-100/50 whitespace-nowrap text-[13px] text-slate-600">{{ optional($user->role)->name ?? 'No Role' }}</td>
                    <td class="py-3 px-4 border-r border-slate-100/50 whitespace-nowrap text-[13px] text-slate-600">{{ optional($user->branch)->branch_name ?? '—' }}</td>
                    <td class="py-3 px-4 border-r border-slate-100/50 text-[13px] text-slate-500">
                        <div class="whitespace-nowrap font-medium">{{ $user->archived_at ? \Carbon\Carbon::parse($user->archived_at)->format('d M Y') : '—' }}</div>
                        @if($user->archive_reason)
                            <div class="text-[11px] text-slate-400 italic max-w-[200px] truncate mt-0.5" title="{{ $user->archive_reason }}">
                                Reason: {{ $user->archive_reason }}
                            </div>
                        @endif
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
                    <td colspan="6" class="py-0">
                        <x-empty-state title="No archived accounts" description="Accounts you archive will show up here." />
                    </td>
                </tr>
            @endforelse
            </tbody>
        </x-data-table>
        <x-pagination :paginator="$archivedUsers" />
    </div>

    {{-- ── Archived Board View ── --}}
    <div x-show="tableView === 'board'" x-transition:enter="transition ease-out duration-400"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @php $avatarCollection = collect(\App\Livewire\ProfileSettings::avatarCollection())->flatten(1); @endphp
            @forelse($archivedUsers as $user)
                @php
                    $colors = ['from-purple-400 to-indigo-500', 'from-pink-400 to-rose-500', 'from-blue-400 to-sky-500', 'from-emerald-400 to-teal-500', 'from-amber-400 to-orange-500'];
                    $grad = $colors[$user->id % count($colors)];
                    $userAvatar = $user->avatar ? $avatarCollection->firstWhere('id', $user->avatar) : null;
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_30px_-6px_rgba(0,0,0,0.1)] hover:-translate-y-0.5 transition-all duration-300 opacity-80">
                    <div class="flex items-start justify-between mb-3">
                        @if($userAvatar)
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-2xl shadow-sm grayscale" style="{{ $userAvatar['style'] }}">
                                {{ $userAvatar['emoji'] }}
                            </div>
                        @else
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br {{ $grad }} grayscale flex items-center justify-center text-white font-bold shadow-sm">
                                {{ strtoupper(substr($user->first_name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                            <span class="text-[11px] font-bold text-slate-500 uppercase">Archived</span>
                        </div>
                    </div>
                    <h3 class="font-bold text-gray-900 text-sm mb-0.5">{{ $user->first_name }} {{ $user->last_name }}</h3>
                    <p class="text-xs text-gray-500 mb-3">{{ $user->email }}</p>
                    @if($user->archive_reason)
                        <div class="mb-3 px-2.5 py-1.5 rounded-xl bg-slate-50 border border-slate-100/80 flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-[11px] text-slate-600 line-clamp-2 leading-tight">
                                <span class="font-bold text-slate-700">Reason:</span> {{ $user->archive_reason }}
                            </p>
                        </div>
                    @endif
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-[11px] font-bold text-gray-600">{{ optional($user->role)->name ?? 'No Role' }}</span>
                            <span class="text-[10px] text-gray-400 font-medium">{{ optional($user->branch)->branch_name ?? '—' }} • {{ $user->archived_at ? \Carbon\Carbon::parse($user->archived_at)->format('d M Y') : '—' }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button @click="openViewProfile({{ $user->id }})"
                                title="View Profile"
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
                            <button wire:click.prevent="confirmRestore({{ $user->id }}, '{{ addslashes($user->first_name . ' ' . $user->last_name) }}')"
                                title="Restore User"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition-all focus:outline-none">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <x-empty-state title="No archived accounts" description="Accounts you archive will show up here." />
                </div>
            @endforelse
        </div>
        <div class="mt-4">
            <x-pagination :paginator="$archivedUsers" />
        </div>
    </div>
</div>

