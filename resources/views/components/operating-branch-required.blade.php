@props([
    'title' => 'Operating Branch Required',
    'message' => 'Please select an operating branch to access this module.',
    'actionText' => 'Go to Settings',
    'actionRoute' => route('settings.index'),
    'icon' => 'branch', // 'branch' | 'building' | 'switch'
    'branches' => null,
])

@php
    $user = auth()->user();
    $rawBranches = $branches ?? ($user && $user->isSuperAdmin() ? \App\Models\Branch::orderBy('branch_name')->get() : []);
    $availableBranches = is_iterable($rawBranches) ? collect($rawBranches) : collect();
@endphp

<div class="w-full min-h-[550px] flex items-center justify-center p-4 sm:p-8 animate-fadeIn">
    <div class="max-w-md w-full bg-white border border-gray-100 rounded-3xl p-6 sm:p-8 shadow-[0_20px_50px_rgba(0,0,0,0.06)] text-center relative overflow-hidden">
        {{-- Soft background decorative blob --}}
        <div class="absolute -top-16 -right-16 w-36 h-36 rounded-full bg-amber-500/10 blur-2xl pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-16 w-36 h-36 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"></div>

        {{-- Icon container --}}
        <div class="w-20 h-20 rounded-3xl bg-gradient-to-tr from-amber-500/15 to-amber-500/5 text-amber-600 flex items-center justify-center mx-auto mb-6 shadow-sm border border-amber-200/50">
            @if($icon === 'building')
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            @elseif($icon === 'switch')
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
            @else
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            @endif
        </div>

        {{-- Heading & Message --}}
        <h2 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight mb-2">
            {{ $title }}
        </h2>
        <p class="text-[13px] text-gray-500 font-medium leading-relaxed mb-6">
            {{ $message }}
        </p>

        {{-- Action Buttons / Quick Branch Switcher --}}
        <div class="space-y-3">
            @if($user && $user->isSuperAdmin() && count($availableBranches) > 0)
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" 
                        class="w-full inline-flex items-center justify-between gap-2 px-4 py-3 bg-gray-900 text-white rounded-2xl text-[13px] font-bold hover:bg-gray-800 transition-all shadow-md focus:outline-none">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
                            </svg>
                            <span>Switch Branch Directly</span>
                        </span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open" @click.outside="open = false" x-transition x-cloak
                        class="absolute left-0 right-0 mt-2 bg-white border border-gray-100 rounded-2xl shadow-2xl p-1.5 z-50 max-h-56 overflow-y-auto">
                        @foreach($availableBranches as $b)
                            @php
                                $bId = is_array($b) ? ($b['id'] ?? null) : ($b->id ?? null);
                                $bName = is_array($b) ? ($b['branch_name'] ?? ($b['name'] ?? '')) : ($b->branch_name ?? ($b->name ?? ''));
                                $bIsMain = is_array($b) ? (!empty($b['is_main'])) : (!empty($b->is_main));
                            @endphp
                            @if($bId)
                                <button type="button" 
                                    wire:click="quickSwitchBranch({{ $bId }})" 
                                    @click="open = false"
                                    class="w-full text-left px-3.5 py-2.5 rounded-xl text-[12px] font-semibold text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors flex items-center justify-between">
                                    <span>{{ $bName }}</span>
                                    @if($bIsMain)
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Main</span>
                                    @endif
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-center gap-2 pt-1">
                @if($actionRoute)
                    <a href="{{ $actionRoute }}" wire:navigate 
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-800 rounded-2xl text-[12px] font-bold hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>{{ $actionText }}</span>
                    </a>
                @endif

                <a href="{{ route('dashboard') }}" wire:navigate 
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-gray-500 hover:text-gray-900 text-[12px] font-semibold transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </div>
</div>

