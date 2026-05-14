<div 
    x-data="window.menuManagement({ 
        panel: @entangle('panel').live, 
        tableView: @entangle('view').live, 
        mode: @entangle('mode').live, 
        activeTab: @entangle('activeTab').live
    })"
    x-on:switch-panel.window="panel = $event.detail.panel"
    class="relative"
    wire:ignore.self
    wire:key="menu-management-main-container">
    {{-- Panel: Form --}}
    <div x-show="panel === 'form'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight" x-text="mode === 'edit' ? 'Update Product' : 'Add New Product'"></h2>
                <p class="text-[12px] text-gray-500 font-medium whitespace-nowrap overflow-hidden text-ellipsis" x-text="mode === 'edit' ? 'Configure product details and pricing' : 'Add a new item to the menu'"></p>
            </div>
            <x-secondary-button wire:click="backToList" class="h-10">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Products
            </x-secondary-button>
        </div>

        <form @submit.prevent class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- Unified Form Tabs --}}
                <x-sliding-tabs model="activeTab" class="mb-2" ref="tabList">
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
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-b border-slate-50 pb-4 mb-2">
                            <div>
                                <h3 class="text-[14px] font-black text-slate-900 uppercase tracking-widest">Configuration Groups</h3>
                                <p class="text-[11px] text-slate-400 font-medium mt-1">Define mandatory or optional item variations</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="$dispatch('open-modal', 'import-template-library')" class="h-9 px-4 bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest rounded-lg hover:bg-slate-100 transition-all flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                    Import Template
                                </button>
                                <button type="button" @click="$dispatch('open-modal', 'add-option-group')" class="h-9 px-4 bg-indigo-50 text-indigo-600 text-[11px] font-black uppercase tracking-widest rounded-lg hover:bg-indigo-100 transition-all">+ Add Group</button>
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
                                            </div>
                                            <div class="flex items-center gap-1.5">
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
                                        <div class="mt-1.5">
                                            <x-dropdown align="left" width="full" containerClasses="block w-full">
                                                <x-slot name="trigger">
                                                    <button type="button" class="flex items-center justify-between w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none transition-all h-11">
                                                        <span class="font-bold truncate">{{ $selectedIngredientName }}</span>
                                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                                    </button>
                                                </x-slot>
                                                <x-slot name="content">
                                                    <div class="p-2">
                                                        <div class="px-2 pb-2 mb-2 border-b border-slate-50">
                                                            <div class="relative">
                                                                <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                                <input wire:model.live.debounce.300ms="ingredientSearch" type="text" placeholder="Search ingredients..." 
                                                                       class="w-full pl-9 pr-4 py-2 bg-slate-50 border-none rounded-lg text-[12px] font-medium focus:ring-1 focus:ring-indigo-500 placeholder-slate-400">
                                                            </div>
                                                        </div>
                                                        <div class="max-h-60 overflow-y-auto custom-scrollbar">
                                                            @forelse($allIngredients as $ing)
                                                                <x-dropdown-link href="#" wire:click.prevent="$set('newIngredientId', {{ $ing->id }})">
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
                                                </x-slot>
                                            </x-dropdown>
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
                                                            <div class="px-4 py-1.5 text-[9px] font-black text-slate-400 uppercase tracking-widest bg-slate-50/50">{{ $group['name'] }}</div>
                                                            @foreach($group['options'] as $oIdx => $option)
                                                                <x-dropdown-link href="#" wire:click.prevent="$set('newIngredientOwner', 'option:{{ $gIdx }}_{{$oIdx}}')">{{ $option['name'] }}</x-dropdown-link>
                                                            @endforeach
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
                                                            <span class="text-[9px] font-black {{ $ri['owner'] === 'base' ? 'text-indigo-600 bg-indigo-50' : 'text-amber-600 bg-amber-50' }} px-2 py-0.5 rounded-full uppercase tracking-widest">{{ $ri['owner'] === 'base' ? 'Base' : 'Option' }}</span>
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

                {{-- Summary & Actions --}}
                <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm">
                    <div class="space-y-3">
                        <x-primary-button type="button" wire:click="validateBeforeSave" class="w-full justify-center h-12 text-[12px] font-black uppercase tracking-widest shadow-lg shadow-indigo-100">
                            <span x-text="mode === 'edit' ? 'Update Catalog' : 'Register Product'"></span>
                        </x-primary-button>
                        <x-secondary-button wire:click="backToList" class="w-full justify-center h-11 text-[12px] font-black uppercase tracking-widest border-slate-200 text-slate-500">
                            Discard Draft
                        </x-secondary-button>
                    </div>

                    @if($editProductId && ($this->isSuperAdmin() || $this->isAdmin()))
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
        </form>
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
                                    <span>{{ $tItem->name }}</span>
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
    <div x-show="panel === 'list'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">
        
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Menu Management</h2>
                <p class="text-[12px] text-gray-500 font-medium">Managing <span class="text-indigo-600 font-bold">{{ $products->total() }} catalog assets</span></p>
            </div>
            <div class="flex items-center gap-3">
                @if($this->isSuperAdmin() || $this->isAdmin())
                    <x-primary-button wire:click="showCreate" class="h-10">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        Add Product
                    </x-primary-button>
                @endif
            </div>
        </div>

        {{-- ── Menu Health Overview ── --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
            {{-- Total Assets --}}
            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200 rounded-2xl p-3 sm:p-4 shadow-sm flex items-center gap-2 sm:gap-4 group hover:shadow-md transition-all">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-white border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <span class="block text-[8px] sm:text-[10px] font-black text-indigo-700/60 uppercase tracking-widest leading-none mb-1">Catalog</span>
                    <span class="block text-[16px] sm:text-[20px] font-black text-gray-900 leading-none">{{ number_format($totalProducts) }}</span>
                </div>
            </div>
            
            {{-- Active --}}
            <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 rounded-2xl p-3 sm:p-4 shadow-sm flex items-center gap-2 sm:gap-4 group hover:shadow-md transition-all">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <span class="block text-[8px] sm:text-[10px] font-black text-emerald-700/60 uppercase tracking-widest leading-none mb-1">Active</span>
                    <span class="block text-[16px] sm:text-[20px] font-black text-emerald-600 leading-none">{{ number_format($this->activeCount) }}</span>
                </div>
            </div>

            {{-- Hidden --}}
            <div class="bg-gradient-to-br from-rose-50 to-rose-100 border border-rose-200 rounded-2xl p-3 sm:p-4 shadow-sm flex items-center gap-2 sm:gap-4 group hover:shadow-md transition-all">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 14.12l4.242-4.242M3 3l18 18"/></svg>
                </div>
                <div>
                    <span class="block text-[8px] sm:text-[10px] font-black text-rose-700/60 uppercase tracking-widest leading-none mb-1">Hidden</span>
                    <span class="block text-[16px] sm:text-[20px] font-black text-rose-600 leading-none">{{ number_format($this->hiddenCount) }}</span>
                </div>
            </div>

            {{-- Categories --}}
            <div class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-2xl p-3 sm:p-4 shadow-sm flex items-center gap-2 sm:gap-4 group hover:shadow-md transition-all">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-white border border-amber-100 flex items-center justify-center text-amber-600 shadow-sm">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                </div>
                <div>
                    <span class="block text-[8px] sm:text-[10px] font-black text-amber-700/60 uppercase tracking-widest leading-none mb-1">Categories</span>
                    <span class="block text-[16px] sm:text-[20px] font-black text-gray-900 leading-none">{{ number_format($totalCategories) }}</span>
                </div>
            </div>
        </div>

        {{-- macOS Style Unified Toolbar --}}
        <div class="relative z-20 flex flex-col lg:flex-row lg:items-center justify-between mb-6 gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
            
            {{-- Left: Search & View Switcher --}}
            <div class="w-full lg:w-auto flex-1">
                <x-search-bar wireModel="search" placeholder="Find product..." width="w-full lg:w-80" />
            </div>

            {{-- Right: Filters --}}
            <div class="w-full lg:w-auto flex items-center gap-2">
                <div class="grid grid-cols-2 lg:flex items-center gap-2 flex-1">
                    <x-dropdown align="right" width="full">
                        <x-slot name="trigger">
                            <x-secondary-button type="button" class="w-full justify-between gap-1.5 h-10 !px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                                <div class="flex items-center gap-1.5 truncate">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 0 1 3 12V7a4 4 0 0 1 4-4z" /></svg>
                                    <span class="text-[12px] truncate">{{ $selectedFilterCategoryName }}</span>
                                </div>
                                <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
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

                    <x-dropdown align="right" width="full">
                        <x-slot name="trigger">
                            <x-secondary-button type="button" class="w-full justify-between gap-1.5 h-10 !px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                                <div class="flex items-center gap-1.5 truncate">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    <span class="text-[12px] truncate">{{ $isActive === '1' ? 'Active' : ($isActive === '0' ? 'Hidden' : 'All Status') }}</span>
                                </div>
                                <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                            </x-secondary-button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link href="#" wire:click.prevent="$set('isActive', '')">All Status</x-dropdown-link>
                            <hr class="border-slate-50">
                            <x-dropdown-link href="#" wire:click.prevent="$set('isActive', '1')">Active Only</x-dropdown-link>
                            <x-dropdown-link href="#" wire:click.prevent="$set('isActive', '0')">Hidden Only</x-dropdown-link>
                        </x-slot>
                    </x-dropdown>
                </div>

                {{-- macOS Divider --}}
                <div class="hidden lg:block w-px h-6 bg-slate-200 mx-1"></div>

                {{-- View Toggle --}}
                <button type="button" @click="tableView = (tableView === 'table' ? 'board' : 'table')"
                    class="w-9 h-9 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors focus:outline-none shrink-0"
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
                        <th class="py-3 px-6 text-right text-[11px] font-bold text-slate-400 uppercase tracking-widest">Actions</th>
                    </x-slot>

                    <tbody class="divide-y divide-slate-100/80" wire:loading.class="opacity-40" wire:target="search, selectedCategoryId, isActive, perPage">
                        @forelse($products as $product)
                            <tr wire:key="prod-row-{{ $product->id }}" class="hover:bg-slate-50/50 transition-colors group/row">
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
                                    <button type="button" wire:click.stop="toggleStatus({{ $product->id }})" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $product->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100' }} transition-all hover:brightness-95 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $product->is_active ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-rose-500' }}"></span>
                                        {{ $product->is_active ? 'Active' : 'Hidden' }}
                                    </button>
                                </td>
                                <td class="py-4 px-6 border-r border-slate-100/50 text-right whitespace-nowrap">
                                    <span class="text-[15px] font-black text-slate-900 italic font-mono tracking-tight">₱{{ number_format($product->price, 2) }}</span>
                                </td>
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        @if(!$this->isStaff())
                                            <x-secondary-button wire:click="showEdit({{ $product->id }})" class="h-8 px-3 text-[11px] font-bold bg-white hover:bg-slate-50 border-slate-200 shadow-none">
                                                Edit Product
                                            </x-secondary-button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12">
                                    <x-empty-state title="Empty Catalog" description="No products found in this scope." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-data-table>
                <div class="mt-4 px-1">
                    <x-pagination :paginator="$products" keyPrefix="menu-table" />
                </div>
            </div>

            {{-- Board View --}}
            <div x-show="tableView === 'board'"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
                    wire:loading.class="opacity-40 pointer-events-none"
                    wire:target="search, selectedCategoryId, isActive, perPage">
                    @forelse($products as $product)
                        <div wire:key="prod-card-{{ $product->id }}" class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm hover:shadow-md transition-all group/card relative cursor-pointer" wire:click="showEdit({{ $product->id }})">
                            <div class="aspect-[4/3] rounded-xl bg-slate-50 border border-slate-100 overflow-hidden mb-4 relative shadow-inner">
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover transition-transform duration-500 group-hover/card:scale-110">
                                @else
                                    <img src="{{ asset('images/placeholder-product.png') }}" class="w-full h-full object-cover transition-transform duration-500 group-hover/card:scale-110">
                                @endif
                                <div class="absolute top-2 right-2">
                                    <span class="px-2 py-1 bg-white/90 backdrop-blur-md border border-white/20 rounded-lg text-[11px] font-black text-slate-900 shadow-sm font-mono tracking-tighter italic">₱{{ number_format($product->price, 2) }}</span>
                                </div>
                                <div class="absolute bottom-2 left-2">
                                    <span class="px-2 py-0.5 {{ $product->is_active ? 'bg-emerald-500' : 'bg-rose-500' }} text-white rounded-md text-[9px] font-black uppercase tracking-widest shadow-sm">{{ $product->is_active ? 'Active' : 'Hidden' }}</span>
                                </div>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="text-[9px] font-black text-indigo-500/80 uppercase tracking-widest block mb-0.5 truncate">{{ $product->category->name ?? 'Uncategorized' }}</span>
                                    <h4 class="text-[13px] font-bold text-slate-900 truncate tracking-tight group-hover/card:text-indigo-600 transition-colors">{{ $product->name }}</h4>
                                </div>
                                <div class="shrink-0 flex items-center gap-1">
                                    <button type="button" wire:click.stop="showEdit({{ $product->id }})" class="p-2 text-slate-300 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all" title="Edit Product">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-24 text-center bg-white rounded-2xl border border-slate-100 shadow-sm">
                            <x-empty-state title="Catalog is empty" description="Try changing your filters or adding a new item." />
                        </div>
                    @endforelse
                </div>
                <div class="mt-6">
                    <x-pagination :paginator="$products" keyPrefix="menu-board" />
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
</div>

@push('scripts')
<script>
    (function() {
        window.menuManagement = function(initials) {
            return {
                panel: initials.panel,
                tableView: initials.tableView,
                mode: initials.mode,
                ...window.slidingTabs(initials.activeTab, 'activeTab'),
                
                init() {
                    const base = window.slidingTabs(initials.activeTab, 'activeTab');
                    if (base.init) base.init.call(this);
                }
            };
        };
    })();
</script>
@endpush
