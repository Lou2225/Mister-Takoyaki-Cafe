@props([
    'content' => '',
    'text' => '',
    'title' => null,
    'position' => 'top', // 'top' or 'bottom'
    'align' => 'center', // 'center', 'start', 'end'
    'width' => 'w-64',
    'className' => '',
])

@php
    $tooltipContent = $content ?: $text;
@endphp

@if($tooltipContent || $title)
<span class="relative group/tooltip inline-flex items-center overflow-visible align-middle {{ $className }}" x-on:click.stop>
    {{-- Trigger --}}
    @if($slot->isNotEmpty())
        {{ $slot }}
    @else
        <button
            type="button"
            tabindex="-1"
            aria-label="Information"
            class="inline-flex items-center justify-center p-0 rounded-full outline-none cursor-help text-slate-400 hover:text-amber-500 transition-colors shrink-0 -translate-y-0.5"
        >
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke-width="2" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01" />
            </svg>
        </button>
    @endif

    {{-- Cloud Container Bubble --}}
    <span
        style="width: 260px; min-width: 250px; max-width: calc(100vw - 32px); white-space: normal; background-color: #0f172a; color: #f8fafc; border: 1px solid rgba(51, 65, 85, 0.7); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.4); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);"
        class="tooltip-bubble absolute {{ $position === 'bottom' ? 'top-full mt-2' : 'bottom-full mb-2' }} {{ $align === 'start' ? 'left-0' : ($align === 'end' ? 'right-0' : 'left-1/2 -translate-x-1/2') }} hidden group-hover/tooltip:flex flex-col z-[100] p-3 rounded-xl text-left pointer-events-none transition-all duration-150 animate-in fade-in zoom-in-95"
    >
        @if($title)
            <span class="text-[11px] font-bold text-white flex items-center gap-1.5 mb-1 shrink-0">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                <span>{{ $title }}</span>
            </span>
        @endif

        <span class="text-[11px] text-slate-300 leading-relaxed block font-normal break-words">
            {{ $tooltipContent }}
        </span>

        {{-- Arrow / Notch --}}
        @if($position === 'bottom')
            <span
                style="background-color: #0f172a; border-left: 1px solid rgba(51, 65, 85, 0.7); border-top: 1px solid rgba(51, 65, 85, 0.7);"
                class="absolute -top-1 {{ $align === 'start' ? 'left-3' : ($align === 'end' ? 'right-3' : 'left-1/2 -translate-x-1/2') }} w-2 h-2 rotate-45 pointer-events-none"
            ></span>
        @else
            <span
                style="background-color: #0f172a; border-right: 1px solid rgba(51, 65, 85, 0.7); border-bottom: 1px solid rgba(51, 65, 85, 0.7);"
                class="absolute top-full -mt-1 {{ $align === 'start' ? 'left-3' : ($align === 'end' ? 'right-3' : 'left-1/2 -translate-x-1/2') }} w-2 h-2 rotate-45 pointer-events-none"
            ></span>
        @endif
    </span>
</span>

<style>
.group\/tooltip:hover > .tooltip-bubble,
.group\/tooltip:focus-within > .tooltip-bubble {
    display: flex !important;
}
</style>
@else
    {{ $slot }}
@endif
