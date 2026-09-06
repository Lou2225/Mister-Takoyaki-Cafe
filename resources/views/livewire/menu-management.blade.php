<div 
    x-data="window.menuManagement($wire)"
    x-on:switch-panel.window="panel = $event.detail.panel"
    class="relative min-h-full flex flex-col p-2 md:p-4"
    wire:ignore.self
    wire:key="menu-management-main-container">
    {{-- Hidden reactive updater to sync products list under wire:ignore --}}
    <div x-effect="updateProductsList(@js($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name])))" class="hidden" wire:key="products-sync-helper"></div>
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
                                    <div class="flex-1">
                                        <x-dropdown align="left" width="full" containerClasses="block w-full">
                                            <x-slot name="trigger">
                                                <button type="button" class="flex items-center justify-between w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none transition-all h-11">
                                                    <span class="font-medium text-slate-600">{{ $selectedCategoryName }}</span>
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                                                </button>
                                            </x-slot>
                                            <x-slot name="content">
                                                <div class="p-2">
                                                    <div class="px-2 pb-2 mb-2 border-b border-slate-50">
                                                        <div class="relative">
                                                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                            <input wire:model.live.debounce.300ms="categorySearch" type="text" placeholder="Search categories..." 
                                                                    class="w-full pl-9 pr-4 py-2 bg-slate-50 border-none rounded-lg text-[12px] font-medium focus:ring-1 focus:ring-indigo-500 placeholder-slate-400">
                                                        </div>
                                                    </div>
                                                    <div class="max-h-60 overflow-y-auto custom-scrollbar">
                                                        @if(empty($categorySearch))
                                                            <x-dropdown-link href="#" wire:click.prevent="$set('categoryId', '')">Uncategorized</x-dropdown-link>
                                                            <hr class="border-slate-50">
                                                        @endif
                                                        
                                                        @forelse($categories as $cat)
                                                            <x-dropdown-link href="#" wire:click.prevent="$set('categoryId', {{ $cat->id }})">{{ $cat->name }}</x-dropdown-link>
                                                        @empty
                                                            <div class="px-4 py-2 text-[12px] text-slate-400 italic">No categories found</div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                    @if($this->isSuperAdmin())
                                    <button type="button" @click="$dispatch('open-modal', 'quick-add-category')" class="h-11 w-11 flex items-center justify-center bg-slate-50 border border-slate-200 rounded-xl text-indigo-600 hover:bg-indigo-50 transition-all shadow-sm">
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
                                <button type="button" @click="$dispatch('open-modal', 'add-option-group')" class="h-10 px-3 bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-indigo-100 transition-all border border-indigo-100/50 flex items-center justify-center truncate">
                                    + Add Group
                                </button>
                            </div>
                        </div>

                        @if(count($optionGroups) === 0)
                            <div class="py-16 border-2 border-dashed border-slate-100 rounded-2xl text-center">
                                <p class="text-[13px] text-slate-400 font-medium">No option groups defined for this product.</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach($optionGroups as $idx => $group)
                                    <div class="border border-slate-100 rounded-2xl overflow-hidden shadow-sm" wire:key="opt-group-{{ $idx }}">
                                        <div class="flex items-center justify-between p-4 bg-slate-50/50 border-b border-slate-100">
                                                                                        <div class="flex items-center gap-3">
                                                <span class="text-[13px] font-bold text-slate-900 uppercase tracking-tight">{{ $group['name'] }}</span>
                                                <span class="px-2 py-0.5 bg-white border border-slate-200 rounded text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ $group['price_mode'] }}</span>
                                                @if($group['is_required'])
                                                    <span class="text-[9px] font-black text-rose-500 uppercase tracking-widest animate-pulse">Required</span>
                                                @endif
                                                @if($group['no_recipe_required'] ?? false)
                                                    <span class="text-[9px] font-black text-amber-600 bg-amber-50 border border-amber-100 px-1.5 py-0.5 rounded uppercase tracking-widest">No Recipe</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <label class="flex items-center gap-1.5 cursor-pointer mr-2" title="Options in this group skip ingredient tracking and are always available">
                                                    <input type="checkbox" wire:model.live="optionGroups.{{ $idx }}.no_recipe_required" class="w-3.5 h-3.5 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">No Recipe</span>
                                                </label>
                                                <button type="button" wire:click="syncGroupFromLibrary({{ $idx }})" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="Sync ingredients from Library">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                </button>
                                                <button type="button" wire:click="saveGroupToLibrary({{ $idx }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all" title="Save as Template">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                                                </button>
                                                <button type="button" wire:click="removeOptionGroup({{ $idx }})" class="text-rose-400 hover:text-rose-600 p-1.5 hover:bg-rose-50 rounded-lg transition-all">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="p-4 bg-white space-y-3">
                                            @foreach($group['options'] as $oIdx => $option)
                                                <div class="flex items-center gap-4" wire:key="opt-item-{{ $idx }}-{{ $oIdx }}">
                                                    <div class="flex-1">
                                                        <x-text-input wire:model.live="optionGroups.{{ $idx }}.options.{{ $oIdx }}.name" class="w-full h-10 text-[13px] font-bold" placeholder="Option name..." inputFilter="productName" />
                                                    </div>
                                                    <div class="w-28 relative">
                                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                                            <span class="text-[12px] font-bold">₱</span>
                                                        </div>
                                                        <x-text-input wire:model.live="optionGroups.{{ $idx }}.options.{{ $oIdx }}.price" class="w-full h-10 pl-7 text-[13px] font-black text-right" placeholder="0.00" inputFilter="price" />
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <label class="flex items-center gap-2 cursor-pointer">
                                                            <input type="radio" name="default_opt_{{ $idx }}" wire:click="setOptionAsDefault({{ $idx }}, {{ $oIdx }})" {{ $option['is_default'] ? 'checked' : '' }} class="w-3.5 h-3.5 text-indigo-600 border-slate-200">
                                                            <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Default</span>
                                                        </label>
                                                        <button type="button" wire:click="removeOptionFromGroup({{ $idx }}, {{ $oIdx }})" class="text-slate-300 hover:text-rose-500 transition-colors p-1.5">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                            <button type="button" wire:click="addOptionToGroup({{ $idx }})" class="w-full flex justify-center items-center h-10 border border-dashed border-slate-200 rounded-xl text-[11px] font-black uppercase tracking-widest text-slate-400 hover:text-indigo-600 hover:border-indigo-200 hover:bg-indigo-50/30 transition-all mt-2">
                                                + Add Option to "{{ $group['name'] }}"
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            {{-- Add Ingredient Form --}}
                            <div class="space-y-4">
                                <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                    Add Component
                                </h4>
                                <div class="p-5 bg-slate-50/50 border border-slate-100 rounded-2xl space-y-4">
                                    <div>
                                        <x-input-label value="Select Ingredient" />
                                                                                <div class="relative mt-1.5"
                                            x-data="{
                                                open: false,
                                                openUpward: false,
                                                top: 0, left: 0, width: 0,
                                                position() {
                                                    const trigger = this.$refs.ingredientTrigger;
                                                    const panel = this.$refs.ingredientPanel;
                                                    if (!trigger || !panel) return;

                                                    const r = trigger.getBoundingClientRect();
                                                    const gap = 6;
                                                    const panelHeight = panel.offsetHeight;
                                                    const spaceBelow = window.innerHeight - r.bottom;
                                                    const spaceAbove = r.top;

                                                    // Flip above the field only if there truly isn't room
                                                    // below AND there's more room above than below.
                                                    this.openUpward = spaceBelow < (panelHeight + gap) && spaceAbove > spaceBelow;

                                                    this.top = this.openUpward
                                                        ? (r.top + window.scrollY - panelHeight - gap)
                                                        : (r.bottom + window.scrollY + gap);

                                                    let left = r.left + window.scrollX;
                                                    const maxLeft = window.scrollX + window.innerWidth - r.width - 8;
                                                    this.left = Math.max(8, Math.min(left, maxLeft));
                                                    this.width = r.width;
                                                },
                                                async openDropdown() {
                                                    this.open = true;
                                                    // Render first (off-position), measure the real panel
                                                    // height, THEN place it — this is what lets us decide
                                                    // up vs down correctly instead of guessing a height.
                                                    await this.$nextTick();
                                                    this.position();
                                                    this.$refs.ingredientSearchInput?.focus();
                                                }
                                            }"
                                            @click.outside="open = false"
                                            @keydown.escape.window="open = false"
                                            @scroll.capture.window="open && position()"
                                            @resize.window="open && position()">

                                            <button type="button" x-ref="ingredientTrigger" @click="open ? (open = false) : openDropdown()"
                                                class="flex items-center justify-between w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none transition-all h-11">
                                                <span class="font-bold truncate">{{ $selectedIngredientName }}</span>
                                                <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                            </button>

                                            {{-- Teleported to <body>: this panel no longer lives inside any
                                                 overflow-y-auto ancestor, so opening it can never add a
                                                 scrollbar to the surrounding card/tab/modal, and it can
                                                 never be clipped by one either. --}}
                                            <template x-teleport="body">
                                                <div x-show="open" x-cloak x-ref="ingredientPanel"
                                                    x-transition:enter="transition ease-out duration-100"
                                                    :x-transition:enter-start="openUpward ? 'opacity-0 translate-y-1' : 'opacity-0 -translate-y-1'"
                                                    x-transition:enter-end="opacity-100 translate-y-0"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100"
                                                    x-transition:leave-end="opacity-0"
                                                    :style="`position:absolute; top:${top}px; left:${left}px; width:${width}px;`"
                                                    class="z-[9999] bg-white rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 p-2">
                                                    <div class="px-2 pb-2 mb-2 border-b border-slate-50">
                                                        <div class="relative">
                                                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                            <input x-ref="ingredientSearchInput" wire:model.live.debounce.300ms="ingredientSearch" type="text" placeholder="Search ingredients..."
                                                                   class="w-full pl-9 pr-4 py-2 bg-slate-50 border-none rounded-lg text-[12px] font-medium focus:ring-1 focus:ring-indigo-500 placeholder-slate-400">
                                                        </div>
                                                    </div>
                                                    {{-- Own max-height + own scroll — this scrolling is
                                                         local to the panel and never bubbles to the page. --}}
                                                    <div class="max-h-60 overflow-y-auto custom-scrollbar">
                                                        @forelse($allIngredients as $ing)
                                                            <x-dropdown-link href="#" wire:click.prevent="$set('newIngredientId', {{ $ing->id }})" @click="open = false">
                                                                <div class="flex items-center justify-between">
                                                                    <span class="font-medium text-slate-700">{{ $ing->name }}</span>
                                                                    <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">{{ $ing->unit }}</span>
                                                                </div>
                                                            </x-dropdown-link>
                                                        @empty
                                                            <div class="px-4 py-2 text-[12px] text-slate-400 italic">No ingredients found</div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <x-input-error :messages="$errors->get('newIngredientId')" class="mt-1" />
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label value="Quantity" />
                                            <div class="relative mt-1.5">
                                                <x-text-input wire:model.live="newIngredientQty" class="w-full h-11 pr-14 text-[13px] font-black" placeholder="0.00" inputFilter="price" />
                                                <span class="absolute inset-y-0 right-4 flex items-center text-[10px] font-black text-slate-400 uppercase">{{ $newIngredientUnit ?: '—' }}</span>
                                            </div>
                                            <x-input-error :messages="$errors->get('newIngredientQty')" class="mt-1" />
                                        </div>
                                        <div>
                                            <x-input-label value="Applied To" />
                                            <div class="mt-1.5">
                                                <x-dropdown align="left" width="full" containerClasses="block w-full">
                                                    <x-slot name="trigger">
                                                        <button type="button" class="flex items-center justify-between w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none transition-all h-11">
                                                            <span class="font-bold truncate">
                                                                @if($newIngredientOwner === 'base') Base Product
                                                                @elseif(str_starts_with($newIngredientOwner, 'option:'))
                                                                    @php
                                                                        $ref = str_replace('option:', '', $newIngredientOwner);
                                                                        $parts = explode('_', $ref);
                                                                        $gIdx = $parts[0] ?? null;
                                                                        $oIdx = $parts[1] ?? null;
                                                                        $optName = isset($optionGroups[$gIdx]['options'][$oIdx]) ? $optionGroups[$gIdx]['options'][$oIdx]['name'] : 'Unknown Option';
                                                                    @endphp
                                                                    {{ $optName }}
                                                                @else Context... @endif
                                                            </span>
                                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                                        </button>
                                                    </x-slot>
                                                    <x-slot name="content" class="max-h-64 overflow-y-auto">
                                                                                                        <x-dropdown-link href="#" wire:click.prevent="$set('newIngredientOwner', 'base')">Base Product</x-dropdown-link>
                                                        @foreach($optionGroups as $gIdx => $group)
                                                            @if(!($group['no_recipe_required'] ?? false))
                                                                <div class="px-4 py-1.5 text-[9px] font-black text-slate-400 uppercase tracking-widest bg-slate-50/50">{{ $group['name'] }}</div>
                                                                @foreach($group['options'] as $oIdx => $option)
                                                                    <x-dropdown-link href="#" wire:click.prevent="$set('newIngredientOwner', 'option:{{ $gIdx }}_{{$oIdx}}')">{{ $option['name'] }}</x-dropdown-link>
                                                                @endforeach
                                                            @endif
                                                        @endforeach
                                                    </x-slot>
                                                </x-dropdown>
                                            </div>
                                        </div>
                                    </div>
                                    <x-primary-button type="button" wire:click="addRecipeIngredient" class="w-full justify-center h-11 text-[12px] font-black uppercase tracking-widest">Add to Recipe</x-primary-button>
                                </div>
                            </div>

                            {{-- Recipe Overview --}}
                            <div class="space-y-4">
                                <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                    Recipe Overview
                                </h4>
                                @if(count($recipeIngredients) === 0)
                                    <div class="py-20 bg-white border border-slate-100 rounded-2xl text-center">
                                        <p class="text-[12px] text-slate-400 font-medium">No ingredients added yet.</p>
                                    </div>
                                @else
                                    <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                                        @foreach($recipeIngredients as $idx => $ri)
                                            <div class="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-2xl shadow-sm group">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-10 h-10 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center text-[12px] font-black text-slate-400 shadow-sm">
                                                        {{ strtoupper(substr($ri['name'] ?? '??', 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-[14px] font-bold text-slate-900 leading-none">{{ $ri['name'] }}</span>
                                                            <span class="text-[9px] font-black {{ $ri['owner'] === 'base' ? 'text-indigo-600 bg-indigo-50' : 'text-amber-600 bg-amber-50' }} px-2 py-0.5 rounded-full uppercase tracking-widest">{{ $this->getOwnerLabel($ri['owner']) }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-3 mt-1.5">
                                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Cost: <span class="text-rose-500">₱{{ number_format(($ri['cost'] ?? 0) * ($ri['quantity'] ?? 0), 2) }}</span></span>
                                                            <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Unit: <span class="text-slate-900">{{ $ri['quantity'] }} {{ strtoupper($ri['unit']) }}</span></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" wire:click="removeRecipeIngredient({{ $idx }})" class="text-slate-300 hover:text-rose-500 transition-all p-2 hover:bg-rose-50 rounded-lg">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
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

                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                <div class="p-5 bg-slate-50/50 rounded-2xl border border-slate-100 transition-all hover:bg-white hover:shadow-md group space-y-2">
                                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest block group-hover:text-slate-500 transition-colors">Base Recipe Cost</span>
                                    <span class="text-[22px] font-black text-slate-900 italic font-mono block leading-none">₱{{ number_format($recipeCost, 2) }}</span>
                                </div>
                                <div class="p-5 bg-slate-50/50 rounded-2xl border border-slate-100 transition-all hover:bg-white hover:shadow-md group space-y-2">
                                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest block group-hover:text-slate-500 transition-colors">Listing Price</span>
                                    <span class="text-[22px] font-black text-slate-900 italic font-mono block leading-none">₱{{ number_format($salePrice, 2) }}</span>
                                </div>
                                <div class="p-5 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-100 flex flex-col justify-center transition-all hover:scale-[1.02] space-y-2">
                                    <span class="text-[11px] font-black text-indigo-100 uppercase tracking-widest block">Net Profit</span>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-[24px] font-black text-white italic font-mono leading-none">₱{{ number_format($netProfit, 2) }}</span>
                                        <span class="text-[11px] font-black text-indigo-200 tracking-widest">{{ $profitMargin }}% MARGIN</span>
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
                            @elseif($existingImage)
                                <img src="{{ Storage::url($existingImage) }}" class="w-full h-full object-cover">
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
                @foreach($this->optionTemplates as $tmpl)
                    <div class="group relative bg-slate-50/50 border border-slate-100 rounded-2xl p-5 hover:bg-white hover:border-indigo-200 hover:shadow-md transition-all cursor-pointer" 
                         wire:click="importOptionTemplate({{ $tmpl->id }})"
                         wire:key="tmpl-card-{{ $tmpl->id }}">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h4 class="text-[14px] font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ $tmpl->name }}</h4>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[9px] font-black {{ $tmpl->is_required ? 'text-rose-500 bg-rose-50' : 'text-slate-400 bg-slate-100' }} px-1.5 py-0.5 rounded uppercase tracking-widest">{{ $tmpl->is_required ? 'Required' : 'Optional' }}</span>
                                    <span class="text-[9px] font-black text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded uppercase tracking-widest">{{ $tmpl->price_mode }}</span>
                                </div>
                            </div>
                            <div class="w-8 h-8 rounded-lg bg-white border border-slate-100 flex items-center justify-center text-slate-300 group-hover:text-indigo-600 group-hover:border-indigo-100 transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                            </div>
                        </div>
                        <div class="space-y-1.5 opacity-60">
                            @foreach($tmpl->items->take(3) as $tItem)
                                <div class="flex items-center justify-between text-[11px] font-medium text-slate-500">
                                    <span class="flex items-center gap-1.5">
                                        {{ $tItem->name }}
                                        @if($tItem->ingredients->count() > 0)
                                            <span class="inline-flex items-center gap-0.5 text-[9px] font-black text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full" title="{{ $tItem->ingredients->pluck('ingredient.name')->join(', ') }}">
                                                🧪 {{ $tItem->ingredients->count() }}
                                            </span>
                                        @endif
                                    </span>
                                    @if($tItem->price > 0)
                                        <span class="font-bold">+₱{{ number_format($tItem->price, 2) }}</span>
                                    @endif
                                </div>
                            @endforeach
                            @if($tmpl->items->count() > 3)
                                <div class="text-[10px] font-bold text-slate-400 italic mt-1">+ {{ $tmpl->items->count() - 3 }} more...</div>
                            @endif
                        </div>
                    </div>
                @endforeach
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
                                            @if($product->image)
                                                <img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover">
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
            <div x-show="tableView === 'board'"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @forelse($products as $product)
                        <div wire:key="prod-card-{{ $product->id }}" x-show="isItemVisible({{ $product->id }})" x-cloak class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm hover:shadow-md transition-all group/card relative {{ $this->isSuperAdmin() ? 'cursor-pointer' : '' }}" @if($this->isSuperAdmin()) wire:click="showEdit({{ $product->id }})" @endif>
                            <div class="aspect-[4/3] rounded-xl bg-slate-50 border border-slate-100 overflow-hidden mb-4 relative shadow-inner">
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover transition-transform duration-500 group-hover/card:scale-110">
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
                <x-text-input wire:model.live.debounce.400ms="newCategoryName" class="w-full mt-1.5 h-10" placeholder="e.g. Snacks, Beverages, Desserts" inputFilter="name" :hasError="$errors->has('newCategoryName')" />
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
                    <x-text-input wire:model.live="newGroupName" class="w-full mt-1.5 h-10" placeholder="e.g. Extras, Sizes, Flavors" inputFilter="productName" />
                    <x-input-error :messages="$errors->get('newGroupName')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Price Calculation Mode" />
                    <div class="mt-1.5">
                        <x-dropdown align="left" width="full" containerClasses="block w-full">
                            <x-slot name="trigger">
                                <button type="button" class="flex items-center justify-between w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none transition-all h-10">
                                    <span class="font-medium">{{ $newGroupPriceMode === 'additive' ? 'Additive (Price + Base Price)' : 'Fixed (Overrides Base Price)' }}</span>
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link href="#" wire:click.prevent="$set('newGroupPriceMode', 'additive')">Additive (Price + Base Price)</x-dropdown-link>
                                <x-dropdown-link href="#" wire:click.prevent="$set('newGroupPriceMode', 'fixed')">Fixed (Overrides Base Price)</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
                                <div class="flex items-center gap-3 p-3 bg-amber-50/50 rounded-xl border border-amber-100 transition-all hover:bg-amber-50">
                    <input type="checkbox" wire:model.live="newGroupNoRecipeRequired" id="no_recipe_group" class="w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                    <label for="no_recipe_group" class="text-[12px] font-medium text-slate-700 cursor-pointer">No Recipe Required (options always available, skip ingredient tracking)</label>
                </div>
                <div class="flex items-center gap-3 p-3 bg-slate-50/50 rounded-xl border border-slate-100 transition-all hover:bg-indigo-50/30">
                    <input type="checkbox" wire:model.live="newGroupIsRequired" id="is_req_group" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="is_req_group" class="text-[12px] font-medium text-slate-700 cursor-pointer">Mandatory Selection (Customer must choose)</label>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'add-option-group')" class="h-10">Cancel</x-secondary-button>
                <x-primary-button wire:click="addOptionGroup" class="h-10">Add Group</x-primary-button>
            </div>
        </div>
    </x-modal>

    <script>
        (function() {
            window.menuManagement = function($wire) {
                return {
                    panel: $wire.entangle('panel').live,
                    tableView: $wire.entangle('view').live,
                    mode: $wire.entangle('mode').live,
                    ...window.slidingTabs($wire.entangle('activeTab').live, 'activeTab'),
                    
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

