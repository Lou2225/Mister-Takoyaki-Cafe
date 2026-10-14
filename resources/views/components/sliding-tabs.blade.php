@props(['model', 'ref' => null, 'border' => true])

@php
    $refName = $ref ?? "{$model}List";
    $showBorder = filter_var($border, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $hasBorder = $showBorder !== null ? $showBorder : (bool)$border;
@endphp

<div class="relative w-full overflow-x-auto overflow-y-hidden scrollbar-none {{ $hasBorder ? 'border-b border-gray-200' : '' }}" style="-webkit-overflow-scrolling: touch;">
    <div {{ $attributes->merge(['class' => 'relative flex items-center gap-1 flex-nowrap min-w-max pb-0.5']) }} x-ref="{{ $refName }}">
        <div 
            class="absolute bottom-0 left-0 h-0.5 bg-gray-900 transition-all duration-300 ease-out z-20 pointer-events-none"
            :style="typeof {{ $model }}Width !== 'undefined' && typeof {{ $model }}Left !== 'undefined'
                ? 'width: ' + {{ $model }}Width + 'px; transform: translateX(' + {{ $model }}Left + 'px); opacity: ' + ({{ $model }}Width > 0 ? 1 : 0)
                : 'width: 0px; transform: translateX(0px); opacity: 0'"
        ></div>
        {{ $slot }}
    </div>
</div>
