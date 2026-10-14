@props(['value', 'model', 'icon' => null])

<button type="button" data-tab="{{ $value }}" @click="typeof {{ $model }} !== 'undefined' && ({{ $model }} = '{{ $value }}')"
    class="relative z-10 flex items-center gap-2 px-3.5 sm:px-4 py-2 text-[12.5px] sm:text-[13px] font-bold whitespace-nowrap shrink-0 transition-all duration-200"
    :class="typeof {{ $model }} !== 'undefined' && {{ $model }} === '{{ $value }}' ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700'"
    {{ $attributes->except(['value', 'model', 'icon']) }}>
    @if($icon)
        {{ $icon }}
    @endif
    {{ $slot }}
</button>
