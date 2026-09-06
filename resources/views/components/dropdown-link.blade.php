@props(['navigate' => false])
<<<<<<< HEAD
<a {{ $navigate ? 'wire:navigate' : '' }} {{ $attributes->merge(['class' => 'block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out']) }} @click.prevent="typeof dropdownOpen !== 'undefined' && (dropdownOpen = false)">{{ $slot }}</a>
=======

<a {{ $navigate ? 'wire:navigate' : '' }} {{ $attributes->merge(['class' => 'block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out']) }} @click="$dispatch('close')">{{ $slot }}</a>


>>>>>>> 8ad7217b87e6ff71676d665e65e7079c934664b2
