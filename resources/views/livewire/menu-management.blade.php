<div 
    x-data="typeof window.menuManagement === 'function' ? window.menuManagement($wire, @js($templatesData), @js($allCategories)) : { activeTab: $wire.entangle('activeTab').live, panel: $wire.entangle('panel').live, mode: $wire.entangle('mode').live }"
    x-on:switch-panel.window="panel = $event.detail.panel"
    class="relative min-h-full flex flex-col p-2 md:p-4"
    wire:ignore.self
    wire:key="menu-management-main-container">
    {{-- Hidden reactive updaters to sync products list, templates, and categories under wire:ignore --}}
    <div x-effect="updateProductsList(@js($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name])))" class="hidden" wire:key="products-sync-helper"></div>
    <div x-effect="if (typeof templates !== 'undefined') templates = @js($templatesData); if (typeof allCategories !== 'undefined') allCategories = @js($allCategories)" class="hidden" wire:key="options-templates-sync-helper"></div>
    <div x-effect="updateServerErrors(@js($errors->getMessages()))" class="hidden" wire:key="server-errors-sync-helper"></div>
    {{-- Panel: Form --}}
    <div x-show="panel === 'form'" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 translate-y-4" 
         x-transition:enter-end="opacity-100 translate-y-0" 
         x-cloak 
         class="flex-1 flex flex-col bg-white rounded-2xl shadow-sm border border-gray-200">
        
        <div class="px-4 py-4 md:px-6 md:py-5 shrink-0 border-b border-gray-100 bg-slate-50/30">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-black text-gray-900 tracking-tight" x-text="mode === 'edit' ? 'Update Product' : 'Add New Product'"></h2>
                    <p class="text-[12px] text-gray-500 font-medium truncate max-w-[200px] sm:max-w-none" x-text="mode === 'edit' ? 'Configure product details and pricing' : 'Add a new item to the menu'"></p>
                </div>
                <x-secondary-button @click="panel = 'list'; mode = 'list'; $wire.discardDraft();" class="h-10 text-[11px] font-black uppercase tracking-widest">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span class="hidden sm:inline">Back to Products</span>
                    <span class="sm:hidden">Back</span>
                </x-secondary-button>
            </div>
        </div>

        <div class="p-4 md:p-6">

        <form @submit.prevent class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- Unified Form Tabs --}}
                <div class="mb-4 -mx-4 px-4 overflow-x-auto custom-scrollbar no-scrollbar scroll-smooth">
                    <div class="inline-flex min-w-full border-b border-slate-100">
                        <x-sliding-tabs model="activeTab" class="w-max" ref="tabList">
                    <x-sliding-tab value="basic" model="activeTab">
                        <x-slot name="icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></x-slot>
                        Information
                    </x-sliding-tab>
                    <x-sliding-tab value="variants" model="activeTab">
                        <x-slot name="icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg></x-slot>
                        Options
                    </x-sliding-tab>
                    <x-sliding-tab value="recipe" model="activeTab">
                        <x-slot name="icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg></x-slot>
                        Recipe
                    </x-sliding-tab>
                    <x-sliding-tab value="analysis" model="activeTab">
                        <x-slot name="icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg></x-slot>
                        Profitability
                    </x-sliding-tab>
                        </x-sliding-tabs>
                    </div>
                </div>

                <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm min-h-[400px]">
                    {{-- Tab: Product Information --}}
                    <div x-show="activeTab === 'basic'" class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label value="Product Name *" />
                                <x-text-input wire:model.live.debounce.400ms="name" class="w-full mt-1.5 h-11 font-medium" placeholder="e.g. Classic Takoyaki (8pcs)" inputFilter="productName" :hasError="$errors->has('name')" />
                                <x-input-error :messages="$errors->get('name')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label value="Category" />
                                <div class="flex gap-2 mt-1.5">
                                    <div class="flex-1 relative"
                                        x-data="{
                                            open: false,
                                            dropUp: false,
                                            search: '',
                                            selectedId: $wire.entangle('categoryId'),
                                            get categoriesList() {
                                                return (typeof allCategories !== 'undefined' && allCategories) ? allCategories : @js($allCategories);
                                            },
                                            get selectedItem() {
                                                if (!this.selectedId) return null;
                                                return this.categoriesList.find(c => Number(c.id) === Number(this.selectedId)) || null;
                                            },
                                            get filteredItems() {
                                                if (!this.search.trim()) return this.categoriesList;
                                                const q = this.search.toLowerCase().trim();
                                                return this.categoriesList.filter(c => (c.name || '').toLowerCase().includes(q));
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
                                                if (!this.$refs.categoryCombobox) return;
                                                const rect = this.$refs.categoryCombobox.getBoundingClientRect();
                                                const spaceBelow = window.innerHeight - rect.bottom;
                                                this.dropUp = spaceBelow < 250 && rect.top > 250;
                                            },
                                            select(id) {
                                                this.selectedId = id ? Number(id) : '';
                                                this.search = '';
                                                this.open = false;
                                            },
                                            clear() {
                                                this.selectedId = '';
                                                this.search = '';
                                                this.open = false;
                                            }
                                        }"
                                        x-ref="categoryCombobox"
                                        @click.outside="closeDropdown()"
                                        @keydown.escape.window="closeDropdown()">

                                        {{-- Search / Select Input Box --}}
                                        <div class="relative flex items-center">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                </svg>
                                            </div>

                                            <input 
                                                type="text"
                                                :value="open ? search : (selectedItem ? selectedItem.name : 'Uncategorized')"
                                                @input="search = $event.target.value; open = true"
                                                @focus="openDropdown()"
                                                @click="openDropdown()"
                                                placeholder="Search or select category..."
                                                class="w-full pl-10 pr-10 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-11"
                                                :class="selectedId && !open ? 'font-medium text-indigo-700 bg-indigo-50/20 border-indigo-200' : 'text-slate-800 font-medium'"
                                                autocomplete="off"
                                            />

                                            {{-- Clear button only (NO up/down arrow!) --}}
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                <button type="button" 
                                                    x-show="(selectedId !== '' && selectedId !== null && selectedId !== undefined) || (search && search.length > 0)"
                                                    x-cloak
                                                    @click.stop="clear()"
                                                    title="Clear selection"
                                                    class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                            class="absolute left-0 right-0 z-50 bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-60 overflow-y-auto custom-scrollbar">
                                            
                                            {{-- Uncategorized option (default) --}}
                                            <template x-if="!search.trim() || 'uncategorized'.includes(search.toLowerCase().trim())">
                                                <div>
                                                    <button type="button" 
                                                        @click="select('')"
                                                        class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors flex items-center justify-between group"
                                                        :class="!selectedId ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                        <span class="text-[13px] font-medium group-hover:font-semibold">Uncategorized</span>
                                                        <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-slate-100 text-slate-400 group-hover:bg-indigo-100 group-hover:text-indigo-600 shrink-0 ml-2">Default</span>
                                                    </button>
                                                    <div class="my-1 border-t border-slate-100"></div>
                                                </div>
                                            </template>

                                            <template x-for="cat in filteredItems" :key="cat.id">
                                                <button type="button" 
                                                    @click="select(cat.id)"
                                                    class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors flex items-center justify-between group"
                                                    :class="Number(selectedId) === Number(cat.id) ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                    <span class="text-[13px] font-medium group-hover:font-semibold truncate" x-text="cat.name"></span>
                                                </button>
                                            </template>
                                            
                                            <template x-if="filteredItems.length === 0 && search.trim() && !'uncategorized'.includes(search.toLowerCase().trim())">
                                                <div class="px-4 py-3 text-[12px] text-slate-400 italic text-center">
                                                    No categories found
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    @if($this->isSuperAdmin())
                                    <button type="button" @click="$dispatch('open-modal', 'quick-add-category')" class="h-11 w-11 flex items-center justify-center bg-slate-50 border border-slate-200 rounded-xl text-indigo-600 hover:bg-indigo-50 transition-all shadow-sm shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                    @endif
                                </div>
                                <x-input-error :messages="$errors->get('categoryId')" class="mt-1" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label value="Sale Price (₱) *" />
                                <div class="relative mt-1.5">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <span class="text-[13px] font-medium">₱</span>
                                    </div>
                                    <x-text-input wire:model.live.debounce.400ms="price" class="w-full h-11 pl-10 pr-4 font-medium" placeholder="0.00" inputFilter="price" :hasError="$errors->has('price')" />
                                </div>
                                <x-input-error :messages="$errors->get('price')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label value="Status *" />
                                <div class="flex items-center gap-4 mt-3">
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="radio" wire:model.live="isActive" value="1" class="w-4 h-4 text-indigo-600 border-slate-200 focus:ring-indigo-500 transition-all">
                                        <span class="text-[13px] font-medium text-slate-600 group-hover:text-slate-900 transition-colors">Available</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="radio" wire:model.live="isActive" value="0" class="w-4 h-4 text-indigo-600 border-slate-200 focus:ring-indigo-500 transition-all">
                                        <span class="text-[13px] font-medium text-slate-600 group-hover:text-slate-900 transition-colors">Hidden</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <x-input-label value="Product Description" />
                            <textarea wire:model.live.debounce.400ms="description" rows="4" 
                                class="w-full mt-1.5 rounded-xl border-slate-200 text-[13px] font-medium focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-400 resize-none h-32" 
                                placeholder="Describe this item for customers... (Numbers and symbols allowed)"></textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-1" />
                        </div>
                    </div>

                    {{-- Tab: Options and Groups --}}
                    <div x-show="activeTab === 'variants'" class="space-y-6">
                        {{-- Operation Guide --}}
                        <div class="flex items-start gap-4 p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100/50 mb-2">
                            <div class="w-8 h-8 bg-white rounded-xl flex items-center justify-center text-indigo-600 shadow-sm shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div class="space-y-1">
                                <h4 class="text-[12px] font-black text-indigo-900 uppercase tracking-tight">Managing Product Options</h4>
                                <p class="text-[11px] text-indigo-700/80 leading-relaxed font-medium">
                                    Use <span class="font-bold text-indigo-900">Groups</span> to organize variations. 
                                    <span class="font-bold text-indigo-900">Additive</span> mode adds the option price to the base price, while 
                                    <span class="font-bold text-indigo-900">Fixed</span> mode replaces the base price entirely when selected.
                                    Use the <span class="font-bold text-indigo-900">↻ sync</span> icon to pull in any new options or ingredients from a matching Library template, and the <span class="font-bold text-indigo-900">💾 save</span> icon to push this group's current options and ingredients back to the Library for reuse on other products.
                                </p>
                            </div>
                        </div>

                        <div class="border-b border-slate-50 pb-4 mb-4">
                            <div class="mb-4">
                                <h3 class="text-[14px] font-black text-slate-900 uppercase tracking-widest">Configuration Groups</h3>
                                <p class="text-[11px] text-slate-400 font-medium mt-1">Define mandatory or optional item variations</p>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="$dispatch('open-modal', 'import-template-library')" class="h-10 px-3 bg-slate-50 text-slate-500 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-slate-100 transition-all flex items-center justify-center gap-1.5 border border-slate-100">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                    <span class="truncate">Import Template</span>
                                </button>
                                <button type="button" @click="openAddGroupModal()" class="h-10 px-3 bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-indigo-100 transition-all border border-indigo-100/50 flex items-center justify-center truncate">
                                    + Add Group
                                </button>
                            </div>
                        </div>

                        <div x-show="!optionGroups || optionGroups.length === 0" class="py-16 border-2 border-dashed border-slate-100 rounded-2xl text-center">
                            <p class="text-[13px] text-slate-400 font-medium">No option groups defined for this product.</p>
                        </div>

                        <div x-show="optionGroups && optionGroups.length > 0" class="space-y-4">
                            <template x-for="(group, idx) in (optionGroups || [])" :key="idx">
                                <div class="border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                                    <div class="p-4 bg-slate-50/50 border-b border-slate-100 space-y-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="text-[14px] font-bold text-slate-900 uppercase tracking-tight" x-text="group.name"></span>
                                                    <span class="px-2 py-0.5 bg-white border border-slate-200 rounded text-[9px] font-black text-slate-400 uppercase tracking-widest" x-text="group.price_mode"></span>
                                                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider"
                                                        :class="group.max_select == 1 ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'bg-blue-50 text-blue-700 border border-blue-100'"
                                                        x-text="group.max_select == 1 ? 'Single (Max 1)' : (group.max_select ? 'Multi (Max ' + group.max_select + ')' : 'Multi (No Limit)')"></span>
                                                    <span x-show="group.is_required" class="text-[9px] font-black text-rose-500 uppercase tracking-widest animate-pulse">Required</span>
                                                    <span x-show="group.no_recipe_required" class="text-[9px] font-black text-amber-600 bg-amber-50 border border-amber-100 px-1.5 py-0.5 rounded uppercase tracking-widest">No Recipe</span>
                                                </div>
                                                <div x-show="fieldError('optionGroups.' + idx + '.name')" x-cloak class="text-[11px] font-medium text-red-500 mt-1.5 flex items-start gap-1.5 animate-in fade-in slide-in-from-top-1 duration-200">
                                                    <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    <ul class="space-y-0.5">
                                                        <li x-text="fieldError('optionGroups.' + idx + '.name')"></li>
                                                    </ul>
                                                </div>
                                            </div>
                                            {{-- Top-right aligned actions: Sync, Save, Delete --}}
                                            <div class="flex items-center gap-1.5 shrink-0">
                                                <button type="button" @click="promptSyncGroup(idx)" class="flex items-center gap-1.5 h-9 px-3 rounded-lg bg-white border border-slate-200 text-emerald-600 hover:bg-emerald-50 hover:border-emerald-300 transition-all text-[11px] font-bold shadow-sm" title="Sync options, prices, and ingredients from Library">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                    <span>Sync</span>
                                                </button>
                                                <button type="button" @click="promptSaveGroup(idx)" class="flex items-center gap-1.5 h-9 px-3 rounded-lg bg-white border border-slate-200 text-indigo-600 hover:bg-indigo-50 hover:border-indigo-300 transition-all text-[11px] font-bold shadow-sm" title="Save as Template">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                                                    <span>Save</span>
                                                </button>
                                                <button type="button" @click="promptDeleteGroup(idx)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-rose-400 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 transition-all shadow-sm" title="Delete Group">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2.5">
                                            {{-- Selection Mode Toggle --}}
                                            <div class="inline-flex rounded-xl border border-slate-200 bg-white p-0.5 shadow-sm h-10 shrink-0">
                                                <button type="button" @click="setGroupSingleMode(idx)"
                                                    class="px-3 h-full rounded-lg text-[11px] font-bold transition-all flex items-center gap-1.5"
                                                    :class="group.max_select == 1 ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                                                    Single (Pick 1)
                                                </button>
                                                <button type="button" @click="setGroupMultiMode(idx)"
                                                    class="px-3 h-full rounded-lg text-[11px] font-bold transition-all flex items-center gap-1.5"
                                                    :class="group.max_select != 1 ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                                                    Multiple Choice
                                                </button>
                                            </div>

                                            {{-- Selection Limit (System Settings Print Copies Style, only for Multiple Choice, bounded by options count) --}}
                                            <div x-show="group.max_select != 1" x-cloak class="flex items-center h-10 border border-gray-200 rounded-xl overflow-hidden shadow-sm bg-white"
                                                :title="(group.options && group.options.length > 2) ? ('Maximum selections allowed (2–' + (group.options.length - 1) + ', or No Limit)') : 'Multiple choice selections (No Limit)'">
                                                <button type="button" tabindex="-1" @click="decrementGroupMaxSelect(idx)"
                                                    :disabled="!group.options || group.options.length <= 2 || (group.max_select !== null && group.max_select <= 2)"
                                                    class="w-10 h-full flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-900 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-white transition-colors border-r border-gray-200 shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                                </button>
                                                <input type="text" inputmode="numeric" maxlength="2"
                                                    :value="group.max_select === null || group.max_select === undefined || group.max_select === '' ? '' : group.max_select"
                                                    @input="handleGroupMaxSelectInput(idx, $event)"
                                                    placeholder="No Limit"
                                                    class="w-20 h-full text-center text-[13px] font-bold text-gray-900 border-0 focus:ring-0 bg-transparent placeholder:text-gray-400 placeholder:font-bold placeholder:text-[11px] [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                                <button type="button" tabindex="-1" @click="incrementGroupMaxSelect(idx)"
                                                    :disabled="!group.options || group.options.length <= 2 || group.max_select === null || group.max_select >= group.options.length"
                                                    class="w-10 h-full flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-900 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-white transition-colors border-l border-gray-200 shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                </button>
                                            </div>

                                            {{-- No Recipe Checkbox (Consistent with Options Library) --}}
                                            <label class="flex items-center gap-2 cursor-pointer bg-white border border-slate-200 rounded-xl px-3 h-10 shadow-sm hover:border-amber-300 transition-all select-none group" title="Options in this group skip ingredient tracking and are always available">
                                                <input type="checkbox" x-model="group.no_recipe_required"
                                                    @change="if (group.no_recipe_required) { (group.options || []).forEach((o, oIdx) => { const ownerA = 'option:' + idx + '_' + oIdx; const ownerB = o.id ? ('option:' + o.id) : null; if (recipeIngredients) recipeIngredients = recipeIngredients.filter(ri => ri.owner !== ownerA && (!ownerB || ri.owner !== ownerB)); }); }"
                                                    class="w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                                <span class="text-[11px] font-bold text-slate-700 group-hover:text-amber-700 transition-colors whitespace-nowrap">No Recipe</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="p-4 bg-white space-y-3">
                                        <template x-for="(option, oIdx) in (group.options || [])" :key="oIdx">
                                            <div class="flex items-start gap-4">
                                                <div class="flex-1">
                                                    <input type="text" x-model="option.name"
                                                        @input="clearOptionError(idx, oIdx, 'name')"
                                                        class="w-full h-10 px-3 text-[13px] font-bold rounded-xl border focus:ring-2 transition-all"
                                                        :class="fieldError('optionGroups.' + idx + '.options.' + oIdx + '.name') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-300 bg-rose-50/30' : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20'"
                                                        placeholder="Option name..." />
                                                    <div x-show="fieldError('optionGroups.' + idx + '.options.' + oIdx + '.name')" x-cloak class="text-[11px] font-medium text-red-500 mt-1.5 flex items-start gap-1.5 animate-in fade-in slide-in-from-top-1 duration-200">
                                                        <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                        <ul class="space-y-0.5">
                                                            <li x-text="fieldError('optionGroups.' + idx + '.options.' + oIdx + '.name')"></li>
                                                        </ul>
                                                    </div>                                                </div>
                                                <div class="w-28">
                                                    <div class="relative">
                                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                                            <span class="text-[12px] font-bold">₱</span>
                                                        </div>
                                                        <input type="text" x-model="option.price"
                                                            @input="clearOptionError(idx, oIdx, 'price')"
                                                            class="w-full h-10 pl-7 pr-3 text-[13px] font-black text-right rounded-xl border focus:ring-2 transition-all"
                                                            :class="fieldError('optionGroups.' + idx + '.options.' + oIdx + '.price') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-300 bg-rose-50/30' : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20'"
                                                            placeholder="0.00" />
                                                    </div>
                                                    <div x-show="fieldError('optionGroups.' + idx + '.options.' + oIdx + '.price')" x-cloak class="text-[11px] font-medium text-red-500 mt-1.5 flex items-start gap-1.5 animate-in fade-in slide-in-from-top-1 duration-200">
                                                        <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                        <ul class="space-y-0.5">
                                                            <li x-text="fieldError('optionGroups.' + idx + '.options.' + oIdx + '.price')"></li>
                                                        </ul>
                                                    </div>                                                </div>
                                                <div class="flex items-center gap-2 h-10">
                                                    <button type="button" @click="toggleDefaultOption(idx, oIdx)"
                                                        class="flex items-center gap-1.5 h-9 px-3 rounded-lg border-2 transition-all shrink-0"
                                                        :class="option.is_default ? 'bg-emerald-500 border-emerald-500 text-white shadow-sm shadow-emerald-100' : 'bg-white border-slate-200 text-slate-400 hover:text-emerald-600 hover:border-emerald-200'"
                                                        title="Toggle system default (click again to unset)">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                        <span class="text-[10px] font-black uppercase tracking-widest whitespace-nowrap">Default</span>
                                                    </button>
                                                    <button type="button" @click="promptDeleteOption(idx, oIdx)" class="w-9 h-9 flex items-center justify-center text-slate-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors shrink-0" title="Remove Option">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                        <button type="button" @click="addOption(idx)" class="w-full flex justify-center items-center h-10 border border-dashed border-slate-200 rounded-xl text-[11px] font-black uppercase tracking-widest text-slate-400 hover:text-indigo-600 hover:border-indigo-200 hover:bg-indigo-50/30 transition-all mt-2">
                                            <span x-text="'+ Add Option to &quot;' + group.name + '&quot;'"></span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Tab: Recipe --}}
                    <div x-show="activeTab === 'recipe'" class="space-y-6">
                        {{-- Operation Guide --}}
                        <div class="flex items-start gap-4 p-4 bg-amber-50/50 rounded-2xl border border-amber-100/50 mb-2">
                            <div class="w-8 h-8 bg-white rounded-xl flex items-center justify-center text-amber-600 shadow-sm shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            </div>
                            <div class="space-y-1">
                                <h4 class="text-[12px] font-black text-amber-900 uppercase tracking-tight">Recipe & Stock Link</h4>
                                <p class="text-[11px] text-amber-700/80 leading-relaxed font-medium">
                                    Ingredients assigned to <span class="font-bold text-amber-900">Base</span> are deducted on every sale. 
                                    Ingredients linked to <span class="font-bold text-amber-900">Options</span> are only deducted when that specific variation is chosen. 
                                    An ingredient can only be added once per Base/Option — adding the same one again will be blocked.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-b border-slate-50 pb-4 mb-2">
                            <div>
                                <h3 class="text-[14px] font-black text-slate-900 uppercase tracking-widest">Inventory Recipe</h3>
                                <p class="text-[11px] text-slate-400 font-medium mt-1">Map ingredients for automatic stock deduction</p>
                            </div>
                        </div>

                        @php
                            $ingredientsList = $allIngredients->map(fn($i) => [
                                'id' => (int)$i->id,
                                'name' => (string)$i->name,
                                'unit' => (string)\App\Helpers\StockHelper::getAbbreviation($i->unit),
                                'cost' => (float)($i->cost ?? 0),
                            ])->values();
                        @endphp
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8"
                            wire:ignore.self
                            x-data="{
                                open: false,
                                search: '',
                                selectedId: 0,
                                qty: '',
                                errorMessage: '',
                                dropUp: false,
                                appliedOpen: false,
                                appliedDropUp: false,
                                appliedOwner: 'base',
                                appliedLabel: 'Base Product',
                                items: {{ Js::from($ingredientsList) }},
                                optionGroups: $wire.entangle('optionGroups'),
                                recipeIngredients: $wire.entangle('recipeIngredients'),
                                get selectedItem() {
                                    return this.items.find(i => i.id === Number(this.selectedId)) || null;
                                },
                                get selectedUnit() {
                                    return this.selectedItem ? this.selectedItem.unit : '';
                                },
                                get filteredItems() {
                                    if (!this.search.trim()) return this.items;
                                    const q = this.search.toLowerCase().trim();
                                    return this.items.filter(i => i.name.toLowerCase().includes(q));
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
                                toggleApplied() {
                                    const rect = this.$refs.appliedContainer.getBoundingClientRect();
                                    const spaceBelow = window.innerHeight - rect.bottom;
                                    this.appliedDropUp = spaceBelow < 220 && rect.top > 220;
                                    this.appliedOpen = !this.appliedOpen;
                                },
                                setApplied(key, label) {
                                    this.appliedOwner = key;
                                    this.appliedLabel = label;
                                    this.appliedOpen = false;
                                    this.errorMessage = '';
                                },
                                getOwnerName(owner) {
                                    if (!owner || owner === 'base') return 'Base';
                                    if (owner.startsWith('option:')) {
                                        const parts = owner.replace('option:', '').split('_');
                                        const gIdx = parseInt(parts[0]);
                                        const oIdx = parseInt(parts[1]);
                                        if (this.optionGroups && this.optionGroups[gIdx] && this.optionGroups[gIdx].options && this.optionGroups[gIdx].options[oIdx]) {
                                            return this.optionGroups[gIdx].options[oIdx].name || 'Option';
                                        }
                                        return 'Option';
                                    }
                                    return owner;
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
                                    const item = this.selectedItem;
                                    if (!item) return;

                                    const ownerKey = this.appliedOwner || 'base';

                                    const exists = this.recipeIngredients.some(ri => Number(ri.id) === Number(this.selectedId) && ri.owner === ownerKey);
                                    if (exists) {
                                        this.errorMessage = 'Ingredient already added for this option.';
                                        return;
                                    }

                                    this.errorMessage = '';

                                    // Instant addition (0ms)!
                                    this.recipeIngredients.push({
                                        id: item.id,
                                        name: item.name,
                                        unit: item.unit,
                                        quantity: qtyNum,
                                        cost: Number(item.cost || 0),
                                        owner: ownerKey
                                    });

                                    // Instant reset
                                    this.clear();
                                    this.qty = '';
                                },
                                removeIngredient(idx) {
                                    this.recipeIngredients.splice(idx, 1);
                                }
                            }">
                            {{-- Add Ingredient Form --}}
                            <div class="space-y-4">
                                <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                    Add Component
                                </h4>
                                <div class="p-5 bg-slate-50/50 border border-slate-100 rounded-2xl space-y-4">
                                    <div>
                                        <x-input-label value="Select Ingredient" />
                                        <div class="relative mt-1.5" x-ref="comboboxContainer"
                                            @click.outside="closeDropdown()"
                                            @keydown.escape.window="closeDropdown()">

                                            {{-- Search / Select Input Box --}}
                                            <div class="relative flex items-center">
                                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                                    class="w-full pl-10 pr-10 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-11"
                                                    :class="selectedItem && !open ? 'font-bold text-indigo-700 bg-indigo-50/20 border-indigo-200' : 'text-slate-800'"
                                                    autocomplete="off"
                                                />

                                                {{-- Clear button only (NO up/down arrow!) --}}
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                    <button type="button" 
                                                        x-show="selectedId > 0 || search.length > 0"
                                                        x-cloak
                                                        @click.stop="clear()"
                                                        title="Clear selection"
                                                        class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                                class="absolute left-0 right-0 z-50 bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-60 overflow-y-auto custom-scrollbar">
                                                <template x-for="item in filteredItems" :key="item.id">
                                                    <button type="button" 
                                                        @click="select(item.id)"
                                                        class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors flex items-center justify-between group"
                                                        :class="selectedId === item.id ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                        <span class="text-[13px] font-medium group-hover:font-semibold truncate" x-text="item.name"></span>
                                                        <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 group-hover:bg-indigo-100 group-hover:text-indigo-600 shrink-0 ml-2" x-text="item.unit"></span>
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

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label value="Quantity" />
                                            <div class="relative mt-1.5">
                                                <x-text-input x-model="qty" @keydown.enter.prevent="addIngredient()" class="w-full h-11 pr-14 text-[13px] font-black" placeholder="0.00" inputFilter="price" />
                                                <span class="absolute inset-y-0 right-4 flex items-center text-[10px] font-black text-slate-400 uppercase pointer-events-none" x-text="selectedUnit || '—'">—</span>
                                            </div>
                                        </div>
                                        <div>
                                            <x-input-label value="Applied To" />
                                            <div class="relative mt-1.5" x-ref="appliedContainer"
                                                @click.outside="appliedOpen = false"
                                                @keydown.escape.window="appliedOpen = false">
                                                <button type="button" 
                                                    @click="toggleApplied()"
                                                    class="flex items-center justify-between w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none transition-all h-11">
                                                    <span class="font-bold truncate" x-text="appliedLabel"></span>
                                                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="appliedOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                                    </svg>
                                                </button>
                                                <div x-show="appliedOpen" x-cloak
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 scale-95"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100 scale-100"
                                                    x-transition:leave-end="opacity-0 scale-95"
                                                    :class="appliedDropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                                    class="absolute left-0 right-0 z-50 bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-60 overflow-y-auto custom-scrollbar">
                                                    <button type="button" 
                                                        @click="setApplied('base', 'Base Product')"
                                                        class="w-full text-left px-3.5 py-2 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors text-[13px] font-medium"
                                                        :class="appliedOwner === 'base' ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                        Base Product
                                                    </button>
                                                    <template x-for="(group, gIdx) in (optionGroups || [])" :key="gIdx">
                                                        <div x-show="!group.no_recipe_required">
                                                            <div class="px-3.5 py-1.5 text-[9px] font-black text-slate-400 uppercase tracking-widest bg-slate-50/50 rounded mt-1" x-text="group.name"></div>
                                                            <template x-for="(option, oIdx) in (group.options || [])" :key="oIdx">
                                                                <button type="button" 
                                                                    @click="setApplied('option:' + gIdx + '_' + oIdx, option.name || ('Option #' + (oIdx + 1)))"
                                                                    class="w-full text-left px-3.5 py-2 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors text-[13px] font-medium"
                                                                    :class="appliedOwner === ('option:' + gIdx + '_' + oIdx) ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'"
                                                                    x-text="option.name || ('Option #' + (oIdx + 1))">
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Local validation error --}}
                                    <div x-show="errorMessage" x-cloak class="p-2.5 bg-rose-50 border border-rose-100 rounded-xl text-[12px] font-bold text-rose-600 flex items-center gap-2">
                                        <svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span x-text="errorMessage"></span>
                                    </div>

                                    <x-primary-button type="button" @click="addIngredient()" class="w-full justify-center h-11 text-[12px] font-black uppercase tracking-widest shadow-md shadow-indigo-100">Add to Recipe</x-primary-button>
                                </div>
                            </div>

                            {{-- Recipe Overview --}}
                            <div class="space-y-4">
                                <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                    Recipe Overview
                                </h4>
                                <div x-show="recipeIngredients.length === 0" class="py-20 bg-white border border-slate-100 rounded-2xl text-center">
                                    <p class="text-[12px] text-slate-400 font-medium">No ingredients added yet.</p>
                                </div>
                                <div x-show="recipeIngredients.length > 0" class="space-y-3 max-h-96 overflow-y-auto pr-2">
                                    <template x-for="(ri, idx) in recipeIngredients" :key="ri.id + '-' + ri.owner">
                                        <div class="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-2xl shadow-sm group">
                                            <div class="flex items-center gap-4 min-w-0">
                                                <div class="w-10 h-10 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center text-[12px] font-black text-slate-400 shadow-sm shrink-0" x-text="(ri.name || '??').charAt(0).toUpperCase()">
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="text-[14px] font-bold text-slate-900 leading-none" x-text="ri.name"></span>
                                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-widest"
                                                            :class="ri.owner === 'base' ? 'text-indigo-600 bg-indigo-50' : 'text-amber-600 bg-amber-50'"
                                                            x-text="getOwnerName(ri.owner)"></span>
                                                    </div>
                                                    <div class="flex items-center gap-2 mt-2">
                                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-rose-50 border border-rose-100 rounded-lg text-[12px] font-bold text-rose-600">
                                                            <span class="text-[10px] font-black text-rose-400 uppercase tracking-tighter">Cost</span>
                                                            <span class="font-mono" x-text="'₱' + ((ri.cost || 0) * (ri.quantity || 0)).toFixed(2)"></span>
                                                        </span>
                                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-slate-50 border border-slate-100 rounded-lg text-[12px] font-bold text-slate-700">
                                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Qty</span>
                                                            <span class="font-mono" x-text="ri.quantity + ' ' + (ri.unit || '').toUpperCase()"></span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" @click="removeIngredient(idx)" class="text-slate-300 hover:text-rose-500 transition-all p-2 hover:bg-rose-50 rounded-lg shrink-0" title="Remove ingredient">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab: Profitability Analysis --}}
                    <div x-show="activeTab === 'analysis'" class="animate-in fade-in slide-in-from-bottom-2 duration-300 space-y-8">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 shadow-sm border border-indigo-100">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                </div>
                                <div>
                                    <h3 class="text-[16px] font-black text-slate-900 tracking-tight">Profitability Dashboard</h3>
                                    <p class="text-[12px] text-slate-500 font-medium mt-0.5">Real-time cost impact and margin analysis based on your recipe</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3.5 sm:gap-4">
                                <div class="p-4 sm:p-5 bg-slate-50/50 rounded-2xl border border-slate-100 transition-all hover:bg-white hover:shadow-md group space-y-1.5 sm:space-y-2">
                                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest block group-hover:text-slate-500 transition-colors">Base Recipe Cost</span>
                                    <span class="text-[20px] sm:text-[22px] font-black text-slate-900 italic font-mono block leading-none truncate">₱{{ number_format($recipeCost, 2) }}</span>
                                </div>
                                <div class="p-4 sm:p-5 bg-slate-50/50 rounded-2xl border border-slate-100 transition-all hover:bg-white hover:shadow-md group space-y-1.5 sm:space-y-2">
                                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest block group-hover:text-slate-500 transition-colors">Listing Price</span>
                                    <span class="text-[20px] sm:text-[22px] font-black text-slate-900 italic font-mono block leading-none truncate">₱{{ number_format($salePrice, 2) }}</span>
                                </div>
                                <div class="p-4 sm:p-5 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-100 flex flex-col justify-center transition-all hover:scale-[1.01] space-y-1.5 sm:space-y-2 sm:col-span-2 xl:col-span-1">
                                    <span class="text-[11px] font-black text-indigo-100 uppercase tracking-widest block">Net Profit</span>
                                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                                        <span class="text-[22px] sm:text-[24px] font-black text-white italic font-mono leading-none truncate">₱{{ number_format($netProfit, 2) }}</span>
                                        <span class="text-[10px] sm:text-[11px] font-black text-indigo-100 tracking-wider px-2 py-0.5 rounded-md bg-white/15 shrink-0">{{ $profitMargin }}% MARGIN</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Cost Impact Breakdown --}}
                            @php $breakdown = $this->getCostBreakdown(); @endphp
                            @if(!empty($breakdown))
                                <div class="pt-2">
                                    <div class="flex items-center justify-between border-b border-slate-50 pb-4 mb-6">
                                        <div>
                                            <h3 class="text-[14px] font-black text-slate-900 uppercase tracking-widest">Cost Impact Breakdown</h3>
                                            <p class="text-[11px] text-slate-400 font-medium mt-1">Sorted by impact (highest to lowest)</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-y-5">
                                        @foreach($breakdown as $item)
                                            <div class="space-y-2.5">
                                                <div class="flex items-center justify-between text-[13px] font-bold">
                                                    <span class="text-slate-700 truncate mr-2">{{ $item['name'] }}</span>
                                                    <span class="text-slate-900 font-mono italic">₱{{ number_format($item['cost'], 2) }}</span>
                                                </div>
                                                <div class="h-2 w-full bg-slate-50 rounded-full overflow-hidden flex border border-slate-100">
                                                    <div class="h-full bg-indigo-500/80 rounded-full transition-all duration-700" style="width: {{ $item['percentage'] }}%"></div>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-[11px] font-black text-indigo-500 uppercase tracking-tighter">{{ round($item['percentage'], 1) }}% <span class="text-slate-300 ml-1">of total</span></span>
                                                    <span class="text-[11px] font-black text-slate-400 italic">{{ number_format($item['qty'] ?? 0, 2) }} {{ strtoupper($item['unit'] ?? '') }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="py-16 text-center border-2 border-dashed border-slate-100 rounded-3xl">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                    </div>
                                    <p class="text-[13px] text-slate-400 font-medium">Add ingredients to the recipe to see your profit analysis.</p>
                                </div>
                            @endif
                        </div>
                </div>
            </div>

            {{-- Sidebar Column --}}
            <div class="space-y-6 lg:sticky lg:top-6 self-start lg:pt-[52px]">
                {{-- Product Image --}}
                <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-1">Product Media</h2>
                                        <div class="relative group">
                        <div class="w-full aspect-square rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 flex items-center justify-center relative overflow-hidden transition-all group-hover:border-indigo-300 group-hover:bg-indigo-50/30">
                            @if($image)
                                <img src="{{ $image->temporaryUrl() }}" class="w-full h-full object-cover animate-in fade-in zoom-in duration-300">
                            @elseif($existingImage && Storage::disk('public')->exists($existingImage))
                                <img src="{{ Storage::url($existingImage) }}" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='{{ asset('images/placeholder-product.png') }}';">
                            @else
                                <img src="{{ asset('images/placeholder-product.png') }}" class="w-full h-full object-cover opacity-80 group-hover:opacity-40 transition-opacity">
                                <div class="absolute inset-0 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                    <div class="w-10 h-10 rounded-xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 mb-2 shadow-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    </div>
                                    <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest bg-white/90 px-2 py-1 rounded backdrop-blur-sm shadow-sm">Upload</span>
                                </div>
                            @endif
                            <input type="file" wire:model.live="image" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*">
                        </div>
                        @if($image || ($existingImage && Storage::disk('public')->exists($existingImage)))
                            <button type="button" wire:click="removeImage" wire:loading.attr="disabled" wire:target="removeImage"
                                class="absolute top-2 right-2 z-20 w-8 h-8 flex items-center justify-center bg-white/95 backdrop-blur-sm border border-slate-200 rounded-lg text-slate-400 hover:text-rose-600 hover:border-rose-200 hover:bg-rose-50 transition-all shadow-sm"
                                title="Remove Image">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        @endif
                    </div>
                    <x-input-error :messages="$errors->get('image')" class="mt-3 text-center" />
                </div>

                {{-- Summary & Actions (Hidden on mobile as we use the sticky footer) --}}
                <div class="hidden lg:block bg-white border border-slate-100 rounded-2xl p-6 shadow-sm">
                    <div class="space-y-3">
                        <x-primary-button type="button" wire:click="validateBeforeSave" class="w-full justify-center h-12 text-[12px] font-black uppercase tracking-widest shadow-lg shadow-indigo-100">
                            <span x-text="mode === 'edit' ? 'Update Catalog' : 'Register Product'"></span>
                        </x-primary-button>
                        <x-secondary-button @click="panel = 'list'; mode = 'list'; $wire.discardDraft();" class="w-full justify-center h-11 text-[12px] font-black uppercase tracking-widest border-slate-200 text-slate-500">
                            Discard Draft
                        </x-secondary-button>
                    </div>

                    @if($editProductId && $this->isSuperAdmin())
                        <div class="bg-rose-50/50 border border-rose-100 rounded-2xl p-5 mt-6">
                            <h2 class="text-[12px] font-black text-rose-600 uppercase tracking-widest mb-2">Danger Zone</h2>
                            <p class="text-[11px] text-rose-400 mb-4 leading-relaxed font-medium">Permanently remove this product from the menu. This action cannot be reversed.</p>
                            <x-danger-button type="button" wire:click="confirmDeleteProduct({{ $editProductId }})" class="w-full justify-center h-10 text-[11px] font-black uppercase tracking-widest">
                                Delete Product
                            </x-danger-button>
                        </div>
                    @endif
                </div>
            </div>
            </div>
        </form>
    </div>

    {{-- Mobile Sticky Action Bar --}}
    <div x-show="panel === 'form'" class="lg:hidden shrink-0 p-4 bg-white border-t border-slate-100 shadow-[0_-4px_20px_rgba(0,0,0,0.05)] z-30">
        <div class="flex flex-col gap-2">
            <x-primary-button type="button" wire:click="validateBeforeSave" class="w-full justify-center h-12 text-[12px] font-black uppercase tracking-widest shadow-lg shadow-indigo-100">
                <span x-text="mode === 'edit' ? 'Update Catalog' : 'Register Product'"></span>
            </x-primary-button>
            <x-secondary-button @click="panel = 'list'; mode = 'list'; $wire.discardDraft();" class="w-full justify-center h-11 text-[11px] font-black uppercase tracking-widest border-slate-100 text-slate-400">
                Discard Draft
            </x-secondary-button>
        </div>
    </div>

    {{-- Option Template Library Modal --}}
    <x-modal name="import-template-library" maxWidth="2xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    </div>
                    <div>
                        <h3 class="text-[16px] font-black text-slate-900 tracking-tight">Option Library</h3>
                        <p class="text-[12px] text-slate-500 font-medium mt-0.5">Select a pre-defined group to import into this product</p>
                    </div>
                </div>
                <button type="button" @click="$dispatch('close-modal', 'import-template-library')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                <template x-for="tmpl in (templates || [])" :key="tmpl.id">
                    <div class="group relative bg-slate-50/50 border border-slate-100 rounded-2xl p-5 hover:bg-white hover:border-indigo-200 hover:shadow-md transition-all cursor-pointer" 
                         @click="importTemplate(tmpl.id)">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h4 class="text-[14px] font-bold text-slate-900 group-hover:text-indigo-600 transition-colors" x-text="tmpl.name"></h4>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-widest"
                                        :class="tmpl.is_required ? 'text-rose-500 bg-rose-50' : 'text-slate-400 bg-slate-100'"
                                        x-text="tmpl.is_required ? 'Required' : 'Optional'"></span>
                                    <span class="text-[9px] font-black text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded uppercase tracking-widest" x-text="tmpl.price_mode"></span>
                                    <span x-show="tmpl.no_recipe_required" class="text-[9px] font-black text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded uppercase tracking-widest">No Recipe</span>
                                </div>
                            </div>
                            <div class="w-8 h-8 rounded-lg bg-white border border-slate-100 flex items-center justify-center text-slate-300 group-hover:text-indigo-600 group-hover:border-indigo-100 transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                            </div>
                        </div>
                        <div class="space-y-1.5 opacity-60">
                            <template x-for="tItem in (tmpl.items || []).slice(0, 3)" :key="tItem.id">
                                <div class="flex items-center justify-between text-[11px] font-medium text-slate-500">
                                    <span class="flex items-center gap-1.5">
                                        <span x-text="tItem.name"></span>
                                        <template x-if="tItem.ingredients && tItem.ingredients.length > 0">
                                            <span class="inline-flex items-center gap-0.5 text-[9px] font-black text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full"
                                                x-text="'🧪 ' + tItem.ingredients.length">
                                            </span>
                                        </template>
                                    </span>
                                    <template x-if="tItem.price > 0">
                                        <span class="font-bold" x-text="'+₱' + Number(tItem.price).toFixed(2)"></span>
                                    </template>
                                </div>
                            </template>
                            <template x-if="(tmpl.items || []).length > 3">
                                <div class="text-[10px] font-bold text-slate-400 italic mt-1" x-text="'+ ' + ((tmpl.items || []).length - 3) + ' more...'"></div>
                            </template>
                        </div>
                    </div>
                </template>
                <template x-if="!templates || templates.length === 0">
                    <div class="col-span-2 py-12 text-center text-slate-400 italic text-[13px]">
                        No template available in the library yet.
                    </div>
                </template>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end gap-3">
                <x-secondary-button @click="$dispatch('close-modal', 'import-template-library')" class="h-11 px-6 text-[11px] font-black uppercase tracking-widest">Cancel</x-secondary-button>
            </div>
        </div>
    </x-modal>

    {{-- Panel: List --}}
    <div x-show="panel === 'list'" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 translate-y-4" 
         x-transition:enter-end="opacity-100 translate-y-0" 
         x-cloak 
         class="flex-1 flex flex-col bg-white rounded-2xl shadow-sm border border-gray-200">
        
        <div class="px-4 py-4 md:px-6 md:py-5 shrink-0 border-b border-gray-100 bg-slate-50/30">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-black text-gray-900 tracking-tight">Menu Management</h2>
                    <p class="text-[12px] text-gray-500 font-medium">Managing <span class="text-indigo-600 font-bold">{{ $totalProducts }} catalog assets</span></p>
                </div>
                <div class="flex items-center gap-3">
                    @if($this->isSuperAdmin())
                        <x-primary-button @click="panel = 'form'; mode = 'create'; activeTab = 'basic'; $wire.showCreate();" class="h-10 text-[11px] font-black uppercase tracking-widest">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            Add Product
                        </x-primary-button>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-4 md:p-6 space-y-6">
            {{-- ── Menu Health Overview ── --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                {{-- Total Assets --}}
                <div class="p-3 sm:p-4 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-white border border-indigo-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Catalog</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ number_format($totalProducts) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Total listed products</p>
                </div>
                
                {{-- Active --}}
                <div class="p-3 sm:p-4 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-emerald-600 tracking-tight leading-none">{{ number_format($this->activeCount) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Available on POS/App</p>
                </div>

                {{-- Hidden --}}
                <div class="p-3 sm:p-4 bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-white border border-rose-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Hidden</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 14.12l4.242-4.242M3 3l18 18"/></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-rose-600 tracking-tight leading-none">{{ number_format($this->hiddenCount) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Archived or inactive items</p>
                </div>

                {{-- Categories --}}
                <div class="p-3 sm:p-4 bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-white border border-amber-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Categories</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-amber-100 flex items-center justify-center text-amber-600 shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ number_format($totalCategories) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Product class divisions</p>
                </div>
            </div>

        {{-- macOS Style Unified Toolbar --}}
        <div class="flex flex-row items-center justify-between mb-6 gap-2 sm:gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
            
            {{-- Left: Search Bar --}}
            <div class="flex-1 min-w-0 lg:flex-initial">
                <x-search-bar x-model.debounce.50ms="searchQuery" placeholder="Find product..." width="w-full lg:w-80" />
            </div>

            {{-- Right: Filters & View Toggle --}}
            <div class="flex flex-nowrap items-center justify-end gap-1.5 sm:gap-2 shrink-0">
                <x-dropdown align="right" width="48" containerClasses="block w-full lg:w-auto">
                    <x-slot name="trigger">
                        <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 0 1 3 12V7a4 4 0 0 1 4-4z" /></svg>
                            <span class="hidden sm:inline text-[12px] whitespace-nowrap">{{ $selectedFilterCategoryName }}</span>
                            <svg class="hidden sm:block w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        </x-secondary-button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="p-2">
                            <div class="px-2 pb-2 mb-2 border-b border-slate-50">
                                <div class="relative">
                                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    <input wire:model.live.debounce.300ms="categoryFilterSearch" type="text" placeholder="Search..." 
                                           class="w-full pl-9 pr-4 py-2 bg-slate-50 border-none rounded-lg text-[12px] font-medium focus:ring-1 focus:ring-indigo-500 placeholder-slate-400">
                                </div>
                            </div>
                            <div class="max-h-60 overflow-y-auto custom-scrollbar">
                                @if(empty($categoryFilterSearch))
                                    <x-dropdown-link href="#" wire:click.prevent="$set('selectedCategoryId', '')" class="{{ $selectedCategoryId === '' ? 'bg-indigo-50 text-indigo-600 font-bold' : '' }}">
                                        All Categories
                                    </x-dropdown-link>
                                    <hr class="border-slate-50">
                                @endif
                                
                                @forelse($filterCategories as $cat)
                                    <x-dropdown-link href="#" wire:click.prevent="$set('selectedCategoryId', {{ $cat->id }})" class="{{ $selectedCategoryId == $cat->id ? 'bg-indigo-50 text-indigo-600 font-bold' : '' }}">
                                        {{ $cat->name }}
                                    </x-dropdown-link>
                                @empty
                                    <div class="px-4 py-2 text-[12px] text-slate-400 italic">No categories found</div>
                                @endforelse
                            </div>
                        </div>
                    </x-slot>
                </x-dropdown>

                @if($this->isSuperAdmin())
                    <x-dropdown align="right" width="48" containerClasses="block w-full lg:w-auto">
                        <x-slot name="trigger">
                            <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            <span class="hidden sm:inline text-[12px] whitespace-nowrap">{{ $selectedBranchId ? $branches->firstWhere('id', $selectedBranchId)?->branch_name : 'All Branches' }}</span>
                                <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </x-secondary-button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link href="#" wire:click.prevent="$set('selectedBranchId', '')">All Branches</x-dropdown-link>
                            <hr class="border-slate-50">
                            @forelse($branches as $branch)
                                <x-dropdown-link href="#" wire:click.prevent="$set('selectedBranchId', {{ $branch->id }})" class="{{ $selectedBranchId == $branch->id ? 'bg-indigo-50 text-indigo-600 font-bold' : '' }}">
                                    {{ $branch->branch_name }}
                                </x-dropdown-link>
                            @empty
                                <div class="px-4 py-2 text-[12px] text-slate-400 italic">No branches found</div>
                            @endforelse
                        </x-slot>
                    </x-dropdown>
                @endif

                <x-dropdown align="right" width="48" containerClasses="block w-full lg:w-auto">
                    <x-slot name="trigger">
                        <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            <span class="hidden sm:inline text-[12px] whitespace-nowrap">{{ $statusFilter === '1' ? 'Active Only' : ($statusFilter === '0' ? 'Hidden Only' : 'All Status') }}</span>
                            <svg class="hidden sm:block w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        </x-secondary-button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link href="#" wire:click.prevent="$set('statusFilter', '')">All Status</x-dropdown-link>
                        <hr class="border-slate-50">
                        <x-dropdown-link href="#" wire:click.prevent="$set('statusFilter', '1')">Active Only</x-dropdown-link>
                        <x-dropdown-link href="#" wire:click.prevent="$set('statusFilter', '0')">Hidden Only</x-dropdown-link>
                    </x-slot>
                </x-dropdown>

                <div class="hidden lg:block w-px h-6 bg-slate-200 mx-2"></div>

                <button type="button" @click="tableView = (tableView === 'table' ? 'board' : 'table')"
                    class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors focus:outline-none shrink-0"
                    :title="tableView === 'table' ? 'Board View' : 'Table View'">
                    <svg x-cloak x-show="tableView === 'table'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                    <svg x-cloak x-show="tableView === 'board'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
            </div>
        </div>

        {{-- ── Table/Board View ── --}}
        <div class="mx-1">
        <div class="relative min-h-[400px]">
            {{-- Table View --}}
            <div x-show="tableView === 'table'" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <x-data-table>
                    <x-slot name="header">
                        <th class="py-3 px-6 border-r border-slate-100/50 text-left text-[11px] font-bold text-slate-400 uppercase tracking-widest">Product Details</th>
                        <th class="py-3 px-6 border-r border-slate-100/50 text-center text-[11px] font-bold text-slate-400 uppercase tracking-widest">Availability</th>
                        <th class="py-3 px-6 border-r border-slate-100/50 text-right text-[11px] font-bold text-slate-400 uppercase tracking-widest">Price</th>
                        @if($this->isSuperAdmin())
                            <th class="py-3 px-6 text-right text-[11px] font-bold text-slate-400 uppercase tracking-widest">Actions</th>
                        @endif
                    </x-slot>

                    <tbody class="divide-y divide-slate-100/80">
                        @forelse($products as $product)
                            <tr wire:key="prod-row-{{ $product->id }}" x-show="isItemVisible({{ $product->id }})" x-cloak class="hover:bg-slate-50/50 transition-colors group/row">
                                <td class="py-4 px-6 border-r border-slate-100/50 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center shrink-0 overflow-hidden shadow-sm transition-transform group-hover/row:scale-105">
                                            @if($product->image && Storage::disk('public')->exists($product->image))
                                                <img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='{{ asset('images/placeholder-product.png') }}';">
                                            @else
                                                <img src="{{ asset('images/placeholder-product.png') }}" class="w-full h-full object-cover">
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-[13px] font-bold text-slate-900 tracking-tight truncate group-hover/row:text-indigo-600 transition-colors">{{ $product->name }}</h4>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[9px] font-black text-indigo-500/80 bg-indigo-50/50 px-1.5 py-0.5 rounded-md border border-indigo-100/50 uppercase tracking-widest">{{ $product->category->name ?? 'Uncategorized' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 border-r border-slate-100/50 text-center whitespace-nowrap">
                                    <button type="button" wire:click.stop="toggleStatus({{ $product->id }})" class="flex items-center justify-center gap-2 hover:opacity-85 transition-opacity focus:outline-none mx-auto">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $product->effective_is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                        <span class="text-[11px] font-bold {{ $product->effective_is_active ? 'text-emerald-600' : 'text-rose-600' }} uppercase">{{ $product->effective_is_active ? 'Active' : 'Hidden' }}</span>
                                    </button>
                                </td>
                                <td class="py-4 px-6 border-r border-slate-100/50 text-right whitespace-nowrap">
                                    <span class="text-[15px] font-black text-slate-900 italic font-mono tracking-tight">₱{{ number_format($product->price, 2) }}</span>
                                </td>
                                @if($this->isSuperAdmin())
                                    <td class="py-4 px-6 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-secondary-button wire:click="showEdit({{ $product->id }})" class="h-8 px-3 text-[11px] font-bold bg-white hover:bg-slate-50 border-slate-200 shadow-none">
                                                Edit Product
                                            </x-secondary-button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $this->isSuperAdmin() ? 4 : 3 }}" class="py-12">
                                    <x-empty-state title="Empty Catalog" description="No products found in this scope." />
                                </td>
                            </tr>
                        @endforelse
                        <tr x-show="filteredProductIds.length === 0" x-cloak>
                            <td colspan="{{ $this->isSuperAdmin() ? 4 : 3 }}" class="py-12">
                                <x-empty-state title="No products match your search" description="Try typing a different name or checking your filters." />
                            </td>
                        </tr>
                    </tbody>
                </x-data-table>
                <div class="mt-4 px-1">
                    <template x-if="filteredProductIds.length > 0">
                        <div class="flex flex-col lg:flex-row items-center justify-between px-4 py-4 bg-white border-t border-gray-100 lg:px-6 gap-6">
                            <div class="flex flex-col sm:flex-row items-center justify-between w-full lg:w-auto gap-4 sm:gap-8">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:gap-1.5 leading-[0.9] sm:leading-none">
                                        <span class="text-[11px] sm:text-[12px] text-gray-400 font-black uppercase tracking-tighter sm:tracking-widest">Row</span>
                                        <span class="text-[9px] sm:text-[12px] text-gray-400/70 font-black uppercase tracking-tighter sm:tracking-widest">per page</span>
                                    </div>
                                    <div>
                                        <x-dropdown align="top" width="20" containerClasses="block">
                                            <x-slot name="trigger">
                                                <button type="button" class="inline-flex items-center justify-between min-w-[70px] px-3 py-1.5 text-[13px] font-black text-gray-900 bg-slate-50 border border-gray-200 rounded-xl hover:border-gray-300 focus:outline-none transition-all h-10 gap-2 shadow-sm">
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
                                    <span class="text-gray-900" x-text="Math.min(filteredProductIds.length, (currentPage - 1) * perPage + 1)"></span>
                                    <span class="mx-0.5 text-gray-300">-</span>
                                    <span class="text-gray-900" x-text="Math.min(filteredProductIds.length, currentPage * perPage)"></span>
                                    <span class="mx-1 text-gray-300 lowercase italic font-medium">of</span>
                                    <span class="text-indigo-600" x-text="filteredProductIds.length"></span>
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
                                    
                                    <template x-if="Math.ceil(filteredProductIds.length / perPage) > currentPage + 1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-gray-300 font-bold mx-1">...</span>
                                            <x-secondary-button @click="currentPage = Math.ceil(filteredProductIds.length / perPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl text-[13px] font-bold">
                                                <span x-text="Math.ceil(filteredProductIds.length / perPage)"></span>
                                            </x-secondary-button>
                                        </div>
                                    </template>
                                </div>
                                <x-secondary-button @click="currentPage = Math.min(Math.ceil(filteredProductIds.length / perPage), currentPage + 1)" ::disabled="currentPage >= Math.ceil(filteredProductIds.length / perPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="currentPage >= Math.ceil(filteredProductIds.length / perPage) ? 'opacity-30 pointer-events-none' : ''">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </x-secondary-button>
                                <x-secondary-button @click="currentPage = Math.ceil(filteredProductIds.length / perPage) || 1" ::disabled="currentPage >= Math.ceil(filteredProductIds.length / perPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="currentPage >= Math.ceil(filteredProductIds.length / perPage) ? 'opacity-30 pointer-events-none' : ''">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                    </svg>
                                </x-secondary-button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Board View --}}
            <template x-if="tableView === 'board'">
            <div x-show="tableView === 'board'"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @forelse($products as $product)
                        <div wire:key="prod-card-{{ $product->id }}" x-show="isItemVisible({{ $product->id }})" x-cloak class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm hover:shadow-md transition-all group/card relative {{ $this->isSuperAdmin() ? 'cursor-pointer' : '' }}" @if($this->isSuperAdmin()) wire:click="showEdit({{ $product->id }})" @endif>
                            <div class="aspect-[4/3] rounded-xl bg-slate-50 border border-slate-100 overflow-hidden mb-4 relative shadow-inner">
                                @if($product->image && Storage::disk('public')->exists($product->image))
                                    <img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover transition-transform duration-500 group-hover/card:scale-110" onerror="this.onerror=null;this.src='{{ asset('images/placeholder-product.png') }}';">
                                @else
                                    <img src="{{ asset('images/placeholder-product.png') }}" class="w-full h-full object-cover transition-transform duration-500 group-hover/card:scale-110">
                                @endif
                                <div class="absolute top-2 right-2">
                                    <span class="px-2 py-1 bg-white/90 backdrop-blur-md border border-white/20 rounded-lg text-[11px] font-black text-slate-900 shadow-sm font-mono tracking-tighter italic">₱{{ number_format($product->price, 2) }}</span>
                                </div>
                                <div class="absolute bottom-2 left-2 bg-white/95 backdrop-blur-sm rounded-lg px-2.5 py-1 shadow-sm border border-slate-100/50 flex items-center justify-center gap-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $product->effective_is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span class="text-[10px] font-bold {{ $product->effective_is_active ? 'text-emerald-600' : 'text-rose-600' }} uppercase tracking-wider">{{ $product->effective_is_active ? 'Active' : 'Hidden' }}</span>
                                </div>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="text-[9px] font-black text-indigo-500/80 uppercase tracking-widest block mb-0.5 truncate">{{ $product->category->name ?? 'Uncategorized' }}</span>
                                    <h4 class="text-[13px] font-bold text-slate-900 truncate tracking-tight group-hover/card:text-indigo-600 transition-colors">{{ $product->name }}</h4>
                                </div>
                                <div class="shrink-0 flex items-center gap-1">
                                    @if($this->isSuperAdmin())
                                        <button type="button" wire:click.stop="showEdit({{ $product->id }})" class="p-2 text-slate-300 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all" title="Edit Product">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-24 text-center bg-white rounded-2xl border border-slate-100 shadow-sm">
                            <x-empty-state title="Catalog is empty" description="Try changing your filters or adding a new item." />
                        </div>
                    @endforelse
                    <div x-show="filteredProductIds.length === 0" x-cloak class="col-span-full py-24 text-center bg-white rounded-2xl border border-slate-100 shadow-sm">
                        <x-empty-state title="No products match your search" description="Try changing your filters or search terms." />
                    </div>
                </div>
                <div class="mt-6">
                    <template x-if="filteredProductIds.length > 0">
                        <div class="flex flex-col lg:flex-row items-center justify-between px-4 py-4 bg-white border border-gray-100 rounded-2xl lg:px-6 gap-6">
                            <div class="flex flex-col sm:flex-row items-center justify-between w-full lg:w-auto gap-4 sm:gap-8">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:gap-1.5 leading-[0.9] sm:leading-none">
                                        <span class="text-[11px] sm:text-[12px] text-gray-400 font-black uppercase tracking-tighter sm:tracking-widest">Row</span>
                                        <span class="text-[9px] sm:text-[12px] text-gray-400/70 font-black uppercase tracking-tighter sm:tracking-widest">per page</span>
                                    </div>
                                    <div>
                                        <x-dropdown align="top" width="20" containerClasses="block">
                                            <x-slot name="trigger">
                                                <button type="button" class="inline-flex items-center justify-between min-w-[70px] px-3 py-1.5 text-[13px] font-black text-gray-900 bg-slate-50 border border-gray-200 rounded-xl hover:border-gray-300 focus:outline-none transition-all h-10 gap-2 shadow-sm">
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
                                    <span class="text-gray-900" x-text="Math.min(filteredProductIds.length, (currentPage - 1) * perPage + 1)"></span>
                                    <span class="mx-0.5 text-gray-300">-</span>
                                    <span class="text-gray-900" x-text="Math.min(filteredProductIds.length, currentPage * perPage)"></span>
                                    <span class="mx-1 text-gray-300 lowercase italic font-medium">of</span>
                                    <span class="text-indigo-600" x-text="filteredProductIds.length"></span>
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
                                    
                                    <template x-if="Math.ceil(filteredProductIds.length / perPage) > currentPage + 1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-gray-300 font-bold mx-1">...</span>
                                            <x-secondary-button @click="currentPage = Math.ceil(filteredProductIds.length / perPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl text-[13px] font-bold">
                                                <span x-text="Math.ceil(filteredProductIds.length / perPage)"></span>
                                            </x-secondary-button>
                                        </div>
                                    </template>
                                </div>
                                <x-secondary-button @click="currentPage = Math.min(Math.ceil(filteredProductIds.length / perPage), currentPage + 1)" ::disabled="currentPage >= Math.ceil(filteredProductIds.length / perPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="currentPage >= Math.ceil(filteredProductIds.length / perPage) ? 'opacity-30 pointer-events-none' : ''">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </x-secondary-button>
                                <x-secondary-button @click="currentPage = Math.ceil(filteredProductIds.length / perPage) || 1" ::disabled="currentPage >= Math.ceil(filteredProductIds.length / perPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="currentPage >= Math.ceil(filteredProductIds.length / perPage) ? 'opacity-30 pointer-events-none' : ''">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                    </svg>
                                </x-secondary-button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            </template>
            </div>
        </div>
        </div>
    </div>{{-- end panel list --}}

    {{-- Modals --}}
    <x-modal name="confirm-save-product" maxWidth="sm" focusable wire:key="modal-confirm-save">
        <div class="h-1 w-full bg-gradient-to-r from-emerald-400 to-teal-500 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 13l4 4L19 7" /></svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight" x-text="mode === 'edit' ? 'Update Product' : 'Register Product'"></h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed" x-text="mode === 'edit' ? 'This will finalize all changes and update the product across all active branch menus.' : 'Register this new product into the catalog?'"></p>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-save-product')" class="h-10">Cancel</x-secondary-button>
                <x-primary-button 
                    wire:click="saveProduct" 
                    @click="$dispatch('close-modal', 'confirm-save-product')" 
                    class="h-10">
                    Confirm & Save
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="delete-product" maxWidth="sm" focusable wire:key="modal-delete-product">
        <div class="h-1 w-full bg-gradient-to-r from-red-400 to-rose-500 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-50 border border-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Delete Product</h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">
                        Are you sure you want to remove this product? All associated recipes and branch configurations will be lost permanently.
                    </p>
                </div>
            </div>
            
            <div class="px-3 py-2 bg-white border border-gray-100 rounded-lg text-[13px] text-gray-600 mb-5">
                <span class="font-semibold text-gray-800">{{ $deleteTargetName }}</span>
            </div>

            <div class="flex items-center justify-end gap-2">
                <x-secondary-button @click="$dispatch('close-modal', 'delete-product')">Cancel</x-secondary-button>
                <x-danger-button wire:click="deleteProduct" @click="$dispatch('close-modal', 'delete-product')">Delete</x-danger-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="quick-add-category" maxWidth="sm" focusable wire:key="modal-quick-add-cat">
        <div class="h-1 w-full bg-gradient-to-r from-indigo-400 to-sky-500 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4" /></svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">New Category Entry</h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">Add a new category to organize your menu.</p>
                </div>
            </div>
            
            <div class="mt-4">
                <x-input-label value="Category Name" />
                <x-text-input wire:model.live.debounce.400ms="newCategoryName" class="w-full mt-1.5 h-10" placeholder="e.g. Snacks & Drinks, Platters / Combos" inputFilter="categoryName" :hasError="$errors->has('newCategoryName')" />
                <x-input-error :messages="$errors->get('newCategoryName')" class="mt-1" />
            </div>

            <div class="mt-4">
                <x-input-label value="Production Station" />
                <div class="mt-2 flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="radio" wire:model.live="newCategoryStation" value="kitchen" class="w-4 h-4 text-indigo-600 border-slate-200 focus:ring-indigo-500 transition-all">
                        <span class="text-[13px] font-medium text-slate-600 group-hover:text-slate-900 transition-colors">Kitchen</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="radio" wire:model.live="newCategoryStation" value="barista" class="w-4 h-4 text-indigo-600 border-slate-200 focus:ring-indigo-500 transition-all">
                        <span class="text-[13px] font-medium text-slate-600 group-hover:text-slate-900 transition-colors">Barista</span>
                    </label>
                </div>
                <x-input-error :messages="$errors->get('newCategoryStation')" class="mt-1" />
            </div>
            
            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'quick-add-category')" class="h-10">Cancel</x-secondary-button>
                <x-primary-button wire:click="quickAddCategory" class="h-10">Create Category</x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- Add Option Group Modal --}}
    <x-modal name="add-option-group" maxWidth="sm" focusable wire:key="modal-add-option-group">
        <div class="h-1 w-full bg-gradient-to-r from-indigo-400 to-sky-500 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4" /></svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Configure Option Group</h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">Groups allow customers to customize their order.</p>
                </div>
            </div>

            <div class="mt-4 space-y-4">
                <div>
                    <x-input-label value="Group Name" />
                    <input type="text" x-model="newGroupName" @keydown.enter.prevent="submitAddGroup()" class="w-full mt-1.5 h-10 px-3.5 rounded-xl border border-slate-200 text-[13px] font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="e.g. Extras, Sizes, Flavors" />
                    <div x-show="newGroupError" x-cloak class="text-[11px] font-medium text-red-500 mt-1.5 flex items-start gap-1.5 animate-in fade-in slide-in-from-top-1 duration-200">
                        <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <ul class="space-y-0.5">
                            <li x-text="newGroupError"></li>
                        </ul>
                    </div>                </div>
                <div>
                    <x-input-label value="Price Calculation Mode" />
                    <div class="mt-1.5">
                        <select x-model="newGroupPriceMode" class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-xl text-[13px] font-medium text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <option value="additive">Additive (Price + Base Price)</option>
                            <option value="fixed">Fixed (Overrides Base Price)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <x-input-label value="Selection Mode" />
                    <div class="grid grid-cols-2 gap-2 mt-1.5">
                        <button type="button" @click="newGroupMaxSelect = 1" 
                            class="h-10 rounded-xl border text-[12px] font-bold transition-all"
                            :class="newGroupMaxSelect == 1 ? 'bg-indigo-50 border-indigo-500 text-indigo-700 shadow-sm' : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'">
                            Single (Pick 1)
                        </button>
                        <button type="button" @click="if (newGroupMaxSelect == 1) newGroupMaxSelect = null" 
                            class="h-10 rounded-xl border text-[12px] font-bold transition-all"
                            :class="newGroupMaxSelect != 1 ? 'bg-indigo-50 border-indigo-500 text-indigo-700 shadow-sm' : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'">
                            Multiple Choice
                        </button>
                    </div>
                    <div x-show="newGroupMaxSelect != 1" x-cloak class="mt-2.5 p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-600 block">Max Selection Limit</label>
                        <div class="mt-1 flex items-center h-10 border border-gray-200 rounded-lg overflow-hidden shadow-sm bg-white">
                            <button type="button" tabindex="-1" @click="decrementNewGroupMaxSelect()"
                                :disabled="newGroupMaxSelect === null || newGroupMaxSelect === undefined"
                                class="w-10 h-full flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-900 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-white transition-colors border-r border-gray-200 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <input type="text" inputmode="numeric" maxlength="2"
                                :value="newGroupMaxSelect === null || newGroupMaxSelect === undefined || newGroupMaxSelect === '' ? '' : newGroupMaxSelect"
                                @input="handleNewGroupMaxSelectInput($event)"
                                placeholder="No Limit"
                                class="flex-1 w-full h-full text-center text-[13px] font-bold text-gray-900 border-0 focus:ring-0 bg-transparent placeholder:text-gray-400 placeholder:font-bold [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                            <button type="button" tabindex="-1" @click="incrementNewGroupMaxSelect()"
                                :disabled="newGroupMaxSelect >= 99"
                                class="w-10 h-full flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-900 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-white transition-colors border-l border-gray-200 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-400">2–99 selections. Leave blank for no limit.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 bg-amber-50/50 rounded-xl border border-amber-100 transition-all hover:bg-amber-50">
                    <input type="checkbox" x-model="newGroupNoRecipeRequired" id="no_recipe_group" class="w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                    <label for="no_recipe_group" class="text-[12px] font-medium text-slate-700 cursor-pointer">No Recipe Required (options always available, skip ingredient tracking)</label>
                </div>
                <div class="flex items-center gap-3 p-3 bg-slate-50/50 rounded-xl border border-slate-100 transition-all hover:bg-indigo-50/30">
                    <input type="checkbox" x-model="newGroupIsRequired" id="is_req_group" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="is_req_group" class="text-[12px] font-medium text-slate-700 cursor-pointer">Mandatory Selection (Customer must choose)</label>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'add-option-group')" class="h-10">Cancel</x-secondary-button>
                <x-primary-button type="button" @click="submitAddGroup()" class="h-10">Add Group</x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- Options Action Confirmation Modal (Sync, Save Template, Delete) --}}
    <x-modal name="confirm-options-action" maxWidth="sm" focusable wire:key="modal-confirm-options-action">
        <div class="h-1 w-full rounded-t-lg"
            :class="confirmActionType === 'delete_group' || confirmActionType === 'delete_option' ? 'bg-gradient-to-r from-red-400 to-rose-500' : (confirmActionType === 'sync' ? 'bg-gradient-to-r from-emerald-400 to-teal-500' : 'bg-gradient-to-r from-indigo-400 to-sky-500')"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                    :class="confirmActionType === 'delete_group' || confirmActionType === 'delete_option' ? 'bg-red-50 border border-red-100 text-red-500' : (confirmActionType === 'sync' ? 'bg-emerald-50 border border-emerald-100 text-emerald-500' : 'bg-indigo-50 border border-indigo-100 text-indigo-500')">
                    <template x-if="confirmActionType === 'delete_group' || confirmActionType === 'delete_option'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </template>
                    <template x-if="confirmActionType === 'sync'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </template>
                    <template x-if="confirmActionType === 'save_template'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    </template>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight" x-text="confirmActionTitle"></h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed" x-text="confirmActionMessage"></p>
                </div>
            </div>

            <template x-if="confirmActionTargetName">
                <div class="px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl text-[13px] font-bold text-slate-800 mb-5 truncate" x-text="confirmActionTargetName"></div>
            </template>

            <div class="flex items-center justify-end gap-2 mt-4">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-options-action')" class="h-10">Cancel</x-secondary-button>
                <template x-if="confirmActionType === 'delete_group' || confirmActionType === 'delete_option'">
                    <x-danger-button type="button" @click="executeConfirmedOptionAction()" class="h-10">Delete</x-danger-button>
                </template>
                <template x-if="confirmActionType === 'sync'">
                    <x-primary-button type="button" @click="executeConfirmedOptionAction()" class="h-10 !bg-emerald-600 hover:!bg-emerald-700">Confirm Sync</x-primary-button>
                </template>
                <template x-if="confirmActionType === 'save_template'">
                    <x-primary-button type="button" @click="executeConfirmedOptionAction()" class="h-10">Save Template</x-primary-button>
                </template>
            </div>
        </div>
    </x-modal>

    <script>
        (function() {
            window.menuManagement = function($wire, initialTemplates = [], initialCategories = []) {
                return {
                    activeTab: $wire.entangle('activeTab').live,
                    panel: $wire.entangle('panel').live,
                    tableView: $wire.entangle('view').live,
                    mode: $wire.entangle('mode').live,
                    ...window.slidingTabs($wire.entangle('activeTab').live, 'activeTab'),

                    optionGroups: $wire.entangle('optionGroups'),
                    recipeIngredients: $wire.entangle('recipeIngredients'),
                    templates: initialTemplates || [],
                    allCategories: initialCategories || [],

                    // Add group modal state
                    newGroupName: '',
                    newGroupPriceMode: 'additive',
                    newGroupMaxSelect: 1,
                    newGroupIsRequired: false,
                    newGroupNoRecipeRequired: false,
                    newGroupError: '',

                    // Options action confirmation modal state
                    confirmActionType: '',
                    confirmActionGroupIndex: null,
                    confirmActionOptionIndex: null,
                    confirmActionTitle: '',
                    confirmActionTargetName: '',
                    confirmActionMessage: '',

                    // Server-side validation error sync (Livewire $errors)
                    serverErrors: {},
                    updateServerErrors(errors) {
                        this.serverErrors = errors || {};
                    },
                    fieldError(key) {
                        const arr = this.serverErrors[key];
                        return (arr && arr.length) ? arr[0] : '';
                    },
                    clearOptionError(gIdx, oIdx, field) {
                        const key = 'optionGroups.' + gIdx + '.options.' + oIdx + '.' + field;
                        if (this.serverErrors && this.serverErrors[key]) {
                            const copy = { ...this.serverErrors };
                            delete copy[key];
                            this.serverErrors = copy;
                        }
                    },

                    openAddGroupModal() {
                        this.newGroupName = '';
                        this.newGroupPriceMode = 'additive';
                        this.newGroupMaxSelect = 1;
                        this.newGroupIsRequired = false;
                        this.newGroupNoRecipeRequired = false;
                        this.newGroupError = '';
                        this.$dispatch('open-modal', 'add-option-group');
                    },
                    submitAddGroup() {
                        const name = (this.newGroupName || '').trim();
                        if (!name) {
                            this.newGroupError = 'Group name is required.';
                            return;
                        }
                        const exists = (this.optionGroups || []).some(g => (g.name || '').toLowerCase() === name.toLowerCase());
                        if (exists) {
                            this.newGroupError = "An option group named '" + name + "' already exists.";
                            return;
                        }
                        if (!this.optionGroups) this.optionGroups = [];
                        this.optionGroups.push({
                            id: null,
                            name: name,
                            price_mode: this.newGroupPriceMode,
                            max_select: this.newGroupMaxSelect ? Number(this.newGroupMaxSelect) : null,
                            is_required: !!this.newGroupIsRequired,
                            no_recipe_required: !!this.newGroupNoRecipeRequired,
                            options: []
                        });
                        this.newGroupName = '';
                        this.newGroupMaxSelect = 1;
                        this.newGroupError = '';
                        this.$dispatch('close-modal', 'add-option-group');
                        this.$dispatch('notify', { type: 'success', message: "Group '" + name + "' added." });
                    },
                    addOption(gIdx) {
                        if (!this.optionGroups[gIdx]) return;
                        if (!this.optionGroups[gIdx].options) this.optionGroups[gIdx].options = [];
                        this.optionGroups[gIdx].options.push({
                            id: null,
                            name: '',
                            price: '',
                            is_default: this.optionGroups[gIdx].options.length === 0
                        });
                    },
                    toggleDefaultOption(gIdx, oIdx) {
                        if (!this.optionGroups[gIdx] || !this.optionGroups[gIdx].options) return;
                        const isCurrent = !!this.optionGroups[gIdx].options[oIdx].is_default;
                        this.optionGroups[gIdx].options.forEach((opt, index) => {
                            opt.is_default = (index === oIdx && !isCurrent);
                        });
                    },
                    // ── Per-group selection mode and max-select stepper (bounded by option count) ──
                    setGroupSingleMode(gIdx) {
                        const group = this.optionGroups[gIdx];
                        if (!group) return;
                        if (group.max_select !== 1) {
                            group._lastMulti = group.max_select;
                        }
                        group.max_select = 1;
                    },
                    setGroupMultiMode(gIdx) {
                        const group = this.optionGroups[gIdx];
                        if (!group) return;
                        if (group.max_select === 1) {
                            const optCount = (group.options || []).length;
                            let last = (group._lastMulti !== undefined && group._lastMulti !== 1) ? group._lastMulti : null;
                            if (last !== null && optCount > 0 && last >= optCount) {
                                last = null;
                            }
                            group.max_select = last;
                        }
                    },
                    handleGroupMaxSelectInput(gIdx, e) {
                        const group = this.optionGroups[gIdx];
                        if (!group) return;
                        const optCount = (group.options || []).length;
                        let digits = e.target.value.replace(/\D/g, '').slice(0, 2);
                        if (digits === '') {
                            group.max_select = null;
                        } else {
                            let n = parseInt(digits, 10);
                            if (n < 2) n = 2;
                            // If it reaches or exceeds the number of options, it limits and sets No Limit
                            if (optCount > 0 && n >= optCount) {
                                group.max_select = null;
                            } else {
                                group.max_select = n;
                            }
                        }
                        group._lastMulti = group.max_select;
                        e.target.value = (group.max_select === null || group.max_select === undefined) ? '' : String(group.max_select);
                    },
                    incrementGroupMaxSelect(gIdx) {
                        const group = this.optionGroups[gIdx];
                        if (!group) return;
                        const optCount = (group.options || []).length;
                        if (optCount <= 2) {
                            group.max_select = null;
                            return;
                        }
                        if (group.max_select === null || group.max_select === undefined) {
                            return;
                        }
                        let n = (typeof group.max_select === 'number' && group.max_select >= 2) ? group.max_select : 1;
                        let next = n + 1;
                        // If it reaches the number of options that will be the limit of multi choice,
                        // if you add it up to maximum it will limit and show "No Limit"
                        if (next >= optCount) {
                            group.max_select = null;
                        } else {
                            group.max_select = next;
                        }
                        group._lastMulti = group.max_select;
                    },
                    decrementGroupMaxSelect(gIdx) {
                        const group = this.optionGroups[gIdx];
                        if (!group) return;
                        const optCount = (group.options || []).length;
                        if (optCount <= 2) {
                            group.max_select = null;
                            return;
                        }
                        if (group.max_select === null || group.max_select === undefined) {
                            group.max_select = Math.max(2, optCount - 1);
                        } else if (group.max_select <= 2) {
                            group.max_select = 2;
                        } else {
                            group.max_select = group.max_select - 1;
                        }
                        group._lastMulti = group.max_select;
                    },
                    // ── Same stepper, for the "Add Option Group" modal's local field ──
                    handleNewGroupMaxSelectInput(e) {
                        let digits = e.target.value.replace(/\D/g, '').slice(0, 2);
                        if (digits === '') {
                            this.newGroupMaxSelect = null;
                        } else {
                            let n = parseInt(digits, 10);
                            if (n < 2) n = 2;
                            if (n > 99) n = 99;
                            this.newGroupMaxSelect = n;
                        }
                        e.target.value = (this.newGroupMaxSelect === null || this.newGroupMaxSelect === undefined) ? '' : String(this.newGroupMaxSelect);
                    },
                    incrementNewGroupMaxSelect() {
                        let n = (typeof this.newGroupMaxSelect === 'number' && this.newGroupMaxSelect >= 2) ? this.newGroupMaxSelect : 1;
                        this.newGroupMaxSelect = Math.min(99, n + 1);
                    },
                    decrementNewGroupMaxSelect() {
                        if (this.newGroupMaxSelect === null || this.newGroupMaxSelect === undefined) return;
                        if (this.newGroupMaxSelect <= 2) {
                            this.newGroupMaxSelect = null;
                        } else {
                            this.newGroupMaxSelect = this.newGroupMaxSelect - 1;
                        }
                    },
                    promptDeleteOption(gIdx, oIdx) {
                        const group = this.optionGroups[gIdx];
                        if (!group) return;
                        const option = group.options ? group.options[oIdx] : null;
                        if (!option) return;
                        this.confirmActionType = 'delete_option';
                        this.confirmActionGroupIndex = gIdx;
                        this.confirmActionOptionIndex = oIdx;
                        this.confirmActionTitle = 'Remove Option';
                        this.confirmActionTargetName = option.name || ('Option #' + (oIdx + 1));
                        this.confirmActionMessage = "Are you sure you want to remove '" + this.confirmActionTargetName + "' from the '" + group.name + "' group?";
                        this.$dispatch('open-modal', 'confirm-options-action');
                    },
                    promptDeleteGroup(gIdx) {
                        const group = this.optionGroups[gIdx];
                        if (!group) return;
                        this.confirmActionType = 'delete_group';
                        this.confirmActionGroupIndex = gIdx;
                        this.confirmActionOptionIndex = null;
                        this.confirmActionTitle = 'Delete Option Group';
                        this.confirmActionTargetName = group.name;
                        this.confirmActionMessage = "Are you sure you want to remove the '" + group.name + "' group? All options within this group and any linked recipe components will be permanently deleted.";
                        this.$dispatch('open-modal', 'confirm-options-action');
                    },
                    promptSyncGroup(gIdx) {
                        const group = this.optionGroups[gIdx];
                        if (!group) return;
                        this.confirmActionType = 'sync';
                        this.confirmActionGroupIndex = gIdx;
                        this.confirmActionOptionIndex = null;
                        this.confirmActionTitle = 'Sync with Library Template';
                        this.confirmActionTargetName = group.name;
                        this.confirmActionMessage = "This will update '" + group.name + "' with the latest options and recipe ingredients from the library template. Any local edits may be overwritten.";
                        this.$dispatch('open-modal', 'confirm-options-action');
                    },
                    promptSaveGroup(gIdx) {
                        const group = this.optionGroups[gIdx];
                        if (!group) return;
                        this.confirmActionType = 'save_template';
                        this.confirmActionGroupIndex = gIdx;
                        this.confirmActionOptionIndex = null;
                        this.confirmActionTitle = 'Save Group as Template';
                        this.confirmActionTargetName = group.name;
                        this.confirmActionMessage = "Save '" + group.name + "' and its configured recipe mappings to the Options Library? This template will become available for all products.";
                        this.$dispatch('open-modal', 'confirm-options-action');
                    },
                    executeConfirmedOptionAction() {
                        const gIdx = this.confirmActionGroupIndex;
                        const oIdx = this.confirmActionOptionIndex;
                        const type = this.confirmActionType;

                        this.$dispatch('close-modal', 'confirm-options-action');

                        if (type === 'delete_option') {
                            if (gIdx !== null && oIdx !== null && this.optionGroups[gIdx] && this.optionGroups[gIdx].options) {
                                const opt = this.optionGroups[gIdx].options[oIdx];
                                const ownerKeyUnsaved = 'option:' + gIdx + '_' + oIdx;
                                const ownerKeySaved = opt && opt.id ? ('option:' + opt.id) : null;
                                if (this.recipeIngredients) {
                                    this.recipeIngredients = this.recipeIngredients.filter(ri => ri.owner !== ownerKeyUnsaved && (!ownerKeySaved || ri.owner !== ownerKeySaved));
                                    this.recipeIngredients.forEach(ri => {
                                        if (ri.owner && ri.owner.startsWith('option:' + gIdx + '_')) {
                                            const currOIdx = parseInt(ri.owner.replace('option:' + gIdx + '_', ''));
                                            if (currOIdx > oIdx) {
                                                ri.owner = 'option:' + gIdx + '_' + (currOIdx - 1);
                                            }
                                        }
                                    });
                                }
                                this.optionGroups[gIdx].options.splice(oIdx, 1);
                                const remainingOpts = this.optionGroups[gIdx].options.length;
                                if (this.optionGroups[gIdx].max_select !== 1 && this.optionGroups[gIdx].max_select !== null && this.optionGroups[gIdx].max_select >= remainingOpts) {
                                    this.optionGroups[gIdx].max_select = null;
                                }
                                this.$dispatch('notify', { type: 'info', message: 'Option removed.' });
                            }
                        } else if (type === 'delete_group') {
                            if (gIdx !== null && this.optionGroups[gIdx]) {
                                const grp = this.optionGroups[gIdx];
                                const prefixUnsaved = 'option:' + gIdx + '_';
                                const savedOptIds = (grp.options || []).map(o => o.id).filter(Boolean).map(id => 'option:' + id);
                                if (this.recipeIngredients) {
                                    this.recipeIngredients = this.recipeIngredients.filter(ri => {
                                        if (ri.owner && ri.owner.startsWith(prefixUnsaved)) return false;
                                        if (savedOptIds.includes(ri.owner)) return false;
                                        return true;
                                    });
                                    this.recipeIngredients.forEach(ri => {
                                        if (ri.owner && ri.owner.startsWith('option:')) {
                                            const ref = ri.owner.replace('option:', '');
                                            if (ref.includes('_')) {
                                                const parts = ref.split('_');
                                                const groupI = parseInt(parts[0]);
                                                const optI = parseInt(parts[1]);
                                                if (groupI > gIdx) {
                                                    ri.owner = 'option:' + (groupI - 1) + '_' + optI;
                                                }
                                            }
                                        }
                                    });
                                }
                                this.optionGroups.splice(gIdx, 1);
                                this.$dispatch('notify', { type: 'info', message: "Group '" + grp.name + "' removed." });
                            }
                        } else if (type === 'sync') {
                            this.executeSync(gIdx);
                        } else if (type === 'save_template') {
                            $wire.set('optionGroups', this.optionGroups, false);
                            $wire.saveGroupToLibrary(gIdx);
                        }
                    },
                    executeSync(gIdx) {
                        const group = this.optionGroups[gIdx];
                        if (!group) return;
                        const template = (this.templates || []).find(t => (t.name || '').toLowerCase() === (group.name || '').toLowerCase());
                        if (!template) {
                            this.$dispatch('notify', { type: 'error', message: "No Library template named '" + group.name + "' found to sync from." });
                            return;
                        }

                        // Pull group-level settings from the template too — pricing
                        // mode, selection limit, required flag, and no-recipe flag
                        // are all part of "the template", not just its ingredients.
                        group.price_mode = template.price_mode;
                        group.max_select = template.max_select !== undefined ? template.max_select : group.max_select;
                        group.is_required = !!template.is_required;
                        group.no_recipe_required = !!template.no_recipe_required;

                        let addedOptionCount = 0;
                        let updatedOptionCount = 0;
                        let addedIngredientCount = 0;
                        const noRecipe = !!group.no_recipe_required;
                        const matchedItemIds = [];
                        if (!this.recipeIngredients) this.recipeIngredients = [];

                        // 1) Sync price, default flag, and ingredients into existing options
                        (group.options || []).forEach((opt, optIndex) => {
                            const templateItem = (template.items || []).find(ti => (ti.name || '').toLowerCase() === (opt.name || '').toLowerCase());
                            if (!templateItem) return;
                            matchedItemIds.push(templateItem.id);

                            const templatePrice = Number(templateItem.price) === 0 ? '' : templateItem.price;
                            if (String(opt.price ?? '') !== String(templatePrice ?? '') || !!opt.is_default !== !!templateItem.is_default) {
                                updatedOptionCount++;
                            }
                            opt.price = templatePrice;
                            opt.is_default = !!templateItem.is_default;

                            if (noRecipe) return;

                            const ownerKey = 'option:' + gIdx + '_' + optIndex;
                            const legacyOwner = opt.id ? ('option:' + opt.id) : null;

                            (templateItem.ingredients || []).forEach(ri => {
                                const ingId = Number(ri.ingredient_id);
                                const exists = this.recipeIngredients.some(existing => Number(existing.id) === ingId && (existing.owner === ownerKey || (legacyOwner && existing.owner === legacyOwner)));
                                if (exists) return;

                                this.recipeIngredients.push({
                                    id: ingId,
                                    name: ri.ingredient ? ri.ingredient.name : '',
                                    unit: ri.ingredient ? ri.ingredient.unit : '',
                                    quantity: parseFloat(ri.quantity) || 0,
                                    cost: parseFloat(ri.ingredient ? ri.ingredient.cost : 0) || 0,
                                    owner: legacyOwner || ownerKey
                                });
                                addedIngredientCount++;
                            });
                        });

                        // If the group is now flagged No Recipe (either it already
                        // was, or the template just turned it on), strip any
                        // ingredients staged for its options — same rule the
                        // checkbox's own @change handler enforces.
                        if (noRecipe) {
                            this.recipeIngredients = this.recipeIngredients.filter(ri => {
                                if (!ri.owner || !ri.owner.startsWith('option:')) return true;
                                const ref = ri.owner.replace('option:', '');
                                if (ref.includes('_')) {
                                    const [refG] = ref.split('_');
                                    return Number(refG) !== Number(gIdx);
                                }
                                const stillBelongs = (group.options || []).some(o => o.id && String(o.id) === ref);
                                return !stillBelongs;
                            });
                        }

                        // 2) Pull in template items that aren't options yet
                        (template.items || []).forEach(templateItem => {
                            if (matchedItemIds.includes(templateItem.id)) return;
                            const alreadyExists = (group.options || []).some(o => (o.name || '').toLowerCase() === (templateItem.name || '').toLowerCase());
                            if (alreadyExists) return;

                            const newOptIndex = group.options.length;
                            group.options.push({
                                id: null,
                                name: templateItem.name,
                                price: Number(templateItem.price) === 0 ? '' : templateItem.price,
                                is_default: !!templateItem.is_default
                            });
                            addedOptionCount++;

                            if (!noRecipe) {
                                const ownerKey = 'option:' + gIdx + '_' + newOptIndex;
                                (templateItem.ingredients || []).forEach(ri => {
                                    this.recipeIngredients.push({
                                        id: Number(ri.ingredient_id),
                                        name: ri.ingredient ? ri.ingredient.name : '',
                                        unit: ri.ingredient ? ri.ingredient.unit : '',
                                        quantity: parseFloat(ri.quantity) || 0,
                                        cost: parseFloat(ri.ingredient ? ri.ingredient.cost : 0) || 0,
                                        owner: ownerKey
                                    });
                                    addedIngredientCount++;
                                });
                            }
                        });

                        if (addedOptionCount > 0 || updatedOptionCount > 0 || addedIngredientCount > 0) {
                            const parts = [];
                            if (addedOptionCount > 0) parts.push(addedOptionCount + ' new option(s)');
                            if (updatedOptionCount > 0) parts.push(updatedOptionCount + ' price/default update(s)');
                            if (addedIngredientCount > 0) parts.push(addedIngredientCount + ' ingredient(s)');
                            this.$dispatch('notify', { type: 'success', message: 'Synced ' + parts.join(', ') + " from the '" + template.name + "' template." });
                        } else {
                            this.$dispatch('notify', { type: 'info', message: 'Already up to date — nothing new to sync.' });
                        }
                    },
                    
                    importTemplate(templateId) {
                        const template = (this.templates || []).find(t => Number(t.id) === Number(templateId));
                        if (!template) return;

                        if (!this.optionGroups) this.optionGroups = [];
                        const exists = this.optionGroups.some(g => (g.name || '').toLowerCase() === (template.name || '').toLowerCase());
                        if (exists) {
                            this.$dispatch('close-modal', 'import-template-library');
                            this.$dispatch('notify', { type: 'error', message: "The '" + template.name + "' template is already added to this product." });
                            return;
                        }

                        const options = (template.items || []).map((item, idx) => ({
                            id: null,
                            name: item.name,
                            price: Number(item.price) === 0 ? '' : item.price,
                            is_default: !!item.is_default,
                            sort_order: idx
                        }));

                        const newGroupIndex = this.optionGroups.length;
                        this.optionGroups.push({
                            id: null,
                            name: template.name,
                            price_mode: template.price_mode,
                            max_select: template.max_select !== undefined ? template.max_select : (template.price_mode === 'fixed' ? 1 : null),
                            is_required: !!template.is_required,
                            no_recipe_required: !!template.no_recipe_required,
                            options: options
                        });

                        let addedIngredientCount = 0;
                        if (!template.no_recipe_required) {
                            if (!this.recipeIngredients) this.recipeIngredients = [];
                            (template.items || []).forEach((item, optIdx) => {
                                const owner = 'option:' + newGroupIndex + '_' + optIdx;
                                (item.ingredients || []).forEach(ri => {
                                    const ingId = Number(ri.ingredient_id);
                                    const alreadyPresent = this.recipeIngredients.some(existing => Number(existing.id) === ingId && existing.owner === owner);
                                    if (alreadyPresent) return;

                                    this.recipeIngredients.push({
                                        id: ingId,
                                        name: ri.ingredient ? ri.ingredient.name : '',
                                        unit: ri.ingredient ? ri.ingredient.unit : '',
                                        quantity: parseFloat(ri.quantity) || 0,
                                        cost: parseFloat(ri.ingredient ? ri.ingredient.cost : 0) || 0,
                                        owner: owner
                                    });
                                    addedIngredientCount++;
                                });
                            });
                        }

                        this.$dispatch('close-modal', 'import-template-library');
                        let msg = "Imported '" + template.name + "' template.";
                        if (addedIngredientCount > 0) {
                            msg += " " + addedIngredientCount + " recipe ingredient(s) auto-mapped — review under the Recipe tab.";
                        }
                        this.$dispatch('notify', { type: 'success', message: msg });
                    },

                    // Client-side search and pagination
                    searchQuery: '',
                    currentPage: 1,
                    perPage: 5,
                    productsList: [],

                    get filteredProductIds() {
                        const query = this.searchQuery.toLowerCase().trim();
                        return this.productsList
                            .filter(p => !query || p.name.toLowerCase().includes(query))
                            .map(p => p.id);
                    },
                    get paginatedProductIds() {
                        const start = (this.currentPage - 1) * this.perPage;
                        return this.filteredProductIds.slice(start, start + this.perPage);
                    },
                    get pageNumbers() {
                        const totalPages = Math.ceil(this.filteredProductIds.length / this.perPage) || 1;
                        const start = Math.max(1, this.currentPage - 1);
                        const end = Math.min(totalPages, this.currentPage + 1);
                        const pages = [];
                        for (let i = start; i <= end; i++) {
                            pages.push(i);
                        }
                        return pages;
                    },
                    isItemVisible(id) {
                        return this.paginatedProductIds.includes(id);
                    },
                    updateProductsList(newList) {
                        const oldIds = this.productsList.map(p => p.id).join(',');
                        const newIds = newList.map(p => p.id).join(',');
                        if (oldIds !== newIds) {
                            this.productsList = newList;
                            this.currentPage = 1;
                        }
                    },

                    init() {
                        const base = window.slidingTabs($wire.entangle('activeTab').live, 'activeTab');
                        if (base.init) base.init.call(this);

                        // Sync tabs when panel changes to form
                        this.$watch('panel', value => {
                            if (value === 'form') {
                                setTimeout(() => this.updateIndicator('activeTab'), 50);
                                setTimeout(() => this.updateIndicator('activeTab'), 300);
                            }
                        });

                        // Reset page on search
                        this.$watch('searchQuery', () => {
                            this.currentPage = 1;
                        });
                    }
                };
            };
        })();
    </script>
</div>

