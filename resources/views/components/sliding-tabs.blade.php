@props(['model', 'ref' => 'tabList'])

<div {{ $attributes->merge(['class' => 'relative flex items-center gap-1 border-b border-gray-200']) }} x-ref="{{ $ref }}">
    <div 
        class="absolute bottom-0 left-0 h-0.5 bg-gray-900 transition-all duration-300 ease-out z-20"
        :style="'width: ' + {{ $model }}Width + 'px; transform: translateX(' + {{ $model }}Left + 'px); opacity: ' + ({{ $model }}Width > 0 ? 1 : 0)"
    ></div>
    {{ $slot }}
</div>
