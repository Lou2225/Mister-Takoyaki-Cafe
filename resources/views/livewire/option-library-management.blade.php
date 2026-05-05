<div x-data="{ panel: @entangle('panel'), mode: @entangle('mode'), tableView: @entangle('view') }" class="relative">
    {{-- ════════════════ PANEL 1 — LIST ════════════════ --}}
    <div x-show="panel === 'list'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">
        
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Options Library</h2>
                <p class="text-[12px] text-gray-500 font-medium">Reusable configurations: <span class="text-indigo-600 font-bold">{{ $templates->total() }} templates available</span></p>
            </div>
            <x-primary-button wire:click="showCreate" class="h-10">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Create New Template
            </x-primary-button>
        </div>

        {{-- Library Metrics Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all">
                <div class="w-10 h-10 rounded-xl bg-white border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-black text-indigo-700/60 uppercase tracking-widest leading-none mb-1">Total Templates</span>
                    <span class="block text-[20px] font-black text-gray-900 leading-none">{{ $templates->total() }}</span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-rose-50 to-rose-100 border border-rose-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all">
                <div class="w-10 h-10 rounded-xl bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-black text-rose-700/60 uppercase tracking-widest leading-none mb-1">Required Fields</span>
                    <span class="block text-[20px] font-black text-rose-600 leading-none">{{ \App\Models\OptionTemplate::where('is_required', true)->count() }}</span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all">
                <div class="w-10 h-10 rounded-xl bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-black text-emerald-700/60 uppercase tracking-widest leading-none mb-1">Total Variations</span>
                    <span class="block text-[20px] font-black text-emerald-600 leading-none">{{ \App\Models\OptionTemplateItem::count() }}</span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all">
                <div class="w-10 h-10 rounded-xl bg-white border border-amber-100 flex items-center justify-center text-amber-600 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-black text-amber-700/60 uppercase tracking-widest leading-none mb-1">Fixed Pricing</span>
                    <span class="block text-[20px] font-black text-gray-900 leading-none">{{ \App\Models\OptionTemplate::where('price_mode', 'fixed')->count() }}</span>
                </div>
            </div>
        </div>

        {{-- macOS Style Unified Toolbar --}}
        <div class="relative z-20 flex flex-col lg:flex-row lg:items-center justify-between mb-6 gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
            
            {{-- Left: Search Bar --}}
            <div class="flex flex-1 w-full lg:w-auto">
                <div class="relative w-full lg:w-72 group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" wire:model.debounce.300ms="search" placeholder="Search templates..." 
                        class="h-9 pl-9 pr-4 w-full bg-slate-50 border-none rounded-lg text-[13px] font-medium placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                </div>
            </div>

            {{-- Right: Filters & View Toggle --}}
            <div class="flex flex-wrap items-center lg:justify-end gap-2">
                {{-- Price Mode Filter --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <x-secondary-button type="button" class="gap-1.5 h-9 !px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span class="text-[12px] whitespace-nowrap">Pricing: {{ $priceModeFilter ? ucfirst($priceModeFilter) : 'All' }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </x-secondary-button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link href="#" wire:click.prevent="$set('priceModeFilter', '')">All Logic</x-dropdown-link>
                        <x-dropdown-link href="#" wire:click.prevent="$set('priceModeFilter', 'additive')">Additive</x-dropdown-link>
                        <x-dropdown-link href="#" wire:click.prevent="$set('priceModeFilter', 'fixed')">Fixed</x-dropdown-link>
                    </x-slot>
                </x-dropdown>

                {{-- macOS Divider --}}
                <div class="hidden lg:block w-px h-6 bg-slate-200 mx-2"></div>

                {{-- View Toggle --}}
                <button type="button" @click="tableView = (tableView === 'table' ? 'board' : 'table')"
                    class="w-9 h-9 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors focus:outline-none shrink-0"
                    :title="tableView === 'table' ? 'Switch to Board View' : 'Switch to Table View'">
                    <svg x-cloak x-show="tableView === 'table'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                    <svg x-cloak x-show="tableView === 'board'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
            </div>
        </div>

        {{-- ── Table View ── --}}
        <div x-show="tableView === 'table'" class="animate-fadeIn">
            <x-data-table>
                <x-slot name="header">
                    <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Template Name</th>
                    <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest text-center">Required</th>
                    <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest text-center">Price Mode</th>
                    <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest text-center">Option Items</th>
                    <th class="py-3 px-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                </x-slot>

                @forelse($templates as $tmpl)
                    <tr class="hover:bg-slate-50/50 transition-colors group cursor-pointer" wire:click="showEdit({{ $tmpl->id }})">
                        <td class="py-4 px-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                                <span class="font-bold text-[14px] text-slate-900">{{ $tmpl->name }}</span>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider {{ $tmpl->is_required ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-slate-50 text-slate-400 border border-slate-100' }}">
                                {{ $tmpl->is_required ? 'Required' : 'Optional' }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-600 border border-indigo-100">
                                {{ ucfirst($tmpl->price_mode) }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="text-[13px] font-bold text-slate-600">{{ $tmpl->items->count() }} Items</span>
                        </td>
                        <td class="py-4 px-4 text-right whitespace-nowrap" wire:click.stop>
                            <div class="flex items-center justify-end">
                                <x-secondary-button wire:click="showEdit({{ $tmpl->id }})" class="h-8 px-3 inline-flex items-center gap-1.5 text-xs shadow-none border-slate-200">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Edit Template
                                </x-secondary-button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-0">
                            <x-empty-state title="Library Empty" description="Start building your configuration templates to speed up product management." />
                        </td>
                    </tr>
                @endforelse
            </x-data-table>
        </div>

        {{-- ── Board View ── --}}
        <div x-show="tableView === 'board'" class="animate-fadeIn">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($templates as $tmpl)
                    <div class="group relative bg-white border border-slate-100 rounded-[24px] p-6 shadow-sm hover:shadow-xl hover:shadow-slate-200/50 hover:border-indigo-100 transition-all duration-300 cursor-pointer" wire:click="showEdit({{ $tmpl->id }})">
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-all duration-300 shadow-sm border border-slate-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            </div>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity" wire:click.stop>
                                <x-secondary-button wire:click="showEdit({{ $tmpl->id }})" class="h-8 px-3 inline-flex items-center gap-1.5 text-xs shadow-none border-slate-200 bg-white">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Edit Template
                                </x-secondary-button>
                            </div>
                        </div>

                        <h3 class="text-[16px] font-black text-slate-900 tracking-tight mb-1 group-hover:text-indigo-600 transition-colors">{{ $tmpl->name }}</h3>
                        <div class="flex items-center gap-2 mb-6">
                            <span class="text-[10px] font-black {{ $tmpl->is_required ? 'text-rose-500 bg-rose-50 border-rose-100' : 'text-slate-400 bg-slate-50 border-slate-100' }} border px-2 py-0.5 rounded-lg uppercase tracking-widest">{{ $tmpl->is_required ? 'Required' : 'Optional' }}</span>
                            <span class="text-[10px] font-black text-indigo-500 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-lg uppercase tracking-widest">{{ ucfirst($tmpl->price_mode) }}</span>
                        </div>

                        <div class="space-y-2.5 pt-4 border-t border-slate-50">
                            @foreach($tmpl->items->take(4) as $item)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        @if($item->is_default) <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> @endif
                                        <span class="text-[12px] font-bold text-slate-600">{{ $item->name }}</span>
                                    </div>
                                    @if($item->price > 0)
                                        <span class="text-[12px] font-black text-slate-900">₱{{ number_format($item->price, 2) }}</span>
                                    @endif
                                </div>
                            @endforeach
                            @if($tmpl->items->count() > 4)
                                <p class="text-[11px] font-bold text-indigo-400/70 italic pt-1 group-hover:text-indigo-500 transition-colors">+ {{ $tmpl->items->count() - 4 }} more variations</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-8">
            <x-pagination :paginator="$templates" />
        </div>
    </div>

    <div x-show="panel === 'form'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight" x-text="mode === 'create' ? 'Create New Template' : 'Refine Template'"></h2>
                <p class="text-[12px] text-gray-500 font-medium" x-text="mode === 'create' ? 'Establish a new reusable configuration group' : 'Modify variation items and pricing logic'"></p>
            </div>
            <x-secondary-button wire:click="backToList" class="h-10">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Library
            </x-secondary-button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content: Variation Items --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] min-h-[400px]">
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-50">
                        <div>
                            <h3 class="text-[13px] font-semibold text-gray-700 uppercase tracking-wider">Variation Options</h3>
                            <p class="text-[11px] text-gray-400 font-medium mt-0.5">Define the specific choices for this template.</p>
                        </div>
                        <button wire:click="addItem" class="flex items-center gap-2 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-[11px] font-bold hover:bg-indigo-600 hover:text-white transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                            Add Option
                        </button>
                    </div>

                    <div class="space-y-4">
                        @foreach($templateItems as $idx => $item)
                            <div class="flex items-center gap-4 p-4 rounded-xl bg-slate-50/50 border border-slate-100 hover:bg-white hover:border-indigo-100 hover:shadow-sm transition-all group animate-fadeIn">
                                <div class="flex-1">
                                    <x-input-label value="Option Name" class="text-[10px] mb-1 ml-1" />
                                    <x-text-input type="text" wire:model="templateItems.{{ $idx }}.name" 
                                        class="w-full h-10 text-[13px] font-bold text-slate-800 placeholder-slate-300" 
                                        placeholder="e.g. Regular Size" />
                                </div>
                                <div class="w-40">
                                    <x-input-label value="Extra Price" class="text-[10px] mb-1 ml-1" />
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-[12px] font-black text-slate-400">₱</span>
                                        </div>
                                        <x-text-input type="text" wire:model="templateItems.{{ $idx }}.price" 
                                            class="w-full h-10 pl-7 text-[13px] font-black text-slate-800 text-right" 
                                            placeholder="0.00" />
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 pt-5">
                                    <button wire:click="setItemAsDefault({{ $idx }})" 
                                        class="w-9 h-9 flex items-center justify-center rounded-lg border-2 transition-all {{ $item['is_default'] ? 'bg-emerald-500 border-emerald-500 text-white shadow-lg shadow-emerald-100' : 'bg-white border-slate-100 text-slate-300 hover:text-emerald-500 hover:border-emerald-100' }}" 
                                        title="Set as system default">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                    <button wire:click="removeItem({{ $idx }})" 
                                        class="w-9 h-9 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-300 hover:text-rose-500 hover:border-rose-100 hover:bg-rose-50 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach

                        @if(count($templateItems) === 0)
                            <div class="py-20 flex flex-col items-center justify-center border-2 border-dashed border-slate-100 rounded-2xl bg-slate-50/30">
                                <div class="w-16 h-16 bg-white border border-slate-100 rounded-2xl flex items-center justify-center text-slate-300 mb-4 shadow-sm">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                </div>
                                <h4 class="text-[14px] font-bold text-slate-900 tracking-tight">Empty Configuration</h4>
                                <p class="text-[12px] font-medium text-slate-400 mt-1 mb-6">Begin by adding your first variation option.</p>
                                <button wire:click="addItem" class="px-6 py-2 bg-slate-900 text-white text-[11px] font-black uppercase tracking-widest rounded-xl hover:bg-indigo-600 transition-all">Add First Option</button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Sidebar Content: Configuration & Actions --}}
            <div class="space-y-6">
                <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                    <h3 class="text-[13px] font-semibold text-gray-700 uppercase tracking-wider mb-5">Configuration Core</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <x-input-label value="Template Identity *" />
                            <x-text-input wire:model="name" class="w-full mt-1.5 h-10 font-bold text-slate-800" placeholder="e.g. Premium Flavors" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label value="Pricing Logic" />
                            <div class="grid grid-cols-2 gap-2 mt-1.5">
                                <button wire:click="$set('priceMode', 'additive')" 
                                    class="h-10 rounded-lg border-2 text-[11px] font-black uppercase tracking-wider transition-all {{ $priceMode === 'additive' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-100 text-slate-400 hover:border-slate-200' }}">
                                    Additive
                                </button>
                                <button wire:click="$set('priceMode', 'fixed')" 
                                    class="h-10 rounded-lg border-2 text-[11px] font-black uppercase tracking-wider transition-all {{ $priceMode === 'fixed' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-100 text-slate-400 hover:border-slate-200' }}">
                                    Fixed
                                </button>
                            </div>
                        </div>

                        <div class="pt-5 border-t border-slate-50">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" wire:model="isRequired" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <span class="text-[13px] font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">Mandatory Field</span>
                                    <p class="text-[11px] text-slate-400 font-medium leading-none mt-1">Require customer selection</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <x-primary-button wire:click="saveTemplate" class="w-full justify-center h-11 text-[12px] font-black uppercase tracking-widest shadow-lg shadow-indigo-100">
                        {{ $mode === 'create' ? 'Register Template' : 'Save Changes' }}
                    </x-primary-button>
                    <x-secondary-button wire:click="backToList" class="w-full justify-center h-11 text-[12px] font-black uppercase tracking-widest">
                        Cancel
                    </x-secondary-button>
                </div>

                @if($editTemplateId)
                    <div class="bg-red-50 border border-red-100 rounded-2xl p-6 shadow-sm mt-4">
                        <h2 class="text-[13px] font-bold text-red-600 uppercase tracking-wider mb-2">Danger Zone</h2>
                        <p class="text-[12px] text-gray-500 mb-4 leading-relaxed">Permanently remove this template from the library. This cannot be undone.</p>
                        <x-danger-button type="button" wire:click="confirmDeleteTemplate({{ $editTemplateId }})" class="w-full justify-center h-11">
                            Delete Template
                        </x-danger-button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Delete Template Modal ── --}}
    <x-modal name="delete-template" maxWidth="sm" focusable>
        <div class="h-1.5 w-full bg-gradient-to-r from-rose-400 to-pink-500 rounded-t-lg"></div>
        <div class="p-8 text-center">
            <div class="w-20 h-20 bg-rose-50 rounded-[28px] flex items-center justify-center text-rose-500 mx-auto mb-6 border border-rose-100 shadow-xl shadow-rose-100/50 animate-bounce-slow">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="text-[22px] font-black text-slate-900 tracking-tight mb-2">Delete Template?</h3>
            <p class="text-[14px] text-slate-500 font-medium leading-relaxed mb-8 px-4">
                You are about to remove <span class="font-black text-slate-900 underline decoration-rose-200">"{{ $deleteTargetName }}"</span> from the library.
            </p>
            
            <div class="flex items-center gap-3">
                <x-secondary-button @click="$dispatch('close-modal', 'delete-template')" class="flex-1 h-12 text-[12px] font-black uppercase tracking-widest border-slate-200">Keep It</x-secondary-button>
                <button wire:click="deleteTemplate" class="flex-1 h-12 bg-rose-500 text-white text-[12px] font-black uppercase tracking-widest rounded-xl hover:bg-rose-600 transition-all shadow-xl shadow-rose-200/40">Confirm Delete</button>
            </div>
        </div>
    </x-modal>
</div>
