@props(['model', 'value'])

@php
    $theme = auth()->user()?->getRoleTheme() ?? ['primary' => 'indigo'];
    $primaryColor = $theme['primary'];
@endphp

<button type="button" 
    @click="{{ $model }} = '{{ $value }}'"
    :class="{{ $model }} == '{{ $value }}' ? 'bg-white text-{{ $primaryColor }}-600 shadow-[0_2px_8px_-2px_rgba(0,0,0,0.08)] border-slate-200/60' : 'text-slate-500 hover:bg-slate-50/50 hover:text-slate-700 border-transparent'"
    class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-[11px] font-bold uppercase tracking-wider border transition-all duration-300 focus:outline-none h-8 select-none">
    {{ $slot }}
</button>
