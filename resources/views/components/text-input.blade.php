@props([
    'disabled'    => false,
    'hasError'    => false,
    'inputFilter' => null,  // 'name' | 'number' | 'price' | 'phone' | 'id' | 'productName' | 'categoryName' | null
])

@php
    $filterAttr = $inputFilter ? "data-filter=\"{$inputFilter}\"" : '';
    $baseClass   = 'border-gray-200 focus:border-gray-300 focus:ring-1 focus:ring-gray-300 rounded-lg shadow-sm text-[13px] text-gray-800 placeholder-gray-400 transition-all h-10 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none';
    $errorClass  = $hasError ? 'border-red-400 focus:border-red-400 focus:ring-red-300 bg-red-50/30' : '';
@endphp

<input
    {{ $disabled ? 'disabled' : '' }}
    {!! $filterAttr !!}
    {!! $attributes->merge(['class' => trim($baseClass . ' ' . $errorClass)]) !!}
>
