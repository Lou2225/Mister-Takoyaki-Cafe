@php
    $user = auth()->user();
    // Primary palette
    $primaryColor = $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald');
    $primaryText = "text-{$primaryColor}-600";
    $primaryBg = "bg-{$primaryColor}-600";
@endphp

<div
    x-data="{ 
        filterType: @entangle('filterType'),
        panel: @js($panel),
        deleteTargetId: null,
        deleteTargetName: ''
    }"
    class="relative"
    x-on:switch-panel.window="panel = $event.detail?.panel || $event.detail[0]?.panel || 'list'">

    <div class="relative min-h-[600px]">

        {{-- ════════════════ PANEL 1 — LIST ════════════════ --}}
        <div x-show="panel === 'list'" 
            x-transition:enter="transition ease-out duration-300" 
            x-transition:enter-start="opacity-0 lg:translate-x-4" 
            x-transition:enter-end="opacity-100 lg:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 lg:translate-x-0"
            x-transition:leave-end="opacity-0 lg:translate-x-4"
            x-cloak class="px-1">

            {{-- Dynamic Header: Management Hub --}}
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Category Intelligence</h2>
                    <p class="text-[12px] text-gray-500 font-medium">Unified Hub: <span class="text-indigo-600 font-bold">{{ $categories->total() }} segments</span></p>
                </div>
                <div class="flex items-center gap-2">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <x-primary-button class="h-10">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                Create Category
                            </x-primary-button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link href="#" wire:click.prevent="showCreate('product')">Product Category</x-dropdown-link>
                            <x-dropdown-link href="#" wire:click.prevent="showCreate('ingredient')">Ingredient Category</x-dropdown-link>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            {{-- System Metrics Grid (User Management Aesthetic) --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                @php $stats = $this->kpiStats; @endphp
                
                {{-- Total Segments --}}
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all">
                    <div class="w-10 h-10 rounded-xl bg-white border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black text-indigo-700/60 uppercase tracking-widest leading-none mb-1">Total Categories</span>
                        <span class="block text-[20px] font-black text-gray-900 leading-none">{{ number_format($stats['total_count']) }}</span>
                    </div>
                </div>

                {{-- Product Focus --}}
                <div class="bg-gradient-to-br from-violet-50 to-violet-100 border border-violet-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all">
                    <div class="w-10 h-10 rounded-xl bg-white border border-violet-100 flex items-center justify-center text-violet-600 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" /></svg>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black text-violet-700/60 uppercase tracking-widest leading-none mb-1">Product Groups</span>
                        <span class="block text-[20px] font-black text-violet-600 leading-none">{{ number_format($stats['product_count']) }}</span>
                    </div>
                </div>

                {{-- Ingredient Focus --}}
                <div class="bg-gradient-to-br from-rose-50 to-rose-100 border border-rose-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all">
                    <div class="w-10 h-10 rounded-xl bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black text-rose-700/60 uppercase tracking-widest leading-none mb-1">Inventory Assets</span>
                        <span class="block text-[20px] font-black text-rose-600 leading-none">{{ number_format($stats['ingredient_count']) }}</span>
                    </div>
                </div>
            </div>

            {{-- macOS Style Unified Toolbar --}}
            <div class="relative z-20 flex flex-col lg:flex-row lg:items-center justify-between mb-6 gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
                
                {{-- Left: Search Bar --}}
                <div class="flex flex-1 w-full lg:w-auto">
                    <x-search-bar wireModel="search" placeholder="Search across catalog..." width="w-full lg:w-72" />
                </div>

                {{-- Right: Filters --}}
                <div class="flex flex-wrap items-center lg:justify-end gap-2">
                    {{-- Type Filter --}}
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <x-secondary-button type="button" class="gap-1.5 h-9 !px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                                <span class="text-[12px] whitespace-nowrap">{{ $filterType === 'all' ? 'All Types' : ucfirst($filterType) . ' Only' }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </x-secondary-button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link href="#" wire:click.prevent="$set('filterType', 'all')">All Category Types</x-dropdown-link>
                            <x-dropdown-link href="#" wire:click.prevent="$set('filterType', 'product')">Product Focus</x-dropdown-link>
                            <x-dropdown-link href="#" wire:click.prevent="$set('filterType', 'ingredient')">Ingredient Focus</x-dropdown-link>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            {{-- Panel Container --}}
            <div class="relative min-h-[500px]" wire:key="table-container-{{ $filterType }}">
                <div class="animate-fadeIn">
                    <x-data-table>
                        <x-slot name="header">
                            <th class="py-3 px-6 text-[11px] font-black text-slate-500 uppercase tracking-widest">Category Information</th>
                            <th class="py-3 px-6 text-[11px] font-black text-slate-500 uppercase tracking-widest text-center">Type</th>
                            <th class="py-3 px-6 text-[11px] font-black text-slate-500 uppercase tracking-widest text-center">Utilization</th>
                            <th class="py-3 px-6 text-right text-[11px] font-black text-slate-500 uppercase tracking-widest">Actions</th>
                        </x-slot>
                        
                        <tbody class="divide-y divide-slate-100/80" 
                            wire:key="table-body-{{ $filterType }}-{{ $categories->currentPage() }}"
                            wire:loading.class="opacity-40" 
                            wire:target="filterType, search, gotoPage, nextPage, previousPage">
                            @forelse($categories as $category)
                                <tr wire:key="cat-row-{{ $category->cat_type }}-{{ $category->id }}" class="hover:bg-slate-50/50 transition-colors group/row">
                                    <td class="py-4 px-6 border-r border-slate-100/50 whitespace-nowrap">
                                        <div class="flex items-center gap-4">
                                            @php 
                                                $catColor = $category->cat_type === 'product' ? 'indigo' : 'rose'; 
                                            @endphp
                                            <div class="w-11 h-11 rounded-xl bg-{{ $catColor }}-50 flex items-center justify-center text-{{ $catColor }}-600 group-hover/row:bg-{{ $catColor }}-100 shadow-sm border border-{{ $catColor }}-100/50">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="font-black text-[14px] text-slate-900 group-hover/row:text-{{ $catColor }}-600 leading-tight">{{ $category->name }}</div>
                                                <div class="text-[11px] text-slate-500 font-medium mt-0.5 line-clamp-1 max-w-[200px]">{{ $category->description ?: 'No operational description...' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 border-r border-slate-100/50 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $category->cat_type === 'product' ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'bg-rose-50 text-rose-700 border border-rose-100' }}">
                                            {{ $category->cat_type }}
                                        </span>
                                    </td>

                                    <td class="py-4 px-6 border-r border-slate-100/50 text-center whitespace-nowrap">
                                        <div class="flex flex-col items-center">
                                            <span class="text-[13px] font-black text-slate-900">{{ $category->associated_count }}</span>
                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">{{ $category->cat_type === 'product' ? 'Products' : 'Ingredients' }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-right whitespace-nowrap">
                                        <x-secondary-button wire:click="selectCategory({{ $category->id }}, '{{ $category->cat_type }}')" class="h-8 px-3 inline-flex items-center gap-1.5 text-xs shadow-none border-slate-200">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                            Edit Category
                                        </x-secondary-button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-12">
                                        <x-empty-state 
                                            title="No Categories Found"
                                            description="Start organizing your catalog by creating a new category group."
                                            icon="M4 6h16M4 12h16M4 18h16"
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </div>
                <div class="mt-4">
                    <x-pagination :paginator="$categories" />
                </div>
            </div>

        </div>{{-- end panel list --}}


        {{-- ════════════════ PANEL 2 — CATEGORY DETAILS ════════════════ --}}
        <div x-show="panel === 'detail'" 
            x-transition:enter="transition ease-out duration-200" 
            x-transition:enter-start="opacity-0 translate-y-4" 
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            x-cloak class="px-1">

            {{-- Dynamic Header: Command Center --}}
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight" x-text="@js($editCategoryId) ? 'Update Category' : 'New Category Cluster'"></h2>
                    <p class="text-[12px] text-gray-500 font-medium">{{ $editCategoryType === 'product' ? 'Product Catalog' : 'Stock Inventory' }} Configuration</p>
                </div>
                
                <x-secondary-button wire:click="backToList" @click="panel = 'list'" class="h-10">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to List
                </x-secondary-button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
                {{-- Main Form Column --}}
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                            <h2 class="text-[13px] font-semibold text-slate-700 uppercase tracking-wider mb-4">Category Details</h2>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <x-input-label value="Display Name *" />
                                    <div class="relative mt-1">
                                        <x-text-input type="text" wire:model.debounce.400ms="name" placeholder="e.g. Premium Seafood Platter"
                                            class="w-full pl-4 pr-10 text-[13px] {{ $errors->has('name') ? 'border-red-400 bg-red-50/30' : 'border-slate-200/60 bg-white' }} rounded-lg h-10 shadow-sm"
                                            inputFilter="name" :hasError="$errors->has('name')"
                                        />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" /></svg>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('name')" class="mt-1"/>
                                </div>

                                <div>
                                    <x-input-label value="Operational Summary" />
                                    <x-textarea wire:model.debounce.400ms="description" rows="4" placeholder="Briefly describe the purpose or contents of this category..."
                                        class="mt-1 w-full px-4 py-3 text-[13px] text-slate-900 bg-white rounded-lg shadow-sm resize-none {{ $errors->has('description') ? 'border-red-400 bg-red-50/30' : 'border-slate-200/60' }}"
                                    ></x-textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-1"/>
                                </div>


                            </div>
                        </div>
                    </div>

                    {{-- Sidebar: Hub Controls --}}
                    <div class="space-y-6">
                        <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                            <h2 class="text-[13px] font-semibold text-slate-700 uppercase tracking-wider mb-4">Information Hub</h2>
                            
                            <div class="space-y-3">
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-[12px] text-slate-500 font-medium">Usage Type</span>
                                    <span class="text-[12px] font-bold {{ $editCategoryType === 'product' ? 'text-indigo-600 bg-indigo-50' : 'text-rose-600 bg-rose-50' }} px-2 py-0.5 rounded-full capitalize">{{ $editCategoryType }}</span>
                                </div>
                                
                                @if($editCategoryId)
                                    <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                        <span class="text-[12px] text-slate-500 font-medium">Ref ID</span>
                                        <span class="text-[12px] font-bold text-slate-800 uppercase">{{ $this->selectedCategoryRefId }}</span>
                                    </div>
                                    <div class="flex items-center justify-between py-2">
                                        <span class="text-[12px] text-slate-500 font-medium">Associated Items</span>
                                        <span class="text-[12px] font-bold text-slate-800">{{ $this->selectedCategoryAssociatedCount }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <x-primary-button type="button" wire:click="validateBeforeSaveCategory" class="w-full justify-center shadow-[0_2px_10px_-3px_rgba(6,81,237,0.3)]">
                                {{ $editCategoryId ? 'Save Changes' : 'Save Category' }}
                            </x-primary-button>
                            
                            <x-secondary-button wire:click="backToList" @click="panel = 'list'" class="w-full justify-center">
                                Cancel
                            </x-secondary-button>
                        </div>

                        <div>
                        @if($editCategoryId)
                            <div class="bg-red-50 border border-red-100 rounded-2xl p-6 shadow-sm">
                                <h2 class="text-[13px] font-bold text-red-600 uppercase tracking-wider mb-2">Danger Zone</h2>
                                <p class="text-[12px] text-gray-500 mb-4 leading-relaxed">Permanently retire this category from the {{ $editCategoryType }} catalog. Associated items must be re-categorized first.</p>
                                <x-danger-button type="button" 
                                    wire:click="confirmDelete({{ $editCategoryId }}, '{{ $editCategoryType }}')"
                                    class="w-full justify-center h-11">
                                    Delete Category
                                </x-danger-button>
                            </div>
                        @endif
                        </div>
                </div>
            </div>

        </div>{{-- end panel detail --}}

    </div>

    {{-- ── Confirmation Modals ── --}}

    {{-- Save Confirmation --}}
    <x-modal name="confirm-save-category" maxWidth="sm" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-{{ $primaryColor }}-50 border border-{{ $primaryColor }}-200 flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $primaryText }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900" x-text="panel === 'detail' && @js($editCategoryId) ? 'Save Changes?' : 'Add Category?'"></h3>
                    <p class="mt-2 text-sm text-gray-600">Are you sure you want to proceed with this category configuration?</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-save-category')">Cancel</x-secondary-button>
                <x-primary-button wire:click="saveCategory">Confirm & Save</x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- Delete Confirmation --}}
    <x-modal name="confirm-delete-category" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-red-400 to-rose-500 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-50 border border-red-100 flex items-center justify-center text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Delete Category</h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">
                        Are you sure you want to permanently delete <span class="text-red-600 font-bold">"{{ $deleteTargetName }}"</span>? 
                        This action cannot be undone.
                    </p>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-delete-category')" class="h-10">Cancel</x-secondary-button>
                <x-danger-button wire:click="deleteCategory" class="h-10">
                    Permanently Delete
                </x-danger-button>
            </div>
        </div>
    </x-modal>
</div>
