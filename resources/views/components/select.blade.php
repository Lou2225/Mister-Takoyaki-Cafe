@props([
    'disabled' => false,
    'hasError' => false,
])

@php
    $baseClass  = 'border-gray-200 focus:border-gray-300 focus:ring-1 focus:ring-gray-300 rounded-lg shadow-sm text-[13px] text-gray-800 transition-all h-10 w-full pr-10 pl-3 bg-white focus:outline-none cursor-pointer';
    $errorClass = $hasError ? 'border-red-400 focus:border-red-400 focus:ring-red-300 bg-red-50/30' : '';
@endphp

<div class="relative">
    <select
        {{ $disabled ? 'disabled' : '' }}
        style="-webkit-appearance:none;-moz-appearance:none;appearance:none;"
        {!! $attributes->merge(['class' => trim($baseClass . ' ' . $errorClass)]) !!}
    >
        {{ $slot }}
    </select>
    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>
</div>
