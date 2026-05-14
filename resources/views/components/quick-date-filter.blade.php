@props(['activeFilter' => 'All Time', 'class' => ''])

<x-dropdown align="left" width="48" containerClasses="w-full">
    <x-slot name="trigger">
        <x-secondary-button type="button" class="w-full justify-between h-10 gap-2 {{ $class }}">
            <div class="flex items-center gap-2 truncate">
                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="truncate">{{ $activeFilter }}</span>
            </div>
            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
        </x-secondary-button>
    </x-slot>
    <x-slot name="content">
        <x-dropdown-link href="#" wire:click.prevent="applyQuickDateFilter('today')">Today Only</x-dropdown-link>
        <x-dropdown-link href="#" wire:click.prevent="applyQuickDateFilter('week')">Last 7 Days</x-dropdown-link>
        <x-dropdown-link href="#" wire:click.prevent="applyQuickDateFilter('month')">Last 30 Days</x-dropdown-link>
        <div class="border-t border-gray-100 my-1"></div>
        <x-dropdown-link href="#" wire:click.prevent="applyQuickDateFilter('all')">All Time</x-dropdown-link>
    </x-slot>
</x-dropdown>
