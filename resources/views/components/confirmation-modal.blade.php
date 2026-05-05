@props(['id' => null, 'maxWidth' => '2xl', 'name'])

<x-modal :id="$id" :maxWidth="$maxWidth" :name="$name" {{ $attributes->only(['show', 'focusable']) }}>
    <div class="p-6" {{ $attributes->except(['name', 'show', 'focusable', 'maxWidth']) }}>
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm">
                {{ $icon ?? '' }}
            </div>
            <div>
                <h3 class="text-lg font-black text-gray-900 tracking-tight">
                    {{ $title }}
                </h3>
            </div>
        </div>

        <div class="text-[14px] text-gray-500 font-bold leading-relaxed mb-6">
            {{ $content }}
        </div>

        <div class="flex flex-row justify-end gap-3 px-6 py-4 bg-gray-50 text-right -mx-6 -mb-6 mt-6 rounded-b-2xl border-t border-gray-100">
            {{ $footer }}
        </div>
    </div>
</x-modal>
