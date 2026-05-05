@props(['type' => 'button'])

<button {{ $attributes->merge(['type' => $type, 'class' => 'inline-flex items-center px-4 h-10 bg-white border border-gray-200 rounded-xl font-black text-[12px] text-gray-700 shadow-sm hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 active:scale-95 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
