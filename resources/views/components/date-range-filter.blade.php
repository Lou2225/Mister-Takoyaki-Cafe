@props([
    'startModel' => 'startDate',
    'endModel' => 'endDate',
    'error' => null,
    'startValue' => null,
    'endValue' => null
])

<div {{ $attributes->merge(['class' => 'flex flex-col lg:flex-row lg:items-center bg-white border ' . ($error ? 'border-red-300' : 'border-gray-200') . ' rounded-xl px-3 sm:px-4 lg:px-3 py-2 lg:py-0 lg:h-10 shadow-sm gap-1 lg:gap-2']) }}>
    <input type="date" wire:model.live="{{ $startModel }}" 
        max="{{ date('Y-m-d') }}"
        placeholder="Start Date"
        class="border-none text-[11px] lg:text-[12px] text-center font-bold {{ $error ? 'text-red-600' : 'text-gray-700' }} focus:ring-0 p-1 lg:p-0 flex-1 bg-transparent outline-none cursor-pointer" />
    <span class="{{ $error ? 'text-red-300' : 'text-gray-200' }} hidden lg:block px-1">|</span>
    <input type="date" wire:model.live="{{ $endModel }}" 
        @if($startValue) min="{{ $startValue }}" @endif
        max="{{ date('Y-m-d') }}"
        placeholder="End Date"
        class="border-none text-[11px] lg:text-[12px] text-center font-bold {{ $error ? 'text-red-600' : 'text-gray-700' }} focus:ring-0 p-1 lg:p-0 flex-1 bg-transparent outline-none cursor-pointer" />
</div>
