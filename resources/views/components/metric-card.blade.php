@props([
    'title',
    'value',
    'icon' => null,
    'color' => 'indigo',
    'gradient' => null
])

@php
    $gradients = [
        'amber'   => 'from-amber-50 to-amber-100 border-amber-200 text-amber-600',
        'blue'    => 'from-blue-50 to-blue-100 border-blue-200 text-blue-600',
        'cyan'    => 'from-cyan-50 to-cyan-100 border-cyan-200 text-cyan-600',
        'emerald' => 'from-emerald-50 to-emerald-100 border-emerald-200 text-emerald-600',
        'indigo'  => 'from-indigo-50 to-indigo-100 border-indigo-200 text-indigo-600',
        'rose'    => 'from-rose-50 to-rose-100 border-rose-200 text-rose-600',
        'slate'   => 'from-slate-50 to-slate-100 border-slate-200 text-slate-600',
    ];

    $style = $gradients[$color] ?? $gradients['indigo'];
@endphp

<div {{ $attributes->merge(['class' => "bg-gradient-to-br $style border rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all"]) }}>
    @if($icon)
        <div class="w-10 h-10 rounded-xl bg-white border border-opacity-50 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
            {{ $icon }}
        </div>
    @endif
    <div>
        <span class="block text-[10px] font-black opacity-60 uppercase tracking-widest leading-none mb-1">{{ $title }}</span>
        <span class="block text-[20px] font-black text-gray-900 leading-none">{{ $value }}</span>
    </div>
</div>
