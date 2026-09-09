<div class="hidden md:flex items-center gap-2.5 px-3 py-1.5 bg-gray-50 border border-gray-100 rounded-xl transition-all shadow-sm ml-2">
    <svg class="w-4 h-4 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
    </svg>
    <span class="text-[12px] font-bold text-gray-500 uppercase tracking-tight truncate max-w-[200px] lg:max-w-none">
        {{ $this->context['label'] }}: <span class="text-gray-900 font-black tracking-normal">{{ $this->context['value'] }}</span>
    </span>
</div>