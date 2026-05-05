<div class="flex items-center gap-4 ml-4 pl-4 border-l border-gray-200 lg:flex">
    <div class="flex flex-col min-w-0">
        <span class="text-[10px] uppercase font-bold text-gray-400 tracking-widest leading-none mb-1.5 whitespace-nowrap">
            @if($isSuperAdmin)
                Super Admin Operating Branch
            @else
                Assigned Location
            @endif
        </span>
        <div class="flex items-center gap-2.5">
            @if($isSuperAdmin)
                <x-dropdown align="left" width="56">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 group focus:outline-none">
                            <span class="text-[15px] font-extrabold text-gray-900 leading-none whitespace-nowrap group-hover:text-indigo-600 transition-colors">
                                {{ $currentBranchName }}
                            </span>
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="px-3 py-2 border-b border-gray-100 bg-gray-50/50">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Select Network Node</span>
                        </div>
                        <x-dropdown-link href="#" wire:click.prevent="switchBranch('all')">
                            <span class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                Global Network (All Branches)
                            </span>
                        </x-dropdown-link>
                        @foreach($branches as $branch)
                            <x-dropdown-link href="#" wire:click.prevent="switchBranch({{ $branch->id }})">
                                <span class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $selectedBranchId == $branch->id ? 'bg-indigo-500 shadow-[0_0_8px_rgba(99,102,241,0.4)]' : 'bg-gray-300' }}"></span>
                                    {{ $branch->branch_name }}
                                </span>
                            </x-dropdown-link>
                        @endforeach
                    </x-slot>
                </x-dropdown>
                <div class="flex items-center gap-1.5 bg-emerald-50 px-1.5 py-0.5 rounded-full border border-emerald-100">
                    <span class="flex h-1.5 w-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]"></span>
                    <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-tighter">Live Context</span>
                </div>
            @else
                <span class="text-[15px] font-extrabold text-gray-900 leading-none whitespace-nowrap">
                    {{ $currentBranchName }}
                </span>
            @endif
        </div>
    </div>
</div>

