@props([
    'name',
    'initialShow' => false,
    'width' => 'max-w-md'
])

<div
    wire:ignore.self
    x-data="sidePanel({ name: '{{ $name }}', show: @js($initialShow) })"
    x-on:open-modal.window="open($event.detail)"
    x-on:close-modal.window="close($event.detail)"
    x-on:keydown.escape.window="closePanel()"
    x-show="isPanelOpen"
    x-cloak
    class="fixed inset-0 z-[100] overflow-hidden"
>
    {{-- Outer wrapper --}}
    <div class="absolute inset-0 overflow-hidden">

        {{-- Backdrop overlay --}}
        <div
            x-show="isPanelOpen"
            x-transition:enter="ease-in-out duration-500"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in-out duration-500"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closePanel()"
            class="absolute inset-0 bg-gray-900/20 backdrop-blur-md transition-opacity"
        ></div>

        {{-- Panel container --}}
        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full">
            <div
                wire:ignore.self
                x-show="isPanelOpen"
                x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="pointer-events-auto w-screen {{ $width }}"
            >
                {{-- Panel body --}}
                <div class="flex h-full flex-col bg-white shadow-2xl border-l border-slate-200 overflow-hidden">
                    {{ $slot }}
                </div>
            </div>
        </div>

    </div>
</div>
