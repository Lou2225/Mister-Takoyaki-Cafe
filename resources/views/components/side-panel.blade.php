@props([
    'name',
    'show' => false,
    'width' => 'max-w-md'
])

<div
    x-data="{ 
        @if($attributes->has('wire:model.live'))
            show: @entangle($attributes->wire('model')).live,
        @else
            show: @js($show),
        @endif
        close() { this.show = false }
    }"
    x-init="$watch('show', value => {
        if (value) {
            document.body.classList.add('overflow-y-hidden');
        } else {
            document.body.classList.remove('overflow-y-hidden');
        }
    })"
    x-on:open-modal.window="(() => {
        const target = $event.detail?.name || $event.detail?.[0]?.name || $event.detail;
        if (target == '{{ $name }}') show = true;
    })()"
    x-on:close-modal.window="(() => {
        const target = $event.detail?.name || $event.detail?.[0]?.name || $event.detail;
        if (target == '{{ $name }}') show = false;
    })()"
    x-on:keydown.escape.window="close()"
    x-show="show"
    class="fixed inset-0 z-[100] overflow-hidden"
    style="display: none;"
>
    <div class="absolute inset-0 overflow-hidden">
        {{-- Overlay --}}
        <div 
            x-show="show"
            x-transition:enter="ease-in-out duration-500"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in-out duration-500"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="close()"
            class="absolute inset-0 bg-gray-900/20 backdrop-blur-md transition-opacity"
        ></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full">
            <div 
                x-show="show"
                x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="pointer-events-auto w-screen {{ $width }}"
            >
                <div class="flex h-full flex-col bg-white shadow-2xl border-l border-slate-200 overflow-hidden">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
