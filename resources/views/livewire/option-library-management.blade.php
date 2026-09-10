@php
    $totalTemplates = $allTemplates->count();
    $requiredTemplates = $allTemplates->where('is_required', true)->count();
    $totalItems = $allTemplates->sum(fn($t) => $t->items->count());
    $fixedTemplates = $allTemplates->where('price_mode', 'fixed')->count();
@endphp

<div 
    x-data="typeof window.optionLibraryManagement === 'function' ? window.optionLibraryManagement($wire, @js($allIngredients)) : { panel: 'list', mode: 'list', tableView: 'table', searchQuery: '', templatesList: [], filteredTemplateIds: [], currentPage: 1, perPage: 5, isItemVisible: () => true, formErrors: {}, formSubmitted: false, isSaving: false }"
    class="relative"
    wire:ignore.self
    wire:key="option-library-main-container">

    {{-- Hidden reactive sync helper under wire:ignore --}}
    <div x-effect="updateTemplatesList(@js($allTemplates->map(fn($t) => [
        'id' => (int)$t->id,
        'name' => (string)$t->name,
        'price_mode' => (string)$t->price_mode,
        'is_required' => (bool)$t->is_required,
        'no_recipe_required' => (bool)$t->no_recipe_required,
        'items_count' => (int)$t->items->count(),
        'items' => $t->items->map(fn($i) => [
            'id' => (int)$i->id,
            'name' => (string)$i->name,
            'price' => (float)$i->price == 0 ? '' : (float)$i->price,
            'is_default' => (bool)$i->is_default,
            'ingredients' => $i->ingredients->map(fn($ri) => [
                'id' => (int)$ri->ingredient_id,
                'name' => $ri->ingredient ? (string)$ri->ingredient->name : 'Unknown',
                'unit' => $ri->ingredient ? (string)\App\Helpers\StockHelper::getAbbreviation($ri->ingredient->unit) : '',
                'quantity' => (float)$ri->quantity,
                'cost' => (float)($ri->ingredient?->cost ?? 0),
            ])->values(),
        ])->values(),
    ])))" class="hidden" wire:key="templates-sync-helper"></div>

    {{-- ════════════════ PANEL 1 — LIST ════════════════ --}}
    <div x-show="panel === 'list'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">
        
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Options Library</h2>
                <p class="text-[12px] text-gray-500 font-medium">Reusable configurations: <span class="text-indigo-600 font-bold" x-text="templatesList.length + ' templates available'"></span></p>
            </div>
            <x-primary-button type="button" @click="createTemplate()" class="h-10">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Create New Template
            </x-primary-button>
        </div>

        {{-- Library Metrics Grid --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            {{-- Total Templates --}}
            <div class="p-3 sm:p-4 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-white border border-indigo-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-1 sm:mb-2">
                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Templates</span>
                    <div class="w-7 h-7 rounded-lg bg-white border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $totalTemplates }}</h3>
                <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Configured library items</p>
            </div>

            {{-- Required Fields --}}
            <div class="p-3 sm:p-4 bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-white border border-rose-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-1 sm:mb-2">
                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Required</span>
                    <div class="w-7 h-7 rounded-lg bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-rose-600 tracking-tight leading-none">{{ $requiredTemplates }}</h3>
                <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Mandatory input fields</p>
            </div>

            {{-- Total Variations --}}
            <div class="p-3 sm:p-4 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-1 sm:mb-2">
                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Variations</span>
                    <div class="w-7 h-7 rounded-lg bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-emerald-600 tracking-tight leading-none">{{ $totalItems }}</h3>
                <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Individual option choices</p>
            </div>

            {{-- Fixed Pricing --}}
            <div class="p-3 sm:p-4 bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-white border border-amber-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-1 sm:mb-2">
                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Fixed Price</span>
                    <div class="w-7 h-7 rounded-lg bg-white border border-amber-100 flex items-center justify-center text-amber-600 shadow-sm shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $fixedTemplates }}</h3>
                <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Templates using set prices</p>
            </div>
        </div>

        {{-- macOS Style Unified Toolbar --}}
        <div class="relative z-20 flex flex-row items-center justify-between mb-6 gap-2 sm:gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
            
            {{-- Left: Search Bar --}}
            <div class="flex flex-1 min-w-0 lg:flex-initial">
                <x-search-bar x-model="searchQuery" placeholder="Search templates..." width="w-full lg:w-72" />
            </div>

            {{-- Right: Filters & View Toggle --}}
            <div class="flex flex-nowrap items-center justify-end gap-1.5 sm:gap-2 shrink-0">
                {{-- Price Mode Filter --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span class="hidden sm:inline text-[12px] whitespace-nowrap" x-text="priceModeFilter === '' ? 'Filter: All Logic' : (priceModeFilter === 'additive' ? 'Filter: Additive' : 'Filter: Fixed')"></span>
                            <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </x-secondary-button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link href="#" @click.prevent="priceModeFilter = ''; $dispatch('close')" ::class="priceModeFilter === '' ? 'bg-slate-50 font-bold text-indigo-600' : ''">All Logic</x-dropdown-link>
                        <x-dropdown-link href="#" @click.prevent="priceModeFilter = 'additive'; $dispatch('close')" ::class="priceModeFilter === 'additive' ? 'bg-slate-50 font-bold text-indigo-600' : ''">Additive</x-dropdown-link>
                        <x-dropdown-link href="#" @click.prevent="priceModeFilter = 'fixed'; $dispatch('close')" ::class="priceModeFilter === 'fixed' ? 'bg-slate-50 font-bold text-indigo-600' : ''">Fixed</x-dropdown-link>
                    </x-slot>
                </x-dropdown>

                {{-- macOS Divider --}}
                <div class="hidden lg:block w-px h-6 bg-slate-200 mx-2"></div>

                {{-- View Toggle --}}
                <button type="button" @click="tableView = (tableView === 'table' ? 'board' : 'table')"
                    class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors focus:outline-none shrink-0"
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

                @forelse($allTemplates as $tmpl)
                    <tr x-show="isItemVisible({{ $tmpl->id }})" x-cloak class="hover:bg-slate-50/50 transition-colors group cursor-pointer" @click="editTemplate({{ $tmpl->id }})">
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
                            <div class="flex items-center justify-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full {{ $tmpl->price_mode === 'fixed' ? 'bg-indigo-500' : 'bg-sky-500' }}"></span>
                                <span class="text-[11px] font-bold {{ $tmpl->price_mode === 'fixed' ? 'text-indigo-600' : 'text-sky-600' }} uppercase">{{ $tmpl->price_mode }}</span>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="text-[13px] font-bold text-slate-600">{{ $tmpl->items->count() }} Items</span>
                        </td>
                        <td class="py-4 px-4 text-right whitespace-nowrap" @click.stop>
                            <div class="flex items-center justify-end">
                                <x-secondary-button @click.stop="editTemplate({{ $tmpl->id }})" class="h-8 px-3 inline-flex items-center gap-1.5 text-xs shadow-none border-slate-200">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Edit Template
                                </x-secondary-button>
                            </div>
                        </td>
                    </tr>
                @empty
                    @if($allTemplates->count() === 0)
                        <tr>
                            <td colspan="5" class="py-0">
                                <x-empty-state title="Library Empty" description="Start building your configuration templates to speed up product management." />
                            </td>
                        </tr>
                    @endif
                @endforelse

                <tr x-show="filteredTemplateIds.length === 0" x-cloak>
                    <td colspan="5" class="py-0">
                        <x-empty-state title="No templates match your search" description="Try changing your filters or search terms." />
                    </td>
                </tr>
            </x-data-table>
        </div>

        {{-- ── Board View ── --}}
        <div x-show="tableView === 'board'" class="animate-fadeIn">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @forelse($allTemplates as $tmpl)
                    <div x-show="isItemVisible({{ $tmpl->id }})" x-cloak class="group relative bg-white border border-slate-100 rounded-[24px] p-6 shadow-sm hover:shadow-xl hover:shadow-slate-200/50 hover:border-indigo-100 transition-all duration-300 cursor-pointer" @click="editTemplate({{ $tmpl->id }})">
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-all duration-300 shadow-sm border border-slate-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            </div>
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                                <x-secondary-button @click.stop="editTemplate({{ $tmpl->id }})" class="h-8 px-3 inline-flex items-center gap-1.5 text-xs shadow-none border-slate-200 bg-white">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Edit
                                </x-secondary-button>
                                <x-danger-button type="button" @click.stop="confirmDelete({{ $tmpl->id }}, '{{ addslashes($tmpl->name) }}')" class="h-8 px-3 inline-flex items-center gap-1.5 text-xs shadow-none border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100">
                                    <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Delete
                                </x-danger-button>
                            </div>
                        </div>

                        <h3 class="text-[16px] font-black text-slate-900 tracking-tight mb-1 group-hover:text-indigo-600 transition-colors">{{ $tmpl->name }}</h3>
                        <div class="flex items-center gap-2 mb-6">
                            <span class="text-[10px] font-black {{ $tmpl->is_required ? 'text-rose-500 bg-rose-50 border-rose-100' : 'text-slate-400 bg-slate-50 border-slate-100' }} border px-2 py-0.5 rounded-lg uppercase tracking-widest">{{ $tmpl->is_required ? 'Required' : 'Optional' }}</span>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-white border border-slate-100 shadow-sm">
                                <span class="h-1.5 w-1.5 rounded-full {{ $tmpl->price_mode === 'fixed' ? 'bg-indigo-500' : 'bg-sky-500' }}"></span>
                                <span class="text-[10px] font-bold {{ $tmpl->price_mode === 'fixed' ? 'text-indigo-600' : 'text-sky-600' }} uppercase tracking-wider">{{ $tmpl->price_mode }}</span>
                            </span>
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
                @empty
                    @if($allTemplates->count() === 0)
                        <div class="col-span-full py-20 flex flex-col items-center justify-center border border-slate-100 rounded-3xl bg-white shadow-sm">
                            <x-empty-state title="Library Empty" description="Start building your configuration templates to speed up product management." />
                        </div>
                    @endif
                @endforelse

                <div x-show="filteredTemplateIds.length === 0" x-cloak class="col-span-full py-20 flex flex-col items-center justify-center border border-slate-100 rounded-3xl bg-white shadow-sm">
                    <x-empty-state title="No templates match your search" description="Try changing your filters or search terms." />
                </div>
            </div>
        </div>

        {{-- Premium Alpine-driven Paginator --}}
        <div class="mt-6">
            <template x-if="filteredTemplateIds.length > 0">
                <div class="flex flex-col lg:flex-row items-center justify-between px-4 py-4 bg-white border border-gray-100 rounded-2xl lg:px-6 gap-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row items-center justify-between w-full lg:w-auto gap-4 sm:gap-8">
                        <div class="flex items-center gap-3">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:gap-1.5 leading-[0.9] sm:leading-none">
                                <span class="text-[11px] sm:text-[12px] text-gray-400 font-black uppercase tracking-tighter sm:tracking-widest">Row</span>
                                <span class="text-[9px] sm:text-[12px] text-gray-400/70 font-black uppercase tracking-tighter sm:tracking-widest">per page</span>
                            </div>
                            <div>
                                <x-dropdown align="top" width="20" containerClasses="block">
                                    <x-slot name="trigger">
                                        <button type="button" class="inline-flex items-center justify-between min-w-[70px] px-3 py-1.5 text-[13px] font-black text-gray-900 bg-slate-50 border border-gray-200 rounded-xl hover:border-gray-300 focus:outline-none transition-all h-10 gap-2 shadow-sm font-sans">
                                            <span x-text="perPage"></span>
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <template x-for="option in [5, 10, 15, 30, 50, 100]">
                                            <x-dropdown-link href="#" x-on:click.prevent="perPage = option; currentPage = 1; dropdownOpen = false;">
                                                <span x-text="option"></span>
                                            </x-dropdown-link>
                                        </template>
                                    </x-slot>
                                </x-dropdown>
                            </div>
                        </div>
                        <div class="text-[11px] text-gray-400 font-bold uppercase tracking-widest whitespace-nowrap">
                            <span class="text-gray-900" x-text="Math.min(filteredTemplateIds.length, (currentPage - 1) * perPage + 1)"></span>
                            <span class="mx-0.5 text-gray-300">-</span>
                            <span class="text-gray-900" x-text="Math.min(filteredTemplateIds.length, currentPage * perPage)"></span>
                            <span class="mx-1 text-gray-300 lowercase italic font-medium">of</span>
                            <span class="text-indigo-600" x-text="filteredTemplateIds.length"></span>
                            <span class="ml-1 text-gray-300 lowercase italic font-medium">results</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 w-full lg:w-auto justify-center lg:justify-end border-t border-gray-50 pt-4 lg:border-0 lg:pt-0">
                        <x-secondary-button @click="currentPage = 1" ::disabled="currentPage === 1" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="currentPage === 1 ? 'opacity-30 pointer-events-none' : ''">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                            </svg>
                        </x-secondary-button>
                        <x-secondary-button @click="currentPage = Math.max(1, currentPage - 1)" ::disabled="currentPage === 1" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="currentPage === 1 ? 'opacity-30 pointer-events-none' : ''">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </x-secondary-button>
                        <div class="flex items-center gap-1.5 px-2">
                            <template x-for="page in pageNumbers">
                                <div class="flex items-center gap-1.5">
                                    <template x-if="page === currentPage">
                                        <x-primary-button class="!p-0 w-9 h-9 items-center justify-center !rounded-xl bg-gray-900 text-[13px] font-black shadow-none ring-0">
                                            <span x-text="page"></span>
                                        </x-primary-button>
                                    </template>
                                    <template x-if="page !== currentPage">
                                        <x-secondary-button @click="currentPage = page" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl text-[13px] font-bold">
                                            <span x-text="page"></span>
                                        </x-secondary-button>
                                    </template>
                                </div>
                            </template>
                            
                            <template x-if="Math.ceil(filteredTemplateIds.length / perPage) > currentPage + 1">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-gray-300 font-bold mx-1">...</span>
                                    <x-secondary-button @click="currentPage = Math.ceil(filteredTemplateIds.length / perPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl text-[13px] font-bold">
                                        <span x-text="Math.ceil(filteredTemplateIds.length / perPage)"></span>
                                    </x-secondary-button>
                                </div>
                            </template>
                        </div>
                        <x-secondary-button @click="currentPage = Math.min(Math.ceil(filteredTemplateIds.length / perPage), currentPage + 1)" ::disabled="currentPage >= Math.ceil(filteredTemplateIds.length / perPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="currentPage >= Math.ceil(filteredTemplateIds.length / perPage) ? 'opacity-30 pointer-events-none' : ''">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </x-secondary-button>
                        <x-secondary-button @click="currentPage = Math.ceil(filteredTemplateIds.length / perPage) || 1" ::disabled="currentPage >= Math.ceil(filteredTemplateIds.length / perPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="currentPage >= Math.ceil(filteredTemplateIds.length / perPage) ? 'opacity-30 pointer-events-none' : ''">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                            </svg>
                        </x-secondary-button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="panel === 'form'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight" x-text="mode === 'create' ? 'Create New Template' : 'Refine Template'"></h2>
                <p class="text-[12px] text-gray-500 font-medium" x-text="mode === 'create' ? 'Establish a new reusable configuration group' : 'Modify variation items and pricing logic'"></p>
            </div>
            <x-secondary-button type="button" @click="backToList()" class="h-10">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Library
            </x-secondary-button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content: Variation Items --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] min-h-[560px] flex flex-col">
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-50">
                        <div>
                            <h3 class="text-[13px] font-semibold text-gray-700 uppercase tracking-wider">Variation Options</h3>
                            <p class="text-[11px] text-gray-400 font-medium mt-0.5">Define the specific choices for this template.</p>
                        </div>
                        <button type="button" @click="addOption()" class="flex items-center gap-2 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-[11px] font-bold hover:bg-indigo-600 hover:text-white transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                            Add Option
                        </button>
                    </div>

                    {{-- Options Level Validation Error Banner --}}
                    <div x-show="typeof formErrors !== 'undefined' && formErrors && formErrors.templateItems" 
                        class="text-[11px] font-medium text-red-500 mb-4 p-3 bg-red-50/50 border border-red-200/80 rounded-xl flex items-start gap-2 animate-in fade-in slide-in-from-top-1 duration-200" 
                        x-cloak>
                        <svg class="w-4 h-4 shrink-0 mt-0.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span x-text="(typeof formErrors !== 'undefined' && formErrors && formErrors.templateItems) || ''"></span>
                    </div>
                    @error('templateItems')
                        <p class="mb-3 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror

                    {{-- Dynamic Alpine-driven Option Cards List --}}
                    <div class="space-y-4 flex-1">
                        <template x-for="(item, idx) in templateItems" :key="idx">
                            <div class="p-4 rounded-xl bg-slate-50/50 border border-slate-100 hover:bg-white hover:border-indigo-100 hover:shadow-sm transition-all group animate-fadeIn mb-4"
                                x-data="{
                                    showRecipe: (item.ingredients && item.ingredients.length > 0),
                                    open: false,
                                    dropUp: false,
                                    search: '',
                                    selectedId: 0,
                                    qty: '',
                                    errorMessage: '',
                                    get allIngredients() {
                                        return (typeof ingredientsList !== 'undefined' && ingredientsList) ? ingredientsList : [];
                                    },
                                    get selectedItem() {
                                        if (!this.selectedId) return null;
                                        return this.allIngredients.find(i => Number(i.id) === Number(this.selectedId)) || null;
                                    },
                                    get selectedUnit() {
                                        return this.selectedItem ? this.selectedItem.unit : '';
                                    },
                                    get filteredItems() {
                                        if (!this.search.trim()) return this.allIngredients;
                                        const q = this.search.toLowerCase().trim();
                                        return this.allIngredients.filter(i => (i.name || '').toLowerCase().includes(q));
                                    },
                                    openDropdown() {
                                        this.checkFlip();
                                        this.open = true;
                                        this.search = '';
                                    },
                                    closeDropdown() {
                                        this.open = false;
                                        this.search = '';
                                    },
                                    checkFlip() {
                                        if (!this.$refs.comboboxContainer) return;
                                        const rect = this.$refs.comboboxContainer.getBoundingClientRect();
                                        const spaceBelow = window.innerHeight - rect.bottom;
                                        this.dropUp = spaceBelow < 250 && rect.top > 250;
                                    },
                                    select(id) {
                                        this.selectedId = Number(id);
                                        this.search = '';
                                        this.open = false;
                                        this.errorMessage = '';
                                    },
                                    clear() {
                                        this.selectedId = 0;
                                        this.search = '';
                                        this.open = false;
                                        this.errorMessage = '';
                                    },
                                    addIngredient() {
                                        if (!this.selectedId) {
                                            this.errorMessage = 'Please select an ingredient.';
                                            return;
                                        }
                                        const qtyNum = parseFloat(this.qty);
                                        if (!qtyNum || qtyNum <= 0) {
                                            this.errorMessage = 'Quantity must be at least 0.01.';
                                            return;
                                        }
                                        const ing = this.selectedItem;
                                        if (!ing) return;

                                        if (!item.ingredients) {
                                            item.ingredients = [];
                                        }

                                        const exists = item.ingredients.some(ri => Number(ri.id) === Number(this.selectedId));
                                        if (exists) {
                                            this.errorMessage = 'Ingredient already added to this option.';
                                            return;
                                        }

                                        this.errorMessage = '';
                                        item.ingredients.push({
                                            id: ing.id,
                                            name: ing.name,
                                            unit: ing.unit,
                                            quantity: qtyNum,
                                            cost: Number(ing.cost || 0)
                                        });

                                        this.clear();
                                        this.qty = '';
                                    },
                                    removeIngredient(ingIdx) {
                                        item.ingredients.splice(ingIdx, 1);
                                    }
                                }">

                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <x-input-label value="Option Name" class="text-[10px] mb-1 ml-1" />
                                        <x-text-input type="text" x-model="item.name" 
                                            @input="if (typeof formErrors !== 'undefined' && formErrors) { delete formErrors.itemNames; delete formErrors.templateItems; }"
                                            ::class="(typeof formSubmitted !== 'undefined' && formSubmitted && (!item.name || !item.name.trim())) ? '!border-red-400 focus:!border-red-400 focus:!ring-red-300 !bg-red-50/30' : ''"
                                            class="w-full h-10 text-[13px] font-bold text-slate-800 placeholder-slate-300" 
                                            placeholder="e.g. Regular Size" />
                                        <div x-show="typeof formSubmitted !== 'undefined' && formSubmitted && (!item.name || !item.name.trim())" 
                                            class="text-[11px] font-medium text-red-500 mt-1.5 flex items-start gap-1.5 animate-in fade-in slide-in-from-top-1 duration-200" 
                                            x-cloak>
                                            <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <ul class="space-y-0.5">
                                                <li>Option name is required.</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="w-40">
                                        <x-input-label value="Extra Price" class="text-[10px] mb-1 ml-1" />
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-[12px] font-black text-slate-400">₱</span>
                                            </div>
                                            <x-text-input type="text" x-model="item.price" 
                                                class="w-full h-10 pl-7 text-[13px] font-black text-slate-800 text-right" 
                                                placeholder="0.00" inputFilter="price" />
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 pt-5">
                                        <button type="button" x-show="!noRecipeRequired" @click="showRecipe = !showRecipe"
                                            class="w-9 h-9 flex items-center justify-center rounded-lg border-2 transition-all relative"
                                            :class="showRecipe ? 'bg-amber-500 border-amber-500 text-white shadow-lg shadow-amber-100' : 'bg-white border-slate-100 text-slate-300 hover:text-amber-500 hover:border-amber-100'"
                                            :title="item.ingredients && item.ingredients.length > 0 ? (item.ingredients.length + ' ingredients mapped') : 'Toggle recipe ingredients'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                            <span x-show="item.ingredients && item.ingredients.length > 0" class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-amber-500 rounded-full border-2 border-white"></span>
                                        </button>
                                        <button type="button" @click="toggleDefault(idx)"
                                            class="w-9 h-9 flex items-center justify-center rounded-lg border-2 transition-all"
                                            :class="item.is_default ? 'bg-emerald-500 border-emerald-500 text-white shadow-lg shadow-emerald-100' : 'bg-white border-slate-100 text-slate-300 hover:text-emerald-500 hover:border-emerald-100'" 
                                            title="Toggle system default">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                        <button type="button" @click="removeOption(idx)" 
                                            class="w-9 h-9 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-300 hover:text-rose-500 hover:border-rose-100 hover:bg-rose-50 transition-all"
                                            title="Remove option">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Per-item recipe: ingredients attached here travel with the template on import --}}
                                <div x-show="!noRecipeRequired && showRecipe" x-collapse class="mt-4 pt-4 border-t border-slate-100/80">
                                    <div class="flex items-end gap-2 mb-3">
                                        <div class="flex-1">
                                            <x-input-label value="Select Ingredient" class="text-[10px] mb-1 ml-1" />
                                            <div class="relative" x-ref="comboboxContainer"
                                                @click.outside="closeDropdown()"
                                                @keydown.escape.window="closeDropdown()">

                                                {{-- Search / Select Input Box --}}
                                                <div class="relative flex items-center">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                        </svg>
                                                    </div>

                                                    <input 
                                                        type="text"
                                                        :value="open ? search : (selectedItem ? selectedItem.name : '')"
                                                        @input="search = $event.target.value; open = true"
                                                        @focus="openDropdown()"
                                                        @click="openDropdown()"
                                                        :placeholder="selectedItem ? selectedItem.name : 'Search or select ingredient...'"
                                                        class="w-full pl-9 pr-9 py-2 bg-white border border-slate-200 rounded-lg text-[12px] text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-9"
                                                        :class="selectedItem && !open ? 'font-bold text-indigo-700 bg-indigo-50/20 border-indigo-200' : 'text-slate-800'"
                                                        autocomplete="off"
                                                    />

                                                    {{-- Clear button only (NO up/down arrow!) --}}
                                                    <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center">
                                                        <button type="button" 
                                                            x-show="selectedId > 0 || search.length > 0"
                                                            x-cloak
                                                            @click.stop="clear()"
                                                            title="Clear selection"
                                                            class="p-0.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- Dropdown Results Menu with auto-flip --}}
                                                <div x-show="open" x-cloak
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 scale-95"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100 scale-100"
                                                    x-transition:leave-end="opacity-0 scale-95"
                                                    :class="dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                                    class="absolute left-0 right-0 z-50 bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-56 overflow-y-auto custom-scrollbar">
                                                    <template x-for="ing in filteredItems" :key="ing.id">
                                                        <button type="button" 
                                                            @click="select(ing.id)"
                                                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors flex items-center justify-between group"
                                                            :class="selectedId === ing.id ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                            <span class="text-[12px] font-medium group-hover:font-semibold truncate" x-text="ing.name"></span>
                                                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 group-hover:bg-indigo-100 group-hover:text-indigo-600 shrink-0 ml-2" x-text="ing.unit"></span>
                                                        </button>
                                                    </template>
                                                    <template x-if="filteredItems.length === 0">
                                                        <div class="px-4 py-3 text-[12px] text-slate-400 italic text-center">
                                                            No ingredients found
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-28">
                                            <x-input-label value="Qty" class="text-[10px] mb-1 ml-1" />
                                            <div class="relative">
                                                <x-text-input x-model="qty" @keydown.enter.prevent="addIngredient()" class="w-full h-9 pr-12 text-[12px] font-bold" placeholder="0.00" inputFilter="price" />
                                                <span class="absolute inset-y-0 right-2 flex items-center text-[9px] font-black text-slate-400 uppercase pointer-events-none" x-text="selectedUnit || '—'">—</span>
                                            </div>
                                        </div>
                                        <x-secondary-button type="button" @click="addIngredient()" class="h-9 px-3 text-[10px] font-black uppercase tracking-wider">Add</x-secondary-button>
                                    </div>

                                    {{-- Local validation error --}}
                                    <div x-show="errorMessage" x-cloak class="mb-3 p-2 bg-rose-50 border border-rose-100 rounded-lg text-[11px] font-bold text-rose-600 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span x-text="errorMessage"></span>
                                    </div>

                                    {{-- Ingredients List --}}
                                    <div x-show="item.ingredients && item.ingredients.length > 0" class="space-y-1.5">
                                        <template x-for="(ri, ingIdx) in (item.ingredients || [])" :key="ri.id || ingIdx">
                                            <div class="flex items-center justify-between px-3 py-1.5 bg-white border border-slate-100 rounded-lg shadow-xs hover:border-slate-200 transition-colors">
                                                <span class="text-[11px] font-bold text-slate-700" x-text="ri.name"></span>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] font-bold text-slate-400 uppercase" x-text="ri.quantity + ' ' + (ri.unit || '')"></span>
                                                    <button type="button" @click="removeIngredient(ingIdx)" class="text-slate-300 hover:text-rose-500 p-0.5 rounded transition-colors" title="Remove ingredient">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="!item.ingredients || item.ingredients.length === 0" class="text-[11px] text-slate-400 italic py-1">
                                        No ingredients mapped yet for this option.
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Empty Configuration state --}}
                        <div x-show="!templateItems || templateItems.length === 0" class="py-20 flex flex-col items-center justify-center border-2 border-dashed border-slate-100 rounded-2xl bg-slate-50/30">
                            <div class="w-16 h-16 bg-white border border-slate-100 rounded-2xl flex items-center justify-center text-slate-300 mb-4 shadow-sm">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            </div>
                            <h4 class="text-[14px] font-bold text-slate-900 tracking-tight">Empty Configuration</h4>
                            <p class="text-[12px] font-medium text-slate-400 mt-1 mb-6">Begin by adding your first variation option.</p>
                            <button type="button" @click="addOption()" class="px-6 py-2 bg-slate-900 text-white text-[11px] font-black uppercase tracking-widest rounded-xl hover:bg-indigo-600 transition-all">Add First Option</button>
                        </div>
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
                            <x-text-input x-model="templateName" 
                                @input="if (typeof formErrors !== 'undefined' && formErrors) delete formErrors.name" 
                                class="w-full mt-1.5 h-10 font-bold text-slate-800" 
                                ::class="(typeof formErrors !== 'undefined' && formErrors && formErrors.name) ? '!border-red-400 focus:!border-red-400 focus:!ring-red-300 !bg-red-50/30' : ''"
                                placeholder="e.g. Premium Flavors" />
                            <div x-show="typeof formErrors !== 'undefined' && formErrors && formErrors.name" 
                                class="text-[11px] font-medium text-red-500 mt-1.5 flex items-start gap-1.5 animate-in fade-in slide-in-from-top-1 duration-200" 
                                x-cloak>
                                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <ul class="space-y-0.5">
                                    <li x-text="(typeof formErrors !== 'undefined' && formErrors && formErrors.name) || ''"></li>
                                </ul>
                            </div>
                            @error('name')
                                <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-input-label value="Pricing Logic" />
                            <div class="grid grid-cols-2 gap-2 mt-1.5">
                                <button type="button" @click="priceMode = 'additive'" 
                                    class="h-10 rounded-lg border-2 text-[11px] font-black uppercase tracking-wider transition-all"
                                    :class="priceMode === 'additive' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-100 text-slate-400 hover:border-slate-200'">
                                    Additive
                                </button>
                                <button type="button" @click="priceMode = 'fixed'" 
                                    class="h-10 rounded-lg border-2 text-[11px] font-black uppercase tracking-wider transition-all"
                                    :class="priceMode === 'fixed' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-100 text-slate-400 hover:border-slate-200'">
                                    Fixed
                                </button>
                            </div>
                        </div>

                        <div class="pt-5 border-t border-slate-50 space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" x-model="noRecipeRequired" @change="if(noRecipeRequired) templateItems.forEach(it => it.ingredients = [])" class="w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                <div>
                                    <span class="text-[13px] font-bold text-slate-800 group-hover:text-amber-600 transition-colors">No Recipe Required</span>
                                    <p class="text-[11px] text-slate-400 font-medium leading-none mt-1">Options skip ingredient tracking and are always available</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" x-model="isRequired" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <span class="text-[13px] font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">Mandatory Field</span>
                                    <p class="text-[11px] text-slate-400 font-medium leading-none mt-1">Require customer selection</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <x-primary-button type="button" @click="validateAndPromptSave()" class="w-full justify-center" x-text="mode === 'create' ? 'Register Template' : 'Save Changes'"></x-primary-button>
                    <x-secondary-button type="button" @click="backToList()" class="w-full justify-center">
                        <span>Cancel</span>
                    </x-secondary-button>
                </div>

                <div x-show="mode === 'edit' && editTemplateId" class="bg-red-50 border border-red-100 rounded-2xl p-6 shadow-sm mt-4">
                    <h2 class="text-[13px] font-bold text-red-600 uppercase tracking-wider mb-2">Danger Zone</h2>
                    <p class="text-[12px] text-gray-500 mb-4 leading-relaxed">Permanently remove this template from the library. This cannot be undone.</p>
                    <x-danger-button type="button" @click="confirmDelete(editTemplateId, templateName)" class="w-full justify-center h-11">
                        Delete Template
                    </x-danger-button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Delete Template Modal ── --}}
    <x-modal name="delete-template" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-red-400 to-rose-500 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-50 border border-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Delete Template</h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">
                        You are about to remove this template from the library. This cannot be undone.
                    </p>
                </div>
            </div>
            
            <div class="px-3 py-2 bg-white border border-gray-100 rounded-lg text-[13px] text-gray-600 mb-5">
                <span class="font-semibold text-gray-800" x-text="deleteTargetName"></span>
            </div>

            <div class="flex items-center justify-end gap-2">
                <x-secondary-button @click="$dispatch('close-modal', 'delete-template')">Cancel</x-secondary-button>
                <x-danger-button wire:click="deleteTemplate" @click="$dispatch('close-modal', 'delete-template')">Delete</x-danger-button>
            </div>
        </div>
    </x-modal>

    {{-- ── Confirm Save Template Modal ── --}}
    <x-modal name="confirm-save-template" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-emerald-400 to-teal-500 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight" x-text="mode === 'create' ? 'Create Template' : 'Save Changes'"></h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed" x-text="mode === 'create' ? 'This will add a new template to the options library.' : 'This will persist the current template updates.'"></p>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-save-template')" ::disabled="isSaving" class="h-10">Cancel</x-secondary-button>
                <x-primary-button 
                    type="button"
                    @click="confirmSave()" 
                    class="h-10"
                    ::disabled="isSaving">
                    <span x-show="!isSaving">Confirm & Save</span>
                    <span x-show="isSaving" x-cloak class="flex items-center gap-1.5">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Saving...
                    </span>
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <script>
        (function() {
            const defineFn = function() {
                window.optionLibraryManagement = function($wire, allIngredients) {
                    return {
                        panel: $wire.entangle('panel').live,
                        mode: $wire.entangle('mode').live,
                        tableView: $wire.entangle('view').live,
                        editTemplateId: $wire.entangle('editTemplateId'),
                        templateName: $wire.entangle('name'),
                        priceMode: $wire.entangle('priceMode'),
                        isRequired: $wire.entangle('isRequired'),
                        noRecipeRequired: $wire.entangle('noRecipeRequired'),
                        templateItems: $wire.entangle('templateItems'),
                        deleteTargetId: $wire.entangle('deleteTargetId'),
                        deleteTargetName: $wire.entangle('deleteTargetName'),
                        priceModeFilter: '',
                        searchQuery: '',
                        currentPage: 1,
                        perPage: 5,
                        templatesList: [],
                        ingredientsList: allIngredients || [],
                        formErrors: {},
                        formSubmitted: false,
                        isSaving: false,

                        get filteredTemplateIds() {
                            const query = (this.searchQuery || '').toLowerCase().trim();
                            const filter = this.priceModeFilter;
                            return (this.templatesList || [])
                                .filter(t => {
                                    const matchesSearch = !query || 
                                        (t.name || '').toLowerCase().includes(query) || 
                                        (t.items && t.items.some(item => (item.name || '').toLowerCase().includes(query)));
                                    
                                    const matchesMode = !filter || t.price_mode === filter;
                                    
                                    return matchesSearch && matchesMode;
                                })
                                .map(t => t.id);
                        },
                        get paginatedTemplateIds() {
                            const start = (this.currentPage - 1) * this.perPage;
                            return this.filteredTemplateIds.slice(start, start + this.perPage);
                        },
                        get pageNumbers() {
                            const totalPages = Math.ceil(this.filteredTemplateIds.length / this.perPage) || 1;
                            const start = Math.max(1, this.currentPage - 1);
                            const end = Math.min(totalPages, this.currentPage + 1);
                            const pages = [];
                            for (let i = start; i <= end; i++) {
                                pages.push(i);
                            }
                            return pages;
                        },
                        isItemVisible(id) {
                            return this.paginatedTemplateIds.includes(id);
                        },
                        updateTemplatesList(newList) {
                            this.templatesList = newList || [];
                        },

                        // ── Instant 0ms Actions ──
                        createTemplate() {
                            this.editTemplateId = null;
                            this.templateName = '';
                            this.priceMode = 'additive';
                            this.isRequired = false;
                            this.noRecipeRequired = false;
                            this.templateItems = [
                                { id: null, name: '', price: '', is_default: true, ingredients: [] }
                            ];
                            this.formErrors = {};
                            this.formSubmitted = false;
                            this.panel = 'form';
                            this.mode = 'create';
                            this.$wire.showCreate();
                        },
                        editTemplate(id) {
                            const tmpl = (this.templatesList || []).find(t => Number(t.id) === Number(id));
                            if (tmpl) {
                                this.editTemplateId = tmpl.id;
                                this.templateName = tmpl.name;
                                this.priceMode = tmpl.price_mode;
                                this.isRequired = tmpl.is_required;
                                this.noRecipeRequired = tmpl.no_recipe_required;
                                this.templateItems = JSON.parse(JSON.stringify(tmpl.items || []));
                                this.formErrors = {};
                                this.formSubmitted = false;
                                this.panel = 'form';
                                this.mode = 'edit';
                            }
                            this.$wire.showEdit(id);
                        },
                        backToList() {
                            this.panel = 'list';
                            this.mode = 'list';
                            this.formErrors = {};
                            this.formSubmitted = false;
                            this.$wire.backToList();
                        },
                        confirmDelete(id, name) {
                            this.deleteTargetId = id;
                            this.deleteTargetName = name;
                            this.$wire.deleteTargetId = id;
                            this.$wire.deleteTargetName = name;
                            this.$dispatch('open-modal', 'delete-template');
                        },
                        addOption() {
                            if (!this.templateItems) this.templateItems = [];
                            this.templateItems.push({
                                id: null,
                                name: '',
                                price: '',
                                is_default: this.templateItems.length === 0,
                                ingredients: []
                            });
                            delete this.formErrors.templateItems;
                        },
                        removeOption(idx) {
                            if (this.templateItems) {
                                this.templateItems.splice(idx, 1);
                            }
                        },
                        toggleDefault(idx) {
                            if (!this.templateItems || !this.templateItems[idx]) return;
                            const isCurrent = !!this.templateItems[idx].is_default;
                            this.templateItems.forEach((item, i) => {
                                item.is_default = (i === idx && !isCurrent);
                            });
                        },

                        // ── Validation & Save Actions ──
                        validateAndPromptSave() {
                            this.formErrors = {};
                            this.formSubmitted = true;
                            const trimmedName = (this.templateName || '').trim();

                            if (!trimmedName) {
                                this.formErrors.name = 'Template name is required.';
                            } else {
                                const dup = (this.templatesList || []).find(t => 
                                    (t.name || '').trim().toLowerCase() === trimmedName.toLowerCase() && 
                                    Number(t.id) !== Number(this.editTemplateId)
                                );
                                if (dup) {
                                    this.formErrors.name = `A template named "${trimmedName}" already exists in the library.`;
                                }
                            }

                            if (!this.templateItems || this.templateItems.length === 0) {
                                this.formErrors.templateItems = 'At least one variation option is required.';
                            } else {
                                const emptyItem = this.templateItems.some(item => !(item.name || '').trim());
                                if (emptyItem) {
                                    this.formErrors.itemNames = 'Every option must have a name.';
                                }
                            }

                            if (Object.keys(this.formErrors).length > 0) {
                                return; // DO NOT OPEN MODAL! Inline errors appear below fields
                            }

                            this.$dispatch('open-modal', 'confirm-save-template');
                        },

                        async confirmSave() {
                            this.$dispatch('close-modal', 'confirm-save-template');
                            this.isSaving = true;
                            try {
                                const payload = {
                                    id: this.editTemplateId,
                                    name: (this.templateName || '').trim(),
                                    priceMode: this.priceMode || 'additive',
                                    isRequired: !!this.isRequired,
                                    noRecipeRequired: !!this.noRecipeRequired,
                                    templateItems: JSON.parse(JSON.stringify(this.templateItems || []))
                                };
                                await this.$wire.saveTemplate(payload);
                            } catch (err) {
                                console.error('Error saving template:', err);
                                this.$dispatch('notify', { type: 'error', message: 'Failed to save template.' });
                            } finally {
                                this.isSaving = false;
                            }
                        },

                        init() {
                            this.$watch('searchQuery', () => { this.currentPage = 1; });
                            this.$watch('priceModeFilter', () => { this.currentPage = 1; });
                            this.$watch('perPage', () => { this.currentPage = 1; });
                        }
                    };
                };
            };
            defineFn();
        })();
    </script>
</div>



