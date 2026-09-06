@php
    $user = auth()->user();
    $roleTheme = $user->getRoleTheme();
    $primaryColor = $roleTheme['primary'];
    $primaryText = "text-{$primaryColor}-600";
    $primaryBg = "bg-{$primaryColor}-600";
@endphp

<div class="relative overflow-hidden" wire:poll.5s>
    <div class="relative min-h-[600px] px-1">
        
        {{-- Header Section --}}
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Customer Feedbacks</h2>
                <p class="text-[12px] text-gray-500 font-medium">System Overview: <span class="{{ $primaryText }} font-bold">{{ $stats['total'] }} interactions</span></p>
            </div>
        </div>

        {{-- KPI Cards Section (Matching Dashboard Premium Aesthetic - Compact Footprint) --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-8">
            {{-- Total Feedback --}}
            <div class="p-3 sm:p-4 bg-gradient-to-br from-{{ $primaryColor }}-500/10 via-{{ $primaryColor }}-500/5 to-white border border-{{ $primaryColor }}-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-1 sm:mb-2">
                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Feedbacks</span>
                    <div class="w-7 h-7 rounded-lg bg-white border border-{{ $primaryColor }}-100 flex items-center justify-center {{ $primaryText }} shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
                    </div>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $stats['total'] }}</h3>
                <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Aggregated customer submissions</p>
            </div>

            {{-- Average Rating --}}
            <div class="p-3 sm:p-4 bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-white border border-amber-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-1 sm:mb-2">
                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Average</span>
                    <div class="w-7 h-7 rounded-lg bg-white border border-amber-100 flex items-center justify-center text-amber-500 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1">
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ number_format($stats['average'], 1) }}</h3>
                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400">/ 5.0</span>
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Overall satisfaction index</p>
            </div>

            {{-- Branch Specific Volume --}}
            <div class="p-3 sm:p-4 bg-gradient-to-br from-blue-500/10 via-blue-500/5 to-white border border-blue-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-1 sm:mb-2">
                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Scope</span>
                    <div class="w-7 h-7 rounded-lg bg-white border border-blue-100 flex items-center justify-center text-blue-600 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $stats['branch_count'] }}</h3>
                <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Interactions in active scope</p>
            </div>

            {{-- Latest Activity --}}
            <div class="p-3 sm:p-4 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-1 sm:mb-2">
                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Latest</span>
                    <div class="w-7 h-7 rounded-lg bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <h3 class="text-[12px] sm:text-[13px] font-black text-slate-900 tracking-tight leading-none uppercase truncate" title="{{ $stats['latest'] }}">{{ $stats['latest'] }}</h3>
                <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-2 sm:mt-2.5 leading-none">Most recent event logged</p>
            </div>
        </div>

        {{-- macOS Style Unified Toolbar --}}
        <div class="relative z-20 flex flex-row items-center justify-between mb-6 gap-2 sm:gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm mx-1">
            
            {{-- Left: Search Bar --}}
            <div class="flex flex-1 min-w-0 lg:flex-initial">
                <x-search-bar wireModel="search" placeholder="Find records..." width="w-full lg:w-72" />
            </div>

            {{-- Right: Filters --}}
            <div class="flex flex-nowrap items-center justify-end gap-1.5 sm:gap-2 shrink-0">
                {{-- 3-in-1 Date Filter Component --}}
                <x-date-filter startModel="startDate" endModel="endDate" activeModel="activeFilter" />

                @if(auth()->user()->isSuperAdmin())
                    <x-dropdown align="right" width="48" wire:key="filter-branch">
                        <x-slot name="trigger">
                            <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="hidden sm:inline text-[12px] whitespace-nowrap">{{ $selectedBranchId ? ($branches->firstWhere('id', $selectedBranchId)->branch_name ?? 'Branch Scope') : 'All Branches' }}</span>
                                <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </x-secondary-button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link href="#" wire:click.prevent="$set('selectedBranchId', '')">All Branches</x-dropdown-link>
                            <hr class="border-gray-50">
                            @foreach($branches as $branch)
                                <x-dropdown-link href="#" wire:click.prevent="$set('selectedBranchId', '{{ $branch->id }}')">
                                    {{ $branch->branch_name }}
                                </x-dropdown-link>
                            @endforeach
                        </x-slot>
                    </x-dropdown>
                @endif
            </div>
        </div>

        {{-- Table View --}}
        <div class="mx-1">
            <x-data-table>
                <x-slot name="header">
                    <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Customer Details</th>
                    <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Branch</th>
                    <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Overall Sentiment</th>
                    <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Submitted Date</th>
                    <th class="py-3 px-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Action</th>
                </x-slot>

                @forelse($reviews as $review)
                    @php
                        $ratings = collect($review->answers)->where('type', 'rating')->pluck('answer');
                        $avg = $ratings->count() > 0 ? $ratings->average() : null;
                        
                        $colors = ['from-indigo-400 to-blue-500', 'from-rose-400 to-pink-500', 'from-emerald-400 to-teal-500', 'from-amber-400 to-orange-500'];
                        $grad = $colors[$review->id % count($colors)];
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br {{ $grad }} flex items-center justify-center text-white text-[12px] font-black shadow-sm">
                                    {{ strtoupper(substr($review->customer_name ?: 'A', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-[13px] text-slate-900">{{ $review->customer_name ?: 'Anonymous' }}</div>
                                    <div class="text-[11px] font-medium text-slate-400 italic">{{ $review->contact_number ?: 'Hidden' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black bg-slate-50 text-slate-500 border border-slate-100">
                                {{ strtoupper($review->branch->branch_name ?? 'N/A') }}
                            </div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if($avg !== null)
                                <div class="flex items-center gap-1.5">
                                    <div class="flex text-amber-400">
                                        @for($i = 1; $i <= 5; $i++)
                                            @php
                                                // Proportional fill instead of round($avg): rounding half-up
                                                // made a 3.5-star average render as 4 fully-lit stars and a
                                                // 4.5 render as 5 — visually overstating the score to anyone
                                                // scanning icons rather than reading the numeric average.
                                                $fillPct = max(0, min(1, $avg - ($i - 1))) * 100;
                                            @endphp
                                            <span class="relative inline-block w-3 h-3">
                                                <svg class="absolute inset-0 w-3 h-3 text-slate-200 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                <span class="absolute inset-0 overflow-hidden" style="width: {{ $fillPct }}%">
                                                    <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                </span>
                                            </span>
                                        @endfor
                                    </div>
                                    <span class="text-[14px] font-black text-slate-900">{{ number_format($avg, 1) }}</span>
                                </div>
                            @else
                                <span class="text-[11px] text-slate-400 italic">No ratings</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="text-[13px] font-bold text-slate-900">{{ $review->created_at->format('M d, Y') }}</div>
                            <div class="text-[11px] text-slate-400 font-medium italic">{{ $review->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-right">
                            <x-secondary-button type="button" wire:click="viewReview({{ $review->id }})" class="h-9 px-3 whitespace-nowrap">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                View Details
                            </x-secondary-button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-0">
                            <x-empty-state title="No Feedbacks Yet" description="Feedback will appear here." icon="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                        </td>
                    </tr>
                @endforelse
            </x-data-table>
            <x-pagination :paginator="$reviews" />
        </div>
    </div>

    {{-- Review Detail Side Panel --}}
    <x-side-panel name="view-review-detail" width="max-w-md">
        @if($viewingReview)
            <div class="flex flex-col h-full bg-white">
                {{-- Premium Header --}}
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/40">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-[15px] font-black text-slate-900 tracking-tight leading-none">Review Details</h3>
                            <span class="text-[11px] text-indigo-600 font-bold uppercase tracking-wider mt-1 block">Feedback ID: #REV-{{ str_pad($viewingReview->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    </div>
                    <button wire:click="closeReview" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-slate-600 hover:shadow-sm transition-all border border-transparent hover:border-slate-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Content Area --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    {{-- Customer Identity Section --}}
                    <div class="p-6 bg-gradient-to-b from-slate-50/80 to-white border-b border-slate-50">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center text-indigo-600 text-lg font-black italic">
                                {{ strtoupper(substr($viewingReview->customer_name ?: 'A', 0, 1)) }}
                            </div>
                            <div class="flex-1">
                                <h4 class="text-[16px] font-black text-slate-900 leading-tight">{{ $viewingReview->customer_name ?: 'Anonymous' }}</h4>
                                <div class="flex flex-col gap-1 mt-2">
                                    <div class="flex items-center gap-2 text-[12px] text-slate-500 font-medium">
                                        <svg class="w-3.5 h-3.5 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        <span>{{ $viewingReview->branch->branch_name ?? 'Global Branch' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[12px] text-slate-500 font-medium">
                                        <svg class="w-3.5 h-3.5 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <span class="font-bold text-slate-700">{{ $viewingReview->contact_number ?: 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="block text-[11px] font-black text-slate-400 uppercase tracking-widest leading-none">Date</span>
                                <span class="block text-[13px] font-bold text-slate-900 mt-1">{{ $viewingReview->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Responses Section --}}
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h5 class="text-[11px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                                Questionnaire Findings
                            </h5>
                        </div>

                        <div class="space-y-8 relative">
                            {{-- Vertical line for the timeline feel --}}
                            <div class="absolute left-[7px] top-2 bottom-2 w-[2px] bg-slate-50 rounded-full"></div>

                            @foreach($viewingReview->answers as $index => $a)
                                <div class="relative pl-7 group">
                                    {{-- Dot --}}
                                    <div class="absolute left-0 top-[6px] w-[16px] h-[16px] rounded-full border-4 border-white bg-slate-200 group-hover:bg-indigo-400 transition-colors z-10"></div>
                                    
                                    <div class="space-y-2">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest leading-tight group-hover:text-slate-600 transition-colors">{{ $a['question'] }}</label>
                                        
                                        @if(($a['type'] ?? '') === 'rating')
                                            <div class="flex items-center gap-2">
                                                <div class="flex text-amber-400">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <svg class="w-3.5 h-3.5 {{ $i <= (int)$a['answer'] ? 'fill-current' : 'text-slate-100 fill-current' }}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                    @endfor
                                                </div>
                                                <span class="text-[14px] font-black text-slate-900 leading-none">{{ $a['answer'] }}</span>
                                            </div>
                                        @else
                                            <div class="text-[13px] text-slate-700 font-medium leading-relaxed bg-slate-50/50 p-3 rounded-xl border border-slate-100/50 group-hover:bg-slate-50 transition-colors">
                                                {{ $a['answer'] ?: 'No response provided.' }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Action Footer --}}
                <div class="p-5 border-t border-slate-100 bg-white">
                    <button wire:click="closeReview" class="w-full h-10 flex items-center justify-center gap-2 bg-slate-900 text-white text-[13px] font-bold rounded-xl hover:bg-indigo-600 transition-all shadow-lg shadow-slate-200">
                        <span>Acknowledge & Close</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>
            </div>
        @endif
    </x-side-panel>
</div>
