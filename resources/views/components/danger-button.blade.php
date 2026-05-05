@props(['type' => 'button'])

<button {{ $attributes->merge(['type' => $type, 'class' => 'inline-flex items-center px-4 h-10 bg-white border-2 border-red-600 rounded-xl font-black text-[12px] text-red-600 shadow-sm hover:bg-red-50 hover:border-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150 active:scale-95 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
