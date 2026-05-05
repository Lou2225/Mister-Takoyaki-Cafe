@props(['disabled' => false])

<textarea {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-gray-200 focus:border-gray-300 rounded-lg shadow-sm text-[13px] text-gray-800 placeholder-gray-400 transition-colors py-2 px-3 min-h-[100px]']) !!}></textarea>
