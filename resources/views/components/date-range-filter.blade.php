@props([
    'startModel' => 'startDate',
    'endModel' => 'endDate',
    'error' => null,
    'startValue' => null,
    'endValue' => null
])

<div {{ $attributes->merge(['class' => 'flex items-center bg-white border ' . ($error ? 'border-red-300' : 'border-gray-200') . ' rounded-xl px-3 sm:px-4 h-10 shadow-sm gap-2 sm:gap-3']) }}>
    <input type="date" wire:model.live="{{ $startModel }}" 
        max="{{ date('Y-m-d') }}"
        placeholder="Start Date"
        class="border-none text-[12px] sm:text-[13px] text-center font-bold {{ $error ? 'text-red-600' : 'text-gray-700' }} focus:ring-0 p-0 w-full flex-1 bg-transparent outline-none cursor-pointer" />
    <span class="{{ $error ? 'text-red-300' : 'text-gray-200' }} px-1">|</span>
    <input type="date" wire:model.live="{{ $endModel }}" 
        @if($startValue) min="{{ $startValue }}" @endif
        max="{{ date('Y-m-d') }}"
        placeholder="End Date"
        class="border-none text-[12px] sm:text-[13px] text-center font-bold {{ $error ? 'text-red-600' : 'text-gray-700' }} focus:ring-0 p-0 w-full flex-1 bg-transparent outline-none cursor-pointer" />
</div>
