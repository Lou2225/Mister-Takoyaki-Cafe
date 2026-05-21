@props(['module' => 'Report'])

<div x-data="{ open: false }" wire:ignore.self class="relative inline-block text-left">
    <button @click="open = !open" @click.outside="open = false" 
        class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 h-10 bg-white border border-gray-200 rounded-xl shadow-sm text-[12px] font-bold text-gray-700 hover:bg-gray-50 transition-all whitespace-nowrap">
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span class="hidden sm:inline">Generate {{ $module }}</span>
        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 hidden sm:block" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-48 bg-white border border-gray-100 rounded-xl shadow-[0_10px_30px_rgba(0,0,0,0.1)] z-50 overflow-hidden" x-cloak>
        <div class="py-1">
            <button wire:click="exportCsv" @click="open = false" class="flex items-center gap-3 w-full px-4 py-2.5 text-[12px] font-bold text-gray-700 hover:bg-gray-50 transition-colors">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <span class="text-emerald-600 text-[10px]">CSV</span>
                </div>
                <span>Export as CSV</span>
            </button>
            <button wire:click="exportExcel" @click="open = false" class="flex items-center gap-3 w-full px-4 py-2.5 text-[12px] font-bold text-gray-700 hover:bg-gray-50 transition-colors">
                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                    <span class="text-blue-600 text-[10px]">XLS</span>
                </div>
                <span>Export as Excel</span>
            </button>
            <button wire:click="exportPdf" @click="open = false" class="flex items-center gap-3 w-full px-4 py-2.5 text-[12px] font-bold text-gray-700 hover:bg-gray-50 transition-colors">
                <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center">
                    <span class="text-rose-600 text-[10px]">PDF</span>
                </div>
                <span>Export as PDF</span>
            </button>
        </div>
    </div>
</div>
