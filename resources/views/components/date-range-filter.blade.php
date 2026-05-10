@props([
    'startModel' => 'startDate',
    'endModel' => 'endDate',
    'error' => null,
    'startValue' => null,
    'endValue' => null
])

<div {{ $attributes->merge(['class' => 'flex items-center bg-white border ' . ($error ? 'border-red-300' : 'border-gray-200') . ' rounded-xl px-3 h-10 shadow-sm gap-2']) }}>
    <svg class="w-3.5 h-3.5 {{ $error ? 'text-red-400' : 'text-gray-400' }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
    </svg>
    <input type="date" wire:model.live="{{ $startModel }}" 
        max="{{ date('Y-m-d') }}"
        onclick="this.showPicker()"
        class="border-none text-[11px] text-center font-bold {{ $error ? 'text-red-600' : 'text-gray-700' }} focus:ring-0 p-0 flex-1 min-w-0 bg-transparent outline-none [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:opacity-0 cursor-pointer" />
    <span class="{{ $error ? 'text-red-300' : 'text-gray-300' }}">|</span>
    <input type="date" wire:model.live="{{ $endModel }}" 
        @if($startValue) min="{{ $startValue }}" @endif
        max="{{ date('Y-m-d') }}"
        onclick="this.showPicker()"
        class="border-none text-[11px] text-center font-bold {{ $error ? 'text-red-600' : 'text-gray-700' }} focus:ring-0 p-0 flex-1 min-w-0 bg-transparent outline-none [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:opacity-0 cursor-pointer" />
</div>
