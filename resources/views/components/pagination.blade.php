@props(['paginator', 'keyPrefix' => 'pag', 'perPageOptions' => [5, 10, 15, 30, 50, 100]])

@if ($paginator->hasPages() || $paginator->total() > 0)
    <div class="flex flex-col lg:flex-row items-center justify-between px-4 py-4 bg-white border-t border-gray-100 lg:px-6 gap-6">
        <div class="flex flex-col sm:flex-row items-center justify-between w-full lg:w-auto gap-4 sm:gap-8">
            {{-- Rows per page & Info text --}}
            <div class="flex items-center justify-between sm:justify-start w-full sm:w-auto gap-4 sm:gap-6">
                <div class="flex items-center gap-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:gap-1.5 leading-[0.9] sm:leading-none">
                        <span class="text-[11px] sm:text-[12px] text-gray-400 font-black uppercase tracking-tighter sm:tracking-widest">Row</span>
                        <span class="text-[9px] sm:text-[12px] text-gray-400/70 font-black uppercase tracking-tighter sm:tracking-widest">per page</span>
                    </div>
                    <div wire:key="{{ $keyPrefix }}-per-page-{{ $paginator->getPageName() }}">
                        <x-dropdown align="top" width="20" containerClasses="block">
                            <x-slot name="trigger">
                                <button type="button" class="inline-flex items-center justify-between min-w-[70px] px-3 py-1.5 text-[13px] font-black text-gray-900 bg-slate-50 border border-gray-200 rounded-xl hover:border-gray-300 focus:outline-none transition-all h-10 gap-2 shadow-sm">
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
                <div class="text-[11px] text-gray-400 font-bold uppercase tracking-widest whitespace-nowrap">
                    <span class="text-gray-900">{{ $paginator->firstItem() ?? 0 }}</span>
                    <span class="mx-0.5 text-gray-300">-</span>
                    <span class="text-gray-900">{{ $paginator->lastItem() ?? 0 }}</span>
                    <span class="mx-1 text-gray-300 lowercase italic font-medium">of</span>
                    <span class="text-indigo-600">{{ $paginator->total() }}</span>
                    <span class="ml-1 text-gray-300 lowercase italic font-medium">results</span>
                </div>
            </div>
        </div>

        {{-- Navigation buttons (Page Selection) --}}
        <div class="flex items-center gap-2 w-full lg:w-auto justify-center lg:justify-end border-t border-gray-50 pt-4 lg:border-0 lg:pt-0">
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