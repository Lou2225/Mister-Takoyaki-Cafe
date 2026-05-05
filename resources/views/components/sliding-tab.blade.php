@props(['value', 'model', 'icon' => null])

<button type="button" data-tab="{{ $value }}" @click="{{ $model }} = '{{ $value }}'"
    class="relative z-10 flex items-center gap-2 px-4 py-2.5 text-[12px] font-bold transition-all -mb-px rounded-t-lg"
    :class="{{ $model }} === '{{ $value }}' ? 'text-gray-900 bg-gray-50/50' : 'text-gray-900 hover:text-gray-700 hover:bg-gray-50/20'">
    @if($icon)
        {!! $icon !!}
    @endif
    {{ $slot }}
</button>
