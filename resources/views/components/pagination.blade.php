@props(['paginator', 'keyPrefix' => 'pag', 'perPageOptions' => [5, 10, 15, 30, 50, 100]])

@if ($paginator->hasPages() || $paginator->total() > 0)
    <div class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-100 sm:px-6">
        <div class="flex items-center gap-6">
            {{-- Rows per page --}}
            <div class="flex items-center gap-2">
                <x-input-label for="perPage" value="Rows per page" class="text-[12px] text-gray-500 font-medium whitespace-nowrap" />
                <div wire:key="{{ $keyPrefix }}-per-page-{{ $paginator->getPageName() }}">
                    <x-dropdown align="top" width="20" containerClasses="block">
                        <x-slot name="trigger">
                            <button type="button" class="inline-flex items-center justify-between w-full px-3 py-1.5 text-[13px] font-bold text-gray-700 bg-white border border-gray-200 rounded-xl hover:border-gray-300 focus:outline-none transition-colors h-9 gap-2 shadow-sm">
                                <span>{{ $paginator->perPage() }}</span>
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            @foreach ($perPageOptions as $option)
                                <x-dropdown-link href="#" wire:click.prevent="$set('perPage', {{ $option }})" wire:loading.attr="disabled">
                                    {{ $option }}
                                </x-dropdown-link>
                            @endforeach
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            {{-- Info text --}}
            <div class="text-[12px] text-gray-400 font-bold uppercase tracking-widest">
                <span class="text-gray-900">{{ $paginator->firstItem() ?? 0 }}</span>
                <span class="mx-0.5">-</span>
                <span class="text-gray-900">{{ $paginator->lastItem() ?? 0 }}</span>
                <span class="mx-1 text-gray-300 font-medium lowercase italic">of</span>
                <span class="text-indigo-600 font-black">{{ $paginator->total() }}</span>
                <span class="ml-1 text-gray-300 font-medium lowercase italic">results</span>
            </div>
        </div>

        {{-- Navigation buttons --}}
        <div class="flex items-center gap-1.5">
            {{-- First Page --}}
            <x-secondary-button 
                wire:key="{{ $keyPrefix }}-{{ $paginator->getPageName() }}-first-{{ $paginator->onFirstPage() ? 'disabled' : 'active' }}"
                wire:click="gotoPage(1, '{{ $paginator->getPageName() }}')" 
                wire:loading.attr="disabled"
                :disabled="$paginator->onFirstPage()" 
                class="!p-0 w-9 h-9 items-center justify-center !rounded-xl {{ $paginator->onFirstPage() ? 'opacity-30' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
            </x-secondary-button>
 
            {{-- Previous Page --}}
            <x-secondary-button 
                wire:key="{{ $keyPrefix }}-{{ $paginator->getPageName() }}-prev-{{ $paginator->onFirstPage() ? 'disabled' : 'active' }}"
                wire:click="previousPage('{{ $paginator->getPageName() }}')" 
                wire:loading.attr="disabled"
                :disabled="$paginator->onFirstPage()" 
                class="!p-0 w-9 h-9 items-center justify-center !rounded-xl {{ $paginator->onFirstPage() ? 'opacity-30' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </x-secondary-button>
 
            {{-- Page Numbers --}}
            <div class="flex items-center gap-1.5 px-2">
                @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 1), min($paginator->lastPage(), $paginator->currentPage() + 1)) as $page => $url)
                    @if($page == $paginator->currentPage())
                        <x-primary-button 
                            wire:key="{{ $keyPrefix }}-{{ $paginator->getPageName() }}-page-{{ $page }}-active"
                            class="!p-0 w-9 h-9 items-center justify-center !rounded-xl bg-gray-900 text-[13px] font-black shadow-none ring-0">
                            {{ $page }}
                        </x-primary-button>
                    @else
                        <x-secondary-button 
                            wire:key="{{ $keyPrefix }}-{{ $paginator->getPageName() }}-page-{{ $page }}-inactive"
                            wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" 
                            wire:loading.attr="disabled"
                            class="!p-0 w-9 h-9 items-center justify-center !rounded-xl text-[13px] font-bold">
                            {{ $page }}
                        </x-secondary-button>
                    @endif
                @endforeach
                
                @if ($paginator->lastPage() > $paginator->currentPage() + 1)
                    <span class="text-gray-300 font-bold mx-1">...</span>
                    <x-secondary-button 
                        wire:key="{{ $keyPrefix }}-{{ $paginator->getPageName() }}-page-last"
                        wire:click="gotoPage({{ $paginator->lastPage() }}, '{{ $paginator->getPageName() }}')" 
                        wire:loading.attr="disabled"
                        class="!p-0 w-9 h-9 items-center justify-center !rounded-xl text-[13px] font-bold">
                        {{ $paginator->lastPage() }}
                    </x-secondary-button>
                @endif
            </div>
 
            {{-- Next Page --}}
            <x-secondary-button 
                wire:key="{{ $keyPrefix }}-{{ $paginator->getPageName() }}-next-{{ !$paginator->hasMorePages() ? 'disabled' : 'active' }}"
                wire:click="nextPage('{{ $paginator->getPageName() }}')" 
                wire:loading.attr="disabled"
                :disabled="!$paginator->hasMorePages()" 
                class="!p-0 w-9 h-9 items-center justify-center !rounded-xl {{ !$paginator->hasMorePages() ? 'opacity-30' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </x-secondary-button>
 
            {{-- Last Page --}}
            <x-secondary-button 
                wire:key="{{ $keyPrefix }}-{{ $paginator->getPageName() }}-last-{{ !$paginator->hasMorePages() ? 'disabled' : 'active' }}"
                wire:click="gotoPage({{ $paginator->lastPage() }}, '{{ $paginator->getPageName() }}')" 
                wire:loading.attr="disabled"
                :disabled="!$paginator->hasMorePages()" 
                class="!p-0 w-9 h-9 items-center justify-center !rounded-xl {{ !$paginator->hasMorePages() ? 'opacity-30' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                </svg>
            </x-secondary-button>
        </div>
    </div>
@endif