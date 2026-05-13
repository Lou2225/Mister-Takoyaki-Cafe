@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white', 'containerClasses' => 'inline-block', 'wireKey' => null])

@php
switch ($align) {
    case 'left':
        $alignmentClasses = 'origin-top-left left-0';
        break;
    case 'top':
        $alignmentClasses = 'origin-bottom bottom-full mb-2 left-0';
        break;
    case 'right':
    default:
        $alignmentClasses = 'origin-top-right right-0';
        break;
}

switch ($width) {
    case '48':
        $widthClass = 'w-48';
        break;
    case 'full':
        $widthClass = 'w-full';
        break;
    default:
        $widthClass = 'w-' . $width;
        break;
}
@endphp
<div wire:ignore.self 
     {{ $wireKey ? 'wire:key='.$wireKey : '' }}
     {{ $attributes->merge(['class' => 'relative ' . $containerClasses . ' text-left']) }} 
     :class="dropdownOpen ? 'z-40' : ''" 
     x-data="{ dropdownOpen: false }" 
     @click.away="dropdownOpen = false" 
     @close.stop="dropdownOpen = false" 
     data-has-alpine-state="true">
    <div @click="dropdownOpen = !dropdownOpen" x-ref="trigger" class="relative z-0">
        {{ $trigger }}
    </div>

    <div x-show="dropdownOpen" 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="transform opacity-0 scale-95" 
         x-transition:enter-end="transform opacity-100 scale-100" 
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="transform opacity-100 scale-100" 
         x-transition:leave-end="transform opacity-0 scale-95" 
         class="absolute z-50 {{ $alignmentClasses }} {{ $widthClass }} rounded-md shadow-lg {{ $contentClasses }}" style="display: none;">
        <div class="rounded-md ring-1 ring-black ring-opacity-5 py-1 bg-white" @click.stop>
            {{ $content }}
        </div>
    </div>
</div>
