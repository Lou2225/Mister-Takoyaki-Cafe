@props(['model', 'ref' => null])

@php
    $refName = $ref ?? "{$model}List";
@endphp

<div {{ $attributes->merge(['class' => 'relative flex items-center gap-1 border-b border-gray-200']) }} x-ref="{{ $refName }}">
    <div 
        class="absolute bottom-0 left-0 h-0.5 bg-gray-900 transition-all duration-300 ease-out z-20"
        :style="typeof {{ $model }}Width !== 'undefined' && typeof {{ $model }}Left !== 'undefined'
            ? 'width: ' + {{ $model }}Width + 'px; transform: translateX(' + {{ $model }}Left + 'px); opacity: ' + ({{ $model }}Width > 0 ? 1 : 0)
            : 'width: 0px; transform: translateX(0px); opacity: 0'"
    ></div>
    {{ $slot }}
</div>
