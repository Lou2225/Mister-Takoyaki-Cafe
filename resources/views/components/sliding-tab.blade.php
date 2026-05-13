@props(['value', 'model', 'icon' => null])

<button type="button" data-tab="{{ $value }}" @click="{{ $model }} = '{{ $value }}'"
    class="relative z-10 flex items-center gap-2 px-4 py-2 text-[13px] font-bold transition-all duration-200"
    :class="{{ $model }} === '{{ $value }}' ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700'"
    {{ $attributes->except(['value', 'model', 'icon']) }}>
    @if($icon)
        {{ $icon }}
    @endif
    {{ $slot }}
</button>
