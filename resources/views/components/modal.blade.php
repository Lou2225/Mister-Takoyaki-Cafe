@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl'
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
    '5xl' => 'sm:max-w-5xl',
    '6xl' => 'sm:max-w-6xl',
    '7xl' => 'sm:max-w-7xl',
    'full' => 'sm:max-w-full',
][$maxWidth ?? '2xl'];
@endphp
 
<div
    wire:ignore.self
    x-data="modal({ name: '{{ $name }}', show: @js($show) })"
    x-on:open-modal.window="open($event.detail)"
    x-on:close-modal.window="close($event.detail)"
    x-on:close.stop="typeof isModalOpen !== 'undefined' && (typeof cartLocked === 'undefined' || !cartLocked) && (isModalOpen = false)"
    x-on:keydown.escape.window="typeof isModalOpen !== 'undefined' && (typeof cartLocked === 'undefined' || !cartLocked) && (isModalOpen = false)"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable()?.focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable()?.focus()"
    x-show="isModalOpen"
    x-cloak
    class="fixed inset-0 z-[9999] overflow-y-auto px-4 py-6 sm:px-0"
    style="display: none;"
>
    <div
    x-show="isModalOpen"
    class="fixed inset-0 transform transition-all"
    x-on:click.self="(typeof cartLocked !== 'undefined' && cartLocked) ? $dispatch('notify', { type: 'warning', message: 'This payment is already verified — you must Place the Order to complete it.' }) : (isModalOpen = false)"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div class="absolute inset-0 bg-gray-900/40 pointer-events-none" style="backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);"></div>
</div>

    <div
        x-show="isModalOpen"
        class="mb-6 bg-white rounded-xl overflow-hidden shadow-2xl transform transition-all sm:w-full {{ $maxWidth }} sm:mx-auto relative z-10"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        {{ $slot }}
    </div>
</div>

