@props([
    'wireModel' => null,
    'placeholder' => 'Search...',
    'width' => 'w-48 focus:w-64',
    'id' => null
])

@php
    $theme = auth()->user()?->getRoleTheme() ?? ['primary' => 'indigo'];
    $primaryColor = $theme['primary'];
    $wireModelName = $wireModel ?? (($attributes->has('x-model') || $attributes->has('wire:model.live')) ? null : 'search');
    $searchId = $id ?? 'search_' . ($wireModelName ? str_replace(['.', '$'], '_', $wireModelName) : ($attributes->get('x-model') ?? 'input'));
@endphp

<div class="relative group" wire:key="container_{{ $searchId }}">
    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 group-focus-within:text-{{ $primaryColor }}-500 transition-colors duration-300">
        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
        </svg>
    </div>
    <label for="{{ $searchId }}" class="sr-only">{{ $placeholder }}</label>
    <input id="{{ $searchId }}" type="text" placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'wire:model.live' => $wireModelName,
            'wire:key' => 'input_' . $searchId
        ])->class([
            "pl-10 pr-4 py-2 $width text-[13px] font-medium text-slate-900 bg-white border border-slate-200/60 rounded-xl transition-all duration-300 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-{$primaryColor}-500/20 focus:border-{$primaryColor}-500 h-[42px]"
        ]) }}
    >
</div>
