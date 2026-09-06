@props(['module' => 'Report'])

<button wire:click="exportPdf"
    class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 h-10 bg-white border border-gray-200 rounded-xl shadow-sm text-[12px] font-bold text-gray-700 hover:bg-gray-50 transition-all whitespace-nowrap">
    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    <span class="hidden sm:inline">Generate {{ $module }}</span>
</button>