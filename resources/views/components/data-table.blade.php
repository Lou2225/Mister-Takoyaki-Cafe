<div class="bg-white rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-slate-200/80 mt-6 transition-all duration-300">
    <div class="overflow-x-auto min-h-[300px]">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/50">
                <tr class="border-b border-slate-200/80">
                    {{ $header ?? '' }}
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100/80 transition-all duration-300">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
