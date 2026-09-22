{{-- ════════════════ DASHBOARD (VIEW MODE) ════════════════ --}}
<div x-show="panel === 'form' && mode === 'view'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Profile Card (Left side) --}}
        <div class="col-span-1 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-[0_8px_30px_-12px_rgba(0,0,0,0.05)] p-8 text-center relative overflow-hidden group">
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
                        @if($editUserArchived)
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Status</span>
                                <span class="flex items-center gap-1.5 text-[10px] font-black text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200">
                                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                                    ARCHIVED
                                </span>
                            </div>
                            @if($archiveReason)
                                <div class="pt-3">
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest block mb-1">Archive Reason</span>
                                    <p class="text-[12px] font-medium text-slate-700 bg-amber-50/60 border border-amber-200/60 rounded-xl p-3 leading-relaxed">
                                        {{ $archiveReason }}
                                    </p>
                                </div>
                            @endif
                        @else
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
                        @endif
                        <div class="flex items-center justify-between pt-2">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Hired Since</span>
                            <span class="text-[12px] font-bold text-slate-700">{{ $dateHired ? \Carbon\Carbon::parse($dateHired)->format('M d, Y') : 'N/A' }}</span>
                        </div>
                        
                        <div class="pt-5 border-t border-slate-50">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest block mb-1.5">Residential Address</span>
                            <p class="text-[12px] font-bold text-slate-700 leading-relaxed">
                                @php
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
                            @if($editUserArchived)
                                <button wire:click.prevent="confirmRestore({{ $editUserId }}, '{{ addslashes($firstName . ' ' . $lastName) }}')"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold rounded-xl transition-colors focus:outline-none">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Restore User
                                </button>
                                <p class="text-[10px] text-slate-400 font-medium text-center leading-snug px-2">Restoring returns them to the directory as inactive — you'll still need to activate access separately.</p>
                            @else
                                <button wire:click.prevent="showEdit({{ $editUserId }})" wire:loading.attr="disabled"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-xl transition-colors focus:outline-none">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Edit Profile
                                </button>
                            @endif
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

                {{-- Sliding Tabs for History --}}
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
                    <div x-cloak x-show="historyTab === 'overview'"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0">
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

                        {{-- Activity Timeline --}}
                        @php 
                            $timelineEventsList = $this->timelineEvents;
                            $timelineCount = $timelineEventsList->count();
                            $timelineTotal = $this->timelineTotalCount;
                        @endphp
                        <div class="mt-6">
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-white border border-slate-100 flex items-center justify-center shadow-sm">
                                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-slate-700">Activity Timeline</p>
                                        <p class="text-[10px] text-slate-500 font-medium tracking-wide italic">Full account lifecycle history</p>
                                    </div>
                                </div>
                                @if($timelineTotal > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-violet-100 text-violet-700 uppercase tracking-widest">
                                        Showing {{ $timelineCount }} of {{ $timelineTotal }} {{ Str::plural('event', $timelineTotal) }}
                                    </span>
                                @endif
                            </div>

                            @if($timelineCount > 0)
                                {{-- Explicit Max-Height Scrollable Container with inline style to guarantee boundary --}}
                                <div class="relative rounded-xl border border-slate-100 bg-slate-50/40 p-4">
                                    <div class="pr-2" style="max-height: 280px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
                                        @foreach($timelineEventsList as $event)
                                            @php
                                                $colorMap = [
                                                    'indigo'  => ['dot' => 'bg-indigo-500 ring-indigo-100',  'badge' => 'bg-indigo-100 text-indigo-700'],
                                                    'emerald' => ['dot' => 'bg-emerald-500 ring-emerald-100','badge' => 'bg-emerald-100 text-emerald-700'],
                                                    'rose'    => ['dot' => 'bg-rose-500 ring-rose-100',      'badge' => 'bg-rose-100 text-rose-700'],
                                                    'amber'   => ['dot' => 'bg-amber-500 ring-amber-100',    'badge' => 'bg-amber-100 text-amber-700'],
                                                    'sky'     => ['dot' => 'bg-sky-500 ring-sky-100',        'badge' => 'bg-sky-100 text-sky-700'],
                                                    'slate'   => ['dot' => 'bg-slate-400 ring-slate-100',    'badge' => 'bg-slate-100 text-slate-600'],
                                                ];
                                                $c = $colorMap[$event['color']] ?? $colorMap['slate'];
                                                $isLast = $loop->last && ($timelineCount >= $timelineTotal);
                                            @endphp
                                            <div class="relative flex gap-3">
                                                {{-- Connector line --}}
                                                @unless($isLast)
                                                    <div class="absolute left-[13px] top-7 bottom-0 w-px bg-slate-200"></div>
                                                @endunless

                                                {{-- Dot with icon --}}
                                                <div class="relative flex-shrink-0 mt-1">
                                                    <div class="w-7 h-7 rounded-full {{ $c['dot'] }} ring-4 flex items-center justify-center shadow-sm">
                                                        @if($event['type'] === 'hired')
                                                            {{-- Person / join --}}
                                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                                        @elseif($event['type'] === 'activated')
                                                            {{-- Check / active --}}
                                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                        @elseif($event['type'] === 'deactivated')
                                                            {{-- Pause / inactive --}}
                                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                                        @elseif($event['type'] === 'archived')
                                                            {{-- Archive box --}}
                                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4zM3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
                                                        @elseif($event['type'] === 'restored')
                                                            {{-- Arrow up / restore --}}
                                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                                        @else
                                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                                        @endif
                                                    </div>
                                                </div>

                                                {{-- Content --}}
                                                <div class="flex-1 pb-5 min-w-0">
                                                    <div class="flex items-center flex-wrap gap-2 mb-0.5">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-widest {{ $c['badge'] }}">
                                                            {{ $event['badge'] }}
                                                        </span>
                                                        <span class="text-[10px] text-slate-400 font-medium">
                                                            {{ $event['timestamp']->format('M d, Y') }}
                                                            <span class="text-slate-300 mx-0.5">&middot;</span>
                                                            {{ $event['timestamp']->diffForHumans() }}
                                                        </span>
                                                    </div>
                                                    <p class="text-[12px] font-bold text-slate-800">{{ $event['title'] }}</p>
                                                    @if(!empty($event['description']))
                                                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">{{ $event['description'] }}</p>
                                                    @endif
                                                    @if(!empty($event['performed_by']))
                                                        <p class="text-[10px] text-slate-400 mt-0.5 italic">by {{ $event['performed_by'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Load More Bar --}}
                                    @if($timelineTotal > $timelineCount)
                                        <div class="mt-3 pt-3 border-t border-slate-200/60 flex items-center justify-between" x-data="{ loadingMore: false }">
                                            <span class="text-[11px] text-slate-500 font-medium">
                                                {{ $timelineTotal - $timelineCount }} older {{ Str::plural('event', $timelineTotal - $timelineCount) }} not loaded
                                            </span>
                                            <button type="button"
                                                :disabled="loadingMore"
                                                @click="loadingMore = true; $wire.loadMoreTimeline().then(() => { loadingMore = false; })"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors disabled:opacity-50">
                                                <span x-show="!loadingMore">Load more history</span>
                                                <span x-show="loadingMore" x-cloak class="flex items-center gap-1">
                                                    <svg class="animate-spin h-3 w-3 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                    </svg>
                                                    Loading...
                                                </span>
                                                <svg x-show="!loadingMore" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="flex flex-col items-center justify-center py-8 text-center bg-slate-50/40 rounded-xl border border-slate-100">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-[12px] font-semibold text-slate-500">No activity events recorded yet</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Events will appear here as actions are taken</p>
                                </div>
                            @endif
                        </div>
                        {{-- /Activity Timeline --}}

                    </div>

                    <div x-cloak x-show="historyTab === 'orders'"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0">
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
                </div>
            </div>
        </div>
    </div>
</div>

